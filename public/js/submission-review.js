document.querySelectorAll('.submission-photo-preview').forEach(function (preview) {
    const photo = preview.querySelector('img');
    const link = preview.querySelector('a');
    const placeholder = preview.querySelector('.submission-photo-missing');

    function showPlaceholder() {
        link.hidden = true;
        placeholder.hidden = false;
    }

    photo.addEventListener('error', showPlaceholder);
    if (photo.complete && photo.naturalWidth === 0) {
        showPlaceholder();
    }
});
