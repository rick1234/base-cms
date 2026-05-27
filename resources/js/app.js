const adminMenuButton = document.querySelector('[data-admin-menu-button]');
const adminNavigation = document.querySelector('[data-admin-navigation]');

if (adminMenuButton instanceof HTMLButtonElement && adminNavigation instanceof HTMLElement) {
    adminMenuButton.addEventListener('click', () => {
        const isExpanded = adminMenuButton.getAttribute('aria-expanded') === 'true';

        adminMenuButton.setAttribute('aria-expanded', String(! isExpanded));
        adminNavigation.toggleAttribute('hidden', isExpanded);
    });
}

document.addEventListener('click', (event) => {
    if (! (event.target instanceof Element)) {
        return;
    }

    const closeButton = event.target.closest('[data-flash-close]');

    if (! (closeButton instanceof HTMLElement)) {
        return;
    }

    closeButton.closest('[data-flash-message]')?.remove();
});

const dragVisualClasses = [
    'is-dragging',
    'is-dragging-item',
    'is-drop-target',
    'is-drop-before',
    'is-drop-after',
];

let activeDrag = null;

const clearDragVisuals = () => {
    document.querySelectorAll('[data-drag-container], [data-drag-item]').forEach((element) => {
        dragVisualClasses.forEach((className) => element.classList.remove(className));

        if (element instanceof HTMLElement) {
            delete element.dataset.dragPosition;
        }
    });
};

const findLivewireComponent = (element) => {
    const componentRoot = element.closest('[wire\\:id]');

    if (! (componentRoot instanceof HTMLElement) || ! window.Livewire) {
        return null;
    }

    return window.Livewire.find(componentRoot.getAttribute('wire:id'));
};

const setDragPreview = (event, item) => {
    if (! event.dataTransfer) {
        return;
    }

    const preview = item.cloneNode(true);

    if (! (preview instanceof HTMLElement)) {
        return;
    }

    preview.classList.add('drag-preview');
    preview.style.width = `${item.getBoundingClientRect().width}px`;
    document.body.append(preview);
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setDragImage(preview, 24, 24);
    window.setTimeout(() => preview.remove(), 0);
};

document.addEventListener('dragstart', (event) => {
    if (! (event.target instanceof Element)) {
        return;
    }

    const item = event.target.closest('[data-drag-item]');

    if (! (item instanceof HTMLElement)) {
        return;
    }

    const container = item.closest('[data-drag-container]');
    const livewireComponent = findLivewireComponent(item);

    if (! (container instanceof HTMLElement)) {
        return;
    }

    activeDrag = {
        component: livewireComponent,
        container,
        id: item.dataset.dragId ?? null,
        item,
        sortMethod: container.dataset.livewireSortMethod ?? null,
    };

    clearDragVisuals();
    item.classList.add('is-dragging-item');
    container.classList.add('is-dragging');
    event.dataTransfer?.setData('text/plain', activeDrag.id ?? '');
    setDragPreview(event, item);
});

document.addEventListener('dragover', (event) => {
    if (! activeDrag || ! (event.target instanceof Element)) {
        return;
    }

    event.preventDefault();

    const target = event.target.closest('[data-drag-item]');

    document.querySelectorAll('[data-drag-item].is-drop-target, [data-drag-item].is-drop-before, [data-drag-item].is-drop-after').forEach((element) => {
        element.classList.remove('is-drop-target', 'is-drop-before', 'is-drop-after');

        if (element instanceof HTMLElement) {
            delete element.dataset.dragPosition;
        }
    });

    if (
        ! (target instanceof HTMLElement)
        || target === activeDrag.item
        || target.closest('[data-drag-container]') !== activeDrag.container
    ) {
        return;
    }

    const box = target.getBoundingClientRect();
    const isHorizontal = target.closest('[data-drag-layout="grid"]') instanceof HTMLElement;
    const midpoint = isHorizontal ? box.left + box.width / 2 : box.top + box.height / 2;
    const pointer = isHorizontal ? event.clientX : event.clientY;
    const position = pointer < midpoint ? 'before' : 'after';

    target.dataset.dragPosition = position;
    target.classList.add('is-drop-target', position === 'before' ? 'is-drop-before' : 'is-drop-after');
    target.closest('[data-drag-container]')?.classList.add('is-dragging');
});

