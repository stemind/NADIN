/* Native form submissions preserve the existing permission/preview checks. */
(function () {
    'use strict';
    var form = document.getElementById('nadin-forscore-form');
    if (!form) return;
    var review = form.querySelector('[data-review]');
    var download = form.querySelector('[name=action][value=download]');
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
    }
    function updateSummary() {
        // Cached pages from the previous workflow keep working during an update.
        if (!form.querySelector('[data-export-counts]')) { download.disabled = busy; return; }
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
        if (!workflow.value) next = 'Choose an import option above.';
        else if (workflow.value === 'update' && form.elements.namedItem('inventory_loaded').value !== '1') next = 'Upload a current forScore inventory CSV above.';
        else if (counts.unresolved) next = 'Choose a PDF for each unresolved tune above.';
        else if (counts.missing && (!placeholders || !placeholders.checked)) next = 'Confirm the missing-note placeholders above.';
        form.querySelector('[data-export-next]').textContent = next || 'Ready. Download the setlist and open it in forScore.';
        download.disabled = busy || !!next;
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
        download.disabled = true;
        try {
            if (typeof form.requestSubmit === 'function') form.requestSubmit(review);
            else review.click();
        } catch (ignore) {
            resetBusy();
            review.hidden = false;
        }
    }
    form.addEventListener('change', function (event) {
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
        if (busy && event.submitter !== review) event.preventDefault();
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
