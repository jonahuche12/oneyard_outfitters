import $ from 'jquery';
import './production-plan';

window.$ = $;
window.jQuery = $;

import './quotations';
import './order-coordinator';
import './procurements/photos';

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
