import '@melloware/coloris/dist/coloris.css';
import Coloris from '@melloware/coloris';
import { Fancybox } from '@fancyapps/ui';
import '@fancyapps/ui/dist/fancybox/fancybox.css';

const adminMenuButton = document.querySelector('[data-admin-menu-button]');
const adminNavigation = document.querySelector('[data-admin-navigation]');
const adminSidebarToggle = document.querySelector('[data-admin-sidebar-toggle]');
const siteMenuButton = document.querySelector('[data-site-menu-toggle]');
const siteMenuPanel = document.querySelector('[data-site-menu-panel]');
const siteMenuOverlay = document.querySelector('[data-site-menu-overlay]');
const siteMenuCloseButton = document.querySelector('[data-site-menu-close]');

Fancybox.bind('[data-fancybox]', {
    animated: true,
    dragToClose: true,
    Images: {
        Panzoom: {
            maxScale: 2,
        },
    },
});

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

document.querySelectorAll('[data-language-modal]').forEach((widget) => {
    const trigger = widget.querySelector('[data-language-modal-trigger]');
    const dialog = widget.querySelector('[data-language-modal-dialog]');

    if (! (trigger instanceof HTMLButtonElement) || ! (dialog instanceof HTMLElement)) {
        return;
    }

    let returnFocusTarget = null;

    const focusableSelector = [
        'button:not([disabled])',
        'a[href]',
        'input:not([disabled])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
    ].join(',');

    const focusableElements = () => Array.from(dialog.querySelectorAll(focusableSelector))
        .filter((element) => element instanceof HTMLElement && element.offsetParent !== null);

    const closeLanguageModal = () => {
        if (dialog.hidden) {
            return;
        }

        dialog.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');

        if (returnFocusTarget instanceof HTMLElement) {
            returnFocusTarget.focus();
        } else {
            trigger.focus();
        }

        returnFocusTarget = null;
    };

    const openLanguageModal = () => {
        returnFocusTarget = document.activeElement instanceof HTMLElement ? document.activeElement : trigger;
        dialog.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');

        const firstChoice = dialog.querySelector('.language-modal-option:not(:disabled)');
        const firstFocusable = firstChoice instanceof HTMLElement ? firstChoice : focusableElements()[0];

        firstFocusable?.focus();
    };

    trigger.addEventListener('click', () => {
        if (dialog.hidden) {
            openLanguageModal();

            return;
        }

        closeLanguageModal();
    });

    dialog.querySelectorAll('[data-language-modal-close]').forEach((closeButton) => {
        closeButton.addEventListener('click', closeLanguageModal);
    });

    dialog.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeLanguageModal();

            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        const elements = focusableElements();

        if (elements.length === 0) {
            event.preventDefault();

            return;
        }

        const firstElement = elements[0];
        const lastElement = elements[elements.length - 1];

        if (event.shiftKey && document.activeElement === firstElement) {
            event.preventDefault();
            lastElement.focus();

            return;
        }

        if (! event.shiftKey && document.activeElement === lastElement) {
            event.preventDefault();
            firstElement.focus();
        }
    });
});

const closeQuickStatus = (quickStatus) => {
    if (! (quickStatus instanceof HTMLDetailsElement)) {
        return;
    }

    quickStatus.open = false;

    const trigger = quickStatus.querySelector('.quick-status-trigger');

    if (trigger instanceof HTMLElement) {
        trigger.focus();
    }
};

document.addEventListener('click', (event) => {
    const closeTarget = event.target instanceof Element ? event.target.closest('[data-quick-status-close]') : null;

    if (! closeTarget) {
        return;
    }

    event.preventDefault();
    closeQuickStatus(closeTarget.closest('.quick-status'));
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    document.querySelectorAll('.quick-status[open]').forEach((quickStatus) => {
        closeQuickStatus(quickStatus);
    });
});

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
            '#0f6f7a',
            '#1b1b1b',
            '#2d7fc5',
            '#d86445',
            '#ffffff',
            '#f5f7fa',
            '#eef3f7',
            '#d8dee8',
            '#b4232d',
            '#1f7a4d',
        ],
        wrap: true,
    });
}

const googleFontPreviewInputs = document.querySelectorAll('[data-google-font-preview-input]');

