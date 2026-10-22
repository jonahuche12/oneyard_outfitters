import $ from 'jquery';

$(function () {
    const $modal = $('#correction-modal');
    if (!$modal.length) return;

    const $form = $('#correction-form');
    let $trigger = null;

    function open(action, trigger) {
        $trigger = trigger ? $(trigger) : null;
        $form.attr('action', action);
        $('#correction-submission-id').val(action.split('/measurement-submissions/')[1]?.split('/')[0] || '');
        $modal.removeClass('hidden').addClass('flex');
        syncSubmit();
        $('body').addClass('overflow-hidden');
        $('#correction-reason').trigger('focus');
    }

    function close() {
        $modal.addClass('hidden').removeClass('flex');
        $('body').removeClass('overflow-hidden');
        if ($trigger) $trigger.trigger('focus');
    }

    $(document).on('click', '[data-correction-open]', function () {
        open($(this).data('action'), this);
    });
    $(document).on('click', '[data-correction-close]', close);
    $modal.on('click', function (e) { if (e.target === this) close(); });
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && !$modal.hasClass('hidden')) close();
    });

    // Send stays disabled until at least one selectable contact is ticked.
    function syncSubmit() {
        const $live = $('#correction-form');
        const ticked = $live.find('[data-correction-contact]:not(:disabled):checked').length;
        $live.find('[data-correction-submit]').prop('disabled', ticked === 0);
    }

    $(document).on('change', '[data-correction-contact]', syncSubmit);

    const reopen = $modal.data('reopen-action');
    if (reopen) open(reopen, null);
});
