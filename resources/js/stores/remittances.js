import $ from 'jquery';

(() => {
    'use strict';

    const csrf = () => $('meta[name="csrf-token"]').attr('content');

    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    })[char]);

    const money = value => '₦' + Number(value ?? 0).toLocaleString('en-NG', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    const rowsFrom = response => {
        const data = response?.data ?? response;
        if (Array.isArray(data)) return data;
        if (Array.isArray(data?.data)) return data.data;
        return [];
    };

    const errorMessage = xhr => {
        if (xhr.responseJSON?.message) return xhr.responseJSON.message;

        const errors = xhr.responseJSON?.errors;
        if (errors) return Object.values(errors).flat().join(' ');

        if (xhr.status === 419) return 'Your session has expired. Refresh and try again.';
        if (xhr.status === 403) return 'You are not authorised to perform this action.';
        return 'The request could not be completed. Please try again.';
    };

    const feedback = ($element, message, type = 'error') => {
        $element
            .removeClass('hidden border-red-200 bg-red-50 text-red-700 border-emerald-200 bg-emerald-50 text-emerald-800')
            .addClass(type === 'success'
                ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                : 'border-red-200 bg-red-50 text-red-700')
            .text(message);
    };

    const clearFeedback = $element => $element.addClass('hidden').text('');

    const request = options => $.ajax({
        ...options,
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf(),
            ...(options.headers || {}),
        },
    });

    const openDialog = element => {
        if (!element) return false;

        if (typeof element.showModal === 'function') {
            if (!element.open) element.showModal();
            return true;
        }

        return false;
    };

    const closeDialog = element => {
        if (element?.open && typeof element.close === 'function') {
            element.close();
        }
    };

    const methodLabel = value => ({
        cash_handover: 'Cash handover',
        bank_transfer: 'Bank transfer',
        bank_deposit: 'Bank deposit',
    }[value] || String(value || '—').replaceAll('_', ' '));

    const statusStyle = status => ({
        pending: 'border-amber-200 bg-amber-50 text-amber-800',
        confirmed: 'border-emerald-200 bg-emerald-50 text-emerald-800',
        rejected: 'border-red-200 bg-red-50 text-red-800',
    }[String(status || '').toLowerCase()] || 'border-slate-200 bg-slate-50 text-slate-700');

    // Store remittance submission and history.
    const $storePage = $('[data-store-remittances]');

    if ($storePage.length) {
        const historyUrl = $storePage.data('history-url');
        const submitUrl = $storePage.data('submit-url');

        const $body = $('#remittance-history-body');
        const $mobileHistory = $('#remittance-history-mobile');
        const $historyFeedback = $('#remittance-history-feedback');
        const $formFeedback = $('#remittance-form-feedback');
        const $form = $('#store-remittance-form');
        const $submit = $('#remittance-submit-button');

        const submitDialog = document.getElementById('remittance-submit-dialog');
        const $dialogFeedback = $('#remittance-dialog-feedback');
        const $dialogConfirm = $('#remittance-dialog-confirm');

        let pendingSubmission = null;
        let submitting = false;

        const emptyHistory = `
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-10 text-center">
                <p class="font-semibold text-slate-800">No remittances found</p>
                <p class="mt-1 text-sm text-slate-500">Your remittance submissions will appear here.</p>
            </div>
        `;

        function loadHistory() {
            $('#remittance-history-refresh').prop('disabled', true);

            request({ url: historyUrl, method: 'GET' })
                .done(response => {
                    const records = rowsFrom(response);

                    if (!records.length) {
                        $body.html(`
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                    No remittance submissions found.
                                </td>
                            </tr>
                        `);
                        $mobileHistory.html(emptyHistory);
                        clearFeedback($historyFeedback);
                        return;
                    }

                    $body.html(records.map(record => `
                        <tr>
                            <td class="whitespace-nowrap">${escapeHtml(record.business_date || '—')}</td>
                            <td class="whitespace-nowrap text-right">${money(record.amount_cash)}</td>
                            <td class="whitespace-nowrap text-right">${money(record.amount_bank)}</td>
                            <td>
                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold ${statusStyle(record.status)}">
                                    ${escapeHtml(record.status || 'Unknown')}
                                </span>
                            </td>
                            <td class="max-w-[12rem] break-words">${escapeHtml(record.reference || '—')}</td>
                            <td class="max-w-[16rem] whitespace-normal break-words text-slate-600">${escapeHtml(record.review_reason || '—')}</td>
                        </tr>
                    `).join(''));

                    $mobileHistory.html(records.map(record => `
                        <article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                            <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/70 px-4 py-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Business date</p>
                                    <p class="mt-0.5 text-sm font-semibold text-slate-900">${escapeHtml(record.business_date || '—')}</p>
                                </div>
                                <span class="inline-flex shrink-0 rounded-full border px-2.5 py-1 text-xs font-semibold ${statusStyle(record.status)}">
                                    ${escapeHtml(record.status || 'Unknown')}
                                </span>
                            </div>
                            <dl class="grid grid-cols-2 gap-3 px-4 py-3">
                                <div>
                                    <dt class="text-xs text-slate-500">Cash</dt>
                                    <dd class="mt-1 text-sm font-semibold text-slate-900">${money(record.amount_cash)}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500">Bank</dt>
                                    <dd class="mt-1 text-sm font-semibold text-slate-900">${money(record.amount_bank)}</dd>
                                </div>
                            </dl>
                            <div class="space-y-2 border-t border-slate-100 px-4 py-3 text-sm">
                                <div class="flex justify-between gap-3">
                                    <span class="shrink-0 text-slate-500">Reference</span>
                                    <span class="break-words text-right text-slate-800">${escapeHtml(record.reference || '—')}</span>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Review note</p>
                                    <p class="mt-1 break-words text-slate-700">${escapeHtml(record.review_reason || 'No review note')}</p>
                                </div>
                            </div>
                        </article>
                    `).join(''));

                    clearFeedback($historyFeedback);
                })
                .fail(xhr => feedback($historyFeedback, errorMessage(xhr)))
                .always(() => $('#remittance-history-refresh').prop('disabled', false));
        }

        function prepareSubmissionDialog(data) {
            const selected = $('#remittance-daily-record option:selected');
            const methodText = $('#remittance-method option:selected').text();

            $('#remittance-dialog-date').text(selected.text().trim() || '—');
            $('#remittance-dialog-cash').text(money(data.amount_cash));
            $('#remittance-dialog-bank').text(money(data.amount_bank));
            $('#remittance-dialog-method').text(methodText || '—');
            $('#remittance-dialog-reference').text(data.reference || '—');
            $('#remittance-dialog-note').text(data.note || 'No note provided');

            clearFeedback($dialogFeedback);
            $dialogConfirm.prop('disabled', false).text('Confirm and submit');

            if (!openDialog(submitDialog)) {
                feedback($formFeedback, 'This browser does not support the confirmation dialog. Please use a supported modern browser.');
                return false;
            }

            return true;
        }

        function submitRemittance() {
            if (!pendingSubmission || submitting) return;

            submitting = true;
            $dialogConfirm.prop('disabled', true).text('Submitting…');

            request({
                url: submitUrl,
                method: 'POST',
                data: pendingSubmission,
            })
                .done(() => {
                    closeDialog(submitDialog);
                    pendingSubmission = null;
                    feedback($formFeedback, 'Remittance submitted successfully.', 'success');
                    $form[0].reset();
                    loadHistory();
                })
                .fail(xhr => {
                    feedback($dialogFeedback, errorMessage(xhr));
                })
                .always(() => {
                    submitting = false;
                    $dialogConfirm.prop('disabled', false).text('Confirm and submit');
                });
        }

        $form.on('submit', function (event) {
            event.preventDefault();

            if (submitting || !$form[0].reportValidity()) return;

            const data = Object.fromEntries(new FormData($form[0]).entries());
            delete data._token;

            data.amount_cash = data.amount_cash || 0;
            data.amount_bank = data.amount_bank || 0;

            if (Number(data.amount_cash) + Number(data.amount_bank) <= 0) {
                feedback($formFeedback, 'Enter a cash amount, bank amount, or both.');
                return;
            }

            clearFeedback($formFeedback);
            pendingSubmission = data;
            prepareSubmissionDialog(data);
        });

        $dialogConfirm.on('click', submitRemittance);

        $('[data-close-remittance-dialog]').on('click', () => {
            if (!submitting) closeDialog(submitDialog);
        });

        $(submitDialog).on('cancel', event => {
            if (submitting) event.preventDefault();
        });

        $('#remittance-history-refresh').on('click', loadHistory);

        loadHistory();
    }

    // Finance/admin remittance review queue.
    const $reviewPage = $('[data-remittance-review]');

    if ($reviewPage.length) {
        const queueUrl = $reviewPage.data('queue-url');
        const confirmBase = $reviewPage.data('confirm-url');
        const rejectBase = $reviewPage.data('reject-url');

        const $list = $('#remittance-review-list');
        const $feedback = $('#remittance-queue-feedback');
        const $queueCount = $('#remittance-queue-count');

        const reviewDialog = document.getElementById('remittance-review-dialog');
        const $dialogFeedback = $('#remittance-review-dialog-feedback');
        const $dialogConfirm = $('#remittance-review-dialog-confirm');
        const $dialogReason = $('#remittance-review-dialog-reason');

        let pendingDecision = null;
        let decisionInProgress = false;

        function loadQueue() {
            $('#remittance-queue-refresh').prop('disabled', true);

            request({ url: queueUrl, method: 'GET' })
                .done(response => {
                    const records = rowsFrom(response);
                    $queueCount.text(`${records.length} pending`);

                    if (!records.length) {
                        $list.html(`
                            <div class="oy-card px-5 py-12 text-center xl:col-span-2">
                                <p class="font-semibold text-slate-900">All caught up</p>
                                <p class="mt-1 text-sm text-slate-500">There are no pending remittances to review.</p>
                            </div>
                        `);
                        clearFeedback($feedback);
                        return;
                    }

                    $list.html(records.map(record => {
                        const id = Number(record.id);
                        const storeName = record.store?.name || `Store #${record.store_id ?? '—'}`;

                        return `
                            <article class="oy-card min-w-0 overflow-hidden" data-remittance-id="${id}">
                                <div class="flex items-start justify-between gap-3 border-b border-slate-100 bg-slate-50/70 px-4 py-4 sm:px-5">
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Remittance #${id}</p>
                                        <h3 class="mt-1 break-words text-base font-bold text-slate-950">${escapeHtml(storeName)}</h3>
                                        <p class="mt-1 text-sm text-slate-500">Business date: ${escapeHtml(record.business_date || '—')}</p>
                                    </div>
                                    <span class="shrink-0 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                        Pending
                                    </span>
                                </div>

                                <div class="p-4 sm:p-5">
                                    <dl class="grid grid-cols-2 gap-3">
                                        <div class="rounded-xl border border-slate-200 bg-white p-3">
                                            <dt class="text-xs text-slate-500">Cash amount</dt>
                                            <dd class="mt-1 break-words text-lg font-bold text-slate-900">${money(record.amount_cash)}</dd>
                                        </div>
                                        <div class="rounded-xl border border-slate-200 bg-white p-3">
                                            <dt class="text-xs text-slate-500">Bank amount</dt>
                                            <dd class="mt-1 break-words text-lg font-bold text-slate-900">${money(record.amount_bank)}</dd>
                                        </div>
                                    </dl>

                                    <dl class="mt-4 space-y-2 text-sm">
                                        <div class="flex justify-between gap-3">
                                            <dt class="shrink-0 text-slate-500">Method</dt>
                                            <dd class="text-right font-medium text-slate-800">${escapeHtml(methodLabel(record.method))}</dd>
                                        </div>
                                        <div class="flex justify-between gap-3">
                                            <dt class="shrink-0 text-slate-500">Reference</dt>
                                            <dd class="break-words text-right text-slate-800">${escapeHtml(record.reference || '—')}</dd>
                                        </div>
                                        <div class="flex justify-between gap-3">
                                            <dt class="shrink-0 text-slate-500">Submitted</dt>
                                            <dd class="text-right text-slate-700">${escapeHtml(record.submitted_at || '—')}</dd>
                                        </div>
                                    </dl>

                                    <div class="mt-4 rounded-lg bg-slate-50 p-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Store note</p>
                                        <p class="mt-1 whitespace-pre-wrap break-words text-sm text-slate-700">${escapeHtml(record.note || 'No note provided')}</p>
                                    </div>

                                    <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                                        <button type="button"
                                                data-action="confirm"
                                                data-id="${id}"
                                                class="oy-btn oy-btn-success w-full sm:flex-1">
                                            Confirm remittance
                                        </button>
                                        <button type="button"
                                                data-action="reject"
                                                data-id="${id}"
                                                class="oy-btn oy-btn-danger w-full sm:flex-1">
                                            Reject remittance
                                        </button>
                                    </div>
                                </div>
                            </article>
                        `;
                    }).join(''));

                    clearFeedback($feedback);
                })
                .fail(xhr => feedback($feedback, errorMessage(xhr)))
                .always(() => $('#remittance-queue-refresh').prop('disabled', false));
        }

        function openDecisionDialog(action, record, $button) {
            pendingDecision = {
                action,
                id: Number(record.id),
                $button,
            };

            $('#remittance-review-dialog-store').text(record.store?.name || `Store #${record.store_id ?? '—'}`);
            $('#remittance-review-dialog-date').text(`Remittance #${record.id} · ${record.business_date || '—'}`);
            $('#remittance-review-dialog-cash').text(money(record.amount_cash));
            $('#remittance-review-dialog-bank').text(money(record.amount_bank));

            const isReject = action === 'reject';

            $('#remittance-review-dialog-title').text(isReject ? 'Reject remittance' : 'Confirm remittance');
            $('#remittance-review-dialog-subtitle').text(
                isReject
                    ? 'Review the submission and provide a reason for rejection.'
                    : 'Review the submission before confirming it.'
            );

            $('#remittance-review-dialog-confirm-copy').toggleClass('hidden', isReject);
            $('#remittance-review-dialog-reject-copy').toggleClass('hidden', !isReject);
            $('#remittance-review-reason-wrap label').text(isReject ? 'Rejection reason' : 'Review note (optional)');
            $dialogReason.val('').attr('placeholder', isReject
                ? 'Explain why this remittance is being rejected.'
                : 'Add an optional review note.');

            $('#remittance-review-reason-hint').text(
                isReject
                    ? 'A rejection reason is required and must contain 10–1000 characters.'
                    : 'This note is optional and will be recorded with the decision.'
            );

            $dialogConfirm
                .removeClass('oy-btn-primary oy-btn-danger')
                .addClass(isReject ? 'oy-btn-danger' : 'oy-btn-primary')
                .text(isReject ? 'Confirm rejection' : 'Confirm remittance')
                .prop('disabled', false);

            clearFeedback($dialogFeedback);

            if (!openDialog(reviewDialog)) {
                pendingDecision = null;
                feedback($feedback, 'This browser does not support the review dialog. Please use a supported modern browser.');
            }
        }

        function submitDecision() {
            if (!pendingDecision || decisionInProgress) return;

            const { action, id, $button } = pendingDecision;
            const reason = String($dialogReason.val() ?? '').trim();

            if (!Number.isInteger(id) || id < 1) {
                feedback($dialogFeedback, 'Invalid remittance record.');
                return;
            }

            if (action === 'reject' && (reason.length < 10 || reason.length > 1000)) {
                feedback($dialogFeedback, 'Enter a rejection reason between 10 and 1000 characters.');
                $dialogReason.trigger('focus');
                return;
            }

            decisionInProgress = true;
            $dialogConfirm.prop('disabled', true).text('Saving decision…');

            request({
                url: action === 'confirm'
                    ? `${confirmBase}/${id}/confirm`
                    : `${rejectBase}/${id}/reject`,
                method: 'POST',
                data: action === 'confirm'
                    ? { review_reason: reason || null }
                    : { review_reason: reason },
            })
                .done(() => {
                    closeDialog(reviewDialog);
                    pendingDecision = null;

                    feedback(
                        $feedback,
                        `Remittance #${id} ${action === 'confirm' ? 'confirmed' : 'rejected'} successfully.`,
                        'success'
                    );

                    loadQueue();
                })
                .fail(xhr => {
                    feedback($dialogFeedback, errorMessage(xhr));
                    $button.prop('disabled', false);
                })
                .always(() => {
                    decisionInProgress = false;
                    $dialogConfirm
                        .prop('disabled', false)
                        .text(action === 'reject' ? 'Confirm rejection' : 'Confirm remittance');
                });
        }

        $('#remittance-queue-refresh').on('click', loadQueue);

        $list.on('click', 'button[data-action]', function () {
            const $button = $(this);
            const action = String($button.data('action') || '');
            const id = Number($button.data('id'));

            if (!Number.isInteger(id) || id < 1 || !['confirm', 'reject'].includes(action)) {
                feedback($feedback, 'Invalid remittance record or review action.');
                return;
            }

            const $card = $button.closest('[data-remittance-id]');
            const record = {
                id,
                store_id: $card.find('h3').text(),
                store: { name: $card.find('h3').text() },
                business_date: $card.find('dt').filter((_, element) => $(element).text().trim() === 'Cash amount').length
                    ? $card.find('.text-sm.text-slate-500').first().text().replace('Business date: ', '')
                    : '',
                amount_cash: $card.find('dl.grid dd').eq(0).text(),
                amount_bank: $card.find('dl.grid dd').eq(1).text(),
            };

            openDecisionDialog(action, record, $button);
        });

        $dialogConfirm.on('click', submitDecision);

        $('[data-close-review-dialog]').on('click', () => {
            if (!decisionInProgress) {
                closeDialog(reviewDialog);
                pendingDecision = null;
            }
        });

        $(reviewDialog).on('cancel', event => {
            if (decisionInProgress) event.preventDefault();
            else pendingDecision = null;
        });

        loadQueue();
    }
})();
