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
        if (!event.currentTarget.reportValidity()) {
            event.preventDefault();
        }
    });
})();
