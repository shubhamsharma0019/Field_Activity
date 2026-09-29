(() => {
    const workers = document.querySelectorAll('.worker-row');
    const search = document.getElementById('worker-search');

    workers.forEach(function (worker) {
        worker.addEventListener('click', function () {
            workers.forEach(function (item) { item.classList.remove('active'); });
            worker.classList.add('active');
            document.getElementById('worker-name').textContent = worker.dataset.worker;
            document.getElementById('worker-avatar').textContent = worker.dataset.worker.charAt(0);
        });
    });

    search.addEventListener('input', function () {
        const text = search.value.toLowerCase();
        workers.forEach(function (worker) {
            worker.hidden = !worker.textContent.toLowerCase().includes(text);
        });
    });

    document.querySelector('.end-activity').addEventListener('click', function () {
        const toast = document.getElementById('tracking-toast');
        toast.textContent = 'Activity ended in design preview.';
        toast.hidden = false;
        setTimeout(function () { toast.hidden = true; }, 2500);
    });
})();
