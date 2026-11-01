/* Native form submissions preserve the existing permission/preview checks. */
(function () {
    'use strict';
    // The original NADIN page stays open; only its export tab is closed.
    var back = document.querySelector('[data-forscore-return]');
    if (back) {
        var links = [back];
        var brand = document.querySelector('.nadin-popup-brand[href]');
        if (brand) { brand.href = back.href; links.push(brand); }
        if (back.getAttribute('data-forscore-return') === 'close') links.forEach(function (link) {
            link.addEventListener('click', function (event) {
                if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
                event.preventDefault();
                try { window.close(); } catch (ignore) {}
                // Direct/bookmarked tabs and browser UI openings may refuse close.
                window.setTimeout(function () { if (!window.closed) window.location.assign(back.href); }, 150);
            });
        });
    }
    var form = document.getElementById('nadin-forscore-form');
    if (!form) return;
    var review = form.querySelector('[data-review]');
    var downloads = form.querySelectorAll('[name=action][value=download]');
    function enableDownloads() { downloads.forEach(function (button) { button.disabled = false; }); }
    var status = form.querySelector('[data-review-status]');
    var workflow = form.elements.namedItem('workflow');
    var csv = form.elements.namedItem('csv');
    var panel = form.querySelector('[data-inventory-panel]');
    var placeholders = form.elements.namedItem('placeholders');
    var busy = false;
    var key = 'nadin-forscore-view:' + window.location.pathname + ':' + form.getAttribute('data-context');
    function inventoryPanel() {
        var show = workflow.value === 'update';
        panel.hidden = !show;
        csv.required = show && form.elements.namedItem('inventory_loaded').value !== '1';
        if (!csv.required) clearFieldError(csv);
    }
    function updateSummary() {
        // Cached pages from the previous workflow keep working during an update.
        if (!form.querySelector('[data-export-counts]')) { enableDownloads(); return; }
        var counts = {entries:0, headings:0, included:0, reused:0, references:0, missing:0, unresolved:0};
        var paths = Object.create(null);
        form.querySelectorAll('.nadin-forscore-row').forEach(function (row) {
            var n = Number(row.getAttribute('data-occurrences')) || 1;
            counts.entries += n;
            if (row.getAttribute('data-kind') === 'heading') { counts.headings += n; return; }
            var field = row.querySelector('[data-pdf-choice]');
            if (!field) { counts.missing += n; return; }
            if (!field.value) { counts.unresolved += n; return; }
            var option = field.tagName === 'SELECT' ? field.options[field.selectedIndex] : field;
            var path = option.getAttribute('data-path');
            if (Object.prototype.hasOwnProperty.call(paths, path)) return;
            paths[path] = true;
            counts[option.getAttribute('data-known') === '1' ? 'reused' :
                (workflow.value === 'references' ? 'references' : 'included')]++;
        });
        form.querySelectorAll('[data-count]').forEach(function (node) {
            node.textContent = String(counts[node.getAttribute('data-count')]);
        });
        form.querySelectorAll('[data-metric]').forEach(function (node) {
            node.hidden = !counts[node.getAttribute('data-metric')];
        });
        form.querySelector('[data-export-counts]').hidden = workflow.value === '';
        var next = '';
        if (!workflow.value) next = 'Choose an import option.';
        else if (workflow.value === 'update' && form.elements.namedItem('inventory_loaded').value !== '1') next = 'Upload a current forScore inventory CSV.';
        else if (counts.unresolved) next = 'Choose a PDF for each unresolved tune.';
        else if (counts.missing && (!placeholders || !placeholders.checked)) next = 'Confirm the missing-note placeholders.';
        if (busy) next = 'Updating selection. Please wait.';
        form.querySelector('[data-export-next]').textContent = next || 'Ready to download.';
        enableDownloads();
    }
    function resetBusy() {
        busy = false;
        form.removeAttribute('aria-busy');
        status.hidden = true;
        updateSummary();
    }
    function rememberView() {
        try {
            sessionStorage.setItem(key, JSON.stringify({
                top: window.scrollY || 0,
                focus: document.activeElement && document.activeElement.name || ''
            }));
        } catch (ignore) {}
    }
    function updateSelection(field) {
        if (busy) return;
        if (field === csv && (!csv.files || !csv.files.length)) return;
        inventoryPanel();
        if (field === csv) {
            form.elements.namedItem('inventory').value = 'W10=';
            form.elements.namedItem('inventory_loaded').value = '0';
            form.elements.namedItem('inventory_name').value = '';
        }
        rememberView();
        busy = true;
        form.setAttribute('aria-busy', 'true');
        status.hidden = false;
        enableDownloads();
        try {
            if (typeof form.requestSubmit === 'function') form.requestSubmit(review);
            else review.click();
        } catch (ignore) {
            resetBusy();
            review.hidden = false;
        }
    }
    function clearFieldError(field) {
        if (!field || typeof field.setCustomValidity !== 'function') return;
        field.setCustomValidity('');
        field.removeAttribute('aria-invalid');
    }
    // Native validation prevents the POST and takes the musician to the first
    // missing field. Automatic refresh deliberately uses the formnovalidate button.
    form.addEventListener('invalid', function (event) {
        var field = event.target;
        if (field.validity && field.validity.valueMissing) {
            var message = field.getAttribute('data-required-message');
            if (message) field.setCustomValidity(message);
        }
        field.setAttribute('aria-invalid', 'true');
        var first = null;
        for (var i = 0; i < form.elements.length; i++) {
            var candidate = form.elements[i];
            if (candidate.willValidate && candidate.validity && !candidate.validity.valid) { first = candidate; break; }
        }
        if (field === first) {
            try { field.scrollIntoView({block:'center', behavior:'auto'}); } catch (ignore) {}
            try { field.focus({preventScroll:true}); } catch (ignore) { field.focus(); }
        }
    }, true);
    form.addEventListener('input', function (event) { clearFieldError(event.target); });
    downloads.forEach(function (button) {
        button.addEventListener('click', function (event) {
            if (!busy) return;
            event.preventDefault();
            status.hidden = false;
            status.textContent = 'Updating selection. Please wait.';
            updateSummary();
        });
    });
    form.addEventListener('change', function (event) {
        clearFieldError(event.target);
        if (event.target.matches('[data-auto-review]')) updateSelection(event.target);
        if (event.target.matches('select[data-pdf-choice]')) {
            var option = event.target.options[event.target.selectedIndex];
            var row = event.target.closest('.nadin-forscore-row');
            var summary = row && row.querySelector('[data-pdf-status]');
            if (summary) summary.textContent = option && option.getAttribute('data-summary') || 'Choose a PDF';
        }
        updateSummary();
    });
    form.addEventListener('submit', function (event) {
        // Enter in a setup field must refresh rather than download a stale selection.
        if (busy && event.submitter && event.submitter !== review) event.preventDefault();
    });
    review.hidden = true;
    inventoryPanel();
    updateSummary();
    try {
        var raw = sessionStorage.getItem(key);
        sessionStorage.removeItem(key);
        if (raw) {
            var view = JSON.parse(raw);
            var focus = typeof view.focus === 'string' && form.elements.namedItem(view.focus);
            if (focus && typeof focus.focus === 'function') focus.focus({preventScroll:true});
            if (typeof view.top === 'number' && isFinite(view.top)) window.scrollTo(0, Math.max(0, view.top));
        }
    } catch (ignore) {}
    window.addEventListener('pageshow', resetBusy);
}());
