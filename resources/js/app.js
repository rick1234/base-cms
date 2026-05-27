import '@melloware/coloris/dist/coloris.css';
import Coloris from '@melloware/coloris';

const adminMenuButton = document.querySelector('[data-admin-menu-button]');
const adminNavigation = document.querySelector('[data-admin-navigation]');
const siteMenuButton = document.querySelector('[data-site-menu-toggle]');
const siteMenuPanel = document.querySelector('[data-site-menu-panel]');
const siteMenuOverlay = document.querySelector('[data-site-menu-overlay]');
const siteMenuCloseButton = document.querySelector('[data-site-menu-close]');

const setSiteMenuOpen = (isOpen) => {
    if (! (siteMenuButton instanceof HTMLButtonElement) || ! (siteMenuPanel instanceof HTMLElement)) {
        return;
    }

    siteMenuButton.setAttribute('aria-expanded', String(isOpen));
    siteMenuPanel.hidden = ! isOpen;
    siteMenuOverlay?.toggleAttribute('hidden', ! isOpen);
    document.body.classList.toggle('offscreen-navigation-active', isOpen);
};

if (siteMenuButton instanceof HTMLButtonElement && siteMenuPanel instanceof HTMLElement) {
    siteMenuButton.addEventListener('click', () => {
        const isExpanded = siteMenuButton.getAttribute('aria-expanded') === 'true';

        setSiteMenuOpen(! isExpanded);
    });
}

siteMenuCloseButton?.addEventListener('click', () => setSiteMenuOpen(false));
siteMenuOverlay?.addEventListener('click', () => setSiteMenuOpen(false));

if (document.querySelector('[data-coloris]')) {
    Coloris.init();
    Coloris({
        el: '[data-coloris]',
        theme: 'large',
        themeMode: 'light',
        alpha: false,
        clearButton: false,
        closeButton: true,
        closeLabel: 'Done',
        format: 'hex',
        formatToggle: false,
        selectInput: true,
        swatches: [
            '#ffa300',
            '#272720',
            '#00a287',
            '#104ba9',
            '#ffffff',
            '#f0f0f0',
            '#e0e0e0',
            '#2d2d29',
            '#d30231',
            '#20b340',
        ],
        wrap: true,
    });
}

const googleFontPreviewInputs = document.querySelectorAll('[data-google-font-preview-input]');

