const adminMenuButton = document.querySelector('[data-admin-menu-button]');
const adminNavigation = document.querySelector('[data-admin-navigation]');

if (adminMenuButton instanceof HTMLButtonElement && adminNavigation instanceof HTMLElement) {
    adminMenuButton.addEventListener('click', () => {
        const isExpanded = adminMenuButton.getAttribute('aria-expanded') === 'true';

        adminMenuButton.setAttribute('aria-expanded', String(! isExpanded));
        adminNavigation.toggleAttribute('hidden', isExpanded);
    });
}
//
