(function ($) {
    let lastMove = Date.now();

    // Tracking keaktifan
    $(document).on('mousemove keydown scroll click', () => {
        lastMove = Date.now();
    });

    // Log aktivitas ke server tiap 30 detik
    setInterval(() => {
        const now = Date.now();
        const activePercentage = Math.min(100, Math.round(((now - lastMove) < 60000 ? 1 : 0) * 100));

        $.post(adminMonitorAjax.ajax_url, {
            action: 'log_admin_activity',
            nonce: adminMonitorAjax.nonce,
            active: activePercentage
        }, function (response) {
            if (!response.success && response.data?.active_user) {
                alert(`Hanya satu admin yang boleh aktif!\nAktif sekarang: ${response.data.active_user}\nDevice: ${response.data.device}`);
                window.location.href = '/wp-login.php?action=logout';
            }
        });
    }, 30000);

    // Realtime fetch data keaktifan
    function loadAdminActivityData() {
        $.post(adminMonitorAjax.ajax_url, {
            action: 'get_admin_activity_stats',
            nonce: adminMonitorAjax.nonce
        }, function (response) {
            if (!response.success) return;

            const data = response.data;
            let html = '<table style="width:100%; border-collapse: collapse;" border="1" cellpadding="5">';
            html += '<thead><tr><th>Admin</th><th>Terakhir Aktif</th><th>Device</th><th>Persentase Aktif</th></tr></thead><tbody>';

            data.forEach(item => {
                let color = 'gray';
                if (item.percentage >= 80) color = 'green';
                else if (item.percentage >= 40) color = 'orange';
                else color = 'red';

                html += `<tr>
                    <td>${item.user}</td>
                    <td>${item.last_active}</td>
                    <td style="max-width:200px;word-break:break-word;">${item.device}</td>
                    <td><span style="color:${color};font-weight:bold">${item.percentage}%</span></td>
                </tr>`;
            });

            html += '</tbody></table>';
            $('#admin-activity-monitor-table').html(html);
        });
    }

    $(window).on('load', function () {
        setTimeout(() => {
            const $table = $('#admin-activity-monitor-table');

            if ($table.length) {
                console.log("Elemen ditemukan setelah delay, mulai ambil data...");
                loadAdminActivityData();
                setInterval(loadAdminActivityData, 30000);
            } else {
                console.warn("Elemen #admin-activity-monitor-table tetap tidak ditemukan.");
            }
        }, 500); // tunggu 500ms setelah window load
    });



})(jQuery);
