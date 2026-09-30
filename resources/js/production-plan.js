import $ from 'jquery';

$(function () {
    var $planModal = $('#productionPlanModal');
    var $completionModal = $('#productionCompletionModal');
    var $unmarkModal = $('#productionUnmarkModal');

    function lockBody() {
        $('body').addClass('overflow-hidden');
        $('html').addClass('overflow-hidden');
    }

    function unlockBody() {
        $('body').removeClass('overflow-hidden');
        $('html').removeClass('overflow-hidden');
    }

    function openPlanModal() {
        $planModal.removeClass('hidden');
        lockBody();
    }

    function closePlanModal() {
        $planModal.addClass('hidden');
        unlockBody();
    }

    function openUnmarkModal(url, activityName, $activityCard) {
        $('#productionUnmarkForm').attr('action', url);
        $('#productionUnmarkActivityName').text(activityName);
        $('#productionUnmarkActivityName').data(
            'activity-card',
            $activityCard
        );
        $('#productionUnmarkNotes').val('');
        $('#productionUnmarkModalTitle').text(
            'Unmark ' + activityName
        );

        $unmarkModal
            .removeClass('hidden')
            .css('pointer-events', 'auto');

        lockBody();
    }

    function closeUnmarkModal() {
        $unmarkModal
            .addClass('hidden')
            .css('pointer-events', 'none');

        $('#productionUnmarkForm').attr('action', '');
        $('#productionUnmarkNotes').val('');

        unlockBody();

        if (document.activeElement) {
            document.activeElement.blur();
        }
    }

    function openCompletionModal(url, activityName, $activityCard) {
        $('#productionCompletionForm').attr('action', url);
        $('#productionCompletionActivityName').text(activityName);
        $('#productionCompletionActivityName').data(
            'activity-card',
            $activityCard
        );
        $('#productionCompletionEvidence').val('');
        $('#productionCompletionNotes').val('');
        $('#productionCompletionModalTitle').text(
            'Complete ' + activityName
        );

        $completionModal
            .removeClass('hidden')
            .css('pointer-events', 'auto');

        lockBody();
    }

    function closeCompletionModal() {
        $completionModal
            .addClass('hidden')
            .css('pointer-events', 'none');

        $('#productionCompletionForm').attr('action', '');
        $('#productionCompletionEvidence').val('');
        $('#productionCompletionNotes').val('');

        // Completely restore the page after the modal closes.
        unlockBody();

        // Remove any stale focus from the modal.
        if (document.activeElement) {
            document.activeElement.blur();
        }
    }

    function showActivitySuccess(message) {
        var $success = $('#productionPlanAjaxSuccess');

        if (!$success.length) {
            $success = $(
                '<div id="productionPlanAjaxSuccess" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700"></div>'
            );

            $('#productionPlanActivities').before($success);
        }

        $success.text(message).removeClass('hidden');

        setTimeout(function () {
            $success.addClass('hidden');
        }, 4000);
    }

    function showActivityError(message) {
        var $error = $('#productionPlanAjaxError');

        if (!$error.length) {
            $error = $(
                '<div id="productionPlanAjaxError" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700"></div>'
            );

            $('#productionPlanActivities').before($error);
        }

        $error.text(message).removeClass('hidden');

        setTimeout(function () {
            $error.addClass('hidden');
        }, 6000);
    }

    function updateProgress() {
        var $activities = $('#productionPlanActivities [data-production-activity]');
        var total = $activities.length;
        var started = $activities.filter('[data-status="started"], [data-status="completed"]').length;
        var completed = $activities.filter('[data-status="completed"]').length;

        $('#productionStartedCount').text(started);
        $('#productionTotalCount').text(total);
        $('#productionCompletedCount').text(completed);

        var percentage = total > 0
            ? Math.round((started / total) * 100)
            : 0;

        $('#productionPlanProgressBar')
            .css('width', percentage + '%')
            .attr('aria-valuenow', percentage);

        $('#productionPlanProgressPercent').text(percentage + '%');
    }

    function setLoading($button, loadingText) {
        $button.prop('disabled', true);
        $button.data('original-text', $button.text());
        $button.text(loadingText);
        $button.addClass('opacity-60 cursor-not-allowed');
    }

    function restoreButton($button) {
        $button.prop('disabled', false);
        $button.text($button.data('original-text'));
        $button.removeClass('opacity-60 cursor-not-allowed');
    }

    function renderStartedActivity($card) {
        $card.attr('data-status', 'started');

        $card.find('.production-activity-completed-at').remove();

        var completionUrl = $card.data('completion-url');
        var activityName = $card.data('activity-name');

        $card.find('.production-activity-status')
            .text('In progress')
            .removeClass(
                'bg-slate-100 text-slate-600 bg-emerald-50 text-emerald-700'
            )
            .addClass('bg-amber-50 text-amber-700');

        $card.find('.production-activity-action').html(
            '<button type="button" ' +
                'class="production-activity-completion-toggle flex items-center gap-2" ' +
                'data-completion-url="' + completionUrl + '" ' +
                'data-activity-name="' + $('<div>').text(activityName).html() + '">' +

                '<span class="text-sm font-medium text-amber-700">' +
                    'In progress' +
                '</span>' +

                '<span ' +
                    'class="relative inline-flex h-6 w-11 items-center rounded-full bg-amber-500 transition" ' +
                    'aria-label="Activity is in progress">' +

                    '<span ' +
                        'class="inline-block h-4 w-4 translate-x-6 rounded-full bg-white shadow transition">' +
                    '</span>' +

                '</span>' +

            '</button>'
        );

        $card.find('.production-activity-number')
            .removeClass('bg-slate-100 text-slate-600')
            .addClass('bg-amber-100 text-amber-700');
    }

    function renderUnmarkedActivity($card) {
        $card.attr('data-status', 'started');

        var completionUrl = $card.data('completion-url');
        var activityName = $card.data('activity-name');

        $card.find('.production-activity-status')
            .text('In progress')
            .removeClass(
                'bg-slate-100 text-slate-600 bg-emerald-50 text-emerald-700'
            )
            .addClass('bg-amber-50 text-amber-700');

        $card.find('.production-activity-action').html(
            '<button type="button" ' +
                'class="production-activity-completion-toggle flex items-center gap-2" ' +
                'data-completion-url="' + completionUrl + '" ' +
                'data-activity-name="' +
                    $('<div>').text(activityName).html() +
                '">' +

                '<span class="text-sm font-medium text-amber-700">' +
                    'In progress' +
                '</span>' +

                '<span ' +
                    'class="relative inline-flex h-6 w-11 items-center rounded-full bg-amber-500 transition" ' +
                    'aria-label="Activity is in progress">' +

                    '<span ' +
                        'class="inline-block h-4 w-4 translate-x-6 rounded-full bg-white shadow">' +
                    '</span>' +

                '</span>' +

            '</button>'
        );

        $card.find('.production-activity-number')
            .removeClass(
                'bg-slate-100 text-slate-600 bg-emerald-100 text-emerald-700'
            )
            .addClass('bg-amber-100 text-amber-700');

        $card.find('.production-activity-completed-at').text('');
    }

    function renderCompletedActivity($card, completedAt) {
        $card.attr('data-status', 'completed');

        $card.find('.production-activity-status')
            .text('Completed')
            .removeClass('bg-slate-100 text-slate-600 bg-amber-50 text-amber-700')
            .addClass('bg-emerald-50 text-emerald-700');

        $card.find('.production-activity-action').html(
            '<div class="flex items-center gap-2">' +
                '<span class="text-sm font-medium text-emerald-700">Completed</span>' +
                '<span class="relative inline-flex h-6 w-11 items-center rounded-full bg-emerald-600" aria-label="Activity completed">' +
                    '<span class="inline-block h-4 w-4 translate-x-6 rounded-full bg-white shadow"></span>' +
                '</span>' +
            '</div>'
        );

        $card.find('.production-activity-number')
            .removeClass('bg-slate-100 text-slate-600 bg-amber-100 text-amber-700')
            .addClass('bg-emerald-100 text-emerald-700');

        if (completedAt) {
            var date = new Date(completedAt);

            if (!isNaN(date.getTime())) {
                $card.find('.production-activity-completed-at').text(
                    'Completed ' + date.toLocaleString()
                );
            }
        }
    }

    $('#openProductionPlanModal').on('click', function () {
        openPlanModal();
    });

    $('#closeProductionPlanModal, #cancelProductionPlanModal').on(
        'click',
        function () {
            closePlanModal();
        }
    );

    $('#productionPlanModalBackdrop').on('click', function () {
        closePlanModal();
    });

    $('#toggleProductionPlan').on('click', function () {
        var $button = $(this);
        var $activities = $('#productionPlanActivities');
        var $chevron = $('#productionPlanChevron');
        var expanded = $button.attr('aria-expanded') === 'true';

        $button.attr('aria-expanded', expanded ? 'false' : 'true');
        $activities.toggleClass('hidden', expanded);
        $chevron.toggleClass('rotate-180', !expanded);
    });

    $(document).on(
        'submit',
        '.production-activity-start-form',
        function (event) {
            event.preventDefault();

            var $form = $(this);
            var $button = $form.find('button[type="submit"]');
            var $card = $form.closest('[data-production-activity]');
            var url = $form.attr('action');

            setLoading($button, 'Starting...');

            $.ajax({
                url: url,
                method: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                headers: {
                    Accept: 'application/json'
                }
            })
                .done(function (response) {
                    renderStartedActivity($card);
                    updateProgress();
                })
                .fail(function (xhr) {
                    restoreButton($button);

                    console.error(
                        'Start activity failed:',
                        xhr.status,
                        xhr.responseJSON,
                        xhr.responseText
                    );

                    var message = 'The activity could not be started. Please try again.';

                    if (xhr.responseJSON?.message) {
                        message = xhr.responseJSON.message;
                    } else if (xhr.responseJSON?.errors) {
                        message = Object.values(xhr.responseJSON.errors)
                            .flat()
                            .join(' ');
                    }

                    showActivityError(message);
                });
        }
    );

    $('#productionUnmarkForm').on('submit', function (event) {
        event.preventDefault();

        var $form = $(this);
        var $submit = $form.find('button[type="submit"]');
        var $card = $('#productionUnmarkActivityName').data(
            'activity-card'
        );

        setLoading($submit, 'Unmarking...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            headers: {
                Accept: 'application/json'
            }
        })
            .done(function (response) {
                if (!response || !response.activity) {
                    restoreButton($submit);

                    showActivityError(
                        'The activity was unmarked, but the server returned an invalid response.'
                    );

                    return;
                }

                renderUnmarkedActivity($card);
                updateProgress();
                closeUnmarkModal();

                showActivitySuccess(
                    response.message ||
                    'Production activity marked as in progress.'
                );
            })
            .fail(function (xhr) {
                restoreButton($submit);

                var message =
                    xhr.responseJSON?.message ||
                    'The activity could not be unmarked. Please try again.';

                showActivityError(message);
            });
    });

    $(document).on(
        'click',
        '.production-activity-unmark-toggle',
        function () {
            var $button = $(this);
            var $card = $button.closest('[data-production-activity]');

            openUnmarkModal(
                $button.data('unmark-url'),
                $button.data('activity-name'),
                $card
            );
        }
    );

    $(document).on(
        'click',
        '.production-activity-completion-toggle',
        function () {
            var $button = $(this);
            var $card = $button.closest('[data-production-activity]');

            openCompletionModal(
                $button.data('completion-url'),
                $button.data('activity-name'),
                $card
            );
        }
    );

    $('#productionCompletionForm').on('submit', function (event) {
        event.preventDefault();

        var $form = $(this);
        var $submit = $form.find('button[type="submit"]');
        var $card = $('#productionCompletionActivityName').data(
            'activity-card'
        );

        var formData = new FormData(this);

        setLoading($submit, 'Completing...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            headers: {
                Accept: 'application/json'
            }
        })
            .done(function (response) {
                if (!response || !response.activity) {
                    restoreButton($submit);

                    showActivityError(
                        'The activity was completed, but the server returned an invalid response.'
                    );

                    return;
                }

                renderCompletedActivity(
                    $card,
                    response.activity.completed_at || response.completed_at
                );

                updateProgress();

                closeCompletionModal();

                showActivitySuccess(
                    response.message ||
                    'Production activity completed successfully.'
                );

                // Give the user time to see the success message,
                // then refresh the page with the authoritative server state.
                setTimeout(function () {
                    window.location.reload();
                }, 3000);
            })
            .fail(function (xhr) {
                restoreButton($submit);

                var message =
                    xhr.responseJSON?.message ||
                    'The activity could not be completed. Please check the evidence and try again.';

                showActivityError(message);
            });
    });

    $('#closeProductionCompletionModal, #cancelProductionCompletionModal').on(
        'click',
        function () {
            closeCompletionModal();
        }
    );

    $('#productionCompletionModalBackdrop').on('click', function () {
        closeCompletionModal();
    });

    $('#closeProductionUnmarkModal, #cancelProductionUnmarkModal').on(
        'click',
        function () {
            closeUnmarkModal();
        }
    );

    $('#productionUnmarkModalBackdrop').on('click', function () {
        closeUnmarkModal();
    });

    $(document).on('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        if (
            $completionModal.length &&
            !$completionModal.hasClass('hidden')
        ) {
            closeCompletionModal();
            return;
        }

        if (
            $unmarkModal.length &&
            !$unmarkModal.hasClass('hidden')
        ) {
            closeUnmarkModal();
            return;
        }

        if (
            $planModal.length &&
            !$planModal.hasClass('hidden')
        ) {
            closePlanModal();
        }
    });

    updateProgress();
});
