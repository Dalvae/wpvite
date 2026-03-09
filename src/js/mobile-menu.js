const toggleMobileMenu = (shouldOpen = null) => {
    const page = document.querySelector('#page');
    if (!page) {
        return;
    }

    if (shouldOpen === null) {
        page.classList.toggle('mobile-menu-open');
        return;
    }

    page.classList.toggle('mobile-menu-open', shouldOpen);
};

if (!window.__starterMobileMenuBound) {
    window.__starterMobileMenuBound = true;

    document.addEventListener('click', function (e) {
        const toggle = e.target.closest('.mobile-menu-toggle');
        if (toggle) {
            e.preventDefault();
            toggleMobileMenu();
            return;
        }

        if (!e.target.closest('#mobile-menu-container')) {
            toggleMobileMenu(false);
        }
    });

    document.addEventListener('turbo:load', function () {
        toggleMobileMenu(false);
    });
}
