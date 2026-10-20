import $ from 'jquery';

/*
 * Public measurement form: method toggle and add/remove person rows.
 * Inert on pages without [data-item].
 */
$(function () {
    $('[data-item]').each(function () {
        const $item = $(this);
        const $panels = $item.find('[data-panel]');

        $item.on('change', '[data-method]', function () {
            const value = $(this).val();
            $panels.each(function () {
                $(this).prop('hidden', $(this).data('panel') !== value);
            });
        });

        const $people = $item.find('[data-people]');
        if (!$people.length) {
            return;
        }

        function retitle() {
            $people.find('[data-person]').each(function (i) {
                $(this).find('[data-person-title]').text('Person ' + (i + 1));
            });
        }

        $item.on('click', '[data-add-person]', function () {
            const next = parseInt($people.attr('data-next'), 10);
            const html = $item.find('[data-person-template]').html().split('__INDEX__').join(next);
            $people.append(html).attr('data-next', next + 1);
            retitle();
        });

        $people.on('click', '[data-remove-person]', function () {
            if ($people.find('[data-person]').length > 1) {
                $(this).closest('[data-person]').remove();
                retitle();
            }
        });

        retitle();
    });
});