const cssString = (value) => String(value).replace(/\\/g, '\\\\').replace(/"/g, '\\"');

const googleFontPreviewFamily = (value) => {
    if (! value || typeof value !== 'string') {
        return null;
    }

    let url;

    try {
        url = new URL(value.trim());
    } catch {
        return null;
    }

    if (url.protocol !== 'https:' || url.hostname !== 'fonts.googleapis.com' || ! ['/css', '/css2'].includes(url.pathname)) {
        return null;
    }

    const family = url.searchParams.getAll('family').find((candidate) => candidate.trim() !== '');

    if (! family) {
        return null;
    }

    const familyName = family.split(':')[0].replace(/\s+/g, ' ').trim();

    return /^[\p{L}\p{N} .'-]+$/u.test(familyName) ? familyName : null;
};

const googleFontPreviewStylesheet = (key) => {
    const selector = `link[data-google-font-preview-stylesheet="${key}"]`;
    let stylesheet = document.head.querySelector(selector);

    if (! (stylesheet instanceof HTMLLinkElement)) {
        stylesheet = document.createElement('link');
        stylesheet.rel = 'stylesheet';
        stylesheet.dataset.googleFontPreviewStylesheet = key;
        document.head.append(stylesheet);
    }

    return stylesheet;
};

const googleFontPreviewStyleElement = () => {
    let style = document.head.querySelector('style[data-google-font-preview-style]');

    if (! (style instanceof HTMLStyleElement)) {
        style = document.createElement('style');
        style.dataset.googleFontPreviewStyle = 'true';
        document.head.append(style);
    }

    return style;
};

const syncGoogleFontPreviewStyles = () => {
    const rules = [];

    document.querySelectorAll('[data-google-font-preview-card]').forEach((card, index) => {
        if (! (card instanceof HTMLElement)) {
            return;
        }

        card.dataset.googleFontPreviewIndex = String(index);

        const family = card.dataset.googleFontPreviewFamily;
        const fallback = ['serif', 'sans-serif', 'monospace'].includes(card.dataset.googleFontPreviewFallback)
            ? card.dataset.googleFontPreviewFallback
            : 'sans-serif';

        if (family) {
            rules.push(`[data-google-font-preview-index="${index}"] .template-font-preview-sample{font-family:"${cssString(family)}", ${fallback};}`);
        }
    });

    googleFontPreviewStyleElement().textContent = rules.join('\n');
};

const updateGoogleFontPreview = (input) => {
    const key = input.dataset.googleFontPreviewInput;
    const card = key ? document.querySelector(`[data-google-font-preview-card="${key}"]`) : null;

    if (! (card instanceof HTMLElement)) {
        return;
    }

    const label = card.querySelector('[data-google-font-preview-label]');
    const value = input.value.trim();
    const family = googleFontPreviewFamily(value);

    delete card.dataset.googleFontPreviewFamily;

    if (value === '') {
        card.dataset.googleFontPreviewState = 'empty';

        if (label instanceof HTMLElement) {
            label.textContent = card.dataset.googleFontPreviewEmpty || 'Font preview';
        }

        syncGoogleFontPreviewStyles();

        return;
    }

    if (! family) {
        card.dataset.googleFontPreviewState = 'invalid';

        if (label instanceof HTMLElement) {
            label.textContent = card.dataset.googleFontPreviewInvalid || 'Invalid font URL';
        }

        syncGoogleFontPreviewStyles();

        return;
    }

    card.dataset.googleFontPreviewState = 'ready';
    card.dataset.googleFontPreviewFamily = family;
    googleFontPreviewStylesheet(key).href = value;

    if (label instanceof HTMLElement) {
        label.textContent = family;
    }

    syncGoogleFontPreviewStyles();
};

googleFontPreviewInputs.forEach((input) => {
    if (! (input instanceof HTMLInputElement)) {
        return;
    }

    updateGoogleFontPreview(input);
    input.addEventListener('input', () => updateGoogleFontPreview(input));
    input.addEventListener('change', () => updateGoogleFontPreview(input));
});

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

const domainSocialItemIndex = (item, fallback) => {
    const parsed = Number.parseInt(item.dataset.domainSocialIndex ?? '', 10);

    return Number.isFinite(parsed) ? parsed : fallback;
};

const updateDomainSocialOrder = (list) => {
    list.querySelectorAll('[data-domain-social-item]').forEach((item, index) => {
        if (! (item instanceof HTMLElement)) {
            return;
        }

        item.dataset.domainSocialIndex = String(index);

        const label = item.querySelector('[data-domain-social-index-label]');

        if (label instanceof HTMLElement) {
            label.textContent = String(index + 1);
        }

        item.querySelectorAll('[data-domain-social-field]').forEach((field) => {
            if (! (field instanceof HTMLInputElement) && ! (field instanceof HTMLSelectElement)) {
                return;
            }

            const key = field.dataset.domainSocialField;

            if (key) {
                field.name = `social_links[${index}][${key}]`;
            }
        });
    });
};

const dragAfterDomainSocialItem = (list, x, y) => {
    const items = [...list.querySelectorAll('[data-domain-social-item]:not(.is-dragging)')];

    return items.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const isAfterRow = y > box.top + box.height / 2;
        const rowDistance = Math.abs(y - (box.top + box.height / 2));
        const columnDistance = Math.abs(x - (box.left + box.width / 2));
        const offset = (rowDistance * 3) + columnDistance;

        if (isAfterRow || (Math.abs(y - (box.top + box.height / 2)) < box.height / 2 && x > box.left + box.width / 2)) {
            return closest;
        }

        if (offset < closest.offset) {
            return { offset, element: child };
        }

        return closest;
    }, { offset: Number.POSITIVE_INFINITY, element: null }).element;
};

document.querySelectorAll('[data-domain-social-sortable-list]').forEach((list) => {
    if (! (list instanceof HTMLElement)) {
        return;
    }

    let draggedItem = null;

    updateDomainSocialOrder(list);

    list.addEventListener('dragstart', (event) => {
        if (! (event.target instanceof HTMLElement) || ! event.target.closest('[data-domain-social-handle]')) {
            event.preventDefault();

            return;
        }

        const item = event.target.closest('[data-domain-social-item]');

        if (! (item instanceof HTMLElement)) {
            return;
        }

        draggedItem = item;
        draggedItem.classList.add('is-dragging');
        event.dataTransfer?.setData('text/plain', String(domainSocialItemIndex(item, 0)));
        event.dataTransfer?.setDragImage(item, 20, 20);
    });

    list.addEventListener('dragover', (event) => {
        event.preventDefault();

        if (! draggedItem) {
            return;
        }

        const afterElement = dragAfterDomainSocialItem(list, event.clientX, event.clientY);

        list.insertBefore(draggedItem, afterElement);
        updateDomainSocialOrder(list);
    });

    list.addEventListener('dragend', () => {
        draggedItem?.classList.remove('is-dragging');
        draggedItem = null;
        updateDomainSocialOrder(list);
    });
});

