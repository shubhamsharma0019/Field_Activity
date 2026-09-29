(() => {
    const rows = Array.from(document.querySelectorAll('#session-rows tr'));
    const search = document.getElementById('session-search');
    const company = document.getElementById('session-company');
    const project = document.getElementById('session-project');
    const status = document.getElementById('session-status');
    const date = document.getElementById('session-date');
    const count = document.getElementById('session-count');
    const empty = document.getElementById('sessions-empty');
    const reviewForm = document.getElementById('session-review-form');
    const reviewStatus = document.getElementById('session-review-status');
    const reviewComment = document.getElementById('session-review-comment');

    function setText(id, value) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value || 'N/A';
        }
    }

    function selectSession(row) {
        if (!row) {
            return;
        }

        rows.forEach(function (item) { item.classList.remove('selected'); });
        row.classList.add('selected');

        setText('detail-initials', row.dataset.workerInitials);
        setText('detail-worker', row.dataset.worker);
        setText('detail-worker-meta', row.dataset.workerCode + '\n' + row.dataset.workerMobile + '\n' + row.dataset.location);
        setText('detail-assignment', row.dataset.assignment);
        setText('detail-project', row.dataset.project);
        setText('detail-company', row.dataset.companyName);
        setText('detail-mode', row.dataset.mode);
        setText('detail-duration', row.dataset.duration + ' (' + row.dataset.statusLabel + ')');
        setText('detail-start-gps', row.dataset.startGps);
        setText('detail-end-gps', row.dataset.endGps);
        setText('detail-status', row.dataset.statusLabel);

        const startEvidence = document.getElementById('start-evidence');
        const endEvidence = document.getElementById('end-evidence');

        if (startEvidence) {
            startEvidence.style.backgroundImage = "url('" + row.dataset.startImage + "')";
        }

        if (endEvidence) {
            endEvidence.style.backgroundImage = "url('" + row.dataset.endImage + "')";
        }

        const timeline = document.getElementById('detail-timeline');
        if (timeline) {
            timeline.innerHTML = [
                '<p>Session Started<br><small>' + row.dataset.startTime + '</small></p>',
                '<p>Start Location Captured<br><small>' + row.dataset.startGps + '</small></p>',
                '<p>Duration Updated<br><small>' + row.dataset.duration + '</small></p>',
                '<p>' + (row.dataset.endTime === '-' ? 'Waiting for Completion' : 'Session Completed') + '<br><small>' + row.dataset.endTime + '</small></p>',
            ].join('');
        }

        if (reviewForm) {
            reviewForm.action = (window.sessionReviewBaseUrl || '/activity-sessions') + '/' + row.dataset.id + '/review';
        }

        if (reviewComment) {
            reviewComment.value = row.dataset.rejectionReason || '';
        }
    }

    function renderSessions() {
        const term = (search?.value || '').toLowerCase().trim();
        let visible = 0;

        rows.forEach(function (row, index) {
            const matches =
                (!term || row.dataset.search.includes(term)) &&
                (!company || company.value === 'all' || row.dataset.company === company.value) &&
                (!project || project.value === 'all' || row.dataset.projectId === project.value) &&
                (!status || status.value === 'all' || row.dataset.status === status.value) &&
                (!date || !date.value || row.dataset.startDate === date.value);

            row.hidden = !matches;
            if (matches) {
                visible++;
                row.children[0].textContent = visible;
            } else {
                row.children[0].textContent = index + 1;
            }
        });

        if (count) {
            count.textContent = 'Activity Sessions (' + visible + ')';
        }

        if (empty) {
            empty.hidden = visible > 0;
        }

        const selected = rows.find(function (row) {
            return row.classList.contains('selected') && !row.hidden;
        });

        if (!selected) {
            selectSession(rows.find(function (row) { return !row.hidden; }));
        }
    }

    rows.forEach(function (row) {
        row.querySelector('.session-view')?.addEventListener('click', function () {
            selectSession(row);
        });

        row.addEventListener('click', function () {
            selectSession(row);
        });
    });

    [search, company, project, status, date].forEach(function (control) {
        control?.addEventListener('input', renderSessions);
        control?.addEventListener('change', renderSessions);
    });

    document.getElementById('session-reset')?.addEventListener('click', function () {
        if (search) search.value = '';
        if (company) company.value = 'all';
        if (project) project.value = 'all';
        if (status) status.value = 'all';
        if (date) date.value = '';
        renderSessions();
    });

    document.querySelectorAll('.session-review-actions button[data-status]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (reviewStatus) {
                reviewStatus.value = button.dataset.status;
            }
        });
    });

    selectSession(rows[0]);
    renderSessions();
})();
