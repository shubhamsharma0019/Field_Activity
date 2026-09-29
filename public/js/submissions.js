(() => {
    const cards = document.querySelectorAll('.submission-item');
    const search = document.getElementById('submission-search');
    const comment = document.getElementById('submission-comment');
    const toast = document.getElementById('submission-toast');

    cards.forEach(function (card) {
        card.addEventListener('click', function () {
            cards.forEach(function (item) { item.classList.remove('selected'); });
            card.classList.add('selected');
            document.getElementById('detail-title').textContent = card.dataset.title;
            document.getElementById('detail-worker').textContent = `${card.dataset.worker} (USR001)`;
        });
    });

    search.addEventListener('input', function () {
        const term = search.value.toLowerCase();
        cards.forEach(function (card) {
            card.hidden = !card.textContent.toLowerCase().includes(term);
        });
    });

    comment.addEventListener('input', function () {
        document.getElementById('comment-count').textContent = `${comment.value.length}/500`;
    });

    document.querySelectorAll('.detail-actions button').forEach(function (button) {
        button.addEventListener('click', function () {
            toast.textContent = `${button.textContent} action saved in design preview.`;
            toast.hidden = false;
            setTimeout(function () { toast.hidden = true; }, 2500);
        });
    });
})();
