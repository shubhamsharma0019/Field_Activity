(() => {
    const workers = Array.isArray(window.fullMapWorkers) ? window.fullMapWorkers : [];
    const workerById = new Map(workers.map(function (worker) { return [String(worker.id), worker]; }));
    const project = document.getElementById('map-project');
    const workerFilter = document.getElementById('map-worker');
    const status = document.getElementById('map-status');
    const list = document.getElementById('map-worker-list');
    const empty = document.getElementById('map-workers-empty');
    const mapEmpty = document.getElementById('full-map-empty');
    const toast = document.getElementById('map-toast');
    const defaultCenter = [28.5355, 77.3910];
    const workersWithGps = workers.filter(function (worker) { return worker.latitude && worker.longitude; });
    const firstWithGps = workersWithGps[0];
    const map = L.map('full-live-map').setView(
        firstWithGps ? [firstWithGps.latitude, firstWithGps.longitude] : defaultCenter,
        firstWithGps ? 13 : 11
    );
    const streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    });
    const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19,
        attribution: 'Tiles &copy; Esri',
    });
    const markers = new Map();
    const photoMarkers = new Map();
    const routeLines = new Map();

    streetLayer.addTo(map);

    function icon(worker) {
        return L.divIcon({
            className: '',
            html: '<div class="live-marker ' + worker.status + '"><span>' + worker.initials + '</span></div>',
            iconSize: [42, 42],
            iconAnchor: [21, 42],
            popupAnchor: [0, -38],
        });
    }

    function photoIcon() {
        return L.divIcon({
            className: '',
            html: '<div class="photo-marker">CAM</div>',
            iconSize: [34, 34],
            iconAnchor: [17, 17],
        });
    }

    function popup(worker) {
        return '<strong>' + worker.name + '</strong><br>' +
            worker.assignment + '<br>' +
            worker.location_text + '<br>' +
            '<small>' + worker.status_label + ' · ' + worker.last_seen + '</small>';
    }

    function renderList(visibleWorkers) {
        if (!list) {
            return;
        }

        list.innerHTML = visibleWorkers.map(function (worker) {
            return '<button class="map-worker-button ' + worker.status + '" type="button" data-worker-id="' + worker.id + '">' +
                '<span>' + worker.initials + '</span>' +
                '<div><strong>' + worker.name + '</strong><small>' + worker.status_label + ' · ' + worker.assignment + '<br>' + worker.project + '</small></div>' +
                '<i></i>' +
                '</button>';
        }).join('');

        list.querySelectorAll('.map-worker-button').forEach(function (button) {
            button.addEventListener('click', function () {
                selectWorker(button.dataset.workerId);
            });
        });
    }

    function selectWorker(workerId, pan = true) {
        const worker = workerById.get(String(workerId));
        if (!worker) {
            return;
        }

        document.querySelectorAll('.map-worker-button').forEach(function (button) {
            button.classList.toggle('active', button.dataset.workerId === String(worker.id));
        });

        document.getElementById('popup-worker').textContent = worker.name;
        document.getElementById('popup-details').innerHTML = [
            worker.assignment,
            'Project: ' + worker.project,
            'Status: ' + worker.status_label,
            'Last GPS: ' + worker.last_seen,
            'Photo: ' + (worker.photo?.time || 'No photo submitted'),
        ].join('<br>');

        const marker = markers.get(String(worker.id));
        const route = routeLines.get(String(worker.id));
        const photo = photoMarkers.get(String(worker.id));

        markers.forEach(function (item) { item.closePopup(); });
        if (marker) {
            marker.openPopup();
        }

        if (pan) {
            if (route) {
                map.fitBounds(route.getBounds().pad(0.24));
            } else if (marker) {
                map.setView(marker.getLatLng(), Math.max(map.getZoom(), 14));
            } else if (photo) {
                map.setView(photo.getLatLng(), Math.max(map.getZoom(), 15));
            }
        }
    }

    workers.forEach(function (worker) {
        if (worker.latitude && worker.longitude) {
            const marker = L.marker([worker.latitude, worker.longitude], { icon: icon(worker) })
                .addTo(map)
                .bindPopup(popup(worker));

            marker.on('click', function () { selectWorker(worker.id, false); });
            markers.set(String(worker.id), marker);
        }

        if (worker.photo && worker.photo.lat && worker.photo.lng) {
            const marker = L.marker([worker.photo.lat, worker.photo.lng], { icon: photoIcon() })
                .addTo(map)
                .bindPopup('<strong>Photo Evidence</strong><br>' + (worker.photo.assignment || worker.assignment) + '<br>' + (worker.photo.time || 'Time unavailable'));

            marker.on('click', function () { selectWorker(worker.id, false); });
            photoMarkers.set(String(worker.id), marker);
        }

        if (Array.isArray(worker.route) && worker.route.length > 1) {
            const line = L.polyline(worker.route.map(function (point) { return [point.lat, point.lng]; }), {
                color: worker.status === 'offline' ? '#ef3850' : '#1c70ed',
                weight: 4,
                opacity: 0.82,
            }).addTo(map);

            routeLines.set(String(worker.id), line);
        }
    });

    function setLayerVisibility(worker, visible) {
        [markers, photoMarkers, routeLines].forEach(function (collection) {
            const layer = collection.get(String(worker.id));
            if (!layer) {
                return;
            }

            if (visible) {
                layer.addTo(map);
            } else {
                layer.remove();
            }
        });
    }

    function render() {
        const visible = workers.filter(function (item) {
            return (project.value === 'all' || item.project === project.value)
                && (workerFilter.value === 'all' || String(item.id) === workerFilter.value)
                && (status.value === 'all' || item.status === status.value);
        });

        workers.forEach(function (item) {
            setLayerVisibility(item, visible.includes(item));
        });

        renderList(visible);

        if (empty) {
            empty.hidden = visible.length > 0;
        }

        const visibleLayers = visible.flatMap(function (item) {
            return [markers.get(String(item.id)), photoMarkers.get(String(item.id))].filter(Boolean);
        });

        if (mapEmpty) {
            mapEmpty.hidden = visibleLayers.length > 0;
        }

        if (visibleLayers.length > 1) {
            map.fitBounds(L.featureGroup(visibleLayers).getBounds().pad(0.2));
        } else if (visibleLayers.length === 1) {
            map.setView(visibleLayers[0].getLatLng(), 14);
        }

        selectWorker(visible[0]?.id, false);
    }

    [project, workerFilter, status].forEach(function (control) {
        control?.addEventListener('change', render);
    });

    document.querySelectorAll('.map-tabs button').forEach(function (button) {
        button.addEventListener('click', function () {
            document.querySelectorAll('.map-tabs button').forEach(function (item) { item.classList.remove('active'); });
            button.classList.add('active');

            if (button.dataset.layer === 'satellite') {
                map.removeLayer(streetLayer);
                satelliteLayer.addTo(map);
            } else {
                map.removeLayer(satelliteLayer);
                streetLayer.addTo(map);
            }
        });
    });

    document.querySelector('.map-tabs button[data-layer="street"]')?.classList.add('active');

    document.getElementById('refresh-map')?.addEventListener('click', function () {
        if (!toast) {
            window.location.reload();
            return;
        }

        toast.textContent = 'Worker locations refreshed.';
        toast.hidden = false;
        setTimeout(function () { toast.hidden = true; }, 2000);
        render();
    });

    setTimeout(function () {
        map.invalidateSize();
        render();
    }, 150);
})();
