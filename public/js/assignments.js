(() => {
    const form = document.getElementById('assignment-form');
    const description = document.getElementById('assignment-description');

    description.addEventListener('input', function () {
        document.getElementById('assignment-description-count').textContent = `${description.value.length}/500`;
    });

    document.getElementById('assignment-form-button').addEventListener('click', function () { form.hidden = false; });
    document.getElementById('close-assignment-form').addEventListener('click', function () { form.hidden = true; });
    document.getElementById('cancel-assignment').addEventListener('click', function () { form.hidden = true; });

    document.getElementById('new-assignment-form').addEventListener('submit', function (event) {
        event.preventDefault();
        if (!event.currentTarget.reportValidity()) return;
        const toast = document.getElementById('assignment-toast');
        toast.textContent = 'Assignment created in design preview.';
        toast.hidden = false;
        setTimeout(function () { toast.hidden = true; }, 3000);
    });
})();
