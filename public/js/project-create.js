(() => {
    const description = document.getElementById('project-description');
    const descriptionCount = document.getElementById('project-description-count');
    const imageInput = document.getElementById('project-image');
    const imagePreview = document.getElementById('project-image-preview');
    const imagePlaceholder = document.getElementById('project-image-placeholder');

    description.addEventListener('input', function () {
        descriptionCount.textContent = `${description.value.length}/500`;
    });

    imageInput.addEventListener('change', function () {
        const image = imageInput.files[0];

        if (!image) {
            return;
        }

        const allowedTypes = ['image/png', 'image/jpeg'];
        const maximumSize = 2 * 1024 * 1024;

        if (!allowedTypes.includes(image.type) || image.size > maximumSize) {
            imageInput.value = '';
            alert('Please choose a PNG or JPEG image smaller than 2 MB.');
            return;
        }

        imagePreview.src = URL.createObjectURL(image);
        imagePreview.hidden = false;
        imagePlaceholder.hidden = true;
    });

    document.getElementById('create-project-form').addEventListener('submit', function (event) {
        if (!event.currentTarget.reportValidity()) {
            event.preventDefault();
        }
    });
})();
