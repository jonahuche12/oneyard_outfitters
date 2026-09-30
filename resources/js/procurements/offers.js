import $ from 'jquery';

$(function () {
    const $modal = $('#procurement-offer-withdrawal-modal');
    const $form = $('#procurement-offer-withdrawal-form');
    const $reason = $('#procurement-offer-withdrawal-reason');
    const $offerId = $('#procurement-offer-withdrawal-offer-id');
    const $title = $('#procurement-offer-withdrawal-title');

    if (!$modal.length || !$form.length) {
        return;
    }

    let $lastTrigger = null;

    function openModal($trigger) {
        const action = $trigger.attr('data-withdraw-action');
        const offerId = $trigger.attr('data-offer-id');
        const offerLabel = $trigger.attr('data-offer-label') || 'this offer';

        if (!action || !offerId) {
            return;
        }

        $lastTrigger = $trigger;

        $form.attr('action', action);
        $offerId.val(offerId);
        $title.text('Withdraw ' + offerLabel);

        $modal.removeClass('hidden');
        $('body').addClass('overflow-hidden');

        window.setTimeout(function () {
            $reason.trigger('focus');
        }, 0);
    }

    function closeModal() {
        $modal.addClass('hidden');
        $('body').removeClass('overflow-hidden');

        if ($lastTrigger && $lastTrigger.length) {
            $lastTrigger.trigger('focus');
        }
    }

    $('[data-withdraw-offer]').on('click', function () {
        openModal($(this));
    });

    $('[data-withdrawal-modal-close]').on('click', function () {
        closeModal();
    });

    $modal.on('click', function (event) {
        if (event.target === this) {
            closeModal();
        }
    });

    $(document).on('keydown', function (event) {
        if (event.key === 'Escape' && !$modal.hasClass('hidden')) {
            closeModal();
        }
    });

    /*
     * If Laravel redirected back because the withdrawal reason
     * failed validation, reopen the modal for the offer that
     * submitted the request.
     */
    const previousOfferId = String($offerId.val() || '');

    if (
        previousOfferId &&
        $modal.attr('data-reopen') === 'true'
    ) {
        const $trigger = $('[data-withdraw-offer][data-offer-id="' + previousOfferId + '"]');

        if ($trigger.length) {
            openModal($trigger);
        } else {
            $modal.removeClass('hidden');
            $('body').addClass('overflow-hidden');
            $reason.trigger('focus');
        }
    }
});
