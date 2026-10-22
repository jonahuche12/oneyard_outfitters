import $ from 'jquery';

$(function () {
    const sel = '#measurement-submissions';
    if (!$(sel).length) return;

    let request = null;

    function swap(url, push) {
        if (request) request.abort();
        $(sel).css('opacity', '0.5');

        request = $.ajax({ url: url, method: 'GET', dataType: 'html', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .done(function (html) {
                const $new = $('<div>').append($.parseHTML(html)).find(sel).first();
                if (!$new.length) { window.location.assign(url); return; }
                $(sel).replaceWith($new);
                if (push) history.pushState({ measurementPager: true }, '', url);
            })
            .fail(function (xhr) {
                if (xhr.statusText !== 'abort') window.location.assign(url);
            })
            .always(function () { $(sel).css('opacity', ''); });
    }

    $(document).on('click', '[data-measurement-pager] a[href]', function (e) {
        if (e.ctrlKey || e.metaKey || e.shiftKey || e.which === 2) return;
        e.preventDefault();
        swap(this.href, true);
    });

    window.addEventListener('popstate', function () {
        swap(window.location.href, false);
    });
});
