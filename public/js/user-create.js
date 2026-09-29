(() => {
    const notes = document.getElementById('user-notes');
    const notesCount = document.getElementById('user-notes-count');
    const photoInput = document.getElementById('profile-photo');
    const photoPreview = document.getElementById('photo-preview');
    const photoPlaceholder = document.getElementById('photo-placeholder');
    const role = document.getElementById('new-user-role');
    const company = document.getElementById('new-user-company');

    notes.addEventListener('input', function () {
        notesCount.textContent = `${notes.value.length}/500`;
    });

    document.querySelectorAll('[data-password]').forEach(function (button) {
        button.addEventListener('click', function () {
            const password = document.getElementById(button.dataset.password);
            password.type = password.type === 'password' ? 'text' : 'password';
        });
    });

    role.addEventListener('change', function () {
        const isAdmin = role.value === 'admin';
        company.required = !isAdmin;
        document.getElementById('company-required').hidden = isAdmin;
        if (isAdmin) company.value = '';
    });

    photoInput.addEventListener('change', function () {
        const photo = photoInput.files[0];
        if (!photo) return;
        if (!['image/png', 'image/jpeg'].includes(photo.type) || photo.size > 2 * 1024 * 1024) {
            photoInput.value = '';
            alert('Please choose a PNG or JPEG image smaller than 2 MB.');
            return;
        }
        photoPreview.src = URL.createObjectURL(photo);
        photoPreview.hidden = false;
        photoPlaceholder.hidden = true;
    });

    document.getElementById('create-user-form').addEventListener('submit', function (event) {
        event.preventDefault();
        if (!event.currentTarget.reportValidity()) return;
        if (document.getElementById('new-user-password').value !== document.getElementById('confirm-user-password').value) {
            alert('Password and confirm password must match.');
            return;
        }
        const toast = document.getElementById('create-user-toast');
        toast.textContent = 'User saved in design preview.';
        toast.hidden = false;
        setTimeout(function () { toast.hidden = true; }, 3000);
    });
})();
