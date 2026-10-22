import $ from 'jquery';

$(function () {
    const note = sessionStorage.getItem('oy-upload-note');
    if (note) {
        sessionStorage.removeItem('oy-upload-note');
        $('<div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">').text(note).prependTo('#measurement-form');
    }

    $('[data-upload]').each(function () {
        const $box = $(this);
        const $item = $box.closest('[data-item]');
        const $file = $box.find('[data-upload-file]');
        const $modal = $box.find('[data-upload-modal]');
        const $errors = $box.find('[data-upload-errors]');
        const token = $('#measurement-form input[name="_token"]').val() || '';

        function kind() {
            const $r = $item.find('[data-method]:checked');
            const m = $r.length ? String($r.val()) : String($item.find('input[name$="[method]"]').first().val() || '');
            return m === 'size_quantity' ? 'sizes' : 'individual';
        }

        function hasEntries() {
            if ($item.find('[data-person][data-saved="1"]').length) return true;
            return $item.find('input[name*="[sizes]"]').filter(function () {
                const v = String($(this).val() ?? '').trim();
                return v !== '' && v !== '0';
            }).length > 0;
        }

        function showErrors(list) {
            $errors.empty().prop('hidden', list.length === 0);
            list.forEach(function (msg) { $('<li>').text(msg).appendTo($errors); });
        }

        function upload() {
            const data = new FormData();
            data.append('file', $file[0].files[0]);
            data.append('kind', kind());
            data.append('_token', token);
            showErrors([]);

            $.ajax({
                url: $box.attr('data-upload-url'),
                method: 'POST',
                data: data,
                processData: false,
                contentType: false,
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }
            }).done(function (res) {
                if (res && res.ignored > 0) {
                    sessionStorage.setItem('oy-upload-note', 'Only the first ' + res.limit + ' people were used. ' + res.ignored + ' extra row(s) in your file were ignored.');
                }
                window.location.reload();
            }).fail(function (xhr) {
                const body = xhr.responseJSON || {};
                const errs = body.errors || {};
                const list = Object.values(errs).flat();
                showErrors(list.length ? list : [body.message || 'The file could not be uploaded.']);
                $file.val('');
            });
        }

        $box.find('[data-template-link]').on('click', function () {
            this.href = $box.attr('data-template-url') + '?kind=' + kind();
        });

        $box.find('[data-upload-open]').on('click', function () { $file.trigger('click'); });

        $file.on('change', function () {
            if (!this.files.length) return;
            if (hasEntries()) { $modal.prop('hidden', false); } else { upload(); }
        });

        $box.find('[data-upload-cancel]').on('click', function () {
            $modal.prop('hidden', true);
            $file.val('');
        });

        $box.find('[data-upload-confirm]').on('click', function () {
            $modal.prop('hidden', true);
            upload();
        });
    });

    $('[data-item]').each(function () {
        const $item = $(this);
        const $panels = $item.find('[data-panel]');
        const $up = $item.find('[data-panel-upload]');
        $item.on('change', '[data-method], [data-method-upload]', function () {
            const isUpload = $(this).is('[data-method-upload]');
            $up.prop('hidden', !isUpload);
            if (isUpload) { $panels.prop('hidden', true); }
        });
    });
});
