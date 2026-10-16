import $ from 'jquery';

/*
 * Shared submit spinner.
 *
 *   <form data-submit-spinner> ... <button type="submit" data-loading-text="Creating...">
 *
 * The browser's own validation runs first; this only reacts to a valid submit.
 * The button is disabled and shows a spinner so the form cannot be sent twice.
 * It is restored when the page is shown again from the back/forward cache.
 */
const SPINNER =
    '<svg class="mr-2 inline-block h-4 w-4 align-[-2px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">' +
    '<circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".25" stroke-width="3"/>' +
    '<path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round">' +
    '<animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="0.8s" repeatCount="indefinite"/>' +
    '</path></svg>';

$(function () {
    function restore($form) {
        $form.removeData('submitting');
        $form.find('[type="submit"][data-spinner-active]').each(function () {
            const $button = $(this);

            $button
                .html($button.data('original-html'))
                .prop('disabled', Boolean($button.data('was-disabled')))
                .removeAttr('data-spinner-active aria-busy');
        });
    }

    $(document).on('submit', 'form[data-submit-spinner]', function (event) {
        const $form = $(this);

        if (event.isDefaultPrevented()) {
            return;
        }

        if ($form.data('submitting')) {
            event.preventDefault();

            return;
        }

        $form.data('submitting', true);

        $form.find('[type="submit"]').each(function () {
            const $button = $(this);
            const label = $button.attr('data-loading-text') || 'Please wait...';

            $button
                .data('original-html', $button.html())
                .data('was-disabled', $button.prop('disabled'))
                .attr({ 'data-spinner-active': '', 'aria-busy': 'true' })
                .html(SPINNER + $('<span>').text(label).html());

            window.setTimeout(function () {
                $button.prop('disabled', true);
            }, 0);
        });
    });

    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            $('form[data-submit-spinner]').each(function () {
                restore($(this));
            });
        }
    });
});
