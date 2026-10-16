import $ from 'jquery';

$(function () {
    const $modal = $('#withdrawal-action-modal');
    const $form = $('#withdrawal-action-form');
    const $title = $('#withdrawal-modal-title');
    const $description = $('#withdrawal-modal-description');
    const $field = $('#withdrawal-action-value');
    const $label = $('#withdrawal-field-label');
    const $help = $('#withdrawal-field-help');
    const $submit = $('#withdrawal-modal-submit');
    const $reasonField = $('#withdrawal-reason-field');
    const $paymentWarning = $('#withdrawal-payment-warning');
    const $typeInput = $('#withdrawal-modal-type');
    const $actionInput = $('#withdrawal-modal-action');

    if (!$modal.length || !$form.length) {
        return;
    }

    let lastAction = null;
    let previousFocus = null;

    const modes = {
        approve: {
            title: 'Approve withdrawal',
            label: 'Approval reason',
            description: 'Approve this request only after reviewing the payout details.',
            help: 'Required. Enter 10 to 1,000 characters. Saved in the withdrawal notes.',
            placeholder: 'Explain why this withdrawal is approved.',
            name: 'approval_reason',
            minlength: 10,
            maxlength: 1000,
            submit: 'Approve withdrawal',
            field: true,
            warning: false,
            style: 'primary',
        },
        reject: {
            title: 'Reject withdrawal',
            label: 'Rejection reason',
            description: 'The reason will be recorded against this request.',
            help: 'Required. Enter 10 to 1,000 characters so the staff member understands the decision.',
            placeholder: 'Explain why this withdrawal is rejected.',
            name: 'reject_reason',
            minlength: 10,
            maxlength: 1000,
            submit: 'Reject withdrawal',
            field: true,
            warning: false,
            style: 'danger',
        },
        paid: {
            title: 'Record completed payment',
            label: 'Payment reference',
            description: 'Record the reference for the transfer that has already been made.',
            help: 'Required. Enter the actual bank transfer or payment reference.',
            placeholder: 'Enter the bank transfer reference.',
            name: 'payment_reference',
            minlength: 3,
            maxlength: 120,
            submit: 'Record payment',
            field: true,
            warning: true,
            style: 'primary',
        },
    };

    function openModal(button) {
        const type = button.dataset.modalType;
        const mode = modes[type];

        if (!mode || !button.dataset.action) {
            return;
        }

        previousFocus = document.activeElement;

        $form.attr('action', button.dataset.action);
        $typeInput.val(type);
        $actionInput.val(button.dataset.action);
        $title.text(mode.title);
        $description.text(
            button.dataset.person
                ? `${button.dataset.person} · ${button.dataset.amount || ''}`
                : 'Correct the entry below and try again.'
        );
        $label.text(mode.label);
        $help.text(mode.help);
        $field.attr({
            name: mode.name,
            minlength: mode.minlength,
            maxlength: mode.maxlength,
            required: true,
            placeholder: mode.placeholder,
        });

        if (lastAction !== type) {
            $field.val('');
        }

        $submit.text(mode.submit);
        $submit.removeClass('oy-btn-primary oy-btn-danger')
            .addClass(mode.style === 'danger' ? 'oy-btn-danger' : 'oy-btn-primary');

        $reasonField.toggleClass('hidden', !mode.field);
        $paymentWarning.toggleClass('hidden', !mode.warning);
        $modal.removeClass('hidden');
        $('body').addClass('overflow-hidden');
        lastAction = type;
        $field.trigger('focus');
    }

    function closeModal() {
        $modal.addClass('hidden');
        $('body').removeClass('overflow-hidden');

        if (previousFocus && typeof previousFocus.focus === 'function') {
            previousFocus.focus();
        }
    }

    $(document).on('click', '[data-withdrawal-modal-open]', function () {
        $('[data-modal-error]').remove();
        openModal(this);
    });

    $(document).on('click', '[data-withdrawal-modal-close]', closeModal);

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

    if ($modal.attr('data-reopen') === 'true') {
        openModal({
            dataset: {
                modalType: $modal.attr('data-reopen-type'),
                action: $modal.attr('data-reopen-action'),
            },
        });
        $field.val($modal.attr('data-reopen-value') || '');
    }

    $form.on('submit', function () {
        if (!this.reportValidity()) {
            return false;
        }

        $submit.prop('disabled', true).text('Processing...');
    });
});
