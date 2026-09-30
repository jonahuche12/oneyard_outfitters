import $ from 'jquery';

$(function () {
    var $modal = $('#coordinatorModal');

    if (!$modal.length) {
        return;
    }

    function openCoordinatorModal() {
        $modal.removeClass('hidden');
        $('body').addClass('overflow-hidden');
    }

    function closeCoordinatorModal() {
        $modal.addClass('hidden');
        $('body').removeClass('overflow-hidden');
    }

    $('#openCoordinatorModal').on('click', function () {
        openCoordinatorModal();
    });

    $('#closeCoordinatorModal, #cancelCoordinatorModal').on('click', function () {
        closeCoordinatorModal();
    });

    $('#coordinatorModalBackdrop').on('click', function () {
        closeCoordinatorModal();
    });

    $(document).on('keydown', function (event) {
        if (event.key === 'Escape' && !$modal.hasClass('hidden')) {
            closeCoordinatorModal();
        }
    });
});
