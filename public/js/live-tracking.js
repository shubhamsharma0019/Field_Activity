(() => {
    const workers = Array.isArray(window.liveWorkers) ? window.liveWorkers : [];
    const rows = Array.from(document.querySelectorAll('.worker-row'));
    const search = document.getElementById('worker-search');
    const status = document.getElementById('worker-status');
    const count = document.getElementById('worker-count');
    const empty = document.getElementById('workers-empty');
    const workerById = new Map(workers.map(function (worker) { return [String(worker.id), worker]; }));
    const defaultCenter = [28.5355, 77.3910];
    const workersWithGps = workers.filter(function (worker) { return worker.latitude && worker.longitude; });
    const firstWithGps = workersWithGps[0];
    const map = L.map('live-map', { zoomControl: true }).setView(
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
    let routeLine = null;
    let photoMarker = null;

    streetLayer.addTo(map);

    function markerIcon(worker) {
        return L.divIcon({
            className: '',
            html: '<div class="live-marker ' + worker.status + '"><span>' + worker.initials + '</span></div>',
            iconSize: [38, 38],
            iconAnchor: [19, 38],
            popupAnchor: [0, -36],
        });
    }

    function popup(worker) {
        return '<strong>' + worker.name + '</strong><br>' +
            worker.assignment + '<br>' +
            worker.location_text + '<br>' +
            '<small>' + worker.status_label + ' · ' + worker.last_seen + '</small>';
    }

    function setText(id, value) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value || 'N/A';
        }
    }

    function selectWorker(workerId, panMap = true) {
        const worker = workerById.get(String(workerId));
        if (!worker) {
            return;
        }

        rows.forEach(function (row) {
            row.classList.toggle('active', row.dataset.workerId === String(worker.id));
        });

        setText('detail-initials', worker.initials);
        setText('detail-name', worker.name);
        setText('detail-meta', worker.code + '\n' + worker.mobile + '\n' + worker.company);
        setText('detail-assignment', worker.assignment);
        setText('detail-activity-meta', [
            'Project: ' + worker.project,
            'Type: ' + worker.activity_type,
            'Mode: ' + worker.activity_mode,
            'Started: ' + worker.started_at,
            'Last GPS: ' + worker.last_seen,
        ].join('\n'));

        const image = document.getElementById('detail-image');
        if (image) {
            image.src = worker.evidence_url;
        }

        const location = document.getElementById('detail-location');
        if (location) {
            location.innerHTML = [
                '<p><strong>Status</strong><small>' + worker.status_label + '</small></p>',
                '<p><strong>Location</strong><small>' + worker.location_text + '</small></p>',
                '<p><strong>GPS</strong><small>' + (worker.latitude && worker.longitude ? worker.latitude + ', ' + worker.longitude : 'Not available') + '</small></p>',
                '<p><strong>Accuracy</strong><small>' + (worker.accuracy ? worker.accuracy + 'm' : 'N/A') + '</small></p>',
                '<p><strong>Speed / Heading</strong><small>' + (worker.speed || '0') + ' m/s · ' + (worker.heading || 'N/A') + '</small></p>',
            ].join('');
        }

        const marker = markers.get(String(worker.id));
        if (routeLine) {
            routeLine.remove();
            routeLine = null;
        }

        if (photoMarker) {
            photoMarker.remove();
            photoMarker = null;
        }

        if (Array.isArray(worker.route) && worker.route.length > 1) {
            const points = worker.route.map(function (point) { return [point.lat, point.lng]; });
            routeLine = L.polyline(points, {
                color: '#1c70ed',
                weight: 4,
                opacity: 0.85,
            }).addTo(map);
        }

        if (worker.photo && worker.photo.lat && worker.photo.lng) {
            photoMarker = L.marker([worker.photo.lat, worker.photo.lng], {
                icon: L.divIcon({
                    className: '',
                    html: '<div class="photo-marker">CAM</div>',
                    iconSize: [34, 34],
                    iconAnchor: [17, 17],
                }),
            }).addTo(map).bindPopup(
                '<strong>Photo Evidence</strong><br>' +
                (worker.photo.assignment || worker.assignment) + '<br>' +
                (worker.photo.time || 'Time unavailable') + '<br>' +
                '<small>' + (worker.photo.remark || 'No remark added.') + '</small>'
            );
        }

        const photoDetail = document.getElementById('detail-photo');
        if (photoDetail) {
            if (worker.photo && worker.photo.url) {
                photoDetail.innerHTML = [
                    '<p><strong>Activity</strong><small>' + (worker.photo.assignment || worker.assignment) + '</small></p>',
                    '<p><strong>Sent At</strong><small>' + (worker.photo.time || 'N/A') + '</small></p>',
                    '<p><strong>GPS</strong><small>' + (worker.photo.lat && worker.photo.lng ? worker.photo.lat + ', ' + worker.photo.lng : 'Not available') + '</small></p>',
                    '<p><strong>Accuracy</strong><small>' + (worker.photo.accuracy ? worker.photo.accuracy + 'm' : 'N/A') + '</small></p>',
                    '<p><strong>Remark</strong><small>' + (worker.photo.remark || 'No remark added.') + '</small></p>',
                ].join('');
            } else {
                photoDetail.textContent = 'No photo submitted yet.';
            }
        }

        if (marker) {
            marker.openPopup();
            if (panMap) {
                if (routeLine) {
                    map.fitBounds(routeLine.getBounds().pad(0.22));
                } else {
                    map.setView(marker.getLatLng(), Math.max(map.getZoom(), 14));
                }
            }
        } else if (photoMarker && panMap) {
            map.setView(photoMarker.getLatLng(), Math.max(map.getZoom(), 15));
        }
    }

    workers.forEach(function (worker) {
        if (!worker.latitude || !worker.longitude) {
            return;
        }

        const marker = L.marker([worker.latitude, worker.longitude], { icon: markerIcon(worker) })
            .addTo(map)
            .bindPopup(popup(worker));

        marker.on('click', function () { selectWorker(worker.id, false); });
        markers.set(String(worker.id), marker);
    });

    if (markers.size > 1) {
        map.fitBounds(L.featureGroup(Array.from(markers.values())).getBounds().pad(0.18));
    }

    const emptyMap = document.getElementById('map-empty-state');
    if (emptyMap) {
        emptyMap.hidden = workersWithGps.length > 0;
    }

    function renderWorkers() {
        const term = (search?.value || '').toLowerCase().trim();
        const selectedStatus = status?.value || 'all';
        let visible = 0;

        rows.forEach(function (row) {
            const worker = workerById.get(row.dataset.workerId);
            const haystack = [
                worker?.name,
                worker?.code,
                worker?.mobile,
                worker?.assignment,
                worker?.project,
                worker?.company,
            ].join(' ').toLowerCase();
            const matches = worker
                && (!term || haystack.includes(term))
                && (selectedStatus === 'all' || worker.status === selectedStatus);

            row.hidden = !matches;

            if (matches) {
                visible++;
            }

            const marker = markers.get(row.dataset.workerId);
            if (marker) {
                if (matches) {
                    marker.addTo(map);
                } else {
                    marker.remove();
                }
            }
        });

        if (count) {
            count.textContent = visible;
        }

        if (empty) {
            empty.hidden = visible > 0;
        }
    }

    rows.forEach(function (row) {
        row.addEventListener('click', function () {
            selectWorker(row.dataset.workerId);
        });
    });

    [search, status].forEach(function (control) {
        control?.addEventListener('input', renderWorkers);
        control?.addEventListener('change', renderWorkers);
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
    renderWorkers();
    selectWorker(workers[0]?.id, false);
    setTimeout(function () {
        map.invalidateSize();
        if (markers.size > 1) {
            map.fitBounds(L.featureGroup(Array.from(markers.values())).getBounds().pad(0.18));
        } else if (markers.size === 1) {
            map.setView(Array.from(markers.values())[0].getLatLng(), 14);
        }
    }, 250);
})();
