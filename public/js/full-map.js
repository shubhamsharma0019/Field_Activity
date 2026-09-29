(() => {
    const markers = document.querySelectorAll('.location-marker');
    const workerName = document.getElementById('popup-worker');
    const details = document.getElementById('popup-details');

    function showWorker(marker) {
        workerName.textContent = marker.dataset.worker;
        details.innerHTML = `${marker.dataset.activity}<br>${marker.dataset.location}<br>Last updated: just now`;
    }

    markers.forEach(function (marker) {
        marker.addEventListener('click', function () {
            showWorker(marker);
        });
    });

    document.querySelectorAll('.map-worker-button').forEach(function (button) {
        button.addEventListener('click', function () {
            const marker = Array.from(markers).find(function (item) {
                return item.dataset.worker === button.dataset.target;
            });
            showWorker(marker);
        });
    });

    document.getElementById('refresh-map').addEventListener('click', function () {
        const toast = document.getElementById('map-toast');
        toast.textContent = 'Worker locations refreshed.';
        toast.hidden = false;
        setTimeout(function () { toast.hidden = true; }, 2000);
    });
})();
