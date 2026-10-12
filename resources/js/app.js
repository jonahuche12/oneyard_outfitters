import $ from 'jquery';
import './production-plan';

window.$ = $;
window.jQuery = $;

import './quotations';
import './ajax-list';
import './order-coordinator';
import './procurements/photos';
import './procurements/offers';

$(function () {
    /*
     * Product Specification Artifact — pre-upload preview
     *
     * The preview is generated locally from the selected file.
     * Nothing is uploaded until the form is submitted.
     */
    const $fileInput = $('#artifact-file');
    const $preview = $('#artifact-upload-preview');
    const $previewContent = $('#artifact-upload-preview-content');
    const $previewName = $('#artifact-upload-preview-name');
    const $previewSize = $('#artifact-upload-preview-size');
    const $previewRemove = $('#artifact-preview-remove');

    if ($fileInput.length) {
        $fileInput.on('change', function (event) {
            const input = event.currentTarget;
            const file = input.files && input.files.length
                ? input.files[0]
                : null;

            $previewContent.empty();
            $previewName.text('');
            $previewSize.text('');
            $preview.addClass('hidden');

            if (!file) {
                return;
            }

            const maxSize = 10 * 1024 * 1024;

            if (file.size > maxSize) {
                input.value = '';

                window.alert(
                    'The selected file is larger than the 10 MB maximum.'
                );

                return;
            }

            $previewName.text(file.name);
            $previewSize.text(formatFileSize(file.size));

            if (file.type.startsWith('image/')) {
                const reader = new FileReader();

                reader.onload = function (readerEvent) {
                    const image = document.createElement('img');

                    image.src = readerEvent.target.result;
                    image.alt = 'Selected artifact preview';
                    image.className =
                        'block h-56 w-full rounded-lg border border-slate-200 bg-white object-contain';

                    $previewContent.empty().append(image);
                    $preview.removeClass('hidden');
                };

                reader.onerror = function () {
                    $previewContent.html(`
                        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                            The selected image could not be previewed.
                        </div>
                    `);

                    $preview.removeClass('hidden');
                };

                reader.readAsDataURL(file);

                return;
            }

            if (file.type === 'application/pdf') {
                $previewContent.html(`
                    <div class="flex min-h-32 items-center justify-center rounded-lg border border-slate-200 bg-white">
                        <div class="text-center">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-lg bg-slate-100 text-sm font-bold text-slate-600">
                                PDF
                            </div>

                            <div class="mt-3 text-sm font-medium text-slate-900">
                                PDF document selected
                            </div>

                            <div class="mt-1 text-xs text-slate-500">
                                Ready to upload
                            </div>
                        </div>
                    </div>
                `);

                $preview.removeClass('hidden');

                return;
            }

            $previewContent.html(`
                <div class="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-600">
                    File selected and ready to upload.
                </div>
            `);

            $preview.removeClass('hidden');
        });
    }

    if ($previewRemove.length) {
        $previewRemove.on('click', function () {
            $fileInput.val('');
            $previewContent.empty();
            $previewName.text('');
            $previewSize.text('');
            $preview.addClass('hidden');

            $fileInput.trigger('focus');
        });
    }

    function formatFileSize(bytes) {
        if (bytes < 1024) {
            return `${bytes} B`;
        }

        if (bytes < 1024 * 1024) {
            return `${(bytes / 1024).toFixed(1)} KB`;
        }

        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }


    /*
     * Product Specification Artifact — image gallery
     */
    const $galleryModal = $('#artifact-gallery-modal');

    function openGallery(index) {
        const $items = $('.oy-artifact-gallery-item');

        if (!$galleryModal.length || !$items.length) {
            return;
        }

        $items.addClass('hidden');

        const $selected = $items.eq(index);

        if (!$selected.length) {
            return;
        }

        $selected.removeClass('hidden');
        $galleryModal.removeClass('hidden');
        $('body').addClass('overflow-hidden');
    }

    function closeGallery() {
        if (!$galleryModal.length) {
            return;
        }

        $galleryModal.addClass('hidden');
        $('body').removeClass('overflow-hidden');
    }

    $('[data-artifact-gallery-index]').on('click', function () {
        openGallery(
            Number($(this).attr('data-artifact-gallery-index'))
        );
    });

    $('[data-artifact-gallery-close]').on('click', function () {
        closeGallery();
    });

    $galleryModal.on('click', function (event) {
        if (event.target === this) {
            closeGallery();
        }
    });

    $(document).on('keydown', function (event) {
        if (event.key === 'Escape') {
            closeGallery();
        }
    });
});

/*
|--------------------------------------------------------------------------
| Oneyard Welcome Page Carousel
|--------------------------------------------------------------------------
*/

