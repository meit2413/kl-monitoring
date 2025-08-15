<?php
// This script is intended to be run from the command line or a cron job.
require_once 'db.php';
require_once 'whm_api.php';

$conn = get_db_connection();
$servers = $conn->query("SELECT * FROM servers WHERE is_active = 1");

if ($servers->num_rows > 0) {
    while($server = $servers->fetch_assoc()) {
        $api = new WhmApi($server['host'], $server['username'], $server['api_token']);

        // Fetch metrics
        $load_avg = $api->getLoadAvg();
        $disk_usage = $api->getDiskUsage();
        $version_info = $api->getVersion();
        $backup_status = $api->getBackupStatus();

        // Store metrics
        if (isset($load_avg['data']['one'])) {
            $load_str = "1m: {$load_avg['data']['one']}, 5m: {$load_avg['data']['five']}, 15m: {$load_avg['data']['fifteen']}";
            $cpu_usage_str = null; // We are not fetching this separately for now
            $disk_usage_json = isset($disk_usage['data']['partitions']) ? json_encode($disk_usage['data']['partitions']) : null;
            $whm_version_str = isset($version_info['data']['version']) ? $version_info['data']['version'] : null;
            $backup_status_json = isset($backup_status['data']['backup_config']) ? json_encode($backup_status['data']['backup_config']) : null;

            $stmt = $conn->prepare("INSERT INTO server_metrics (server_id, load_avg, cpu_usage, disk_usage, whm_version, backup_status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("isssss", $server['id'], $load_str, $cpu_usage_str, $disk_usage_json, $whm_version_str, $backup_status_json);
            $stmt->execute();
            $stmt->close();
        }

        // Fetch and store SSL certs
        $ssl_certs = $api->getInstalledSslCerts(null);
        if (isset($ssl_certs['data']['vhosts'])) {
            // Clear old certs for this server
            $stmt = $conn->prepare("DELETE FROM ssl_certificates WHERE server_id = ?");
            $stmt->bind_param("i", $server['id']);
            $stmt->execute();
            $stmt->close();

            // Insert new certs
            $stmt = $conn->prepare("INSERT INTO ssl_certificates (server_id, domain, issuer, expires, days_remaining, is_valid, last_updated) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            foreach ($ssl_certs['data']['vhosts'] as $vhost) {
                $domain = $vhost['domain'];
                $issuer = $vhost['crt']['issuer']['commonName'];
                $expires = date('Y-m-d', $vhost['crt']['not_after']);
                $days_remaining = floor(($vhost['crt']['not_after'] - time()) / (60*60*24));
                $is_valid = $vhost['crt']['not_after'] > time();
                $stmt->bind_param("isssii", $server['id'], $domain, $issuer, $expires, $days_remaining, $is_valid);
                $stmt->execute();
            }
            $stmt->close();
        }

        // Update last_updated timestamp on server
        $stmt = $conn->prepare("UPDATE servers SET last_updated = NOW() WHERE id = ?");
        $stmt->bind_param("i", $server['id']);
        $stmt->execute();
        $stmt->close();

        echo "Successfully fetched data for server: {$server['name']}\n";
    }
} else {
    echo "No active servers found.\n";
}

$conn->close();
?>
