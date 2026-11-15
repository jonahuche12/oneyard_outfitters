import $ from 'jquery';

$(function () {
    const $modals = $('#store-assign-modal, #store-move-modal, #store-end-modal');
    if (!$modals.length) return;

    let $trigger = null;

    function show($modal) {
        $modal.removeClass('hidden').addClass('flex');
        $('body').addClass('overflow-hidden');
        $modal.find('select, textarea').filter(':visible').first().trigger('focus');
    }

    function hideAll() {
        $modals.addClass('hidden').removeClass('flex');
        $('body').removeClass('overflow-hidden');
        if ($trigger) {
            $trigger.trigger('focus');
            $trigger = null;
        }
    }

    // Warn that choosing "coordinator" replaces the current coordinator.
    function syncAssign() {
        const $modal = $('#store-assign-modal');
        if (!$modal.length) return;
        const current = String($modal.attr('data-current-coordinator') || '');
        const isCoordinator = $modal.find('[name="role"]').val() === 'coordinator';
        $('#store-assign-replace-hint').toggleClass('hidden', !(isCoordinator && current !== ''));
    }

    function syncMove() {
        const $modal = $('#store-move-modal');
        if (!$modal.length) return;
        const isCoordinator = $modal.find('[name="role"]').val() === 'coordinator';
        $('#store-move-coordinator-hint').toggleClass('hidden', !isCoordinator);
    }

    // End stays disabled until the reason has the 10 characters the server requires.
    function syncEnd() {
        const $modal = $('#store-end-modal');
        if (!$modal.length) return;
        const reason = String($modal.find('[name="reason"]').val() || '').trim();
        $modal.find('[data-store-end-submit]').prop('disabled', reason.length < 10);
    }

    $(document).on('click', '[data-store-modal-open]', function () {
        const $button = $(this);
        const name = $button.attr('data-store-modal-open');
        const $modal = $('#store-' + name + '-modal');
        if (!$modal.length) return;

        $trigger = $button;

        const title = $button.attr('data-title');
        if (title) {
            $modal.find('[data-store-modal-title]').text(title);
        }

        if (name === 'assign') {
            $modal.find('[name="role"]').val($button.attr('data-role') || 'assistant');
            syncAssign();
        }

        if (name === 'move') {
            $modal.find('form').attr('action', $button.attr('data-action'));
            $modal.find('[name="_staff_assignment"]').val($button.attr('data-assignment'));
            $modal.find('[name="to_store_id"]').val($button.attr('data-store'));
            $modal.find('[name="role"]').val($button.attr('data-role'));
            $modal.find('[data-store-person]').text($button.attr('data-person'));
            syncMove();
        }

        if (name === 'end') {
            $modal.find('form').attr('action', $button.attr('data-action'));
            $modal.find('[name="_staff_assignment"]').val($button.attr('data-assignment'));
            $modal.find('[name="reason"]').val('');
            $modal.find('[data-store-person]').text($button.attr('data-person'));
            syncEnd();
        }

        show($modal);
    });

    $(document).on('click', '[data-store-modal-close]', hideAll);

    $modals.on('click', function (event) {
        if (event.target === this) hideAll();
    });

    $(document).on('keydown', function (event) {
        if (event.key === 'Escape' && $modals.filter('.flex').length) hideAll();
    });

    $(document).on('change', '#store-assign-modal [name="role"]', syncAssign);
    $(document).on('change', '#store-move-modal [name="role"]', syncMove);
    $(document).on('input', '#store-end-modal [name="reason"]', syncEnd);

    // After a server-side rejection the same modal reopens with its input kept.
    $modals.filter('[data-reopen]').each(function () {
        syncAssign();
        syncMove();
        syncEnd();
        show($(this));
    });
});