document.addEventListener('DOMContentLoaded', () => {
    const carousels = document.querySelectorAll('[data-home-carousel]');

    if (!carousels.length) {
        return;
    }

    carousels.forEach((carousel) => {
        const slides = Array.from(
            carousel.querySelectorAll('[data-carousel-slide]')
        );

        const previousButton = carousel.querySelector('[data-carousel-prev]');
        const nextButton = carousel.querySelector('[data-carousel-next]');
        const dots = Array.from(
            carousel.querySelectorAll('[data-carousel-dot]')
        );
        const status = carousel.querySelector('[data-carousel-status]');

        if (slides.length < 2) {
            return;
        }

        let currentIndex = 0;
        let autoplayTimer = null;

        const autoplayDelay = 2000;

        const updateSlide = (newIndex) => {
            currentIndex = (newIndex + slides.length) % slides.length;

            slides.forEach((slide, index) => {
                const active = index === currentIndex;

                slide.classList.toggle('opacity-100', active);
                slide.classList.toggle('opacity-0', !active);
                slide.setAttribute('aria-hidden', active ? 'false' : 'true');
            });

            dots.forEach((dot, index) => {
                const active = index === currentIndex;

                dot.setAttribute(
                    'aria-current',
                    active ? 'true' : 'false'
                );

                dot.classList.toggle('w-7', active);
                dot.classList.toggle('bg-amber-400', active);
                dot.classList.toggle('w-2.5', !active);
                dot.classList.toggle('bg-white/60', !active);
            });

            if (status) {
                status.textContent =
                    `Slide ${currentIndex + 1} of ${slides.length}`;
            }
        };

        const nextSlide = () => {
            updateSlide(currentIndex + 1);
        };

        const previousSlide = () => {
            updateSlide(currentIndex - 1);
        };

        const stopAutoplay = () => {
            if (autoplayTimer !== null) {
                window.clearInterval(autoplayTimer);
                autoplayTimer = null;
            }
        };

        const startAutoplay = () => {
            stopAutoplay();

            autoplayTimer = window.setInterval(() => {
                nextSlide();
            }, autoplayDelay);
        };

        if (previousButton) {
            previousButton.addEventListener('click', () => {
                previousSlide();
                startAutoplay();
            });
        }

        if (nextButton) {
            nextButton.addEventListener('click', () => {
                nextSlide();
                startAutoplay();
            });
        }

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                updateSlide(index);
                startAutoplay();
            });
        });

        carousel.addEventListener('mouseenter', stopAutoplay);
        carousel.addEventListener('mouseleave', startAutoplay);

        carousel.addEventListener('focusin', stopAutoplay);

        carousel.addEventListener('focusout', (event) => {
            if (!carousel.contains(event.relatedTarget)) {
                startAutoplay();
            }
        });

        carousel.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                previousSlide();
                startAutoplay();
            }

            if (event.key === 'ArrowRight') {
                event.preventDefault();
                nextSlide();
                startAutoplay();
            }

            if (event.key === 'Home') {
                event.preventDefault();
                updateSlide(0);
                startAutoplay();
            }

            if (event.key === 'End') {
                event.preventDefault();
                updateSlide(slides.length - 1);
                startAutoplay();
            }
        });

        updateSlide(0);
        startAutoplay();
    });
});

// FAQ accordion
document.addEventListener('DOMContentLoaded', () => {
    const triggers = document.querySelectorAll('[data-faq-trigger]');

    if (!triggers.length) return;

    triggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const panelId = trigger.getAttribute('aria-controls');
            const panel = document.getElementById(panelId);
            const icon = trigger.querySelector('[data-faq-icon]');

            if (!panel) return;

            const isExpanded = trigger.getAttribute('aria-expanded') === 'true';

            trigger.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
            panel.classList.toggle('hidden', isExpanded);

            if (icon) {
                icon.textContent = isExpanded ? '+' : '−';
            }
        });
    });
});

// Contact form → Lead capture → WhatsApp
document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('[data-whatsapp-form]');

    if (!forms.length) return;

    const whatsappNumber = '2349036518913';

    forms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();

            const $form = $(form);
            const formData = new FormData(form);

            const name = String(formData.get('name') || '').trim();
            const organization = String(formData.get('organization') || '').trim();
            const email = String(formData.get('email') || '').trim();
            const phone = String(formData.get('phone') || '').trim();
            const category = String(formData.get('category') || '').trim();
            const message = String(formData.get('message') || '').trim();

            if (!name || !email || !phone || !category || !message) {
                form.reportValidity();
                return;
            }

            const $submitButton = $form.find('button[type="submit"]');
            const originalButtonText = $submitButton.text();

            $submitButton
                .prop('disabled', true)
                .text('Preparing WhatsApp...');

            $.ajax({
                url: form.action,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                success: function (data, textStatus, xhr) {
                    console.log(
                        'Contact form submission successful:',
                        {
                            status: xhr.status,
                            responseJSON: data,
                        }
                    );

                    const lines = [
                        'Hello Oneyard Outfitters,',
                        '',
                        `My name is ${name}.`,
                    ];

                    if (organization) {
                        lines.push(`Organization: ${organization}`);
                    }

                    lines.push(
                        `Email: ${email}`,
                        `Phone: ${phone}`,
                        `Requirement: ${category}`,
                        '',
                        'Requirement details:',
                        message,
                        '',
                        'I would like to discuss this requirement with Oneyard Outfitters.'
                    );

                    const whatsappUrl =
                        `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(lines.join('\n'))}`;

                    window.open(
                        whatsappUrl,
                        '_blank',
                        'noopener,noreferrer'
                    );
                },
                error: function (xhr, textStatus, errorThrown) {
                    console.error(
                        'Contact form AJAX request failed:',
                        {
                            status: xhr.status,
                            statusText: xhr.statusText,
                            textStatus: textStatus,
                            errorThrown: errorThrown,
                            url: form.action,
                            responseText: xhr.responseText,
                            responseJSON: xhr.responseJSON,
                        }
                    );

                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        console.error(
                            'Contact form validation errors:',
                            xhr.responseJSON.errors
                        );

                        alert(
                            'Please check the information you entered and try again.'
                        );
                    } else if (xhr.status === 419) {
                        console.error(
                            'Laravel CSRF/token mismatch:',
                            xhr.responseText
                        );

                        alert(
                            'Your session has expired. Please refresh the page and try again.'
                        );
                    } else {
                        console.log(xhr.responseText)
                        
                    }
                },
                complete: function () {
                    $submitButton
                        .prop('disabled', false)
                        .text(originalButtonText);
                },
            });
        });
    });
});