document.addEventListener('drop', (event) => {
    if (! activeDrag || ! (event.target instanceof Element)) {
        clearDragVisuals();
        activeDrag = null;

        return;
    }

    event.preventDefault();

    const target = event.target.closest('[data-drag-item]');

    if (
        target instanceof HTMLElement
        && target !== activeDrag.item
        && target.closest('[data-drag-container]') === activeDrag.container
        && activeDrag.component
        && activeDrag.sortMethod
        && activeDrag.id
        && target.dataset.dragId
    ) {
        activeDrag.component.call(
            activeDrag.sortMethod,
            Number(target.dataset.dragId),
            Number(activeDrag.id),
            target.dataset.dragPosition === 'after' ? 'after' : 'before',
        );
    }

    clearDragVisuals();
    activeDrag = null;
});

document.addEventListener('dragend', () => {
    clearDragVisuals();
    activeDrag = null;
});

const contentBlockWidthClasses = Array.from(
    { length: 21 },
    (_, index) => `content-block-builder-item--width-${index * 5}`,
);

const contentBlockWidthByItemKey = new Map();

const normalizeContentBlockWidth = (value) => {
    const parsed = Number.parseInt(value ?? '50', 10);

    if (! Number.isFinite(parsed)) {
        return 50;
    }

    return Math.max(15, Math.min(100, Math.round(parsed / 5) * 5));
};

const contentBlockItemKey = (item) => {
    if (! (item instanceof HTMLElement)) {
        return null;
    }

    const uuidField = item.querySelector('input[type="hidden"][wire\\:model$=".uuid"]');

    return item.getAttribute('x-sortable-item')
        || (uuidField instanceof HTMLInputElement ? uuidField.value : null)
        || item.getAttribute('wire:key');
};

const rememberContentBlockWidth = (item, width) => {
    const key = contentBlockItemKey(item);

    if (! key) {
        return;
    }

    contentBlockWidthByItemKey.set(key, normalizeContentBlockWidth(width));
};

const cacheContentBlockBuilderWidths = (root = document) => {
    root.querySelectorAll('[data-content-block-builder] .fi-fo-builder-item').forEach((item) => {
        if (! (item instanceof HTMLElement)) {
            return;
        }

        const select = item.querySelector('[data-content-block-width-select]');
        const slider = item.querySelector('[data-content-block-width-range]');
        const width = select instanceof HTMLSelectElement
            ? select.value
            : (slider instanceof HTMLInputElement ? slider.value : item.dataset.contentBlockWidth);

        rememberContentBlockWidth(item, width);
    });
};

const applyContentBlockWidthClass = (item, width) => {
    item.classList.remove(...contentBlockWidthClasses);
    item.classList.add(`content-block-builder-item--width-${width}`);
    item.dataset.contentBlockWidth = String(width);
    rememberContentBlockWidth(item, width);
};

const ensureContentBlockWidthSlider = (item, select) => {
    let slider = item.querySelector('[data-content-block-width-range]');

    if (! (slider instanceof HTMLInputElement)) {
        const sliderWrap = document.createElement('div');
        sliderWrap.className = 'content-block-width-control';
        sliderWrap.dataset.contentBlockWidthControl = 'true';

        const decreaseButton = document.createElement('button');
        decreaseButton.className = 'content-block-width-step-button';
        decreaseButton.type = 'button';
        decreaseButton.dataset.contentBlockWidthStep = '-5';
        decreaseButton.setAttribute('aria-label', 'Decrease block width');

        const decreaseIcon = document.createElement('span');
        decreaseIcon.className = 'admin-material-icon';
        decreaseIcon.setAttribute('aria-hidden', 'true');
        decreaseIcon.textContent = 'remove';

        slider = document.createElement('input');
        slider.className = 'content-block-width-slider';
        slider.type = 'range';
        slider.min = '15';
        slider.max = '100';
        slider.step = '5';
        slider.dataset.contentBlockWidthRange = 'true';
        slider.setAttribute('aria-label', select.getAttribute('aria-label') || 'Block width');

        const increaseButton = document.createElement('button');
        increaseButton.className = 'content-block-width-step-button';
        increaseButton.type = 'button';
        increaseButton.dataset.contentBlockWidthStep = '5';
        increaseButton.setAttribute('aria-label', 'Increase block width');

        const increaseIcon = document.createElement('span');
        increaseIcon.className = 'admin-material-icon';
        increaseIcon.setAttribute('aria-hidden', 'true');
        increaseIcon.textContent = 'add';

        decreaseButton.append(decreaseIcon);
        increaseButton.append(increaseIcon);
        sliderWrap.append(decreaseButton, slider, increaseButton);
        item.append(sliderWrap);
    }

    return slider;
};

