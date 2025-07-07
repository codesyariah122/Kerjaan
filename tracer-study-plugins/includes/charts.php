<?php
function tracer_chart_page()
{
    global $wpdb;
    $data = $wpdb->get_results("SELECT status_pekerjaan, COUNT(*) as total FROM {$wpdb->prefix}tracer_alumni GROUP BY status_pekerjaan");
    $labels = [];
    $counts = [];
    foreach ($data as $row) {
        $labels[] = $row->status_pekerjaan;
        $counts[] = $row->total;
    }
?>
    <div class="wrap">
        <h2>Statistik Tracer</h2>
        <canvas id="statusChart" width="400" height="200"></canvas>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            const ctx = document.getElementById('statusChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($labels) ?>,
                    datasets: [{
                        label: 'Jumlah Alumni',
                        data: <?= json_encode($counts) ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)'
                    }]
                }
            });
        </script>
    </div>
<?php
}
