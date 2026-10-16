import $ from 'jquery';

/*
 * Contact-selection forms (quotation send, order approve, order resend):
 * the submit button stays disabled until at least one selectable contact is
 * ticked. The server still validates the selection.
 *
 *   <form data-send-contacts-form>
 *     <input type="checkbox" data-send-contact>
 *     <p data-send-hint>...</p>
 *     <button data-send-submit>
 */
$(function () {
    $('[data-send-contacts-form]').each(function () {
        const $form = $(this);
        const $button = $form.find('[data-send-submit]');
        const $hint = $form.find('[data-send-hint]');

        function sync() {
            if ($form.data('submitting')) {
                return;
            }

            const hasSelection =
                $form.find('[data-send-contact]:checked:not(:disabled)').length > 0;

            $button.prop('disabled', !hasSelection);
            $hint.toggle(!hasSelection);
        }

        $form.on('change', '[data-send-contact]', sync);

        window.addEventListener('pageshow', function () {
            window.setTimeout(sync, 0);
        });

        sync();
    });
});
