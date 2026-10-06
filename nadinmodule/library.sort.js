(function () {
    'use strict';

    var table = document.getElementById('nadin-library-table');
    var select = document.getElementById('library-sort');
    var form = document.getElementById('library-sort-form');
    var alphabet = document.getElementById('library-alphabet-links');
    if (!table || !select) return;

    var tunes = [];
    var headings = {};
    var letters = [];
    var bodies = table.tBodies;
    var i;
    for (i = 0; i < bodies.length; i++) {
        var body = bodies[i];
        var letter = body.getAttribute('data-library-letter');
        if (body.getAttribute('data-library-alpha') !== null) {
            tunes.push(body);
        } else if (letter !== null) {
            headings[letter] = body;
            letters.push(letter);
        }
    }

    function alphaRank(body) {
        return Number(body.getAttribute('data-library-alpha'));
    }

    function timestamp(body) {
        return Number(body.getAttribute('data-library-time')) || 0;
    }

    function updateSortParameter(url, mode) {
        // Keep all existing parameters, including the admin/gig context.
        var result = new URL(url, window.location.href);
        result.searchParams.set('sort', mode);
        return result.href;
    }

    // Build the letter links once. Tune cells, rows and their handlers are never rebuilt.
    var alphaTunes = tunes.slice().sort(function (a, b) {
        return alphaRank(a) - alphaRank(b);
    });
    var seen = {};
    if (alphabet) {
        for (i = 0; i < alphaTunes.length; i++) {
            var alphaLetter = alphaTunes[i].getAttribute('data-library-letter');
            if (seen[alphaLetter]) continue;
            seen[alphaLetter] = true;
            var link = document.createElement('a');
            link.href = '#' + alphaLetter;
            link.appendChild(document.createTextNode(alphaLetter));
            link.onmouseover = function () {
                window.location.href = this.getAttribute('href');
            };
            alphabet.appendChild(link);
            alphabet.appendChild(document.createElement('br'));
        }
    }

    function applySort(mode) {
        mode = mode === 'newest' ? 'newest' : 'alpha';
        var scrollX = window.pageXOffset;
        var scrollY = window.pageYOffset;
        // The mode selects CSS that was available before the first page paint.
        if (form) form.setAttribute('data-library-sort', mode);
        var ordered = tunes.slice().sort(function (a, b) {
            if (mode === 'newest') {
                var difference = timestamp(b) - timestamp(a);
                if (difference) return difference;
            }
            return alphaRank(a) - alphaRank(b);
        });

        table.setAttribute('data-library-sort', mode);
        var inserted = {};
        for (var index = 0; index < ordered.length; index++) {
            var tune = ordered[index];
            var tuneLetter = tune.getAttribute('data-library-letter');
            if (mode === 'alpha' && !inserted[tuneLetter] && headings[tuneLetter]) {
                table.appendChild(headings[tuneLetter]);
                inserted[tuneLetter] = true;
            }
            // Move the original tbody: title, Info and more stay together.
            // Inline styles, IDs, opened panels and event listeners stay on these same nodes.
            table.appendChild(tune);
        }
        if (mode === 'newest') {
            for (var headingIndex = 0; headingIndex < letters.length; headingIndex++) {
                table.appendChild(headings[letters[headingIndex]]);
            }
        }

        select.value = mode;
        if (alphabet) alphabet.style.display = mode === 'newest' ? 'none' : '';

        var exports = ['library-xls-link', 'library-print-link'];
        for (var exportIndex = 0; exportIndex < exports.length; exportIndex++) {
            var exportLink = document.getElementById(exports[exportIndex]);
            if (exportLink) exportLink.href = updateSortParameter(exportLink.href, mode);
        }

        if (window.history && window.history.replaceState) {
            try {
                // Changes only the current URL; neither the page nor its editor iframes navigate.
                window.history.replaceState(window.history.state, '', updateSortParameter(window.location.href, mode));
            } catch (error) {
                // Sorting and export links still work if history updates are unavailable.
            }
        }
        if (window.pageXOffset !== scrollX || window.pageYOffset !== scrollY) {
            window.scrollTo(scrollX, scrollY);
        }
    }

    window.nadinLibrarySort = function (mode) {
        applySort(mode);
    };

    // PHP already renders the initial order and the toolbar mode.
    // Do not move rows or correct scrolling while the page is loading.
}());
