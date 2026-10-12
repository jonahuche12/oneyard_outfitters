import $ from 'jquery';

/*
 * Generic in-place list: search, filters and pagination without a page reload.
 *
 * Markup contract (one list per page):
 *   [data-ajax-list-form]       the filter form (GET); every named field is a filter
 *   [data-ajax-list-results]    container for the server-rendered results partial
 *   [data-list-total-badge]     optional element that shows the total result count
 *   [data-list-clear-toggle]    optional button, visible only while a filter is active
 *   [data-list-clear]           any control that resets all filters
 *   [data-list-total="N"]       marker rendered by the results partial. A response
 *                               without it (for example the login page after the
 *                               session expired) is never swapped in.
 *
 * The server renders the full page on a normal request and only the results
 * partial for AJAX requests, so the page also works without JavaScript.
 */
$(function () {
    const $form = $('[data-ajax-list-form]').first();
    const $results = $('[data-ajax-list-results]').first();

    if (!$form.length || !$results.length) {
        return;
    }

    const $badge = $('[data-list-total-badge]').first();
    const $clearToggle = $form.find('[data-list-clear-toggle]');
    const baseUrl = $form.attr('action');

    let activeRequest = null;
    let searchTimer = null;

    function activeFields() {
        return $form.serializeArray().filter(function (field) {
            return field.name !== '_token' && String(field.value).trim() !== '';
        });
    }

    function hasFilters() {
        return activeFields().length > 0;
    }

    function currentUrl() {
        const params = new URLSearchParams();

        activeFields().forEach(function (field) {
            params.append(field.name, String(field.value).trim());
        });

        const query = params.toString();

        return query ? `${baseUrl}?${query}` : baseUrl;
    }

    function containsResults(html) {
        return $('<div>')
            .append($.parseHTML(html))
            .find('[data-list-total]')
            .length > 0;
    }

    function syncChrome() {
        const total = $results
            .find('[data-list-total]')
            .first()
            .attr('data-list-total');

        if (total !== undefined) {
            $badge.text(total);
        }

        $clearToggle.toggle(hasFilters());
    }

    function load(url, push) {
        if (activeRequest) {
            activeRequest.abort();
        }

        $results.addClass('opacity-60 pointer-events-none');

        const request = $.ajax({
            url: url,
            method: 'GET',
            dataType: 'html',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        activeRequest = request;

        request
            .done(function (html) {
                if (!containsResults(html)) {
                    window.location.href = url;

                    return;
                }

                $results.html(html);
                syncChrome();

                if (push && window.history && window.history.pushState) {
                    window.history.pushState({ ajaxList: true }, '', url);
                }
            })
            .fail(function (xhr, textStatus) {
                if (textStatus === 'abort') {
                    return;
                }

                window.location.href = url;
            })
            .always(function () {
                if (activeRequest === request) {
                    activeRequest = null;
                    $results.removeClass('opacity-60 pointer-events-none');
                }
            });
    }

    $form.on('submit', function (event) {
        event.preventDefault();
        window.clearTimeout(searchTimer);
        load(currentUrl(), true);
    });

    $form.on(
        'change',
        'select, input[type="date"], input[type="checkbox"], input[type="radio"]',
        function () {
            window.clearTimeout(searchTimer);
            load(currentUrl(), true);
        }
    );

    $form.on('input', 'input[type="search"], input[type="text"]', function () {
        window.clearTimeout(searchTimer);
        $clearToggle.toggle(hasFilters());

        searchTimer = window.setTimeout(function () {
            load(currentUrl(), true);
        }, 350);
    });

    $(document).on('click', '[data-list-clear]', function (event) {
        event.preventDefault();
        window.clearTimeout(searchTimer);

        $form
            .find('input[type="search"], input[type="text"], input[type="date"], select')
            .val('');

        load(baseUrl, true);
    });

    $results.on('click', "nav[role='navigation'] a", function (event) {
        const url = $(this).attr('href');

        if (!url || event.ctrlKey || event.metaKey || event.shiftKey) {
            return;
        }

        event.preventDefault();
        load(url, true);
    });

    window.addEventListener('popstate', function () {
        const params = new URL(window.location.href).searchParams;

        $form
            .find('input[type="search"], input[type="text"], input[type="date"], select')
            .each(function () {
                $(this).val(params.get(this.name) || '');
            });

        load(window.location.href, false);
    });
});
