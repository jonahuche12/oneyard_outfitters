const initProcurementPhotos = () => {
    const modal = document.getElementById('procurement-photo-modal');

    if (!modal) {
        return;
    }

    const image = document.getElementById('procurement-photo-modal-image');
    const count = document.getElementById('procurement-photo-modal-count');
    const thumbnails = document.getElementById('procurement-photo-modal-thumbnails');

    let photos = [];
    let currentIndex = 0;
    let lastTrigger = null;

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');

        image.src = '';
        image.alt = '';
        thumbnails.innerHTML = '';

        if (lastTrigger) {
            lastTrigger.focus();
        }
    };

    const renderPhoto = (index) => {
        if (!photos.length || !photos[index]) {
            return;
        }

        currentIndex = index;

        const photo = photos[index];

        image.src = photo.src;
        image.alt = photo.alt || 'Procurement reference photo';

        count.textContent = `${index + 1} of ${photos.length}`;

        thumbnails.innerHTML = '';

        if (photos.length > 1) {
            photos.forEach((item, itemIndex) => {
                const button = document.createElement('button');

                button.type = 'button';
                button.className =
                    'h-16 w-16 shrink-0 overflow-hidden rounded-lg border-2 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-1';

                if (itemIndex === currentIndex) {
                    button.classList.add('border-slate-900');
                } else {
                    button.classList.add('border-transparent');
                }

                button.setAttribute(
                    'aria-label',
                    `View reference photo ${itemIndex + 1}`
                );

                const thumbnail = document.createElement('img');

                thumbnail.src = item.src;
                thumbnail.alt = item.alt || '';
                thumbnail.className = 'h-full w-full object-cover';

                button.appendChild(thumbnail);

                button.addEventListener('click', () => {
                    renderPhoto(itemIndex);
                });

                thumbnails.appendChild(button);
            });
        }
    };

    const openModal = (trigger) => {
        let parsedPhotos = [];

        try {
            parsedPhotos = JSON.parse(trigger.dataset.photoImages || '[]');
        } catch (error) {
            parsedPhotos = [];
        }

        if (!parsedPhotos.length) {
            return;
        }

        photos = parsedPhotos;
        currentIndex = Number.parseInt(trigger.dataset.photoIndex || '0', 10);
        lastTrigger = trigger;

        renderPhoto(currentIndex);

        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');

        requestAnimationFrame(() => {
            const closeButton = modal.querySelector('[data-procurement-photo-close]');

            if (closeButton) {
                closeButton.focus();
            }
        });
    };

    document.querySelectorAll('[data-procurement-photo-modal]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            openModal(trigger);
        });
    });

    modal.querySelectorAll('[data-procurement-photo-close]').forEach((element) => {
        element.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', (event) => {
        if (modal.classList.contains('hidden')) {
            return;
        }

        if (event.key === 'Escape') {
            closeModal();
            return;
        }

        if (event.key === 'ArrowRight' && photos.length > 1) {
            renderPhoto((currentIndex + 1) % photos.length);
        }

        if (event.key === 'ArrowLeft' && photos.length > 1) {
            renderPhoto(
                (currentIndex - 1 + photos.length) % photos.length
            );
        }
    });

    document.querySelectorAll('[data-procurement-photo-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const galleryId = toggle.getAttribute('aria-controls');
            const gallery = document.getElementById(galleryId);

            if (!gallery) {
                return;
            }

            const expanded = toggle.getAttribute('aria-expanded') === 'true';

            toggle.setAttribute('aria-expanded', String(!expanded));
            gallery.classList.toggle('hidden', expanded);

            const chevron = toggle.querySelector(
                '[data-procurement-photo-chevron]'
            );

            if (chevron) {
                chevron.classList.toggle('rotate-180', !expanded);
            }
        });
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initProcurementPhotos);
} else {
    initProcurementPhotos();
}