document.querySelectorAll('[data-navigation-builder]').forEach((builder) => {
    if (! (builder instanceof HTMLFormElement)) {
        return;
    }

    const rootList = builder.querySelector('[data-navigation-list]');
    const payloadInput = builder.querySelector('[data-navigation-payload]');
    const emptyState = builder.querySelector('[data-navigation-empty-state]');
    const modal = builder.querySelector('[data-navigation-selector-modal]');
    const typeContainer = builder.querySelector('[data-navigation-selector-types]');
    const searchPanel = builder.querySelector('[data-navigation-selector-search]');
    const customPanel = builder.querySelector('[data-navigation-selector-custom]');
    const searchInput = builder.querySelector('[data-navigation-search-input]');
    const resultsContainer = builder.querySelector('[data-navigation-results]');
    const customTitleInput = builder.querySelector('[data-navigation-custom-title]');
    const customUrlInput = builder.querySelector('[data-navigation-custom-url]');
    const linkOptionsUrl = builder.dataset.linkOptionsUrl;

    if (
        ! (rootList instanceof HTMLOListElement)
        || ! (payloadInput instanceof HTMLInputElement)
        || ! (modal instanceof HTMLElement)
        || ! (typeContainer instanceof HTMLElement)
        || ! (searchPanel instanceof HTMLElement)
        || ! (customPanel instanceof HTMLElement)
        || ! (searchInput instanceof HTMLInputElement)
        || ! (resultsContainer instanceof HTMLElement)
        || ! (customTitleInput instanceof HTMLInputElement)
        || ! (customUrlInput instanceof HTMLInputElement)
        || ! linkOptionsUrl
    ) {
        return;
    }

    const parseJson = (value, fallback) => {
        try {
            return JSON.parse(value || '');
        } catch {
            return fallback;
        }
    };

    const linkTypes = parseJson(builder.dataset.linkTypes, []);
    const typeByKey = new Map(linkTypes.map((type) => [type.key, type]));
    const defaultSelectorType = linkTypes.find((type) => type.key !== 'custom')?.key ?? linkTypes[0]?.key ?? 'custom';
    let selectedType = defaultSelectorType;
    let editingItem = null;
    let draggedItem = null;
    let searchTimer = null;

    const itemKey = () => `navigation-item-${Date.now()}-${Math.random().toString(16).slice(2)}`;

    const materialIcon = (name) => {
        const icon = document.createElement('span');

        icon.className = 'admin-material-icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.textContent = name;

        return icon;
    };

    const boolValue = (value, fallback = false) => {
        if (value === undefined || value === null) {
            return fallback;
        }

        return value === true || value === 'true' || value === '1' || value === 1;
    };

    const readItem = (item) => {
        const titleInput = item.querySelector(':scope > .navigation-builder-item-panel [data-navigation-item-title]');
        const customUrlInput = item.querySelector(':scope > .navigation-builder-item-panel [data-navigation-item-custom-url]');
        const activeInput = item.querySelector(':scope > .navigation-builder-item-panel [data-navigation-item-active]');
        const expandInput = item.querySelector(':scope > .navigation-builder-item-panel [data-navigation-item-expand]');
        const childList = item.querySelector(':scope > [data-navigation-child-list]');

        return {
            title: titleInput instanceof HTMLInputElement ? titleInput.value : item.dataset.title,
            link_type: item.dataset.linkType ?? 'custom',
            link_id: item.dataset.linkId ? Number(item.dataset.linkId) : null,
            custom_url: customUrlInput instanceof HTMLInputElement ? customUrlInput.value : (item.dataset.customUrl || null),
            is_active: activeInput instanceof HTMLInputElement ? activeInput.checked : true,
            expand_children: expandInput instanceof HTMLInputElement ? expandInput.checked : false,
            target_label: item.dataset.targetLabel || null,
            target_type_label: item.dataset.targetTypeLabel || null,
            is_category: item.dataset.isCategory === 'true',
            children: childList instanceof HTMLOListElement
                ? Array.from(childList.children).filter((child) => child instanceof HTMLLIElement).map(readItem)
                : [],
        };
    };

    const serialize = () => {
        const items = Array.from(rootList.children)
            .filter((child) => child instanceof HTMLLIElement)
            .map(readItem);

        payloadInput.value = JSON.stringify(items);

        if (emptyState instanceof HTMLElement) {
            emptyState.hidden = items.length > 0;
        }
    };

    const targetSummary = (item) => {
        if (item.link_type === 'custom') {
            return item.custom_url || 'Custom URL';
        }

        return [item.target_type_label, item.target_label].filter(Boolean).join(': ') || 'Linked item';
    };

    const renderItem = (item) => {
        const li = document.createElement('li');
        const linkType = item.link_type ?? 'custom';
        const type = typeByKey.get(linkType);

        li.className = 'navigation-builder-item';
        li.dataset.navigationItem = 'true';
        li.dataset.itemKey = item.client_id ?? itemKey();
        li.dataset.linkType = linkType;
        li.dataset.isCategory = String(Boolean(item.is_category ?? type?.is_category));
        li.dataset.targetLabel = item.target_label ?? item.label ?? item.title ?? '';
        li.dataset.targetTypeLabel = item.target_type_label ?? type?.label ?? '';

        if (item.link_id) {
            li.dataset.linkId = String(item.link_id);
        }

        if (item.custom_url) {
            li.dataset.customUrl = item.custom_url;
        }

        const panel = document.createElement('div');
        panel.className = 'navigation-builder-item-panel';

        const handle = document.createElement('button');
        handle.className = 'navigation-builder-drag-handle';
        handle.type = 'button';
        handle.draggable = true;
        handle.dataset.navigationDragHandle = 'true';
        handle.setAttribute('aria-label', 'Move item');
        handle.append(materialIcon('drag_indicator'));

        const body = document.createElement('div');
        body.className = 'navigation-builder-item-body';

        const titleInput = document.createElement('input');
        titleInput.className = 'navigation-builder-title-input';
        titleInput.type = 'text';
        titleInput.value = item.title ?? item.target_label ?? item.label ?? '';
        titleInput.dataset.navigationItemTitle = 'true';
        titleInput.setAttribute('aria-label', 'Navigation title');

        const meta = document.createElement('div');
        meta.className = 'navigation-builder-item-meta';
        meta.dataset.navigationItemMeta = 'true';
        meta.textContent = targetSummary({
            ...item,
            target_type_label: item.target_type_label ?? type?.label,
        });

        body.append(titleInput, meta);

        if (linkType === 'custom') {
            const urlInput = document.createElement('input');

            urlInput.className = 'navigation-builder-url-input';
            urlInput.type = 'text';
            urlInput.value = item.custom_url ?? '';
            urlInput.dataset.navigationItemCustomUrl = 'true';
            urlInput.setAttribute('aria-label', 'Custom URL');
            body.append(urlInput);
        }

        const options = document.createElement('div');
        options.className = 'navigation-builder-item-options';

        const activeLabel = document.createElement('label');
        activeLabel.className = 'navigation-builder-check';

        const activeInput = document.createElement('input');
        activeInput.type = 'checkbox';
        activeInput.checked = boolValue(item.is_active, true);
        activeInput.dataset.navigationItemActive = 'true';

        activeLabel.append(activeInput, document.createTextNode('Active'));
        options.append(activeLabel);

        if (li.dataset.isCategory === 'true') {
            const expandLabel = document.createElement('label');
            expandLabel.className = 'navigation-builder-check';

            const expandInput = document.createElement('input');
            expandInput.type = 'checkbox';
            expandInput.checked = boolValue(item.expand_children);
            expandInput.dataset.navigationItemExpand = 'true';

            expandLabel.append(expandInput, document.createTextNode('Use subcategories as submenu'));
            options.append(expandLabel);
        }

        const changeButton = document.createElement('button');
        changeButton.className = 'config-button';
        changeButton.type = 'button';
        changeButton.dataset.navigationChangeLink = 'true';
        changeButton.setAttribute('aria-label', 'Change link');
        changeButton.append(materialIcon('link'));

        const removeButton = document.createElement('button');
        removeButton.className = 'config-button';
        removeButton.type = 'button';
        removeButton.dataset.navigationRemoveItem = 'true';
        removeButton.setAttribute('aria-label', 'Remove item');
        removeButton.append(materialIcon('delete'));

        options.append(changeButton, removeButton);
        panel.append(handle, body, options);

        const childList = document.createElement('ol');
        childList.className = 'navigation-builder-list navigation-builder-list-children';
        childList.dataset.navigationChildList = 'true';

        (item.children ?? []).forEach((child) => childList.append(renderItem(child)));

        li.append(panel, childList);

        return li;
    };

    const renderItems = (items) => {
        rootList.replaceChildren();
        items.forEach((item) => rootList.append(renderItem(item)));
        serialize();
    };

    const addItem = (item) => {
        if (editingItem instanceof HTMLLIElement) {
            const current = readItem(editingItem);
            const nextItem = {
                ...current,
                ...item,
                title: current.title || item.title,
                children: current.children,
            };

            editingItem.replaceWith(renderItem(nextItem));
            editingItem = null;
            serialize();

            return;
        }

        rootList.append(renderItem(item));
        serialize();
    };

    const closeModal = () => {
        modal.hidden = true;
        editingItem = null;
    };

    const renderTypes = () => {
        typeContainer.replaceChildren();

        linkTypes.forEach((type) => {
            const button = document.createElement('button');

            button.className = 'navigation-selector-type';
            button.type = 'button';
            button.dataset.navigationSelectorType = type.key;
            button.classList.toggle('is-active', type.key === selectedType);
            button.textContent = type.label;
            typeContainer.append(button);
        });
    };

    const renderResults = (results) => {
        resultsContainer.replaceChildren();

        if (results.length === 0) {
            const empty = document.createElement('p');

            empty.className = 'navigation-selector-empty';
            empty.textContent = 'No results found.';
            resultsContainer.append(empty);

            return;
        }

        results.forEach((result) => {
            const item = document.createElement('div');
            item.className = 'navigation-selector-result';

            const content = document.createElement('div');
            content.className = 'navigation-selector-result-content';

            const title = document.createElement('strong');
            title.textContent = result.label;

            const meta = document.createElement('span');
            meta.textContent = [result.type_label, result.url].filter(Boolean).join(' - ');

            const button = document.createElement('button');
            button.className = 'btn';
            button.type = 'button';
            button.textContent = 'Select';
            button.addEventListener('click', () => {
                addItem({
                    title: result.label,
                    link_type: result.type,
                    link_id: result.id,
                    custom_url: null,
                    is_active: true,
                    expand_children: false,
                    target_label: result.label,
                    target_type_label: result.type_label,
                    is_category: result.is_category,
                    children: [],
                });
                closeModal();
            });

            content.append(title, meta);
            item.append(content, button);
            resultsContainer.append(item);
        });
    };

    const search = async () => {
        if (selectedType === 'custom') {
            return;
        }

        resultsContainer.textContent = 'Searching...';

        const url = new URL(linkOptionsUrl, window.location.origin);

        url.searchParams.set('type', selectedType);
        url.searchParams.set('q', searchInput.value);

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    Accept: 'application/json',
                },
            });
            const data = await response.json();

            renderResults(Array.isArray(data.results) ? data.results : []);
        } catch {
            resultsContainer.textContent = 'Search failed.';
        }
    };

    const scheduleSearch = () => {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(search, 220);
    };

    const selectType = (type) => {
        selectedType = type;
        renderTypes();
        customPanel.hidden = selectedType !== 'custom';
        searchPanel.hidden = selectedType === 'custom';
        resultsContainer.replaceChildren();

        if (selectedType !== 'custom') {
            scheduleSearch();
        }
    };

    const openModal = (item = null) => {
        editingItem = item;
        selectedType = item?.dataset.linkType ?? defaultSelectorType;
        customTitleInput.value = '';
        customUrlInput.value = '';
        searchInput.value = '';
        modal.hidden = false;
        selectType(selectedType);

        if (selectedType === 'custom') {
            customTitleInput.focus();
        } else {
            searchInput.focus();
        }
    };

    const clearDropState = () => {
        builder.querySelectorAll('.is-navigation-dragging, .is-navigation-drop-before, .is-navigation-drop-after, .is-navigation-drop-inside').forEach((element) => {
            element.classList.remove('is-navigation-dragging', 'is-navigation-drop-before', 'is-navigation-drop-after', 'is-navigation-drop-inside');
        });
    };

    const dropPosition = (target, event) => {
        const box = target.getBoundingClientRect();
        const offset = event.clientY - box.top;

        if (offset < box.height * 0.25) {
            return 'before';
        }

        if (offset > box.height * 0.75) {
            return 'after';
        }

        return 'inside';
    };

    const dropItem = (target, position) => {
        if (! draggedItem || ! (draggedItem instanceof HTMLLIElement)) {
            return;
        }

        if (! target) {
            rootList.append(draggedItem);
            serialize();

            return;
        }

        if (! (target instanceof HTMLLIElement) || draggedItem === target || draggedItem.contains(target)) {
            return;
        }

        if (position === 'inside') {
            const childList = target.querySelector(':scope > [data-navigation-child-list]');

            if (childList instanceof HTMLOListElement) {
                childList.append(draggedItem);
            }
        } else if (position === 'after') {
            target.after(draggedItem);
        } else {
            target.before(draggedItem);
        }

        serialize();
    };

    builder.querySelector('[data-navigation-open-selector]')?.addEventListener('click', () => openModal());

    builder.querySelectorAll('[data-navigation-selector-close]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    typeContainer.addEventListener('click', (event) => {
        if (! (event.target instanceof HTMLElement)) {
            return;
        }

        const typeButton = event.target.closest('[data-navigation-selector-type]');

        if (typeButton instanceof HTMLButtonElement) {
            selectType(typeButton.dataset.navigationSelectorType ?? 'custom');
        }
    });

    searchInput.addEventListener('input', scheduleSearch);

    builder.querySelector('[data-navigation-add-custom]')?.addEventListener('click', () => {
        const customUrl = customUrlInput.value.trim();
        const customTitle = customTitleInput.value.trim() || customUrl;

        if (customUrl === '') {
            customUrlInput.focus();

            return;
        }

        addItem({
            title: customTitle,
            link_type: 'custom',
            link_id: null,
            custom_url: customUrl,
            is_active: true,
            expand_children: false,
            target_label: customTitle,
            target_type_label: 'Custom URL',
            is_category: false,
            children: [],
        });
        closeModal();
    });

    rootList.addEventListener('input', serialize);
    rootList.addEventListener('change', serialize);
    builder.addEventListener('submit', serialize);

    rootList.addEventListener('click', (event) => {
        if (! (event.target instanceof Element)) {
            return;
        }

        const removeButton = event.target.closest('[data-navigation-remove-item]');

        if (removeButton instanceof HTMLButtonElement) {
            removeButton.closest('[data-navigation-item]')?.remove();
            serialize();

            return;
        }

        const changeButton = event.target.closest('[data-navigation-change-link]');

        if (changeButton instanceof HTMLButtonElement) {
            const item = changeButton.closest('[data-navigation-item]');

            if (item instanceof HTMLLIElement) {
                openModal(item);
            }
        }
    });

    rootList.addEventListener('dragstart', (event) => {
        const handle = event.target instanceof Element
            ? event.target.closest('[data-navigation-drag-handle]')
            : null;

        if (! (handle instanceof HTMLElement)) {
            event.preventDefault();

            return;
        }

        const item = handle.closest('[data-navigation-item]');

        if (! (item instanceof HTMLLIElement)) {
            return;
        }

        draggedItem = item;
        item.classList.add('is-navigation-dragging');
        event.dataTransfer?.setData('text/plain', item.dataset.itemKey ?? '');
        event.dataTransfer?.setDragImage(item, 24, 24);
    });

    builder.addEventListener('dragover', (event) => {
        if (! draggedItem || ! (event.target instanceof Element)) {
            return;
        }

        event.preventDefault();
        clearDropState();

        const target = event.target.closest('[data-navigation-item]');

        if (! (target instanceof HTMLLIElement) || target === draggedItem || draggedItem.contains(target)) {
            return;
        }

        const position = dropPosition(target, event);

        target.classList.add(`is-navigation-drop-${position}`);
    });

    builder.addEventListener('drop', (event) => {
        if (! draggedItem || ! (event.target instanceof Element)) {
            return;
        }

        event.preventDefault();

        const target = event.target.closest('[data-navigation-item]');
        const position = target instanceof HTMLLIElement ? dropPosition(target, event) : 'inside';

        dropItem(target, position);
        clearDropState();
        draggedItem = null;
    });

    builder.addEventListener('dragend', () => {
        clearDropState();
        draggedItem = null;
    });

    renderTypes();
    renderItems(parseJson(builder.dataset.initialItems, []));
});