const updateContentBlockWidth = (item, width, shouldCommit = false) => {
    const select = item.querySelector('[data-content-block-width-select]');

    if (! (select instanceof HTMLSelectElement)) {
        applyContentBlockWidthClass(item, width);

        return;
    }

    const nextValue = String(width);
    const previousValue = select.value;

    select.value = nextValue;
    applyContentBlockWidthClass(item, width);

    if (shouldCommit && previousValue !== nextValue) {
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }
};

const commitContentBlockWidthRange = (range) => {
    const item = range.closest('.fi-fo-builder-item');

    if (! (item instanceof HTMLElement)) {
        return;
    }

    const width = normalizeContentBlockWidth(range.value);

    range.value = String(width);
    updateContentBlockWidth(item, width, true);
    item.classList.remove('is-width-resizing');

    if (activeContentBlockWidthRange === range) {
        activeContentBlockWidthRange = null;
    }
};

const syncContentBlockBuilderWidths = (root = document, options = {}) => {
    const preferCached = options.preferCached === true;
    const commitCached = options.commitCached === true;

    root.querySelectorAll('[data-content-block-builder] .fi-fo-builder-item').forEach((item) => {
        if (! (item instanceof HTMLElement)) {
            return;
        }

        const cachedWidth = contentBlockWidthByItemKey.get(contentBlockItemKey(item));
        const select = item.querySelector('[data-content-block-width-select]');
        const selectedWidth = select instanceof HTMLSelectElement
            ? normalizeContentBlockWidth(select.value)
            : 50;
        const width = preferCached && Number.isFinite(cachedWidth)
            ? cachedWidth
            : selectedWidth;
        const slider = select instanceof HTMLSelectElement
            ? ensureContentBlockWidthSlider(item, select)
            : null;

        if (select instanceof HTMLSelectElement && select.value !== String(width)) {
            select.value = String(width);

            if (commitCached) {
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        if (slider instanceof HTMLInputElement) {
            slider.value = String(width);
        }

        applyContentBlockWidthClass(item, width);
    });
};

let contentBlockWidthSyncFrame = null;
let pendingContentBlockWidthSyncOptions = {};
let contentBlockSortRestoreUntil = 0;

const scheduleContentBlockWidthSync = (root = document, options = {}) => {
    pendingContentBlockWidthSyncOptions = {
        ...pendingContentBlockWidthSyncOptions,
        ...options,
    };

    if (contentBlockWidthSyncFrame !== null) {
        window.cancelAnimationFrame(contentBlockWidthSyncFrame);
    }

    contentBlockWidthSyncFrame = window.requestAnimationFrame(() => {
        const syncOptions = pendingContentBlockWidthSyncOptions;

        pendingContentBlockWidthSyncOptions = {};
        syncContentBlockBuilderWidths(root, syncOptions);
        contentBlockWidthSyncFrame = null;
    });
};

const contentBlockUuid = (item) => {
    const uuidField = item.querySelector('input[type="hidden"][wire\\:model$=".uuid"]');

    return uuidField instanceof HTMLInputElement ? uuidField.value : null;
};

const closeSavedContentBlockItem = (detail = {}) => {
    if (detail.closeAll === true) {
        document.querySelectorAll('[data-content-block-builder] .fi-fo-builder-item.is-block-form-open').forEach((item) => {
            item.classList.remove('is-block-form-open');
        });

        scheduleContentBlockWidthSync();

        return;
    }

    const savedKeys = [detail.itemKey, detail.uuid]
        .filter((value) => typeof value === 'string' && value.length > 0);

    if (savedKeys.length === 0) {
        return;
    }

    let savedItem = null;

    document.querySelectorAll('[data-content-block-builder] .fi-fo-builder-item').forEach((item) => {
        if (savedItem instanceof HTMLElement || ! (item instanceof HTMLElement)) {
            return;
        }

        if (savedKeys.includes(contentBlockItemKey(item)) || savedKeys.includes(contentBlockUuid(item))) {
            savedItem = item;
        }
    });

    if (! (savedItem instanceof HTMLElement)) {
        return;
    }

    savedItem.classList.remove('is-block-form-open');
    scheduleContentBlockWidthSync(savedItem.closest('[data-content-block-builder]') ?? document);
};

window.addEventListener('content-block-saved', (event) => {
    window.requestAnimationFrame(() => closeSavedContentBlockItem(event.detail ?? {}));
});

const clearContentBlockSortVisuals = () => {
    document.querySelectorAll('[data-content-block-builder] .is-sortable-active').forEach((element) => {
        element.classList.remove('is-sortable-active');
    });

    document.querySelectorAll('[data-content-block-builder] .is-sort-dragging, [data-content-block-builder] .is-sort-drop-target').forEach((element) => {
        element.classList.remove('is-sort-dragging', 'is-sort-drop-target');
    });
};

const sortableEventItem = (event) => {
    const candidate = event.item ?? event.target;

    return candidate instanceof HTMLElement
        ? candidate.closest('.fi-fo-builder-item')
        : null;
};

const sortableEventList = (event) => {
    const candidate = event.to ?? event.target;

    return candidate instanceof HTMLElement
        ? candidate.closest('.fi-fo-builder-items')
        : null;
};

document.addEventListener('choose', (event) => {
    if (! (event.item instanceof HTMLElement) || ! (event.to instanceof HTMLElement)) {
        return;
    }

    const item = sortableEventItem(event);
    const list = sortableEventList(event);

    if (! item?.closest('[data-content-block-builder]') || ! list?.closest('[data-content-block-builder]')) {
        return;
    }

    cacheContentBlockBuilderWidths(list.closest('[data-content-block-builder]') ?? list);
    clearContentBlockSortVisuals();
    item.classList.add('is-sort-dragging');
    list.classList.add('is-sortable-active');
});

document.addEventListener('change', (event) => {
    if (! (event.item instanceof HTMLElement) || ! (event.to instanceof HTMLElement)) {
        return;
    }

    const list = sortableEventList(event);

    if (! list?.closest('[data-content-block-builder]')) {
        return;
    }

    cacheContentBlockBuilderWidths(list.closest('[data-content-block-builder]') ?? list);
    list.classList.add('is-sortable-active');
    list.querySelectorAll('.is-sort-drop-target').forEach((element) => {
        element.classList.remove('is-sort-drop-target');
    });

    const index = Number.isInteger(event.newDraggableIndex) ? event.newDraggableIndex : event.newIndex;
    const items = Array.from(list.querySelectorAll('.fi-fo-builder-item'));
    const target = Number.isInteger(index) ? items[index] : null;

    if (target instanceof HTMLElement) {
        target.classList.add('is-sort-drop-target');
    }
});

['end', 'unchoose', 'drop'].forEach((eventName) => {
    document.addEventListener(eventName, (event) => {
        const list = sortableEventList(event);
        const builder = list?.closest('[data-content-block-builder]');

        if (builder instanceof HTMLElement) {
            cacheContentBlockBuilderWidths(builder);
            contentBlockSortRestoreUntil = Date.now() + 2500;
        }

        window.setTimeout(() => {
            clearContentBlockSortVisuals();

            if (builder instanceof HTMLElement) {
                scheduleContentBlockWidthSync(builder, {
                    commitCached: true,
                    preferCached: true,
                });
            }
        }, 80);

        window.setTimeout(() => {
            if (builder instanceof HTMLElement) {
                scheduleContentBlockWidthSync(builder, {
                    commitCached: true,
                    preferCached: true,
                });
            }
        }, 350);
    });
});

document.addEventListener('click', (event) => {
    if (! (event.target instanceof Element)) {
        return;
    }

    const stepButton = event.target.closest('[data-content-block-width-step]');

    if (stepButton instanceof HTMLButtonElement) {
        const item = stepButton.closest('.fi-fo-builder-item');
        const slider = item?.querySelector('[data-content-block-width-range]');
        const step = Number.parseInt(stepButton.dataset.contentBlockWidthStep ?? '0', 10);

        if (item instanceof HTMLElement && slider instanceof HTMLInputElement && Number.isFinite(step)) {
            const width = normalizeContentBlockWidth(Number.parseInt(slider.value ?? '50', 10) + step);

            slider.value = String(width);
            updateContentBlockWidth(item, width, true);
        }

        return;
    }

    const toggle = event.target.closest('[data-content-block-edit-toggle]');

    if (! (toggle instanceof HTMLElement)) {
        return;
    }

    const item = toggle.closest('.fi-fo-builder-item');

    if (! (item instanceof HTMLElement)) {
        return;
    }

    item.classList.toggle('is-block-form-open');
});

document.addEventListener('input', (event) => {
    if (! (event.target instanceof HTMLInputElement) || ! event.target.matches('[data-content-block-width-range]')) {
        return;
    }

    event.target.closest('.fi-fo-builder-item')?.classList.add('is-width-resizing');
});

let activeContentBlockWidthRange = null;

document.addEventListener('pointerdown', (event) => {
    if (! (event.target instanceof HTMLInputElement) || ! event.target.matches('[data-content-block-width-range]')) {
        return;
    }

    activeContentBlockWidthRange = event.target;
    event.target.closest('.fi-fo-builder-item')?.classList.add('is-width-resizing');
});

['pointerup', 'pointercancel'].forEach((eventName) => {
    document.addEventListener(eventName, () => {
        if (activeContentBlockWidthRange instanceof HTMLInputElement) {
            commitContentBlockWidthRange(activeContentBlockWidthRange);
        }

        activeContentBlockWidthRange = null;

        document.querySelectorAll('.fi-fo-builder-item.is-width-resizing').forEach((item) => {
            item.classList.remove('is-width-resizing');
        });
    });
});

document.addEventListener('change', (event) => {
    if (event.target instanceof HTMLInputElement && event.target.matches('[data-content-block-width-range]')) {
        commitContentBlockWidthRange(event.target);

        return;
    }

    if (! (event.target instanceof HTMLSelectElement) || ! event.target.matches('[data-content-block-width-select]')) {
        return;
    }

    scheduleContentBlockWidthSync(event.target.closest('[data-content-block-builder]') ?? document);
});

window.addEventListener('load', () => scheduleContentBlockWidthSync());
document.addEventListener('livewire:navigated', () => scheduleContentBlockWidthSync());

if (window.MutationObserver && document.body) {
    const contentBlockObserver = new MutationObserver((mutations) => {
        const shouldSync = mutations.some((mutation) => Array.from(mutation.addedNodes).some((node) => {
            if (! (node instanceof Element)) {
                return false;
            }

            return node.matches('[data-content-block-builder], [data-content-block-width-select], [data-content-block-inline-preview]')
                || node.querySelector('[data-content-block-builder], [data-content-block-width-select], [data-content-block-inline-preview]');
        }));

        if (shouldSync) {
            const restoreAfterSort = Date.now() < contentBlockSortRestoreUntil;

            scheduleContentBlockWidthSync(document, {
                commitCached: restoreAfterSort,
                preferCached: restoreAfterSort,
            });
        }
    });

    contentBlockObserver.observe(document.body, {
        childList: true,
        subtree: true,
    });
}

const fileNameWithoutExtension = (name) => {
    const lastDot = name.lastIndexOf('.');

    return lastDot > 0 ? name.slice(0, lastDot) : name;
};

const readableFileSize = (bytes) => {
    if (! Number.isFinite(bytes) || bytes <= 0) {
        return '0 KB';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unitIndex = 0;

    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex += 1;
    }

    return `${size.toFixed(unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
};

const renderSelectedAttachments = (input, list) => {
    list.replaceChildren();

    const files = Array.from(input.files ?? []);

    list.hidden = files.length === 0;

    files.forEach((file, index) => {
        const item = document.createElement('div');
        item.className = 'attachment-selection-item';

        const icon = document.createElement('span');
        icon.className = 'admin-symbol admin-symbol-attachment';
        icon.setAttribute('aria-hidden', 'true');

        const content = document.createElement('div');
        content.className = 'attachment-selection-content';

        const title = document.createElement('span');
        title.className = 'attachment-selection-title';
        title.textContent = file.name;

        const meta = document.createElement('span');
        meta.className = 'attachment-selection-meta';
        meta.textContent = readableFileSize(file.size);

        const label = document.createElement('label');
        label.className = 'u-sr-only';
        label.setAttribute('for', `attachment-selected-name-${index}`);
        label.textContent = list.dataset.namePlaceholder ?? 'Attachment name';

        const nameInput = document.createElement('input');
        nameInput.className = 'attachment-selection-name';
        nameInput.id = `attachment-selected-name-${index}`;
        nameInput.name = 'attachment_names[]';
        nameInput.placeholder = list.dataset.namePlaceholder ?? 'Attachment name';
        nameInput.type = 'text';
        nameInput.value = fileNameWithoutExtension(file.name);

        content.append(title, meta, label, nameInput);
        item.append(icon, content);
        list.append(item);
    });
};

document.querySelectorAll('[data-attachment-manager]').forEach((manager) => {
    const input = manager.querySelector('[data-attachment-input]');
    const list = manager.querySelector('[data-attachment-selection-list]');
    const dropzone = manager.querySelector('[data-attachment-dropzone]');
    let selectedFiles = [];

    if (! (input instanceof HTMLInputElement) || ! (list instanceof HTMLElement)) {
        return;
    }

    const syncSelectedFiles = () => {
        if (! window.DataTransfer) {
            return;
        }

        const transfer = new DataTransfer();

        selectedFiles.forEach((file) => transfer.items.add(file));
        input.files = transfer.files;
    };

    const appendSelectedFiles = (files) => {
        selectedFiles = [...selectedFiles, ...Array.from(files ?? [])];
        syncSelectedFiles();
        renderSelectedAttachments(input, list);
    };

    input.addEventListener('change', () => appendSelectedFiles(input.files));

    if (dropzone instanceof HTMLElement) {
        ['dragenter', 'dragover'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropzone.classList.add('is-drag-over');
            });
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            dropzone.addEventListener(eventName, () => {
                dropzone.classList.remove('is-drag-over');
            });
        });

        dropzone.addEventListener('drop', (event) => {
            event.preventDefault();

            if (event.dataTransfer?.files?.length) {
                appendSelectedFiles(event.dataTransfer.files);
            }
        });
    }
});

const updateAttachmentSortOrder = (list) => {
    list.querySelectorAll('[data-attachment-item]').forEach((item, index) => {
        const input = item.querySelector('[data-attachment-sort-input]');

        if (input instanceof HTMLInputElement) {
            input.value = String(index + 1);
        }
    });
};

const dragAfterAttachment = (list, y) => {
    const items = [...list.querySelectorAll('[data-attachment-item]:not(.is-dragging)')];

    return items.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;

        if (offset < 0 && offset > closest.offset) {
            return { offset, element: child };
        }

        return closest;
    }, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
};

document.querySelectorAll('[data-attachment-sortable-list]').forEach((list) => {
    if (! (list instanceof HTMLElement)) {
        return;
    }

    let draggedItem = null;

    updateAttachmentSortOrder(list);

    list.addEventListener('dragstart', (event) => {
        if (! (event.target instanceof HTMLElement) || ! event.target.closest('[data-attachment-handle]')) {
            event.preventDefault();

            return;
        }

        const item = event.target.closest('[data-attachment-item]');

        if (! (item instanceof HTMLElement)) {
            return;
        }

        draggedItem = item;
        draggedItem.classList.add('is-dragging');
        event.dataTransfer?.setData('text/plain', item.dataset.attachmentId ?? '');
        event.dataTransfer?.setDragImage(item, 20, 20);
    });

    list.addEventListener('dragover', (event) => {
        event.preventDefault();

        if (! draggedItem) {
            return;
        }

        const afterElement = dragAfterAttachment(list, event.clientY);
        list.insertBefore(draggedItem, afterElement);
        updateAttachmentSortOrder(list);
    });

    list.addEventListener('dragend', () => {
        draggedItem?.classList.remove('is-dragging');
        draggedItem = null;
        updateAttachmentSortOrder(list);
    });

    list.addEventListener('change', (event) => {
        if (! (event.target instanceof HTMLInputElement) || ! event.target.matches('[data-attachment-delete-input]')) {
            return;
        }

        event.target.closest('[data-attachment-item]')?.classList.toggle('is-marked-for-delete', event.target.checked);
    });
});
