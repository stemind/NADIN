/* Interaction checks against actual HTTP-rendered forms; no native browser implied. */
const assert = require('assert'), fs = require('fs'), {JSDOM} = require('jsdom');
const input = JSON.parse(fs.readFileSync(process.argv[2], 'utf8')); let checks = 0;
function ok(value, label) { assert(value, label); checks++; }
function page(html) {
    const d = new JSDOM(html, {url:'http://fixture.invalid/band/nadin/get/forscore.php', runScripts:'outside-only'});
    const w = d.window, calls = [], errors = [], form = w.document.getElementById('nadin-forscore-form');
    w.scrollTo = () => {};
    w.addEventListener('error', event => errors.push(event.error));
    form.requestSubmit = button => calls.push({value:button.value, novalidate:button.formNoValidate});
    w.eval(fs.readFileSync(process.env.NADIN_FORSCORE_JS, 'utf8'));
    return {d, w, form, calls, errors};
}
function change(p, field) { field.dispatchEvent(new p.w.Event('change', {bubbles:true})); }
function count(p, name) { return Number(p.form.querySelector('[data-count="' + name + '"]').textContent); }
for (const name of ['blank', 'first', 'update', 'references']) {
    const p = page(input[name]), {w, form, calls} = p;
    const review = form.querySelector('[data-review]'), download = form.querySelector('[value=download]');
    const status = form.querySelector('[data-review-status]');
    const downloads = Array.from(form.querySelectorAll('[value=download]'));
    ok(downloads.length === 2 && downloads.every(b => b.form === form && !b.disabled), 'both download buttons use the same complete form');
    ok(review.hidden, 'manual refresh is hidden after the automatic handler is active');
    ok(!form.hasAttribute('aria-busy') && !download.disabled, 'download remains active before all obligatory choices are complete');
    ok(count(p, 'entries') === 6 && count(p, 'headings') === 3, 'summary retains repeated tune and heading positions');
    if (name !== 'blank') ok(count(p, name === 'first' ? 'included' : name === 'update' ? 'reused' : 'references') === 2, 'summary counts unique PDF filenames for this import intent');
    form.elements.profile.value = 'parts'; change(p, form.elements.profile);
    ok(calls.length === 1 && calls[0].value === 'review' && calls[0].novalidate, 'section change refreshes even before other required choices are made');
    ok(form.getAttribute('aria-busy') === 'true' && !download.disabled && !status.hidden, 'download stays active while the refresh status is visible');
    for (const action of downloads) {
        const click = new w.MouseEvent('click', {bubbles:true,cancelable:true});
        ok(!action.dispatchEvent(click) && !action.disabled && calls.length === 1, 'each active download waits for the current automatic refresh without a second submission');
    }
    ok(form.querySelector('[data-export-next]').textContent === 'Updating selection. Please wait.', 'the top summary reports a pending refresh instead of a stale ready state');
    const legacySubmit = new w.Event('submit', {bubbles:true, cancelable:true});
    ok(form.dispatchEvent(legacySubmit) && new w.FormData(form).has('profile') && new w.FormData(form).has('csrf'), 'automatic refresh accepts older submit events and preserves submitted fields');
    w.dispatchEvent(new w.Event('pageshow'));
    ok(!form.hasAttribute('aria-busy') && status.hidden, 'back/forward resets the pending state');
    form.elements.workflow.value = 'update'; change(p, form.elements.workflow);
    ok(calls.length === 2 && !form.querySelector('[data-inventory-panel]').hidden, 'following import reveals CSV uploader and refreshes');
    w.dispatchEvent(new w.Event('pageshow'));
    const csv = form.elements.csv;
    Object.defineProperty(csv, 'files', {configurable:true, value:[]}); change(p, csv);
    ok(calls.length === 2, 'cancelling the file chooser leaves the current preview intact');
    form.elements.inventory.value = 'previous'; form.elements.inventory_loaded.value = '1';
    Object.defineProperty(csv, 'files', {configurable:true, value:[new w.File(['Filename\nA.pdf\n'], 'fresh.csv')]}); change(p, csv);
    ok(calls.length === 3 && form.elements.inventory.value === 'W10=' && form.elements.inventory_loaded.value === '0', 'replacement CSV refreshes without resending a stale inventory');
    p.d.window.close();
}
{
    const p = page(input.ambiguous), {form} = p, field = form.querySelector('select[data-pdf-choice]');
    const download = form.querySelector('[value=download]');
    ok(!download.disabled && field.required && count(p, 'unresolved') === 1, 'ambiguous PDF is an obligatory field while download remains active');
    field.dispatchEvent(new p.w.Event('invalid', {cancelable:true}));
    ok(p.errors.length === 0 && form.ownerDocument.activeElement === field && field.getAttribute('aria-invalid') === 'true', 'invalid PDF selection receives focus and a visible error marker');
    field.value = Array.from(field.options).find(o => o.value).value; change(p, field);
    ok(!download.disabled && count(p, 'unresolved') === 0 && count(p, 'included') === 1, 'choosing a PDF clears its error and updates the summary without review');
    ok(p.calls.length === 0, 'manual PDF choice does not reload the page');
    field.value = ''; change(p, field);
    ok(!download.disabled && field.required && count(p, 'unresolved') === 1, 'clearing the PDF selection updates the summary immediately');
    p.d.window.close();
}
{
    const p = page(input.missing), {form} = p, download = form.querySelector('[value=download]');
    ok(!download.disabled && form.elements.placeholders.required && count(p, 'missing') === 4, 'missing-note confirmation is obligatory and download stays active');
    form.elements.placeholders.checked = true; change(p, form.elements.placeholders);
    ok(!download.disabled && !form.elements.placeholders.hasAttribute('aria-invalid') && p.calls.length === 0, 'confirming placeholders applies immediately without review');
    p.d.window.close();
}
console.log('PASS:', checks, 'forScore workflow software-DOM checks');
