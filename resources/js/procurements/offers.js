import $ from 'jquery';

$(function () {
    /*
     * Offer withdrawal modal
     */
    const $withdrawalModal = $('#procurement-offer-withdrawal-modal');
    const $withdrawalForm = $('#procurement-offer-withdrawal-form');
    const $withdrawalReason = $('#procurement-offer-withdrawal-reason');
    const $withdrawalOfferId = $('#procurement-offer-withdrawal-offer-id');
    const $withdrawalTitle = $('#procurement-offer-withdrawal-title');

    if ($withdrawalModal.length && $withdrawalForm.length) {
        let $lastWithdrawalTrigger = null;

        function openWithdrawalModal($trigger) {
            const action = $trigger.attr('data-withdraw-action');
            const offerId = $trigger.attr('data-offer-id');
            const offerLabel = $trigger.attr('data-offer-label') || 'this offer';

            if (!action || !offerId) {
                return;
            }

            $lastWithdrawalTrigger = $trigger;

            $withdrawalForm.attr('action', action);
            $withdrawalOfferId.val(offerId);
            $withdrawalTitle.text('Withdraw ' + offerLabel);

            $withdrawalModal.removeClass('hidden');
            $('body').addClass('overflow-hidden');

            window.setTimeout(function () {
                $withdrawalReason.trigger('focus');
            }, 0);
        }

        function closeWithdrawalModal() {
            $withdrawalModal.addClass('hidden');
            $('body').removeClass('overflow-hidden');

            if ($lastWithdrawalTrigger && $lastWithdrawalTrigger.length) {
                $lastWithdrawalTrigger.trigger('focus');
            }
        }

        $('[data-withdraw-offer]').on('click', function () {
            openWithdrawalModal($(this));
        });

        $('[data-withdrawal-modal-close]').on('click', function () {
            closeWithdrawalModal();
        });

        $withdrawalModal.on('click', function (event) {
            if (event.target === this) {
                closeWithdrawalModal();
            }
        });

        const previousOfferId = String($withdrawalOfferId.val() || '');

        if (
            previousOfferId &&
            $withdrawalModal.attr('data-reopen') === 'true'
        ) {
            const $trigger = $(
                '[data-withdraw-offer][data-offer-id="' +
                previousOfferId +
                '"]'
            );

            if ($trigger.length) {
                openWithdrawalModal($trigger);
            } else {
                $withdrawalModal.removeClass('hidden');
                $('body').addClass('overflow-hidden');
                $withdrawalReason.trigger('focus');
            }
        }

        $(document).on('keydown', function (event) {
            if (
                event.key === 'Escape' &&
                !$withdrawalModal.hasClass('hidden')
            ) {
                closeWithdrawalModal();
            }
        });
    }

    /*
     * Offer acceptance confirmation modal
     */
    const $acceptModal = $('#procurement-offer-accept-modal');
    const $acceptForm = $('#procurement-offer-accept-form');
    const $acceptTitle = $('#procurement-offer-accept-title');
    const $acceptOfferId = $('#procurement-offer-accept-offer-id');

    if ($acceptModal.length && $acceptForm.length) {
        let $lastAcceptTrigger = null;

        function openAcceptModal($trigger) {
            const action = $trigger.attr('data-accept-action');
            const offerId = $trigger.attr('data-offer-id');
            const offerLabel = $trigger.attr('data-offer-label') || 'this offer';

            if (!action || !offerId) {
                return;
            }

            $lastAcceptTrigger = $trigger;

            $acceptForm.attr('action', action);
            $acceptOfferId.val(offerId);
            $acceptTitle.text('Accept ' + offerLabel);

            $acceptModal.removeClass('hidden');
            $('body').addClass('overflow-hidden');

            window.setTimeout(function () {
                $('[data-accept-modal-cancel]').trigger('focus');
            }, 0);
        }

        function closeAcceptModal() {
            $acceptModal.addClass('hidden');
            $('body').removeClass('overflow-hidden');

            if ($lastAcceptTrigger && $lastAcceptTrigger.length) {
                $lastAcceptTrigger.trigger('focus');
            }
        }

        $('[data-accept-offer]').on('click', function () {
            openAcceptModal($(this));
        });

        $('[data-accept-modal-close], [data-accept-modal-cancel]').on(
            'click',
            function () {
                closeAcceptModal();
            }
        );

        $acceptModal.on('click', function (event) {
            if (event.target === this) {
                closeAcceptModal();
            }
        });

        $(document).on('keydown', function (event) {
            if (
                event.key === 'Escape' &&
                !$acceptModal.hasClass('hidden')
            ) {
                closeAcceptModal();
            }
        });
    }

    /*
     * Offer rejection confirmation modal
     */
    const $rejectModal = $('#procurement-offer-reject-modal');
    const $rejectForm = $('#procurement-offer-reject-form');
    const $rejectTitle = $('#procurement-offer-reject-title');
    const $rejectOfferId = $('#procurement-offer-reject-offer-id');

    if ($rejectModal.length && $rejectForm.length) {
        let $lastRejectTrigger = null;

        function openRejectModal($trigger) {
            const action = $trigger.attr('data-reject-action');
            const offerId = $trigger.attr('data-offer-id');
            const offerLabel = $trigger.attr('data-offer-label') || 'this offer';

            if (!action || !offerId) {
                return;
            }

            $lastRejectTrigger = $trigger;

            $rejectForm.attr('action', action);
            $rejectOfferId.val(offerId);
            $rejectTitle.text('Reject ' + offerLabel);

            $rejectModal.removeClass('hidden');
            $('body').addClass('overflow-hidden');

            window.setTimeout(function () {
                $('[data-reject-modal-cancel]').trigger('focus');
            }, 0);
        }

        function closeRejectModal() {
            $rejectModal.addClass('hidden');
            $('body').removeClass('overflow-hidden');

            if ($lastRejectTrigger && $lastRejectTrigger.length) {
                $lastRejectTrigger.trigger('focus');
            }
        }

        $('[data-reject-offer]').on('click', function () {
            openRejectModal($(this));
        });

        $('[data-reject-modal-close], [data-reject-modal-cancel]').on(
            'click',
            function () {
                closeRejectModal();
            }
        );

        $rejectModal.on('click', function (event) {
            if (event.target === this) {
                closeRejectModal();
            }
        });

        $(document).on('keydown', function (event) {
            if (
                event.key === 'Escape' &&
                !$rejectModal.hasClass('hidden')
            ) {
                closeRejectModal();
            }
        });
    }
});
