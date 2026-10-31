/* File actions use the same dialog style as the gig and member editors.
 * Mount in the editor window so a short files iframe cannot clip the dialog. */
(function () {
    'use strict';
    window.nadinMediaDialog = function (button, done) {
        var rename = !!button.dataset.rename, doc = document;
        try {
            if (parent !== window && parent.document.body.classList.contains('nadin-popup')) doc = parent.document;
        } catch (e) { /* A standalone list can use its own document. */ }
        var dialog = doc.createElement('dialog');
        if (typeof dialog.showModal !== 'function') {
            var result = rename ? prompt('New file name (including extension):', button.dataset.name) : confirm('Delete ' + button.dataset.name + '?');
            if (rename ? result !== null && result.trim() !== '' : result) done(rename ? result : null);
            return;
        }
        dialog.className = 'nadin-dialog';
        dialog.setAttribute('aria-labelledby', 'media-dialog-title');
        var form = doc.createElement('form'); form.method = 'dialog';
        var title = doc.createElement('h3'); title.id = 'media-dialog-title';
        title.textContent = rename ? 'Rename file' : 'Delete file'; form.appendChild(title);
        var field;
        if (rename) {
            var label = doc.createElement('label'); label.textContent = 'File name (including extension)';
            field = doc.createElement('input'); field.type = 'text'; field.value = button.dataset.name; field.required = true;
            field.addEventListener('input', function () { field.setCustomValidity(field.value.trim() ? '' : 'Enter a file name.'); });
            label.appendChild(field); form.appendChild(label);
        } else {
            var message = doc.createElement('p'); message.textContent = 'Delete ' + button.dataset.name + '?'; form.appendChild(message);
        }
        var actions = doc.createElement('div'); actions.className = 'nadin-toolbar';
        var cancel = doc.createElement('button'); cancel.type = 'button'; cancel.textContent = 'Cancel';
        cancel.addEventListener('click', function () { dialog.close('cancel'); });
        var confirmButton = doc.createElement('button'); confirmButton.type = 'submit'; confirmButton.value = 'confirm';
        confirmButton.className = rename ? 'nadin-primary' : 'nadin-danger'; confirmButton.textContent = rename ? 'Rename' : 'Delete';
        actions.appendChild(cancel); actions.appendChild(confirmButton); form.appendChild(actions); dialog.appendChild(form);
        dialog.addEventListener('close', function () {
            var confirmed = dialog.returnValue === 'confirm', value = field ? field.value : null;
            dialog.remove(); if (button.isConnected) button.focus();
            if (confirmed) done(value);
        }, {once: true});
        doc.body.appendChild(dialog); dialog.showModal();
        if (field) { field.focus(); field.select(); } else cancel.focus();
    };
}());
