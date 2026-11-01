/* Interaction checks against actual HTTP-rendered forms; no native browser implied. */
const assert = require('assert'), fs = require('fs'), {JSDOM} = require('jsdom');
const input = JSON.parse(fs.readFileSync(process.argv[2], 'utf8')); let checks = 0;
function ok(value, label) { assert(value, label); checks++; }
function page(html) {
    const d = new JSDOM(html, {url:'http://fixture.invalid/band/nadin/get/forscore.php', runScripts:'outside-only'});
    const w = d.window, calls = [], form = w.document.getElementById('nadin-forscore-form');
    w.scrollTo = () => {};
    form.requestSubmit = button => calls.push({value:button.value, novalidate:button.formNoValidate});
    w.eval(fs.readFileSync(process.env.NADIN_FORSCORE_JS, 'utf8'));
    return {d, w, form, calls};
}
function change(p, field) { field.dispatchEvent(new p.w.Event('change', {bubbles:true})); }
function count(p, name) { return Number(p.form.querySelector('[data-count="' + name + '"]').textContent); }
for (const name of ['blank', 'first', 'update', 'references']) {
    const p = page(input[name]), {w, form, calls} = p;
    const review = form.querySelector('[data-review]'), download = form.querySelector('[value=download]');
    const status = form.querySelector('[data-review-status]');
    ok(review.hidden, 'manual refresh is hidden after the automatic handler is active');
    ok(!form.hasAttribute('aria-busy') && download.disabled === (name === 'blank'), 'only a complete import choice enables the initial download');
    ok(count(p, 'entries') === 6 && count(p, 'headings') === 3, 'summary retains repeated tune and heading positions');
    if (name !== 'blank') ok(count(p, name === 'first' ? 'included' : name === 'update' ? 'reused' : 'references') === 2, 'summary counts unique PDF filenames for this import intent');
    form.elements.profile.value = 'parts'; change(p, form.elements.profile);
    ok(calls.length === 1 && calls[0].value === 'review' && calls[0].novalidate, 'section change refreshes even before other required choices are made');
    ok(form.getAttribute('aria-busy') === 'true' && download.disabled && !status.hidden, 'download is unavailable during automatic refresh');
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
    ok(download.disabled && count(p, 'unresolved') === 1, 'ambiguous PDFs block download with a visible remaining-choice count');
    field.value = Array.from(field.options).find(o => o.value).value; change(p, field);
    ok(!download.disabled && count(p, 'unresolved') === 0 && count(p, 'included') === 1, 'choosing a PDF updates summary and enables download without review');
    ok(p.calls.length === 0, 'manual PDF choice does not reload the page');
    field.value = ''; change(p, field);
    ok(download.disabled && count(p, 'unresolved') === 1, 'clearing the PDF selection updates the summary immediately');
    p.d.window.close();
}
{
    const p = page(input.missing), {form} = p, download = form.querySelector('[value=download]');
    ok(download.disabled && count(p, 'missing') === 4, 'missing notes need explicit confirmation');
    form.elements.placeholders.checked = true; change(p, form.elements.placeholders);
    ok(!download.disabled && p.calls.length === 0, 'confirming placeholders enables download without review');
    p.d.window.close();
}
console.log('PASS:', checks, 'forScore workflow software-DOM checks');
