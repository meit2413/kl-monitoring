<?php
require_once '../app/db.php';

$conn = get_db_connection();
// Fetch servers and their latest metrics in one go
$servers_query = "
    SELECT
        s.id, s.name, s.last_updated, s.ssl_verify,
        sm.load_avg, sm.disk_usage, sm.whm_version, sm.backup_status
    FROM servers s
    LEFT JOIN (
        SELECT *, ROW_NUMBER() OVER(PARTITION BY server_id ORDER BY created_at DESC) as rn
        FROM server_metrics
    ) sm ON s.id = sm.server_id AND sm.rn = 1
    WHERE s.is_active = 1
";
$servers = $conn->query($servers_query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>WHM Monitoring Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .whm-version-bubble {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            color: #fff;
            font-size: 0.8em;
        }
        .whm-version-ok {
            background-color: #28a745; /* green */
        }
        .whm-version-old {
            background-color: #dc3545; /* red */
        }
        .last-updated {
            font-size: 0.8em;
            color: #6c757d;
        }
        .ssl-warning {
            color: #6c757d;
            margin-left: 8px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h1 class="mt-5">WHM Monitoring Dashboard</h1>
        <p>Data is periodically updated in the background. Last successful cron job run can be inferred from the 'Last Updated' column.</p>
        <a href="index.php" class="btn btn-secondary mb-3">Home</a>
        <a href="add_server.php" class="btn btn-primary mb-3">Add New Server</a>

        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>Server Name</th>
                    <th>Load Average</th>
                    <th>Disk Usage</th>
                    <th>WHM Version</th>
                    <th>Backup Status</th>
                    <th>SSL Certificates</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($servers && $servers->num_rows > 0): ?>
                    <?php while($server = $servers->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($server['name']); ?>
                                <?php if (!$server['ssl_verify']): ?>
                                    <span class="ssl-warning" title="SSL verification is disabled for this server."><i class="fas fa-unlock-alt"></i></span>
                                <?php endif; ?>
                                <div class='last-updated'>Last Updated: <?php echo $server['last_updated'] ? date('Y-m-d H:i:s', strtotime($server['last_updated'])) : 'Never'; ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($server['load_avg']); ?></td>
                            <td>
                                <?php
                                if ($server['disk_usage']) {
                                    $partitions = json_decode($server['disk_usage'], true);
                                    if (is_array($partitions)) {
                                        foreach ($partitions as $partition) {
                                            echo htmlspecialchars($partition['mount']) . ": " .
                                                 htmlspecialchars($partition['used']) . " / " .
                                                 htmlspecialchars($partition['total']) . " (" .
                                                 htmlspecialchars($partition['percentage']) . "%)<br>";
                                        }
                                    }
                                } else {
                                    echo "No data.";
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                if ($server['whm_version']) {
                                    $current_version = $server['whm_version'];
                                    // In a real app, you'd fetch the latest stable version dynamically
                                    $stable_version = '110.0.9999.123'; // Example stable version
                                    $is_old = version_compare($current_version, $stable_version, '<');
                                    $bubble_class = $is_old ? 'whm-version-old' : 'whm-version-ok';
                                    echo htmlspecialchars($current_version) .
                                         " <span class='whm-version-bubble " . $bubble_class . "'>" .
                                         ($is_old ? 'Outdated' : 'Up-to-date') . "</span>";
                                } else {
                                    echo "No data.";
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                if ($server['backup_status']) {
                                    $backup_config = json_decode($server['backup_status'], true);
                                    echo isset($backup_config['backup_enabled']) && $backup_config['backup_enabled'] ? 'Enabled' : 'Disabled';
                                } else {
                                    echo "No data.";
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                $ssl_certs_query = $conn->prepare("SELECT * FROM ssl_certificates WHERE server_id = ? ORDER BY days_remaining ASC");
                                $ssl_certs_query->bind_param("i", $server['id']);
                                $ssl_certs_query->execute();
                                $ssl_certs = $ssl_certs_query->get_result();

                                if ($ssl_certs->num_rows > 0) {
                                    while($cert = $ssl_certs->fetch_assoc()) {
                                        $color = $cert['days_remaining'] < 30 ? 'red' : 'inherit';
                                        echo "<div style='color: {$color}'>" . htmlspecialchars($cert['domain']) . ": Expires on " . $cert['expires'] . " (" . $cert['days_remaining'] . " days left)</div>";
                                    }
                                } else {
                                    echo "No SSL data.";
                                }
                                $ssl_certs_query->close();
                                ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No servers found. <a href="add_server.php">Add one now!</a></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
<?php
$conn->close();
?>
