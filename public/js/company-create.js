const notes = document.getElementById('company-notes');
const notesCount = document.getElementById('notes-count');
const logoInput = document.getElementById('company-logo');
const logoPreview = document.getElementById('logo-preview');
const logoPlaceholder = document.getElementById('logo-placeholder');
const uploadError = document.getElementById('upload-error');
notes.addEventListener('input', () => { notesCount.textContent = `${notes.value.length}/500`; });
logoInput.addEventListener('change', () => {
    const file = logoInput.files[0];
    uploadError.textContent = '';
    if (!file) return;
    if (!['image/png', 'image/jpeg'].includes(file.type) || file.size > 2 * 1024 * 1024) { uploadError.textContent = 'Choose a PNG or JPEG image smaller than 2 MB.'; logoInput.value = ''; return; }
    logoPreview.src = URL.createObjectURL(file); logoPreview.hidden = false; logoPlaceholder.hidden = true;
});
document.getElementById('create-company-form').addEventListener('submit', (event) => { event.preventDefault(); if (!event.currentTarget.reportValidity()) return; const toast = document.getElementById('create-company-toast'); toast.textContent = 'Company saved in design preview.'; toast.hidden = false; setTimeout(() => { toast.hidden = true; }, 3000); });
