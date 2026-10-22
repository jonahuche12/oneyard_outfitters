import $ from 'jquery';

$(function () {
    const $form = $('#measurement-form');
    if (!$form.length) return;

    const $submit = $('#measurement-submit');
    const $submitHelp = $form.find('[data-submit-help]');
    const $items = $form.find('[data-item]');

    function selectedMethod($item) {
        const $selected = $item.find('[data-method]:checked');
        if ($selected.length) return String($selected.val());

        return String($item.find('input[name$="[method]"]').first().val() || '');
    }

    function itemComplete($item) {
        if ($item.attr('data-open') !== '1') return true;
        if ($item.attr('data-saving') === '1') return false;

        const method = selectedMethod($item);

        if (method === 'individual') {
            const $people = $item.find('[data-people]');
            const $rows = $people.find('[data-person]');
            const saved = $rows.filter('[data-saved="1"]').length;
            const hasEditingRow = $rows.filter(function () {
                return $(this).attr('data-saved') !== '1'
                    || $(this).attr('data-active') === '1';
            }).length > 0;

            if (hasEditingRow || saved < 1) return false;

            if ($item.attr('data-strict') === '1') {
                const quantity = parseInt($item.attr('data-quantity'), 10);
                return saved === quantity;
            }

            return true;
        }

        if (method === 'size_quantity') {
            const $sizes = $item.find('input[name*="[sizes]"]');
            let total = 0;
            let hasQuantity = false;
            let invalid = false;

            $sizes.each(function () {
                const raw = String($(this).val() ?? '').trim();

                if (raw === '' || raw === '0') return;

                if (!/^\d+$/.test(raw) || Number(raw) < 1) {
                    invalid = true;
                    return;
                }

                total += Number(raw);
                hasQuantity = true;
            });

            if (invalid || !hasQuantity) return false;

            if ($item.attr('data-strict') === '1') {
                return total === parseInt($item.attr('data-quantity'), 10);
            }

            return true;
        }

        return false;
    }

    function refreshSubmit() {
        if (!$submit.length) return;

        const complete = $items.toArray().every(function (element) {
            return itemComplete($(element));
        });

        $submit.prop('disabled', !complete);

        if (complete) {
            $submitHelp
                .text('All required item measurements are complete. You can submit now.')
                .removeClass('text-red-700')
                .addClass('text-emerald-700');
        } else {
            $submitHelp
                .text('Complete and save the required measurements for every item to enable submission.')
                .removeClass('text-emerald-700')
                .addClass('text-slate-600');
        }
    }


    // Persist commercial-size selections before allowing final submission.
    const sizeSaveTimers = new WeakMap();

    function saveCommercialSizeDraft($item) {
        if (selectedMethod($item) !== 'size_quantity') return;

        const element = $item.get(0);
        const url = $item.attr('data-save-sizes-url');

        if (!url) {
            $item.attr('data-saving', '1');
            $submitHelp.text('Unable to save size quantities. Refresh the page and try again.')
                .removeClass('text-emerald-700')
                .addClass('text-red-700');
            refreshSubmit();
            return;
        }

        const sizes = {};

        $item.find('input[name*="[sizes]"]').each(function () {
            const match = this.name.match(/\[sizes\]\[([^\]]+)\]$/);
            if (match) {
                const value = String($(this).val() ?? '').trim();
                sizes[match[1]] = value === '' ? 0 : value;
            }
        });

        $item.attr('data-saving', '1');
        refreshSubmit();

        $.ajax({
            url: url,
            method: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $item.find('input[name="_token"]').val() || ''
            },
            data: JSON.stringify({ sizes: sizes })
        }).done(function () {
            // A successful method switch replaces the old server-side draft.
            // Remove stale person cards so they cannot falsely satisfy completion.
            $item.find('[data-person]').remove();

            $submitHelp.text('Size quantities saved.')
                .removeClass('text-red-700')
                .addClass('text-emerald-700');

        }).fail(function (xhr) {
            const body = xhr.responseJSON || {};
            const message = body.message
                || Object.values(body.errors || {}).flat()[0]
                || 'Unable to save size quantities. Check the quantities and try again.';

            $submitHelp.text(message)
                .removeClass('text-emerald-700')
                .addClass('text-red-700');
        }).always(function () {
            $item.removeAttr('data-saving');
            refreshSubmit();
        });
    }

    $form.on('change', '[data-method]', function () {
        const $item = $(this).closest('[data-item]');

        if (selectedMethod($item) !== 'size_quantity') {
            const timer = sizeSaveTimers.get($item.get(0));
            if (timer) clearTimeout(timer);
            sizeSaveTimers.delete($item.get(0));
            refreshSubmit();
            return;
        }

        const previous = sizeSaveTimers.get($item.get(0));
        if (previous) clearTimeout(previous);

        // Wait briefly so the panel's current values are included.
        sizeSaveTimers.set($item.get(0), setTimeout(function () {
            sizeSaveTimers.delete($item.get(0));
            saveCommercialSizeDraft($item);
        }, 150));
    });

    $form.on('change', 'input[name*="[sizes]"]', function () {
        const $item = $(this).closest('[data-item]');
        if (selectedMethod($item) !== 'size_quantity') return;

        const previous = sizeSaveTimers.get($item.get(0));
        if (previous) clearTimeout(previous);

        sizeSaveTimers.set($item.get(0), setTimeout(function () {
            sizeSaveTimers.delete($item.get(0));
            saveCommercialSizeDraft($item);
        }, 250));
    });

    $items.each(function () {
        const $item = $(this);
        const $panels = $item.find('[data-panel]');
        const $people = $item.find('[data-people]');
        const $add = $item.find('[data-add-person]');
        const $help = $item.find('[data-add-person-help]');

        if (!$people.length) return;

        const strict = String($people.attr('data-strict') || '0') === '1';
        const maxPeople = parseInt($people.attr('data-max'), 10) || 500;
        let nextIndex = parseInt($people.attr('data-next'), 10)
            || $people.find('[data-person]').length;

        const rows = () => $people.find('[data-person]');
        const activeRow = () => rows().filter('[data-active="1"]').first();

        function personData($row) {
            const measurements = {};

            $row.find('[data-measurement]').each(function () {
                const match = (this.name || '').match(/\[measurements\]\[([^\]]+)\]$/);
                if (match) measurements[match[1]] = $(this).val();
            });

            return {
                label: $row.find('[data-person-label]').val() || '',
                measurements,
                notes: $row.find('[data-person-notes]').val() || ''
            };
        }

        function renumberTitles() {
            rows().each(function (i) {
                const $row = $(this);
                const position = parseInt($row.attr('data-position'), 10);
                $row.find('[data-person-title]').text('Person ' + (position || i + 1));
            });
        }

        function clearPersonError($row) {
            $row.find('[data-person-error]')
                .text('')
                .prop('hidden', true);
        }

        function showPersonError($row, message) {
            $row.find('[data-person-error]')
                .text(message)
                .prop('hidden', false);
        }

        function showSaved($row) {
            // Keep a snapshot so cancelling an edit restores the last saved values.
            $row.data('saved-person-data', personData($row));

            $row.attr('data-saved', '1').attr('data-active', '0');
            $row.find('[data-person-fields]').prop('hidden', true);
            $row.find('[data-person-summary]').prop('hidden', false);
            $row.find('[data-edit-person]')
                .prop('hidden', false)
                .text('Edit');
            $row.find('[data-save-person]').prop('hidden', true);

            const label = String($row.find('[data-person-label]').val() || '').trim();
            $row.find('[data-person-summary]').text(
                (label ? label + ' · ' : '') + 'Measurements saved'
            );

            $row.removeClass('border-amber-400 bg-amber-50');
        }

        function restoreSavedValues($row) {
            const saved = $row.data('saved-person-data');
            if (!saved) return;

            $row.find('[data-person-label]').val(saved.label);

            $row.find('[data-measurement]').each(function () {
                const match = (this.name || '').match(/\[measurements\]\[([^\]]+)\]$/);
                if (match) $(this).val(saved.measurements[match[1]] ?? '');
            });

            $row.find('[data-person-notes]').val(saved.notes);
            clearPersonError($row);
        }

        function showEditor($row) {
            rows().not($row).each(function () {
                const $other = $(this);

                if ($other.attr('data-saved') === '1') {
                    if ($other.attr('data-active') === '1') {
                        restoreSavedValues($other);
                    }
                    showSaved($other);
                }
            });

            $row.attr('data-active', '1');
            $row.find('[data-person-fields]').prop('hidden', false);
            $row.find('[data-person-summary]').prop('hidden', true);
            $row.find('[data-edit-person]')
                .prop('hidden', false)
                .text('Cancel edit');
            $row.find('[data-save-person]').prop('hidden', false);
            $row.addClass('border-amber-400 bg-amber-50');

            $add.prop('hidden', true);
            $help.text('');
        }

        function updateAdd() {
            const saved = rows().filter('[data-saved="1"]').length;
            const active = activeRow().length > 0;
            const canAdd = !active && saved < maxPeople;

            $add.prop('hidden', !canAdd);
            $add.prop('disabled', !canAdd);

            if (strict && saved >= maxPeople) {
                $help.text('All required person entries have been saved.');
            } else if (!active) {
                $help.text(saved
                    ? saved + ' person measurement set(s) saved.'
                    : 'Save this person before adding another.');
            }
        }

        function renameInputs($row, index) {
            $row.find(':input[name]').each(function () {
                this.name = this.name.replace(
                    /items\[\d+\]\[people\]\[\d+\]/,
                    function (prefix) {
                        return prefix.replace(/\[\d+\]$/, '[' + index + ']');
                    }
                );
            });
        }

        function addBlank() {
            const $template = $item.find('[data-person-template]');
            const html = $template.html().split('__INDEX__').join(String(nextIndex));

            $people.append(html);

            const $row = rows().last();
            renameInputs($row, nextIndex);
            nextIndex++;
            $people.attr('data-next', nextIndex);

            showEditor($row);
        }

        $item.on('change', '[data-method]', function () {
            const method = selectedMethod($item);

            $panels.each(function () {
                $(this).prop('hidden', $(this).data('panel') !== method);
            });

            refreshSubmit();
        });

        $item.on('input change', 'input', function () {
            const $row = $(this).closest('[data-person]');

            if ($row.length) clearPersonError($row);

            refreshSubmit();
        });

        // Display saved server-side drafts as summaries; keep unsaved entries editable.
        rows().each(function () {
            const $row = $(this);

            if ($row.attr('data-saved') === '1') {
                showSaved($row);
            } else {
                showEditor($row);
            }
        });

        $item.on('click', '[data-save-person]', function () {
            const $row = $(this).closest('[data-person]');
            const position = parseInt($row.attr('data-position'), 10);
            const isSaved = $row.attr('data-saved') === '1';
            const url = isSaved
                ? $people.attr('data-update-url').replace('999999', String(position))
                : $people.attr('data-save-url');
            const method = isSaved ? 'PUT' : 'POST';

            const $button = $(this);
            clearPersonError($row);

            $item.attr('data-saving', '1');
            $button.prop('disabled', true).text('Saving...');
            refreshSubmit();

            $.ajax({
                url,
                method,
                data: JSON.stringify({
                    _token: $item.find('input[name="_token"]').val(),
                    person: personData($row)
                }),
                contentType: 'application/json',
                dataType: 'json',
                headers: { Accept: 'application/json' }
            }).done(function (result) {
                $row.attr('data-position', result.position);
                $row.attr('data-saved', '1');
                $row.find('[data-person-title]').text('Person ' + result.position);

                showSaved($row);
                renumberTitles();
                updateAdd();
            }).fail(function (xhr) {
                const body = xhr.responseJSON || {};
                const errors = body.errors;
                const messages = errors
                    ? Object.values(errors).flat()
                    : [];

                const message = messages.length
                    ? messages.join(' ')
                    : (body.message || 'Unable to save these measurements. Please try again.');

                showPersonError($row, message);
                $row.find('[data-person-fields]').prop('hidden', false);
                $row.find('[data-person-summary]').prop('hidden', true);
                $row.find('[data-save-person]').prop('hidden', false);
            }).always(function () {
                $item.removeAttr('data-saving');
                $button.prop('disabled', false).text('Save measurements');
                updateAdd();
                refreshSubmit();
            });
        });

        $item.on('click', '[data-edit-person]', function () {
            const $row = $(this).closest('[data-person]');

            if ($row.attr('data-active') === '1') {
                // Cancel editing: discard unsaved changes and return to the saved summary.
                restoreSavedValues($row);
                showSaved($row);
            } else {
                showEditor($row);
            }

            updateAdd();
            refreshSubmit();
        });

        $add.on('click', function () {
            if (activeRow().length) return;

            const saved = rows().filter('[data-saved="1"]').length;
            if (saved >= maxPeople) return;

            addBlank();
            updateAdd();
            refreshSubmit();
        });

        $item.on('click', '[data-remove-person]', function () {
            const $row = $(this).closest('[data-person]');

            if ($row.attr('data-saved') !== '1') {
                $row.remove();
                if (!rows().length) addBlank();

                updateAdd();
                refreshSubmit();
                return;
            }

            const position = parseInt($row.attr('data-position'), 10);

            $item.attr('data-saving', '1');
            refreshSubmit();

            $.ajax({
                url: $people.attr('data-remove-url').replace('999999', String(position)),
                method: 'DELETE',
                data: { _token: $item.find('input[name="_token"]').val() },
                headers: { Accept: 'application/json' }
            }).done(function () {
                window.location.reload();
            }).fail(function (xhr) {
                const body = xhr.responseJSON || {};
                $help.text(body.message || 'Unable to remove this saved entry. Please refresh and try again.')
                    .addClass('text-red-700');
            }).always(function () {
                $item.removeAttr('data-saving');
                refreshSubmit();
            });
        });

        updateAdd();
    });

    $form.on('submit', function (event) {
        const incomplete = $items.filter(function () {
            return !itemComplete($(this));
        }).length > 0;

        if (incomplete) {
            event.preventDefault();
            refreshSubmit();

            $submitHelp
                .text('Finish and save the required measurements for every item before submitting.')
                .removeClass('text-emerald-700')
                .addClass('text-red-700');
        }
    });

    refreshSubmit();
});
