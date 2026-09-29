(() => {
    const form = document.getElementById('create-assignment-form');
    const notes = document.getElementById('assignment-notes');
    const notesCount = document.getElementById('assignment-notes-count');
    const toast = document.getElementById('assignment-create-toast');

    notes.addEventListener('input', function () {
        notesCount.textContent = `${notes.value.length}/500`;
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        if (!form.reportValidity()) {
            return;
        }

        toast.textContent = 'Assignment saved in design preview.';
        toast.hidden = false;

        setTimeout(function () {
            toast.hidden = true;
        }, 3000);
    });
})();
