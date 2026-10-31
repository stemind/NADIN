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
    var busy = false;
    var key = 'nadin-forscore-view:' + form.getAttribute('data-context');
    function inventoryPanel() {
        var show = workflow.value === 'update';
        panel.hidden = !show;
        csv.required = show && form.elements.namedItem('inventory_loaded').value !== '1';
    }
    function resetBusy() {
        busy = false;
        form.removeAttribute('aria-busy');
        if (download) download.disabled = false;
        status.hidden = true;
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
            // A replacement CSV need not be sent alongside the previous inventory.
            form.elements.namedItem('inventory').value = 'W10=';
            form.elements.namedItem('inventory_loaded').value = '0';
            form.elements.namedItem('inventory_name').value = '';
        }
        rememberView();
        busy = true;
        form.setAttribute('aria-busy', 'true');
        status.hidden = false;
        if (download) download.disabled = true;
        if (typeof form.requestSubmit === 'function') form.requestSubmit(review);
        else review.click();
    }
    form.addEventListener('change', function (event) {
        if (event.target.matches('[data-auto-review]')) updateSelection(event.target);
        if (event.target.matches('[data-pdf-choice]')) {
            var option = event.target.options[event.target.selectedIndex];
            var row = event.target.closest('.nadin-forscore-row');
            var summary = row && row.querySelector('[data-pdf-status]');
            if (summary) summary.textContent = option && option.getAttribute('data-summary') || 'Choose a PDF';
        }
    });
    review.hidden = true;
    inventoryPanel();
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
