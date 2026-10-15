import $ from 'jquery';

$(function () {
    function openModal($modal) {
        $modal.removeClass('hidden');
        $('body').addClass('overflow-hidden');
        $modal.find('[data-modal-focus]').first().trigger('focus');
    }

    function closeModal($modal) {
        $modal.addClass('hidden');

        if (!$('[data-pool-modal]:not(.hidden)').length) {
            $('body').removeClass('overflow-hidden');
        }
    }

    $('[data-open-pool-modal]').on('click', function () {
        openModal($('#' + $(this).attr('data-open-pool-modal')));
    });

    $('[data-reverse-entry]').on('click', function () {
        $('#reverse-form').attr('action', $(this).attr('data-reverse-url'));
        openModal($('#reverse-modal'));
    });

    $('[data-void-contribution]').on('click', function () {
        $('#void-form').attr('action', $(this).attr('data-void-url'));
        $('#void-target').val($(this).attr('data-void-id'));
        openModal($('#void-modal'));
    });

    $('[data-pool-modal]').each(function () {
        const $modal = $(this);

        $modal.find('[data-close-pool-modal]').on('click', function () {
            closeModal($modal);
        });

        $modal.on('click', function (event) {
            if (event.target === this) {
                closeModal($modal);
            }
        });

        if ($modal.attr('data-reopen') === 'true') {
            openModal($modal);
        }
    });

    $(document).on('keydown', function (event) {
        if (event.key === 'Escape') {
            $('[data-pool-modal]:not(.hidden)').each(function () {
                closeModal($(this));
            });
        }
    });
});
