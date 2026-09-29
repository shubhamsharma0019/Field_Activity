(() => {
    const activities = [
        ['Installation', 'Install promotional materials at assigned locations', 'Single Submission', 'Active', 42, '🔧'],
        ['Survey', 'Conduct field survey and collect information', 'Single Submission', 'Active', 28, '📄'],
        ['Cleaning', 'Clean assigned area', 'Start - End', 'Active', 26, '🧹'],
        ['Visit', 'Visit location and capture photos', 'Single Submission', 'Active', 20, '📍'],
        ['Campaign', 'Campaign and awareness activity', 'Continuous Tracking', 'Active', 18, '📣'],
        ['Shop Visit', 'Visit retail shops', 'Single Submission', 'Active', 16, '🏪'],
        ['Road Cleaning', 'Road and public area cleaning', 'Start - End', 'Active', 14, '🧹'],
        ['Other', 'Other type of activity', 'Single Submission', 'Inactive', 6, '•'],
    ];

    let selectedIcon = '🔧';
    const search = document.getElementById('activity-search');
    const status = document.getElementById('activity-status');

    function modeClass(mode) {
        if (mode === 'Start - End') return 'start';
        if (mode === 'Continuous Tracking') return 'continuous';
        return '';
    }

    function renderActivities() {
        const query = search.value.toLowerCase();
        const list = activities.filter(function (activity) {
            return activity.join(' ').toLowerCase().includes(query) && (status.value === 'all' || activity[3] === status.value);
        });

        const rows = document.getElementById('activity-rows');
        rows.innerHTML = '';

        list.forEach(function (activity, index) {
            const row = document.createElement('tr');
            row.innerHTML = `<td>${index + 1}</td><td><span class="activity-name"><span class="activity-icon">${activity[5]}</span>${activity[0]}</span></td><td>${activity[1]}</td><td><span class="mode-badge ${modeClass(activity[2])}">${activity[2]}</span></td><td><span class="activity-status ${activity[3] === 'Inactive' ? 'inactive' : ''}">${activity[3]}</span></td><td>${activity[4]}</td><td><div class="activity-actions"><button class="activity-action" type="button" aria-label="Edit ${activity[0]}">✎</button><button class="activity-action delete" type="button" aria-label="Delete ${activity[0]}">⌫</button></div></td>`;
            rows.appendChild(row);
        });

        document.getElementById('activities-empty').hidden = list.length > 0;
    }

    search.addEventListener('input', renderActivities);
    status.addEventListener('change', renderActivities);
    document.getElementById('activity-description').addEventListener('input', function (event) {
        document.getElementById('activity-description-count').textContent = `${event.target.value.length}/500`;
    });

    document.querySelectorAll('.icon-choice').forEach(function (button) {
        button.addEventListener('click', function () {
            selectedIcon = button.dataset.icon;
            document.querySelectorAll('.icon-choice').forEach(function (item) { item.classList.remove('selected'); });
            button.classList.add('selected');
        });
    });

    function hideActivityForm() {
        document.getElementById('activity-form-panel').hidden = true;
    }

    document.getElementById('close-activity-form').addEventListener('click', hideActivityForm);
    document.getElementById('cancel-activity').addEventListener('click', hideActivityForm);

    document.getElementById('activity-form').addEventListener('submit', function (event) {
        event.preventDefault();
        if (!event.currentTarget.reportValidity()) return;

        const selectedMode = document.querySelector('input[name="mode"]:checked').value;
        const selectedStatus = document.getElementById('activity-active').checked ? 'Active' : 'Inactive';
        activities.unshift([document.getElementById('activity-name').value, document.getElementById('activity-description').value, selectedMode, selectedStatus, 0, selectedIcon]);
        event.currentTarget.reset();
        document.getElementById('activity-active').checked = true;
        hideActivityForm();
        renderActivities();

        const toast = document.getElementById('activity-toast');
        toast.textContent = 'Activity type added in design preview.';
        toast.hidden = false;
        setTimeout(function () { toast.hidden = true; }, 3000);
    });

    renderActivities();
})();
