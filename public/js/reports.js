(() => {
    document.getElementById('download-report').addEventListener('click', function () {
        const toast = document.getElementById('report-toast');
        toast.textContent = 'Report download started in design preview.';
        toast.hidden = false;
        setTimeout(function () { toast.hidden = true; }, 2500);
    });
})();