const cssString = (value) => String(value).replace(/\\/g, '\\\\').replace(/"/g, '\\"');

let cropperModulePromise = null;

const loadCropper = async () => {
    cropperModulePromise ??= import('cropperjs');

    return (await cropperModulePromise).default;
};

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

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

if (adminSidebarToggle instanceof HTMLButtonElement) {
    const adminShell = adminSidebarToggle.closest('.site-wrapper-container');
    const adminNavigationGroups = document.querySelectorAll('.navigation-group');
    const collapseLabel = adminSidebarToggle.dataset.collapseLabel || 'Collapse menu';
    const expandLabel = adminSidebarToggle.dataset.expandLabel || 'Expand menu';
    const adminSidebarCookieName = 'base_cms_admin_sidebar_collapsed';

    const cookieValue = (name) => document.cookie
        .split(';')
        .map((cookie) => cookie.trim())
        .find((cookie) => cookie.startsWith(`${name}=`))
        ?.slice(name.length + 1);

    const setCookieValue = (name, value) => {
        const maxAge = 60 * 60 * 24 * 365;

        document.cookie = `${name}=${encodeURIComponent(value)}; path=/; max-age=${maxAge}; SameSite=Lax`;
    };

    const setAdminSidebarCollapsed = (isCollapsed, shouldPersist = true) => {
        if (! (adminShell instanceof HTMLElement)) {
            return;
        }

        adminShell.classList.toggle('is-admin-sidebar-collapsed', isCollapsed);
        document.body.classList.toggle('admin-sidebar-is-collapsed', isCollapsed);
        adminSidebarToggle.setAttribute('aria-expanded', String(! isCollapsed));

        adminNavigationGroups.forEach((group) => {
            if (! (group instanceof HTMLDetailsElement)) {
                return;
            }

            if (isCollapsed) {
                group.open = true;

                return;
            }

            group.open = false;
        });

        const label = isCollapsed ? expandLabel : collapseLabel;
        adminSidebarToggle.title = label;
        adminSidebarToggle.setAttribute('aria-label', label);
        if (shouldPersist) {
            setCookieValue(adminSidebarCookieName, isCollapsed ? 'true' : 'false');
        }
    };

    setAdminSidebarCollapsed(cookieValue(adminSidebarCookieName) === 'true', false);

    adminSidebarToggle.addEventListener('click', () => {
        setAdminSidebarCollapsed(! adminShell?.classList.contains('is-admin-sidebar-collapsed'));
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

document.querySelectorAll('[data-wysiwyg-editor]').forEach((editor) => {
    if (! (editor instanceof HTMLElement)) {
        return;
    }

    const surface = editor.querySelector('[data-wysiwyg-surface]');
    const input = editor.querySelector('[data-wysiwyg-input]');

    if (! (surface instanceof HTMLElement) || ! (input instanceof HTMLTextAreaElement)) {
        return;
    }

    const syncInput = () => {
        const html = surface.innerHTML.trim();

        input.value = html === '<br>' ? '' : surface.innerHTML;
    };

    editor.querySelectorAll('[data-wysiwyg-command]').forEach((button) => {
        if (! (button instanceof HTMLButtonElement)) {
            return;
        }

        button.addEventListener('mousedown', (event) => {
            event.preventDefault();
        });

        button.addEventListener('click', () => {
            const command = button.dataset.wysiwygCommand;

            if (! command) {
                return;
            }

            surface.focus();

            if (command === 'createLink') {
                const url = window.prompt(button.dataset.wysiwygPrompt || '');

                if (! url) {
                    return;
                }

                document.execCommand(command, false, url);
                syncInput();

                return;
            }

            document.execCommand(command, false, null);
            syncInput();
        });
    });

    surface.addEventListener('input', syncInput);
    editor.closest('form')?.addEventListener('submit', syncInput);
    syncInput();
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

const CONTENT_BLOCK_WIDTH_MIN = 15;
const CONTENT_BLOCK_WIDTH_MAX = 100;
const CONTENT_BLOCK_WIDTH_STEP = 5;
const CONTENT_BLOCK_WIDTH_EDGE_TOLERANCE = 0.025;

const contentBlockWidthByItemKey = new Map();

const normalizeContentBlockWidth = (value) => {
    const parsed = Number.parseInt(value ?? '50', 10);

    if (! Number.isFinite(parsed)) {
        return 50;
    }

    return Math.max(
        CONTENT_BLOCK_WIDTH_MIN,
        Math.min(
            CONTENT_BLOCK_WIDTH_MAX,
            Math.round(parsed / CONTENT_BLOCK_WIDTH_STEP) * CONTENT_BLOCK_WIDTH_STEP,
        ),
    );
};

const contentBlockWidthFromPointer = (range, clientX) => {
    const rect = range.getBoundingClientRect();

    if (rect.width <= 0) {
        return normalizeContentBlockWidth(range.value);
    }

    const ratio = Math.max(0, Math.min(1, (clientX - rect.left) / rect.width));

    if (ratio >= 1 - CONTENT_BLOCK_WIDTH_EDGE_TOLERANCE) {
        return CONTENT_BLOCK_WIDTH_MAX;
    }

    if (ratio <= CONTENT_BLOCK_WIDTH_EDGE_TOLERANCE) {
        return CONTENT_BLOCK_WIDTH_MIN;
    }

    return normalizeContentBlockWidth(
        CONTENT_BLOCK_WIDTH_MIN + (ratio * (CONTENT_BLOCK_WIDTH_MAX - CONTENT_BLOCK_WIDTH_MIN)),
    );
};

const contentBlockItemKey = (item) => {
    if (! (item instanceof HTMLElement)) {
        return null;
    }

    const uuidField = item.querySelector('input[type="hidden"][wire\\:model$=".uuid"]');

    return (uuidField instanceof HTMLInputElement ? uuidField.value : null)
        || item.getAttribute('x-sortable-item')
        || item.getAttribute('wire:key');
};

const rememberContentBlockWidth = (item, width) => {
    const key = contentBlockItemKey(item);

    if (! key) {
        return;
    }

    contentBlockWidthByItemKey.set(key, normalizeContentBlockWidth(width));
};

const contentBlockWidthLabel = (width) => `${normalizeContentBlockWidth(width)}%`;

const ensureContentBlockWidthIndicator = (item) => {
    let indicator = item.querySelector('[data-content-block-width-indicator]');

    if (! (indicator instanceof HTMLElement)) {
        indicator = document.createElement('span');
        indicator.className = 'content-block-width-indicator';
        indicator.dataset.contentBlockWidthIndicator = 'true';
        indicator.setAttribute('aria-hidden', 'true');
        item.append(indicator);
    }

    return indicator;
};

const setContentBlockWidthIndicator = (item, label) => {
    ensureContentBlockWidthIndicator(item).textContent = label;
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
    const normalizedWidth = normalizeContentBlockWidth(width);

    item.classList.remove(...contentBlockWidthClasses);
    item.classList.add(`content-block-builder-item--width-${normalizedWidth}`);
    item.dataset.contentBlockWidth = String(normalizedWidth);
    item.dataset.contentBlockWidthLabel = contentBlockWidthLabel(normalizedWidth);
    item.style.setProperty('--content-block-width-percent', String(normalizedWidth));
    item.style.setProperty('--content-block-width-size', `${normalizedWidth}%`);
    setContentBlockWidthIndicator(item, item.dataset.contentBlockWidthLabel);
    rememberContentBlockWidth(item, normalizedWidth);
};

const setContentBlockWidthPreview = (item, width) => {
    const normalizedWidth = normalizeContentBlockWidth(width);

    item.dataset.contentBlockPreviewWidth = String(normalizedWidth);
    item.dataset.contentBlockPreviewLabel = contentBlockWidthLabel(normalizedWidth);
    item.style.setProperty('--content-block-width-preview-percent', String(normalizedWidth));
    item.style.setProperty('--content-block-width-preview-size', `${normalizedWidth}%`);
    setContentBlockWidthIndicator(item, item.dataset.contentBlockPreviewLabel);
};

const clearContentBlockWidthPreview = (item) => {
    item.removeAttribute('data-content-block-preview-width');
    item.removeAttribute('data-content-block-preview-label');
    item.style.removeProperty('--content-block-width-preview-percent');
    item.style.removeProperty('--content-block-width-preview-size');
    setContentBlockWidthIndicator(item, item.dataset.contentBlockWidthLabel ?? contentBlockWidthLabel(item.dataset.contentBlockWidth));
};

const highlightCommittedContentBlockWidth = (item) => {
    item.classList.add('is-width-committed');

    window.setTimeout(() => {
        item.classList.remove('is-width-committed');
    }, 650);
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
        decreaseIcon.className = 'mso';
        decreaseIcon.setAttribute('aria-hidden', 'true');
        decreaseIcon.textContent = 'remove';

        slider = document.createElement('input');
        slider.className = 'content-block-width-slider';
        slider.type = 'range';
        slider.min = String(CONTENT_BLOCK_WIDTH_MIN);
        slider.max = String(CONTENT_BLOCK_WIDTH_MAX);
        slider.step = String(CONTENT_BLOCK_WIDTH_STEP);
        slider.dataset.contentBlockWidthRange = 'true';
        slider.setAttribute('aria-label', select.getAttribute('aria-label') || 'Block width');

        const increaseButton = document.createElement('button');
        increaseButton.className = 'content-block-width-step-button';
        increaseButton.type = 'button';
        increaseButton.dataset.contentBlockWidthStep = '5';
        increaseButton.setAttribute('aria-label', 'Increase block width');

        const increaseIcon = document.createElement('span');
        increaseIcon.className = 'mso';
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

    const nextValue = String(normalizeContentBlockWidth(width));
    const previousValue = select.value;

    select.value = nextValue;
    applyContentBlockWidthClass(item, nextValue);

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
    const builder = item.closest('[data-content-block-builder]') ?? document;

    range.value = String(width);
    setContentBlockWidthPreview(item, width);
    updateContentBlockWidth(item, width, true);
    highlightCommittedContentBlockWidth(item);
    item.classList.remove('is-width-resizing');
    clearContentBlockWidthPreview(item);
    scheduleContentBlockWidthSync(builder, {
        commitCached: true,
        preferCached: true,
    });

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
            const builder = item.closest('[data-content-block-builder]') ?? document;

            slider.value = String(width);
            updateContentBlockWidth(item, width, true);
            highlightCommittedContentBlockWidth(item);
            scheduleContentBlockWidthSync(builder, {
                commitCached: true,
                preferCached: true,
            });
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

    const item = event.target.closest('.fi-fo-builder-item');

    if (! (item instanceof HTMLElement)) {
        return;
    }

    const width = normalizeContentBlockWidth(event.target.value);

    event.target.value = String(width);
    setContentBlockWidthPreview(item, width);
    item.classList.add('is-width-resizing');
});

let activeContentBlockWidthRange = null;
let activeContentBlockWidthPointerId = null;

document.addEventListener('pointerdown', (event) => {
    if (! (event.target instanceof HTMLInputElement) || ! event.target.matches('[data-content-block-width-range]')) {
        return;
    }

    activeContentBlockWidthRange = event.target;
    activeContentBlockWidthPointerId = event.pointerId;
    const item = event.target.closest('.fi-fo-builder-item');
    const width = contentBlockWidthFromPointer(event.target, event.clientX);

    event.target.value = String(width);
    event.target.setPointerCapture?.(event.pointerId);

    if (item instanceof HTMLElement) {
        setContentBlockWidthPreview(item, width);
        item.classList.add('is-width-resizing');
    }
});

document.addEventListener('pointermove', (event) => {
    if (
        ! (activeContentBlockWidthRange instanceof HTMLInputElement)
        || activeContentBlockWidthPointerId !== event.pointerId
    ) {
        return;
    }

    const item = activeContentBlockWidthRange.closest('.fi-fo-builder-item');
    const width = contentBlockWidthFromPointer(activeContentBlockWidthRange, event.clientX);

    activeContentBlockWidthRange.value = String(width);

    if (item instanceof HTMLElement) {
        setContentBlockWidthPreview(item, width);
        item.classList.add('is-width-resizing');
    }
});

['pointerup', 'pointercancel'].forEach((eventName) => {
    document.addEventListener(eventName, (event) => {
        if (
            activeContentBlockWidthRange instanceof HTMLInputElement
            && activeContentBlockWidthPointerId !== event.pointerId
        ) {
            return;
        }

        if (
            activeContentBlockWidthRange instanceof HTMLInputElement
            && activeContentBlockWidthPointerId === event.pointerId
        ) {
            const width = eventName === 'pointerup'
                ? contentBlockWidthFromPointer(activeContentBlockWidthRange, event.clientX)
                : normalizeContentBlockWidth(activeContentBlockWidthRange.value);

            activeContentBlockWidthRange.value = String(width);
            commitContentBlockWidthRange(activeContentBlockWidthRange);
            activeContentBlockWidthRange.releasePointerCapture?.(event.pointerId);
        }

        activeContentBlockWidthRange = null;
        activeContentBlockWidthPointerId = null;

        document.querySelectorAll('.fi-fo-builder-item.is-width-resizing').forEach((item) => {
            item.classList.remove('is-width-resizing');

            if (item instanceof HTMLElement) {
                clearContentBlockWidthPreview(item);
            }
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

const contentBlockEditorElement = () => document.querySelector('[data-content-block-editor]');

const contentBlockEditorSaveButton = (editor) => editor?.querySelector('[data-content-block-editor-save]')
    ?? editor?.querySelector('.content-block-editor-toolbar .btn-save');

const contentBlockAutoSaveError = (editor) => editor instanceof HTMLElement
    ? editor.dataset.contentBlockAutoSaveError
    : null;

document.addEventListener('click', (event) => {
    if (! (event.target instanceof Element)) {
        return;
    }

    const toolbarSaveButton = event.target.closest('[data-content-block-toolbar-save]');

    if (! (toolbarSaveButton instanceof HTMLButtonElement)) {
        return;
    }

    const editor = contentBlockEditorElement();
    const saveButton = contentBlockEditorSaveButton(editor);

    if (! (editor instanceof HTMLElement) || ! (saveButton instanceof HTMLButtonElement)) {
        return;
    }

    toolbarSaveButton.disabled = true;
    toolbarSaveButton.setAttribute('aria-busy', 'true');

    const cleanup = () => {
        window.clearTimeout(timeout);
        toolbarSaveButton.disabled = false;
        toolbarSaveButton.removeAttribute('aria-busy');
    };

    const timeout = window.setTimeout(() => {
        window.removeEventListener('content-block-saved', cleanup);

        const message = contentBlockAutoSaveError(editor);

        if (message) {
            window.alert(message);
        }

        cleanup();
    }, 10000);

    window.addEventListener('content-block-saved', cleanup, { once: true });
    saveButton.click();
});

const submitContentItemFormAfterBlockSave = (form, submitter = null) => {
    const editor = contentBlockEditorElement();
    const saveButton = contentBlockEditorSaveButton(editor);

    if (! (editor instanceof HTMLElement) || ! (saveButton instanceof HTMLButtonElement)) {
        return false;
    }

    if (form.dataset.contentBlockSubmitting === 'true') {
        return true;
    }

    const saveAndStayField = form.querySelector('input[name="saveAndStay"]');

    if (saveAndStayField instanceof HTMLInputElement) {
        saveAndStayField.value = submitter instanceof HTMLButtonElement && submitter.name === 'saveAndStay'
            ? (submitter.value || '1')
            : '0';
    }

    form.dataset.contentBlockSubmitting = 'true';

    if (submitter instanceof HTMLButtonElement) {
        submitter.disabled = true;
        submitter.setAttribute('aria-busy', 'true');
    }

    const cleanup = () => {
        delete form.dataset.contentBlockSubmitting;

        if (submitter instanceof HTMLButtonElement) {
            submitter.disabled = false;
            submitter.removeAttribute('aria-busy');
        }
    };

    const continueSubmit = () => {
        window.clearTimeout(timeout);
        form.dataset.contentBlockSubmitReady = 'true';
        window.setTimeout(() => {
            delete form.dataset.contentBlockSubmitReady;
        }, 0);
        cleanup();

        try {
            if (typeof form.requestSubmit === 'function') {
                if (submitter instanceof HTMLButtonElement) {
                    form.requestSubmit(submitter);
                } else {
                    form.requestSubmit();
                }

                return;
            }
        } catch {
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();

                return;
            }
        }

        form.submit();
    };

    const timeout = window.setTimeout(() => {
        window.removeEventListener('content-block-saved', continueSubmit);
        cleanup();

        const message = contentBlockAutoSaveError(editor);

        if (message) {
            window.alert(message);
        }
    }, 10000);

    window.addEventListener('content-block-saved', continueSubmit, { once: true });
    saveButton.click();

    return true;
};

document.addEventListener('submit', (event) => {
    if (! (event.target instanceof HTMLFormElement) || event.target.getAttribute('id') !== 'content-item-form') {
        return;
    }

    if (event.target.dataset.contentBlockSubmitReady === 'true') {
        return;
    }

    if (! document.querySelector('[data-content-block-builder]')) {
        return;
    }

    if (submitContentItemFormAfterBlockSave(event.target, event.submitter instanceof HTMLButtonElement ? event.submitter : null)) {
        event.preventDefault();
    }
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

const imageMimeType = (file) => {
    if (['image/jpeg', 'image/png', 'image/webp'].includes(file?.type)) {
        return file.type;
    }

    return 'image/jpeg';
};

const imageExtension = (mimeType) => ({
    'image/jpeg': 'jpg',
    'image/png': 'png',
    'image/webp': 'webp',
})[mimeType] ?? 'jpg';

const editedImageFileName = (file, mimeType, suffix = 'edited') => {
    const baseName = fileNameWithoutExtension(file?.name ?? 'image') || 'image';

    return `${baseName}-${suffix}.${imageExtension(mimeType)}`;
};

const renderImageEditorFileList = (input, list, form) => {
    list.replaceChildren();

    const files = Array.from(input.files ?? []);

    list.hidden = files.length === 0;

    if (files.length === 0) {
        return;
    }

    const title = document.createElement('strong');
    title.textContent = files.length === 1
        ? (form.dataset.filesSelectedSingular ?? '')
        : (form.dataset.filesSelectedPlural ?? '');

    const items = document.createElement('ul');

    files.forEach((file) => {
        const item = document.createElement('li');
        item.textContent = `${file.name} (${readableFileSize(file.size)})`;
        items.append(item);
    });

    list.append(title, items);
};

const initializeContentImageEditors = (root = document) => {
    root.querySelectorAll('[data-content-image-editor]').forEach((form) => {
        if (! (form instanceof HTMLFormElement) || form.dataset.contentImageEditorReady === 'true') {
            return;
        }

        const input = form.querySelector('[data-content-image-editor-input]');
        const fileList = form.querySelector('[data-content-image-editor-file-list]');
        const panel = form.querySelector('[data-content-image-editor-panel]');
        const cropperStage = form.querySelector('[data-content-image-editor-cropper]');
        const fileName = form.querySelector('[data-content-image-editor-file-name]');
        const ratioSelect = form.querySelector('[data-content-image-editor-ratio]');
        const uploadButton = form.querySelector('[data-content-image-editor-upload]');
        const status = form.querySelector('[data-content-image-editor-status]');
        const statusTitle = form.querySelector('[data-content-image-editor-status-title]');
        const statusCopy = form.querySelector('[data-content-image-editor-status-copy]');
        let cropper = null;
        let editedFile = null;
        let editedFileUrl = null;

        if (! (input instanceof HTMLInputElement)
            || ! (fileList instanceof HTMLElement)
            || ! (panel instanceof HTMLElement)
            || ! (cropperStage instanceof HTMLElement)
            || ! (uploadButton instanceof HTMLButtonElement)
        ) {
            return;
        }

        form.dataset.contentImageEditorReady = 'true';

        const setStatus = (isVisible, title = '', copy = '') => {
            if (! (status instanceof HTMLElement)) {
                return;
            }

            status.hidden = ! isVisible;

            if (statusTitle instanceof HTMLElement && title) {
                statusTitle.textContent = title;
            }

            if (statusCopy instanceof HTMLElement && copy) {
                statusCopy.textContent = copy;
            }
        };

        const destroyCropper = () => {
            cropper?.destroy?.();
            cropper = null;
            cropperStage.replaceChildren();

            if (editedFileUrl) {
                URL.revokeObjectURL(editedFileUrl);
                editedFileUrl = null;
            }
        };

        const closeEditor = () => {
            destroyCropper();
            editedFile = null;
            panel.hidden = true;
            setStatus(false);
        };

        const applyAspectRatio = () => {
            if (! cropper || ! (ratioSelect instanceof HTMLSelectElement)) {
                return;
            }

            const selection = cropper.getCropperSelection?.();

            if (! selection) {
                return;
            }

            const ratio = ratioSelect.value;
            const [width, height] = ratio.split(':').map((value) => Number.parseFloat(value));

            selection.aspectRatio = Number.isFinite(width) && Number.isFinite(height) && width > 0 && height > 0
                ? width / height
                : Number.NaN;
            selection.$render?.();
        };

        const openEditor = async (file) => {
            closeEditor();
            editedFile = file;
            editedFileUrl = URL.createObjectURL(file);

            const Cropper = await loadCropper();
            const image = new Image();
            image.src = editedFileUrl;
            image.alt = file.name;

            cropper = new Cropper(image, {
                container: cropperStage,
            });

            if (fileName instanceof HTMLElement) {
                fileName.textContent = `${file.name} (${readableFileSize(file.size)})`;
            }

            panel.hidden = false;

            cropper.getCropperImage?.()?.$ready?.(() => {
                applyAspectRatio();
            });
        };

        const selectedFiles = () => Array.from(input.files ?? []).filter((file) => file.type.startsWith('image/'));

        input.addEventListener('change', () => {
            const files = selectedFiles();

            renderImageEditorFileList(input, fileList, form);

            if (files.length === 1) {
                openEditor(files[0]);

                return;
            }

            closeEditor();
        });

        ratioSelect?.addEventListener('change', applyAspectRatio);

        form.querySelectorAll('[data-content-image-editor-action]').forEach((button) => {
            button.addEventListener('click', () => {
                const action = button.dataset.contentImageEditorAction;
                const image = cropper?.getCropperImage?.();

                if (action === 'close') {
                    closeEditor();

                    return;
                }

                if (! image) {
                    return;
                }

                if (action === 'rotate-left') {
                    image.$rotate('-90deg');
                } else if (action === 'rotate-right') {
                    image.$rotate('90deg');
                } else if (action === 'zoom-out') {
                    image.$zoom(-0.1);
                } else if (action === 'zoom-in') {
                    image.$zoom(0.1);
                } else if (action === 'reset' && editedFile) {
                    openEditor(editedFile);
                }
            });
        });

        uploadButton.addEventListener('click', async () => {
            const selection = cropper?.getCropperSelection?.();

            if (! selection || ! editedFile) {
                return;
            }

            setStatus(true, form.dataset.processingTitle, form.dataset.processingCopy);
            uploadButton.disabled = true;

            try {
                const mimeType = imageMimeType(editedFile);
                const canvas = await selection.$toCanvas({
                    beforeDraw: mimeType === 'image/jpeg'
                        ? (context, canvasElement) => {
                            context.fillStyle = '#ffffff';
                            context.fillRect(0, 0, canvasElement.width, canvasElement.height);
                        }
                        : undefined,
                });
                const blob = await new Promise((resolve) => {
                    canvas.toBlob(resolve, mimeType, 0.92);
                });

                if (! (blob instanceof Blob)) {
                    throw new Error(form.dataset.exportErrorMessage || form.dataset.uploadErrorMessage || '');
                }

                const formData = new FormData();
                formData.append('image', blob, editedImageFileName(editedFile, mimeType, form.dataset.editedFileSuffix || 'edited'));
                formData.append('caption', fileNameWithoutExtension(editedFile.name).replace(/[-_]+/g, ' '));

                setStatus(true, form.dataset.uploadingTitle, form.dataset.uploadingCopy);

                const response = await fetch(form.dataset.uploadUrl || form.action, {
                    body: formData,
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    method: 'POST',
                });

                if (! response.ok) {
                    throw new Error((await response.json().catch(() => null))?.message || form.dataset.uploadErrorMessage || '');
                }

                window.location.reload();
            } catch (error) {
                setStatus(true, form.dataset.uploadErrorMessage || '', error.message || form.dataset.uploadErrorMessage || '');
                uploadButton.disabled = false;
            }
        });
    });
};

initializeContentImageEditors();

document.addEventListener('livewire:navigated', () => initializeContentImageEditors());

if (document.body) {
    const contentImageEditorObserver = new MutationObserver((mutations) => {
        const shouldInitialize = mutations.some((mutation) => Array.from(mutation.addedNodes).some((node) => {
            if (! (node instanceof Element)) {
                return false;
            }

            return node.matches('[data-content-image-editor]')
                || Boolean(node.querySelector('[data-content-image-editor]'));
        }));

        if (shouldInitialize) {
            initializeContentImageEditors();
        }
    });

    contentImageEditorObserver.observe(document.body, {
        childList: true,
        subtree: true,
    });
}

const renderSelectedAttachments = (input, list) => {
    list.replaceChildren();

    const files = Array.from(input.files ?? []);

    list.hidden = files.length === 0;

    files.forEach((file, index) => {
        const item = document.createElement('div');
        item.className = 'attachment-selection-item';

        const icon = document.createElement('span');
        icon.className = 'mso attachment-selection-icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.textContent = 'attach_file';

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

document.querySelectorAll('[data-form-builder]').forEach((builder) => {
    const canvas = builder.querySelector('[data-form-builder-canvas]');
    const hiddenInputs = builder.querySelector('[data-form-builder-inputs]');
    const emptyState = builder.querySelector('[data-form-builder-empty]');
    const modal = builder.querySelector('[data-form-builder-modal]');
    const optionsPanel = builder.querySelector('[data-form-builder-options-panel]');

    if (! (canvas instanceof HTMLOListElement) || ! (hiddenInputs instanceof HTMLElement) || ! (modal instanceof HTMLElement)) {
        return;
    }

    const dropSurface = canvas.closest('.form-builder-canvas-panel') ?? canvas;

    const parseJson = (value, fallback) => {
        try {
            return value ? JSON.parse(value) : fallback;
        } catch {
            return fallback;
        }
    };

    const fieldTypes = parseJson(builder.dataset.fieldTypes, {});
    const fieldIcons = parseJson(builder.dataset.fieldIcons, {});
    const initialBlocks = parseJson(builder.dataset.initialBlocks, []);
    const labels = {
        defaultBlockTitle: 'Form',
        defaultFieldLabel: 'New field',
        deleteField: 'Delete field',
        decreaseFieldWidth: 'Decrease field width',
        dropFields: 'No fields yet',
        editField: 'Edit field',
        fieldWidth: 'Field width',
        increaseFieldWidth: 'Increase field width',
        moveField: 'Move field',
        optionOne: 'Option 1',
        optionTwo: 'Option 2',
        ...parseJson(builder.dataset.formBuilderLabels, {}),
    };
    const optionTypes = new Set(['select', 'radio', 'checkbox', 'image-set-choice', 'image_set_choice']);
    const fields = [];
    const fieldByKey = new Map();
    const firstBlock = initialBlocks[0] ?? {};
    const firstRow = initialBlocks.flatMap((block) => block.rows ?? [])[0] ?? {};
    let editingKey = null;
    let draggedFieldKey = null;
    let draggedPaletteType = null;
    let pendingDropTargetKey = null;
    let pendingDropPosition = 'after';

    const materialIcon = (name) => {
        const icon = document.createElement('span');

        icon.className = 'mso';
        icon.setAttribute('aria-hidden', 'true');
        icon.textContent = name;

        return icon;
    };

    const inputName = (value) => String(value ?? '')
        .trim()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '')
        || 'field';

    const boolValue = (value, fallback = false) => {
        if (value === undefined || value === null || value === '') {
            return fallback;
        }

        return value === true || value === 'true' || value === '1' || value === 1;
    };

    const fieldKey = () => `form-field-${Date.now()}-${Math.random().toString(16).slice(2)}`;
    const labelForType = (type) => fieldTypes[type] ?? labels.defaultFieldLabel;
    const supportsOptions = (field) => optionTypes.has(field.type);
    const normalizeFieldWidth = (value) => {
        const parsed = Number.parseInt(value ?? '100', 10);
        const width = Number.isFinite(parsed) ? parsed : 100;

        return Math.min(100, Math.max(10, Math.round(width / 5) * 5));
    };
    const fieldWidthClass = (width) => `form-builder-canvas-item--width-${normalizeFieldWidth(width)}`;
    const applyFieldWidthClass = (item, width) => {
        item.className = item.className
            .split(/\s+/)
            .filter((className) => className && ! className.startsWith('form-builder-canvas-item--width-'))
            .join(' ');
        item.classList.add(fieldWidthClass(width));
        item.dataset.fieldWidth = String(normalizeFieldWidth(width));
    };

    const normalizeField = (field, index = 0) => {
        const type = field.type === 'image_set_choice' ? 'image-set-choice' : (field.type || 'input');
        const label = field.label || labelForType(type);

        return {
            client_id: field.client_id || fieldKey(),
            id: field.id || null,
            name: field.name || inputName(label),
            label,
            type,
            help_text: field.help_text || '',
            is_required: boolValue(field.is_required),
            sort_order: Number(field.sort_order || index + 1),
            validation_rules: field.validation_rules || '',
            placeholder: field.placeholder || '',
            default_value: field.default_value || '',
            label_visible: boolValue(field.label_visible, true),
            width: normalizeFieldWidth(field.width),
            custom_error_message: field.custom_error_message || '',
            information: field.information || '',
            css_class: field.css_class || '',
            options: Array.isArray(field.options) ? field.options.map((option, optionIndex) => ({
                id: option.id || null,
                label: option.label || '',
                value: option.value || inputName(option.label || `option_${optionIndex + 1}`),
                sort_order: Number(option.sort_order || optionIndex + 1),
                image_path: option.image_path || '',
                description: option.description || '',
            })) : [],
        };
    };

    const defaultOptions = (type) => optionTypes.has(type)
        ? [
            { label: labels.optionOne, value: 'option_1', sort_order: 1 },
            { label: labels.optionTwo, value: 'option_2', sort_order: 2 },
        ]
        : [];

    const createField = (type) => normalizeField({
        type,
        label: labelForType(type),
        name: inputName(labelForType(type)),
        options: defaultOptions(type),
    }, fields.length);

    const flattenInitialFields = () => {
        initialBlocks.forEach((block) => {
            (block.rows ?? []).forEach((row) => {
                (row.fields ?? []).forEach((field) => fields.push(normalizeField(field, fields.length)));
            });
        });
    };

    const syncFieldMap = () => {
        fieldByKey.clear();
        fields.forEach((field) => fieldByKey.set(field.client_id, field));
    };

    const orderedFields = () => Array.from(canvas.children)
        .map((item) => item instanceof HTMLElement ? fieldByKey.get(item.dataset.fieldKey) : null)
        .filter((field) => field);
    const fieldItemByKey = (key) => Array.from(canvas.querySelectorAll('[data-field-key]'))
        .find((item) => item instanceof HTMLElement && item.dataset.fieldKey === key);

    const optionsText = (field) => (field.options ?? [])
        .map((option) => [option.label, option.value].filter(Boolean).join('|'))
        .join('\n');

    const parseOptionsText = (value, previousOptions = []) => value
        .split(/\r?\n/)
        .map((line) => line.trim())
        .filter(Boolean)
        .map((line, index) => {
            const [label, valuePart] = line.split('|');
            const optionLabel = label.trim();

            return {
                id: previousOptions[index]?.id || null,
                label: optionLabel,
                value: (valuePart || '').trim() || inputName(optionLabel),
                sort_order: index + 1,
                image_path: previousOptions[index]?.image_path || '',
                description: previousOptions[index]?.description || '',
            };
        });

    const appendHidden = (name, value) => {
        const input = document.createElement('input');

        input.type = 'hidden';
        input.name = name;
        input.value = value ?? '';
        hiddenInputs.append(input);
    };

    const serialize = () => {
        const currentFields = orderedFields();

        hiddenInputs.replaceChildren();
        appendHidden('blocks[0][title]', firstBlock.title || labels.defaultBlockTitle);
        appendHidden('blocks[0][sort_order]', '1');

        if (firstBlock.id) {
            appendHidden('blocks[0][id]', firstBlock.id);
        }

        if (firstBlock.css_class) {
            appendHidden('blocks[0][css_class]', firstBlock.css_class);
        }

        appendHidden('blocks[0][rows][0][sort_order]', '1');
        appendHidden('blocks[0][rows][0][width]', firstRow.width || 100);

        if (firstRow.id) {
            appendHidden('blocks[0][rows][0][id]', firstRow.id);
        }

        if (firstRow.css_class) {
            appendHidden('blocks[0][rows][0][css_class]', firstRow.css_class);
        }

        currentFields.forEach((field, fieldIndex) => {
            const base = `blocks[0][rows][0][fields][${fieldIndex}]`;

            if (field.id) {
                appendHidden(`${base}[id]`, field.id);
            }

            appendHidden(`${base}[name]`, field.name);
            appendHidden(`${base}[label]`, field.label);
            appendHidden(`${base}[type]`, field.type);
            appendHidden(`${base}[help_text]`, field.help_text);
            appendHidden(`${base}[is_required]`, field.is_required ? '1' : '0');
            appendHidden(`${base}[sort_order]`, fieldIndex + 1);
            appendHidden(`${base}[validation_rules]`, field.validation_rules);
            appendHidden(`${base}[placeholder]`, field.placeholder);
            appendHidden(`${base}[default_value]`, field.default_value);
            appendHidden(`${base}[label_visible]`, field.label_visible ? '1' : '0');
            appendHidden(`${base}[width]`, field.width || 100);
            appendHidden(`${base}[custom_error_message]`, field.custom_error_message);
            appendHidden(`${base}[information]`, field.information);
            appendHidden(`${base}[css_class]`, field.css_class);

            (field.options ?? []).forEach((option, optionIndex) => {
                const optionBase = `${base}[options][${optionIndex}]`;

                if (option.id) {
                    appendHidden(`${optionBase}[id]`, option.id);
                }

                appendHidden(`${optionBase}[label]`, option.label);
                appendHidden(`${optionBase}[value]`, option.value);
                appendHidden(`${optionBase}[sort_order]`, optionIndex + 1);
                appendHidden(`${optionBase}[image_path]`, option.image_path);
                appendHidden(`${optionBase}[description]`, option.description);
            });
        });

        if (emptyState instanceof HTMLElement) {
            emptyState.hidden = currentFields.length > 0;
            emptyState.textContent = labels.dropFields;
        }
    };

    const fieldSummary = (field) => [fieldTypes[field.type] ?? field.type, field.is_required ? '*' : null].filter(Boolean).join(' ');
    const syncFieldWidthControls = (item, width) => {
        const normalizedWidth = normalizeFieldWidth(width);

        item.querySelectorAll('[data-form-builder-width-range]').forEach((input) => {
            if (input instanceof HTMLInputElement) {
                input.value = String(normalizedWidth);
            }
        });
    };
    const updateFieldWidth = (item, width) => {
        const field = fieldByKey.get(item.dataset.fieldKey);

        if (! field) {
            return;
        }

        field.width = normalizeFieldWidth(width);
        applyFieldWidthClass(item, field.width);
        syncFieldWidthControls(item, field.width);
        serialize();
    };
    const renderWidthControl = (field) => {
        const control = document.createElement('div');
        control.className = 'form-builder-width-control';
        control.dataset.formBuilderWidthControl = 'true';

        const decreaseButton = document.createElement('button');
        decreaseButton.className = 'form-builder-width-step-button';
        decreaseButton.type = 'button';
        decreaseButton.dataset.formBuilderWidthStep = '-5';
        decreaseButton.setAttribute('aria-label', labels.decreaseFieldWidth);

        const decreaseIcon = materialIcon('remove');

        const slider = document.createElement('input');
        slider.className = 'form-builder-width-slider';
        slider.type = 'range';
        slider.min = '10';
        slider.max = '100';
        slider.step = '5';
        slider.value = String(normalizeFieldWidth(field.width));
        slider.dataset.formBuilderWidthRange = 'true';
        slider.setAttribute('aria-label', labels.fieldWidth);

        const increaseButton = document.createElement('button');
        increaseButton.className = 'form-builder-width-step-button';
        increaseButton.type = 'button';
        increaseButton.dataset.formBuilderWidthStep = '5';
        increaseButton.setAttribute('aria-label', labels.increaseFieldWidth);

        const increaseIcon = materialIcon('add');

        decreaseButton.append(decreaseIcon);
        increaseButton.append(increaseIcon);
        control.append(decreaseButton, slider, increaseButton);

        return control;
    };

    const renderField = (field) => {
        const item = document.createElement('li');

        item.className = 'form-builder-canvas-item';
        item.dataset.fieldKey = field.client_id;
        applyFieldWidthClass(item, field.width);

        const main = document.createElement('button');
        main.className = 'form-builder-canvas-main';
        main.type = 'button';
        main.dataset.formBuilderOpenField = 'true';

        const copy = document.createElement('span');
        copy.className = 'form-builder-canvas-copy';

        const title = document.createElement('strong');
        title.textContent = field.label || labels.defaultFieldLabel;

        const meta = document.createElement('span');
        meta.textContent = fieldSummary(field);

        copy.append(title, meta);
        main.append(materialIcon(fieldIcons[field.type] ?? 'input'), copy);

        const handle = document.createElement('button');
        handle.className = 'form-builder-drag-handle';
        handle.type = 'button';
        handle.draggable = true;
        handle.dataset.formBuilderDragHandle = 'true';
        handle.setAttribute('aria-label', labels.moveField);
        handle.append(materialIcon('drag_indicator'));

        item.append(main, handle, renderWidthControl(field));

        return item;
    };

    const renderCanvas = () => {
        canvas.replaceChildren();
        fields.forEach((field) => canvas.append(renderField(field)));
        serialize();
    };

    const openModal = (field) => {
        editingKey = field.client_id;

        modal.querySelectorAll('[data-form-builder-setting]').forEach((input) => {
            const key = input.dataset.formBuilderSetting;

            if (input instanceof HTMLInputElement && input.type === 'checkbox') {
                input.checked = boolValue(field[key], key === 'label_visible');
            } else if (key === 'options_text' && (input instanceof HTMLTextAreaElement || input instanceof HTMLInputElement)) {
                input.value = optionsText(field);
            } else if (input instanceof HTMLInputElement || input instanceof HTMLSelectElement || input instanceof HTMLTextAreaElement) {
                input.value = field[key] ?? '';
            }
        });

        if (optionsPanel instanceof HTMLElement) {
            optionsPanel.hidden = ! supportsOptions(field);
        }

        modal.hidden = false;
        modal.querySelector('[data-form-builder-setting="label"]')?.focus();
    };

    const closeModal = () => {
        modal.hidden = true;
        editingKey = null;
    };

    const saveModal = () => {
        const field = fieldByKey.get(editingKey);

        if (! field) {
            closeModal();

            return;
        }

        modal.querySelectorAll('[data-form-builder-setting]').forEach((input) => {
            const key = input.dataset.formBuilderSetting;

            if (input instanceof HTMLInputElement && input.type === 'checkbox') {
                field[key] = input.checked;
            } else if (key === 'options_text' && (input instanceof HTMLTextAreaElement || input instanceof HTMLInputElement)) {
                field.options = parseOptionsText(input.value, field.options);
            } else if (input instanceof HTMLInputElement || input instanceof HTMLSelectElement || input instanceof HTMLTextAreaElement) {
                field[key] = key === 'width' ? normalizeFieldWidth(input.value || 100) : input.value;
            }
        });

        field.name = field.name || inputName(field.label);
        field.options = supportsOptions(field) ? field.options : [];
        syncFieldMap();
        renderCanvas();
        closeModal();
    };

    const addField = (type, target = null, position = 'after') => {
        const field = createField(type);
        const targetKey = target instanceof HTMLElement ? target.dataset.fieldKey : null;
        const targetIndex = targetKey ? fields.findIndex((candidate) => candidate.client_id === targetKey) : -1;

        if (targetIndex === -1) {
            fields.push(field);
        } else {
            fields.splice(position === 'before' ? targetIndex : targetIndex + 1, 0, field);
        }

        syncFieldMap();
        renderCanvas();
        openModal(field);
    };

    const moveField = (sourceKey, target = null, position = 'after') => {
        const sourceIndex = fields.findIndex((field) => field.client_id === sourceKey);

        if (sourceIndex === -1) {
            return;
        }

        const [field] = fields.splice(sourceIndex, 1);
        const targetKey = target instanceof HTMLElement ? target.dataset.fieldKey : null;
        const targetIndex = targetKey ? fields.findIndex((candidate) => candidate.client_id === targetKey) : -1;

        if (targetIndex === -1) {
            fields.push(field);
        } else {
            fields.splice(position === 'before' ? targetIndex : targetIndex + 1, 0, field);
        }

        renderCanvas();
    };

    const dropPosition = (target, event) => {
        const box = target.getBoundingClientRect();

        return event.clientY < box.top + box.height / 2 ? 'before' : 'after';
    };

    const restoreEmptyState = () => {
        if (emptyState instanceof HTMLElement) {
            emptyState.hidden = orderedFields().length > 0 || canvas.querySelector('[data-form-builder-drop-preview]') !== null;
        }
    };

    const draggedWidth = () => draggedFieldKey
        ? normalizeFieldWidth(fieldByKey.get(draggedFieldKey)?.width)
        : 100;

    const clearDropState = (includeDragging = false) => {
        canvas.classList.remove('is-form-builder-drag-over');
        canvas.querySelectorAll('.is-form-builder-drop-before, .is-form-builder-drop-after').forEach((element) => {
            element.classList.remove('is-form-builder-drop-before', 'is-form-builder-drop-after');
        });
        canvas.querySelector('[data-form-builder-drop-preview]')?.remove();
        pendingDropTargetKey = null;
        pendingDropPosition = 'after';

        if (includeDragging) {
            canvas.querySelectorAll('.is-form-builder-dragging').forEach((element) => {
                element.classList.remove('is-form-builder-dragging');
            });
        }

        restoreEmptyState();
    };

    const renderDropPreview = (target = null, position = 'after') => {
        canvas.querySelector('[data-form-builder-drop-preview]')?.remove();
        canvas.classList.add('is-form-builder-drag-over');

        const preview = document.createElement('li');
        preview.className = 'form-builder-drop-preview';
        preview.dataset.formBuilderDropPreview = 'true';
        preview.setAttribute('aria-hidden', 'true');
        applyFieldWidthClass(preview, draggedWidth());
        pendingDropTargetKey = target instanceof HTMLElement ? target.dataset.fieldKey : null;
        pendingDropPosition = position;

        if (target instanceof HTMLElement) {
            target.classList.add(position === 'before' ? 'is-form-builder-drop-before' : 'is-form-builder-drop-after');
            target[position === 'before' ? 'before' : 'after'](preview);
        } else {
            canvas.append(preview);
        }

        restoreEmptyState();
    };

    builder.querySelectorAll('[data-form-builder-add]').forEach((button) => {
        button.addEventListener('click', () => addField(button.dataset.formBuilderAdd || 'input'));
        button.addEventListener('dragstart', (event) => {
            draggedPaletteType = button.dataset.formBuilderAdd || 'input';
            event.dataTransfer?.setData('text/plain', draggedPaletteType);
            event.dataTransfer?.setData('application/x-form-builder-type', draggedPaletteType);
        });
        button.addEventListener('dragend', () => {
            draggedPaletteType = null;
            clearDropState(true);
        });
    });

    canvas.addEventListener('click', (event) => {
        if (! (event.target instanceof Element)) {
            return;
        }

        const widthStepButton = event.target.closest('[data-form-builder-width-step]');

        if (widthStepButton instanceof HTMLButtonElement) {
            const item = widthStepButton.closest('[data-field-key]');
            const slider = item?.querySelector('[data-form-builder-width-range]');
            const step = Number.parseInt(widthStepButton.dataset.formBuilderWidthStep ?? '0', 10);

            if (item instanceof HTMLElement && slider instanceof HTMLInputElement && Number.isFinite(step)) {
                const width = normalizeFieldWidth(Number.parseInt(slider.value || '100', 10) + step);

                item.classList.add('is-width-resizing');
                updateFieldWidth(item, width);
                window.setTimeout(() => item.classList.remove('is-width-resizing'), 220);
            }

            return;
        }

        const item = event.target.closest('[data-field-key]');

        if (
            ! (item instanceof HTMLElement)
            || event.target.closest('[data-form-builder-drag-handle]')
            || event.target.closest('[data-form-builder-width-control]')
        ) {
            return;
        }

        const field = fieldByKey.get(item.dataset.fieldKey);

        if (field) {
            openModal(field);
        }
    });

    canvas.addEventListener('input', (event) => {
        if (! (event.target instanceof HTMLInputElement) || ! event.target.matches('[data-form-builder-width-range]')) {
            return;
        }

        const item = event.target.closest('[data-field-key]');

        if (item instanceof HTMLElement) {
            item.classList.add('is-width-resizing');
            updateFieldWidth(item, event.target.value);
        }
    });

    canvas.addEventListener('change', (event) => {
        if (! (event.target instanceof HTMLInputElement) || ! event.target.matches('[data-form-builder-width-range]')) {
            return;
        }

        event.target.closest('[data-field-key]')?.classList.remove('is-width-resizing');
    });

    canvas.addEventListener('dragstart', (event) => {
        const handle = event.target instanceof Element ? event.target.closest('[data-form-builder-drag-handle]') : null;

        if (! (handle instanceof HTMLElement)) {
            event.preventDefault();

            return;
        }

        const item = handle.closest('[data-field-key]');

        if (! (item instanceof HTMLElement)) {
            return;
        }

        draggedFieldKey = item.dataset.fieldKey;
        item.classList.add('is-form-builder-dragging');
        event.dataTransfer?.setData('text/plain', draggedFieldKey || '');
        event.dataTransfer?.setData('application/x-form-builder-field', draggedFieldKey || '');
    });

    dropSurface.addEventListener('dragover', (event) => {
        if (! draggedFieldKey && ! draggedPaletteType) {
            return;
        }

        event.preventDefault();
        clearDropState(false);

        const target = event.target instanceof Element ? event.target.closest('[data-field-key]') : null;

        if (target instanceof HTMLElement && target.dataset.fieldKey === draggedFieldKey) {
            return;
        }

        if (! (target instanceof HTMLElement)) {
            renderDropPreview();

            return;
        }

        renderDropPreview(target, dropPosition(target, event));
    });

    dropSurface.addEventListener('drop', (event) => {
        if (! draggedFieldKey && ! draggedPaletteType) {
            return;
        }

        event.preventDefault();

        let target = event.target instanceof Element ? event.target.closest('[data-field-key]') : null;
        let position = target instanceof HTMLElement ? dropPosition(target, event) : 'after';

        if ((! (target instanceof HTMLElement) || target.dataset.fieldKey === draggedFieldKey) && pendingDropTargetKey) {
            target = fieldItemByKey(pendingDropTargetKey) ?? null;
            position = pendingDropPosition;
        }

        if (draggedFieldKey && target instanceof HTMLElement && target.dataset.fieldKey === draggedFieldKey) {
            draggedFieldKey = null;
            draggedPaletteType = null;
            clearDropState(true);

            return;
        }

        if (draggedPaletteType) {
            addField(draggedPaletteType, target, position);
        } else if (draggedFieldKey) {
            moveField(draggedFieldKey, target, position);
        }

        draggedFieldKey = null;
        draggedPaletteType = null;
        clearDropState(true);
    });

    canvas.addEventListener('dragend', () => {
        draggedFieldKey = null;
        clearDropState(true);
    });

    builder.addEventListener('submit', serialize);
    builder.querySelectorAll('[data-form-builder-close]').forEach((button) => button.addEventListener('click', closeModal));
    builder.querySelector('[data-form-builder-save-settings]')?.addEventListener('click', saveModal);
    builder.querySelector('[data-form-builder-setting="type"]')?.addEventListener('change', (event) => {
        if (optionsPanel instanceof HTMLElement && event.target instanceof HTMLSelectElement) {
            optionsPanel.hidden = ! optionTypes.has(event.target.value);
        }
    });
    builder.querySelector('[data-form-builder-delete-field]')?.addEventListener('click', () => {
        const index = fields.findIndex((field) => field.client_id === editingKey);

        if (index !== -1) {
            fields.splice(index, 1);
        }

        syncFieldMap();
        renderCanvas();
        closeModal();
    });
    modal.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeModal();
        }
    });

    flattenInitialFields();
    syncFieldMap();
    renderCanvas();
});

document.querySelectorAll('[data-form-managed-list]').forEach((list) => {
    if (! (list instanceof HTMLElement)) {
        return;
    }

    const listName = list.dataset.formManagedList;
    const template = list.querySelector(`[data-form-managed-list-template="${listName}"]`);

    const nextIndex = () => {
        const current = Number.parseInt(list.dataset.formManagedListNextIndex ?? '0', 10);
        const next = Number.isFinite(current) ? current : 0;

        list.dataset.formManagedListNextIndex = String(next + 1);

        return next;
    };

    const currentRowCount = () => list.querySelectorAll('[data-form-managed-list-row]:not(.is-marked-for-delete)').length;

    const addRow = () => {
        if (! (template instanceof HTMLTemplateElement)) {
            return;
        }

        const index = nextIndex();
        const html = template.innerHTML
            .replaceAll('__INDEX__', String(index))
            .replaceAll('__SORT__', String(currentRowCount() + 1));
        const container = document.createElement('div');

        container.innerHTML = html.trim();

        const row = container.firstElementChild;

        if (! (row instanceof HTMLElement)) {
            return;
        }

        template.before(row);
        row.querySelector('input:not([type="hidden"]), select, textarea')?.focus();
    };

    list.addEventListener('click', (event) => {
        if (! (event.target instanceof Element)) {
            return;
        }

        const deleteButton = event.target.closest('[data-form-managed-list-delete]');

        if (! (deleteButton instanceof HTMLButtonElement)) {
            return;
        }

        const row = deleteButton.closest('[data-form-managed-list-row]');

        if (! (row instanceof HTMLElement)) {
            return;
        }

        const idInput = row.querySelector('input[name$="[id]"]');
        const deleteInput = row.querySelector('[data-form-managed-list-delete-input]');

        if (idInput instanceof HTMLInputElement && idInput.value !== '' && deleteInput instanceof HTMLInputElement) {
            deleteInput.value = '1';
            row.classList.add('is-marked-for-delete');

            return;
        }

        row.remove();
    });

    document.querySelectorAll(`[data-form-managed-list-add="${listName}"]`).forEach((button) => {
        button.addEventListener('click', addRow);
    });
});

document.addEventListener('focusin', (event) => {
    if (! (event.target instanceof HTMLInputElement) && ! (event.target instanceof HTMLTextAreaElement)) {
        return;
    }

    if (! event.target.matches('[data-mail-builder-insertable]')) {
        return;
    }

    const builder = event.target.closest('[data-response-mail-builder]');

    if (! (builder instanceof HTMLElement)) {
        return;
    }

    builder.querySelectorAll('[data-mail-builder-insertable][data-mail-builder-active="true"]').forEach((field) => {
        field.removeAttribute('data-mail-builder-active');
    });

    event.target.dataset.mailBuilderActive = 'true';
});

document.addEventListener('click', (event) => {
    if (! (event.target instanceof Element)) {
        return;
    }

    const tokenButton = event.target.closest('[data-mail-placeholder-token]');

    if (! (tokenButton instanceof HTMLButtonElement)) {
        return;
    }

    const builder = tokenButton.closest('[data-response-mail-builder]');
    const token = tokenButton.dataset.mailPlaceholderToken;

    if (! (builder instanceof HTMLElement) || ! token) {
        return;
    }

    const target = builder.querySelector('[data-mail-builder-insertable][data-mail-builder-active="true"]')
        ?? builder.querySelector('textarea[data-mail-builder-insertable]')
        ?? builder.querySelector('input[data-mail-builder-insertable]');

    if (! (target instanceof HTMLInputElement) && ! (target instanceof HTMLTextAreaElement)) {
        return;
    }

    const start = target.selectionStart ?? target.value.length;
    const end = target.selectionEnd ?? start;
    const prefix = target.value.slice(0, start);
    const suffix = target.value.slice(end);

    target.value = `${prefix}${token}${suffix}`;
    target.selectionStart = start + token.length;
    target.selectionEnd = start + token.length;
    target.focus();
    target.dispatchEvent(new Event('input', { bubbles: true }));
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
    const linkTypesUrl = builder.dataset.linkTypesUrl;
    const allLanguagesInput = builder.querySelector('[data-navigation-all-languages]');

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
        || ! linkTypesUrl
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

    let linkTypes = parseJson(builder.dataset.linkTypes, []);
    const labels = {
        active: 'Active',
        changeLink: 'Change link',
        customUrl: 'Custom URL',
        editSource: 'Edit source',
        linkedItem: 'Linked item',
        moveItem: 'Move item',
        navigationTitle: 'Navigation title',
        noResults: 'No results found.',
        removeItem: 'Remove item',
        searchFailed: 'Search failed.',
        searching: 'Searching...',
        select: 'Select',
        useSubcategoriesAsSubmenu: 'Use subcategories as submenu',
        ...parseJson(builder.dataset.navigationLabels, {}),
    };
    let typeByKey = new Map(linkTypes.map((type) => [type.key, type]));
    const defaultSelectorType = () => linkTypes.find((type) => type.key !== 'custom')?.key ?? linkTypes[0]?.key ?? 'custom';
    let selectedType = defaultSelectorType();
    let editingItem = null;
    let draggedItem = null;
    let searchTimer = null;

    const itemKey = () => `navigation-item-${Date.now()}-${Math.random().toString(16).slice(2)}`;

    const materialIcon = (name) => {
        const icon = document.createElement('span');

        icon.className = 'mso';
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

    const selectedLocale = () => {
        const checkedLocale = builder.querySelector('input[name="locale"]:checked');

        if (checkedLocale instanceof HTMLInputElement && checkedLocale.value) {
            return checkedLocale.value;
        }

        return builder.dataset.navigationLocale || '';
    };

    const allLanguages = () => allLanguagesInput instanceof HTMLInputElement && allLanguagesInput.checked;

    const refreshTypeMap = () => {
        typeByKey = new Map(linkTypes.map((type) => [type.key, type]));
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
            source_edit_url: item.dataset.sourceEditUrl || null,
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
            return item.custom_url || labels.customUrl;
        }

        return [item.target_type_label, item.target_label].filter(Boolean).join(': ') || labels.linkedItem;
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

        const sourceEditUrl = item.source_edit_url ?? item.admin_url ?? item.edit_url ?? '';

        if (item.link_id) {
            li.dataset.linkId = String(item.link_id);
        }

        if (sourceEditUrl) {
            li.dataset.sourceEditUrl = sourceEditUrl;
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
        handle.setAttribute('aria-label', labels.moveItem);
        handle.append(materialIcon('drag_indicator'));

        const body = document.createElement('div');
        body.className = 'navigation-builder-item-body';

        const titleInput = document.createElement('input');
        titleInput.className = 'navigation-builder-title-input';
        titleInput.type = 'text';
        titleInput.value = item.title ?? item.target_label ?? item.label ?? '';
        titleInput.dataset.navigationItemTitle = 'true';
        titleInput.setAttribute('aria-label', labels.navigationTitle);

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
            urlInput.setAttribute('aria-label', labels.customUrl);
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

        activeLabel.append(activeInput, document.createTextNode(labels.active));
        options.append(activeLabel);

        if (li.dataset.isCategory === 'true') {
            const expandLabel = document.createElement('label');
            expandLabel.className = 'navigation-builder-check';

            const expandInput = document.createElement('input');
            expandInput.type = 'checkbox';
            expandInput.checked = boolValue(item.expand_children);
            expandInput.dataset.navigationItemExpand = 'true';

            expandLabel.append(expandInput, document.createTextNode(labels.useSubcategoriesAsSubmenu));
            options.append(expandLabel);
        }

        if (sourceEditUrl && linkType !== 'custom') {
            const editSourceLink = document.createElement('a');

            editSourceLink.className = 'config-button';
            editSourceLink.href = sourceEditUrl;
            editSourceLink.target = '_blank';
            editSourceLink.rel = 'noopener';
            editSourceLink.setAttribute('aria-label', labels.editSource);
            editSourceLink.title = labels.editSource;
            editSourceLink.append(materialIcon('edit_square'));
            options.append(editSourceLink);
        }

        const changeButton = document.createElement('button');
        changeButton.className = 'config-button';
        changeButton.type = 'button';
        changeButton.dataset.navigationChangeLink = 'true';
        changeButton.setAttribute('aria-label', labels.changeLink);
        changeButton.append(materialIcon('link'));

        const removeButton = document.createElement('button');
        removeButton.className = 'config-button';
        removeButton.type = 'button';
        removeButton.dataset.navigationRemoveItem = 'true';
        removeButton.setAttribute('aria-label', labels.removeItem);
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

    const loadTypes = async () => {
        const url = new URL(linkTypesUrl, window.location.origin);

        url.searchParams.set('locale', selectedLocale());

        if (allLanguages()) {
            url.searchParams.set('all_languages', '1');
        }

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    Accept: 'application/json',
                },
            });
            const data = await response.json();

            if (Array.isArray(data.types)) {
                linkTypes = data.types;
                refreshTypeMap();

                if (! typeByKey.has(selectedType)) {
                    selectedType = defaultSelectorType();
                }

                renderTypes();
            }
        } catch {
            renderTypes();
        }
    };

    const renderResults = (results) => {
        resultsContainer.replaceChildren();

        if (results.length === 0) {
            const empty = document.createElement('p');

            empty.className = 'navigation-selector-empty';
            empty.textContent = labels.noResults;
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
            meta.textContent = [result.locale_label, result.type_label, result.url].filter(Boolean).join(' - ');

            if (result.flag_url) {
                const flag = document.createElement('img');

                flag.className = 'navigation-selector-result-flag';
                flag.src = result.flag_url;
                flag.alt = result.locale_label || '';
                content.append(flag);
            }

            const button = document.createElement('button');
            button.className = 'btn';
            button.type = 'button';
            button.textContent = labels.select;
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
                    source_edit_url: result.source_edit_url,
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

        resultsContainer.textContent = labels.searching;

        const url = new URL(linkOptionsUrl, window.location.origin);

        url.searchParams.set('type', selectedType);
        url.searchParams.set('q', searchInput.value);
        url.searchParams.set('locale', selectedLocale());

        if (allLanguages()) {
            url.searchParams.set('all_languages', '1');
        }

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    Accept: 'application/json',
                },
            });
            const data = await response.json();

            renderResults(Array.isArray(data.results) ? data.results : []);
        } catch {
            resultsContainer.textContent = labels.searchFailed;
        }
    };

    const scheduleSearch = () => {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(search, 220);
    };

    const selectType = (type) => {
        selectedType = typeByKey.has(type) ? type : defaultSelectorType();
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
        selectedType = item?.dataset.linkType ?? defaultSelectorType();
        customTitleInput.value = '';
        customUrlInput.value = '';
        searchInput.value = '';
        modal.hidden = false;
        loadTypes().then(() => selectType(selectedType));

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

    if (allLanguagesInput instanceof HTMLInputElement) {
        allLanguagesInput.addEventListener('change', () => {
            loadTypes().then(() => selectType(selectedType));
        });
    }

    builder.querySelectorAll('input[name="locale"]').forEach((localeInput) => {
        localeInput.addEventListener('change', () => {
            if (localeInput instanceof HTMLInputElement && localeInput.checked) {
                builder.dataset.navigationLocale = localeInput.value;
                loadTypes().then(() => selectType(selectedType));
            }
        });
    });

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
            target_type_label: labels.customUrl,
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

    refreshTypeMap();
    renderTypes();
    renderItems(parseJson(builder.dataset.initialItems, []));
});

const unsavedBackSnapshotField = (field) => {
    const type = field instanceof HTMLInputElement ? field.type.toLowerCase() : field.tagName.toLowerCase();

    if (field instanceof HTMLInputElement && ['button', 'image', 'reset', 'submit'].includes(type)) {
        return null;
    }

    if (field.disabled || ['_method', '_token'].includes(field.name)) {
        return null;
    }

    let value = field.value;

    if (field instanceof HTMLInputElement && ['checkbox', 'radio'].includes(type)) {
        value = field.checked;
    } else if (field instanceof HTMLInputElement && type === 'file') {
        value = Array.from(field.files ?? []).map((file) => `${file.name}:${file.size}:${file.lastModified}`);
    } else if (field instanceof HTMLSelectElement && field.multiple) {
        value = Array.from(field.selectedOptions).map((option) => option.value);
    }

    return [
        field.name || field.id || field.getAttribute('aria-label') || field.tagName,
        type,
        value,
    ];
};

const unsavedBackSnapshotForm = (form) => JSON.stringify(
    Array.from(form.querySelectorAll('input, select, textarea'))
        .map(unsavedBackSnapshotField)
        .filter((field) => field !== null),
);

const unsavedBackTrackedForms = new Map();

const registerUnsavedBackForm = (form) => {
    if (unsavedBackTrackedForms.has(form) || form.matches('[data-dirty-ignore]') || form.closest('[data-dirty-ignore]')) {
        return;
    }

    unsavedBackTrackedForms.set(form, unsavedBackSnapshotForm(form));
    form.addEventListener('submit', () => {
        form.dataset.dirtySubmitting = 'true';
        window.setTimeout(() => {
            delete form.dataset.dirtySubmitting;
        }, 1500);
    });
};

document.querySelectorAll('form').forEach((form) => {
    if (form instanceof HTMLFormElement) {
        registerUnsavedBackForm(form);
    }
});

let listingDeleteModal = null;
let listingDeletePendingForm = null;
let listingDeleteReturnFocus = null;

const listingDeleteText = (key, fallback) => document.body.dataset[key] || fallback;

const overviewRowText = (row, selectors) => {
    for (const selector of selectors) {
        const element = row.querySelector(selector);
        const text = element?.textContent?.trim().replace(/\s+/g, ' ');

        if (text) {
            return text;
        }
    }

    return '';
};

const listingDeleteItem = (form) => {
    const row = form.closest('.overview-row');
    const fallbackName = listingDeleteText('deleteItemFallbackName', 'item');

    if (! (row instanceof HTMLElement)) {
        return {
            id: form.dataset.deleteItemId || '',
            name: form.dataset.deleteItemName || fallbackName,
        };
    }

    return {
        id: form.dataset.deleteItemId || overviewRowText(row, ['.overview-item.id']) || '',
        name: form.dataset.deleteItemName || overviewRowText(row, [
            '.overview-item.name',
            '.overview-item.title',
            '.overview-item.source',
            '.overview-item.email',
            '.overview-item.code',
            '.overview-item.subject',
        ]) || fallbackName,
    };
};

const closeListingDeleteModal = () => {
    if (! listingDeleteModal) {
        return;
    }

    listingDeleteModal.hidden = true;
    document.body.classList.remove('has-listing-delete-modal');
    listingDeletePendingForm = null;

    if (listingDeleteReturnFocus instanceof HTMLElement) {
        listingDeleteReturnFocus.focus();
    }

    listingDeleteReturnFocus = null;
};

const buildListingDeleteModal = () => {
    if (listingDeleteModal) {
        return listingDeleteModal;
    }

    const modal = document.createElement('div');
    const titleId = 'listing-delete-modal-title';
    const descriptionId = 'listing-delete-modal-description';

    modal.className = 'listing-delete-modal';
    modal.hidden = true;
    modal.dataset.listingDeleteModal = 'true';
    modal.innerHTML = `
        <div class="listing-delete-modal-backdrop" data-listing-delete-close></div>
        <section class="listing-delete-modal-panel" role="dialog" aria-modal="true" aria-labelledby="${titleId}" aria-describedby="${descriptionId}">
            <div class="listing-delete-modal-body">
                <h2 class="listing-delete-modal-title" id="${titleId}"></h2>
                <p class="listing-delete-modal-description" id="${descriptionId}"></p>
            </div>
            <div class="listing-delete-modal-actions">
                <button class="btn btn-cancel" type="button" data-listing-delete-close></button>
                <button class="btn btn-remove" type="button" data-listing-delete-confirm></button>
            </div>
        </section>
    `;

    modal.querySelectorAll('[data-listing-delete-close]').forEach((button) => {
        button.addEventListener('click', closeListingDeleteModal);
    });

    modal.querySelector('[data-listing-delete-confirm]')?.addEventListener('click', () => {
        const form = listingDeletePendingForm;

        if (! (form instanceof HTMLFormElement)) {
            closeListingDeleteModal();

            return;
        }

        form.dataset.listingDeleteConfirmed = 'true';
        form.requestSubmit();
    });

    document.body.append(modal);
    listingDeleteModal = modal;

    return modal;
};

const openListingDeleteModal = (form, returnFocus) => {
    const modal = buildListingDeleteModal();
    const item = listingDeleteItem(form);
    const itemLabel = item.id ? `${item.name} #${item.id}` : item.name;
    const title = modal.querySelector('.listing-delete-modal-title');
    const description = modal.querySelector('.listing-delete-modal-description');
    const cancel = modal.querySelector('[data-listing-delete-close].btn');
    const confirm = modal.querySelector('[data-listing-delete-confirm]');

    if (title instanceof HTMLElement) {
        title.textContent = listingDeleteText('deleteConfirmTitle', 'Delete item?');
    }

    if (description instanceof HTMLElement) {
        description.textContent = listingDeleteText('deleteConfirmMessage', 'Are you sure you want to delete :item?').split(':item').join(itemLabel);
    }

    if (cancel instanceof HTMLButtonElement) {
        cancel.textContent = listingDeleteText('deleteConfirmCancel', 'Cancel');
    }

    if (confirm instanceof HTMLButtonElement) {
        confirm.textContent = listingDeleteText('deleteConfirmButton', 'Delete');
    }

    listingDeletePendingForm = form;
    listingDeleteReturnFocus = returnFocus;
    modal.hidden = false;
    document.body.classList.add('has-listing-delete-modal');
    confirm?.focus();
};

document.addEventListener('submit', (event) => {
    if (! (event.target instanceof HTMLFormElement)) {
        return;
    }

    const form = event.target;

    if (
        form.dataset.listingDeleteConfirmed === 'true'
        || ! form.querySelector('input[name="_method"][value="delete"], input[name="_method"][value="DELETE"]')
        || ! document.body.dataset.deleteConfirmTitle
    ) {
        return;
    }

    event.preventDefault();
    openListingDeleteModal(form, event.submitter instanceof HTMLElement ? event.submitter : form.querySelector('button[type="submit"]'));
});

const hasUnsavedBackChanges = () => {
    let isDirty = false;

    unsavedBackTrackedForms.forEach((snapshot, form) => {
        if (isDirty || ! form.isConnected || form.dataset.dirtySubmitting === 'true') {
            return;
        }

        isDirty = unsavedBackSnapshotForm(form) !== snapshot;
    });

    return isDirty;
};

let unsavedBackModal = null;
let unsavedBackPendingUrl = null;
let unsavedBackReturnFocus = null;

const unsavedBackBodyConfig = () => {
    const { dataset } = document.body;

    if (! dataset.unsavedBackTitle || ! dataset.unsavedBackMessage || ! dataset.unsavedBackConfirm || ! dataset.unsavedBackCancel) {
        return null;
    }

    return {
        cancel: dataset.unsavedBackCancel,
        confirm: dataset.unsavedBackConfirm,
        message: dataset.unsavedBackMessage,
        module: dataset.unsavedBackModule || '',
        title: dataset.unsavedBackTitle,
    };
};

const closeUnsavedBackModal = () => {
    if (! unsavedBackModal) {
        return;
    }

    unsavedBackModal.hidden = true;
    document.body.classList.remove('has-unsaved-back-modal');
    unsavedBackPendingUrl = null;

    if (unsavedBackReturnFocus instanceof HTMLElement) {
        unsavedBackReturnFocus.focus();
    }

    unsavedBackReturnFocus = null;
};

const buildUnsavedBackModal = () => {
    if (unsavedBackModal) {
        return unsavedBackModal;
    }

    const modal = document.createElement('div');
    const titleId = 'unsaved-back-modal-title';
    const descriptionId = 'unsaved-back-modal-description';

    modal.className = 'unsaved-back-modal';
    modal.hidden = true;
    modal.dataset.unsavedBackModal = 'true';
    modal.innerHTML = `
        <div class="unsaved-back-modal-backdrop" data-unsaved-back-close></div>
        <section class="unsaved-back-modal-panel" role="dialog" aria-modal="true" aria-labelledby="${titleId}" aria-describedby="${descriptionId}">
            <div class="unsaved-back-modal-body">
                <h2 class="unsaved-back-modal-title" id="${titleId}"></h2>
                <p class="unsaved-back-modal-description" id="${descriptionId}"></p>
            </div>
            <div class="unsaved-back-modal-actions">
                <button class="btn btn-cancel" type="button" data-unsaved-back-close></button>
                <button class="btn btn-remove" type="button" data-unsaved-back-confirm></button>
            </div>
        </section>
    `;

    modal.querySelectorAll('[data-unsaved-back-close]').forEach((button) => {
        button.addEventListener('click', closeUnsavedBackModal);
    });

    modal.querySelector('[data-unsaved-back-confirm]')?.addEventListener('click', () => {
        if (! unsavedBackPendingUrl) {
            closeUnsavedBackModal();

            return;
        }

        window.location.assign(unsavedBackPendingUrl);
    });

    document.body.append(modal);
    unsavedBackModal = modal;

    return modal;
};

const openUnsavedBackModal = (url, returnFocus) => {
    const config = unsavedBackBodyConfig();

    if (! config) {
        window.location.assign(url);

        return;
    }

    const modal = buildUnsavedBackModal();
    const title = modal.querySelector('.unsaved-back-modal-title');
    const description = modal.querySelector('.unsaved-back-modal-description');
    const cancel = modal.querySelector('[data-unsaved-back-close].btn');
    const confirm = modal.querySelector('[data-unsaved-back-confirm]');

    if (title instanceof HTMLElement) {
        title.textContent = config.title;
    }

    if (description instanceof HTMLElement) {
        description.textContent = config.message.split(':module').join(config.module);
    }

    if (cancel instanceof HTMLButtonElement) {
        cancel.textContent = config.cancel;
    }

    if (confirm instanceof HTMLButtonElement) {
        confirm.textContent = config.confirm;
    }

    unsavedBackPendingUrl = url;
    unsavedBackReturnFocus = returnFocus;
    modal.hidden = false;
    document.body.classList.add('has-unsaved-back-modal');
    confirm?.focus();
};

document.addEventListener('click', (event) => {
    if (! (event.target instanceof Element)) {
        return;
    }

    const link = event.target.closest('a.btn-cancel[href]');

    if (! (link instanceof HTMLAnchorElement) || link.target && link.target !== '_self') {
        return;
    }

    if (! hasUnsavedBackChanges()) {
        return;
    }

    event.preventDefault();
    openUnsavedBackModal(link.href, link);
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && unsavedBackModal && ! unsavedBackModal.hidden) {
        closeUnsavedBackModal();
    }
});
