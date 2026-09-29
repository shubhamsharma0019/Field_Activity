(() => {
    const description = document.getElementById('create-type-description');
    const count = document.getElementById('create-type-description-count');
    const iconPreview = document.getElementById('selected-icon-preview');

    description.addEventListener('input', function () {
        count.textContent = `${description.value.length}/500`;
    });

    document.querySelectorAll('.create-icon').forEach(function (button) {
        button.addEventListener('click', function () {
            document.querySelectorAll('.create-icon').forEach(function (item) {
                item.classList.remove('selected');
            });
            button.classList.add('selected');
            iconPreview.textContent = button.dataset.icon;
        });
    });

    document.getElementById('create-activity-type-form').addEventListener('submit', function (event) {
        event.preventDefault();
        if (!event.currentTarget.reportValidity()) return;
        const toast = document.getElementById('create-type-toast');
        toast.textContent = 'Activity type saved in design preview.';
        toast.hidden = false;
        setTimeout(function () { toast.hidden = true; }, 3000);
    });
})();
