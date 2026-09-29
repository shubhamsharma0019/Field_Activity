(() => {
    const toast = document.getElementById('settings-toast');

    function showToast(message) {
        if (!toast) return;
        toast.textContent = message;
        toast.hidden = false;
        setTimeout(function () { toast.hidden = true; }, 2500);
    }

    document.getElementById('test-notification')?.addEventListener('click', function () {
        showToast('Test notification is ready. Email delivery depends on mail configuration.');
    });

    document.getElementById('backup-database')?.addEventListener('click', function () {
        showToast('Use your hosting/database backup tool for database backups.');
    });

    document.getElementById('settings-form')?.addEventListener('submit', function (event) {
        if (!event.currentTarget.reportValidity()) {
            event.preventDefault();
        }
    });
})();
