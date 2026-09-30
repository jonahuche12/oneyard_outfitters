import $ from 'jquery';

$(function () {
    const input = $('#procurement-photos');
    const preview = $('#procurement-photo-preview');

    if (!input.length || !preview.length) {
        return;
    }

    const maximumPhotos = Number(
        input.data('maximum-photos') || 3
    );

    input.on('change', function () {
        preview.empty();

        const files = Array.from(this.files || []);

        if (files.length > maximumPhotos) {
            alert(
                'You can select a maximum of ' +
                maximumPhotos +
                (maximumPhotos === 1
                    ? ' reference photo.'
                    : ' reference photos.')
            );

            this.value = '';
            return;
        }

        files.forEach(function (file) {
            if (!file.type.startsWith('image/')) {
                return;
            }

            const reader = new FileReader();

            reader.onload = function (event) {
                const wrapper = $('<div>', {
                    class: 'overflow-hidden rounded-xl border border-slate-200 bg-slate-50'
                });

                const imageContainer = $('<div>', {
                    class: 'aspect-square bg-slate-100'
                });

                const image = $('<img>', {
                    src: event.target.result,
                    alt: file.name,
                    class: 'h-full w-full object-cover'
                });

                const filename = $('<div>', {
                    class: 'truncate px-3 py-2 text-xs text-slate-600',
                    text: file.name
                });

                imageContainer.append(image);
                wrapper.append(imageContainer);
                wrapper.append(filename);

                preview.append(wrapper);
            };

            reader.readAsDataURL(file);
        });
    });
});
