(() => {
    const cards = Array.from(document.querySelectorAll('.submission-item'));
    const search = document.getElementById('submission-search');
    const company = document.getElementById('submission-company');
    const project = document.getElementById('submission-project');
    const activityType = document.getElementById('submission-activity-type');
    const status = document.getElementById('submission-status');
    const sort = document.getElementById('submission-sort');
    const comment = document.getElementById('submission-comment');
    const reviewForm = document.getElementById('submission-review-form');
    const reviewStatus = document.getElementById('review-status');
    const empty = document.getElementById('submissions-empty');
    const count = document.getElementById('submission-count');

    function setText(id, value) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value || 'N/A';
        }
    }

    function selectCard(card) {
        if (!card) {
            return;
        }

        cards.forEach(function (item) { item.classList.remove('selected'); });
        card.classList.add('selected');

        setText('detail-title', card.dataset.title);
        setText('detail-project', card.dataset.project + ' (' + card.dataset.projectCode + ')');
        setText('detail-activity-type', card.dataset.activityType);
        setText('detail-mode', card.dataset.mode);
        setText('detail-worker', card.dataset.worker + ' (' + card.dataset.workerCode + ')');
        setText('detail-mobile', card.dataset.workerMobile);
        setText('detail-location', card.dataset.location);
        setText('detail-gps', card.dataset.latitude + ', ' + card.dataset.longitude + (card.dataset.accuracy ? ' | accuracy ' + card.dataset.accuracy + 'm' : ''));
        setText('detail-time', card.dataset.submittedAt);
        setText('detail-status', card.dataset.statusLabel);
        setText('detail-remark', card.dataset.remark);

        const mapLink = document.getElementById('detail-map-link');
        if (mapLink) {
            const hasGps = card.dataset.latitude && card.dataset.longitude;
            mapLink.hidden = !hasGps;
            mapLink.href = hasGps
                ? 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(card.dataset.latitude + ',' + card.dataset.longitude)
                : '#';
        }

        const photo = document.getElementById('detail-photo');
        if (photo) {
            photo.style.backgroundImage = "linear-gradient(135deg, #1c3a4db0, #22927580), url('" + card.dataset.image + "')";
        }

        if (reviewForm) {
            reviewForm.action = (window.submissionReviewBaseUrl || '/submissions') + '/' + card.dataset.id + '/review';
        }

        if (comment) {
            comment.value = card.dataset.rejectionReason || '';
            updateCommentCount();
        }
    }

    function renderCards() {
        const query = (search?.value || '').toLowerCase().trim();
        let visibleCards = 0;

        cards.forEach(function (card) {
            const visible =
                (!query || card.dataset.search.includes(query)) &&
                (!company || company.value === 'all' || card.dataset.company === company.value) &&
                (!project || project.value === 'all' || card.dataset.projectId === project.value) &&
                (!activityType || activityType.value === 'all' || card.dataset.activityTypeId === activityType.value) &&
                (!status || status.value === 'all' || card.dataset.status === status.value);

            card.hidden = !visible;
            if (visible) visibleCards++;
        });

        if (empty) {
            empty.hidden = visibleCards > 0;
        }

        if (count) {
            count.textContent = 'Submissions (' + visibleCards + ')';
        }

        const selected = cards.find(function (card) {
            return card.classList.contains('selected') && !card.hidden;
        });

        if (!selected) {
            selectCard(cards.find(function (card) { return !card.hidden; }));
        }
    }

    function sortCards() {
        const grid = document.getElementById('submission-grid');
        if (!grid) {
            return;
        }

        const ordered = [...cards];
        if (sort?.value === 'oldest') {
            ordered.reverse();
        }

        ordered.forEach(function (card) { grid.appendChild(card); });
    }

    function updateCommentCount() {
        const counter = document.getElementById('comment-count');
        if (comment && counter) {
            counter.textContent = comment.value.length + '/500';
        }
    }

    function exportCsv() {
        const visible = cards.filter(function (card) { return !card.hidden; });
        const rows = [['Assignment', 'Project', 'Worker', 'Location', 'Submitted At', 'Status']];

        visible.forEach(function (card) {
            rows.push([
                card.dataset.title,
                card.dataset.project,
                card.dataset.worker,
                card.dataset.location,
                card.dataset.submittedAt,
                card.dataset.statusLabel,
            ]);
        });

        const csv = rows.map(function (row) {
            return row.map(function (cell) {
                return '"' + String(cell || '').replaceAll('"', '""') + '"';
            }).join(',');
        }).join('\n');

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'field-submissions.csv';
        link.click();
        URL.revokeObjectURL(link.href);
    }

    cards.forEach(function (card) {
        card.addEventListener('click', function () {
            selectCard(card);
        });
    });

    [search, company, project, activityType, status].forEach(function (control) {
        control?.addEventListener('input', renderCards);
        control?.addEventListener('change', renderCards);
    });

    sort?.addEventListener('change', function () {
        sortCards();
        renderCards();
    });

    document.getElementById('submission-reset')?.addEventListener('click', function () {
        if (search) search.value = '';
        if (company) company.value = 'all';
        if (project) project.value = 'all';
        if (activityType) activityType.value = 'all';
        if (status) status.value = 'all';
        renderCards();
    });

    comment?.addEventListener('input', updateCommentCount);
    document.getElementById('export-submissions')?.addEventListener('click', exportCsv);

    document.querySelectorAll('.detail-actions button[data-status]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (reviewStatus) {
                reviewStatus.value = button.dataset.status;
            }
        });
    });

    selectCard(cards[0]);
    renderCards();
})();
