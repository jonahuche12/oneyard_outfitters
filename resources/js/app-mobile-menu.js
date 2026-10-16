/*
 * Mobile navigation for layouts.app.
 * Mirrors the public layout menu; exits quietly on pages without the markup.
 */
document.addEventListener('DOMContentLoaded', function () {
    const menu = document.getElementById('app-mobile-menu');
    const sidebar = document.getElementById('app-mobile-sidebar');
    const openButton = document.getElementById('app-mobile-menu-open');
    const closeButton = document.getElementById('app-mobile-menu-close');
    const backdrop = document.getElementById('app-mobile-backdrop');
    const links = document.querySelectorAll('[data-app-mobile-menu-link]');

    if (!menu || !sidebar || !openButton || !closeButton || !backdrop) {
        return;
    }

    let hideTimer = null;

    function isOpen() {
        return menu.getAttribute('aria-hidden') === 'false';
    }

    function openMenu() {
        window.clearTimeout(hideTimer);
        menu.classList.remove('invisible', 'pointer-events-none');
        menu.setAttribute('aria-hidden', 'false');
        openButton.setAttribute('aria-expanded', 'true');

        requestAnimationFrame(function () {
            sidebar.classList.remove('-translate-x-full');
            backdrop.classList.remove('opacity-0');
        });

        document.body.classList.add('overflow-hidden');
        window.setTimeout(function () { closeButton.focus(); }, 100);
    }

    function closeMenu(returnFocus) {
        sidebar.classList.add('-translate-x-full');
        backdrop.classList.add('opacity-0');
        menu.setAttribute('aria-hidden', 'true');
        openButton.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('overflow-hidden');

        hideTimer = window.setTimeout(function () {
            menu.classList.add('invisible', 'pointer-events-none');
        }, 300);

        if (returnFocus) {
            openButton.focus();
        }
    }

    openButton.addEventListener('click', openMenu);
    closeButton.addEventListener('click', function () { closeMenu(true); });
    backdrop.addEventListener('click', function () { closeMenu(true); });
    links.forEach(function (link) {
        link.addEventListener('click', function () { closeMenu(false); });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && isOpen()) {
            closeMenu(true);
        }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth >= 1024 && isOpen()) {
            closeMenu(false);
        }
    });
});
