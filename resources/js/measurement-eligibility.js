import $ from 'jquery';

$(function () {
    var $modal = $('#measurementEligibilityModal');

    if (!$modal.length) {
        return;
    }

    var $form = $('#measurementEligibilityForm');

    function openModal() {
        $modal.removeClass('hidden');
        $('body').addClass('overflow-hidden');
    }

    function closeModal() {
        $modal.addClass('hidden');
        $('body').removeClass('overflow-hidden');
    }

    $(document).on('click', '.open-measurement-eligibility', function () {
        var $button = $(this);

        $form.attr('action', $button.data('action'));
        $('#measurementEligibilityItemId').val($button.data('item-id'));
        $('#measurementEligibilityItemName').text($button.data('item-name'));
        $form.find('input[name="eligibility"]').prop('checked', false);
        $form.find('textarea[name="reason"]').val('');
        $modal.find('[data-eligibility-error]').addClass('hidden');
        openModal();
    });

    $('#closeMeasurementEligibilityModal, #cancelMeasurementEligibilityModal, #measurementEligibilityBackdrop')
        .on('click', closeModal);

    $(document).on('keydown', function (event) {
        if (event.key === 'Escape' && !$modal.hasClass('hidden')) {
            closeModal();
        }
    });

    if ($modal.data('reopen') === 1) {
        openModal();
    }
});
