<?php
// This script is intended to be run from the command line or a cron job.
require_once 'db.php';
require_once 'whm_api.php';

$conn = get_db_connection();
$servers = $conn->query("SELECT * FROM servers WHERE is_active = 1");

if ($servers->num_rows > 0) {
    while($server = $servers->fetch_assoc()) {
        echo "Fetching data for server: {$server['name']}...\n";
        $api = new WhmApi($server['host'], $server['username'], $server['api_token']);
        $has_errors = false;

        // Fetch metrics
        $load_avg = $api->getLoadAvg();
        if (isset($load_avg['error'])) {
            echo " - Error fetching load average: " . $load_avg['error'] . "\n";
            $load_avg = null;
            $has_errors = true;
        }

        $disk_usage = $api->getDiskUsage();
        if (isset($disk_usage['error'])) {
            echo " - Error fetching disk usage: " . $disk_usage['error'] . "\n";
            $disk_usage = null;
            $has_errors = true;
        }

        $version_info = $api->getVersion();
        if (isset($version_info['error'])) {
            echo " - Error fetching version: " . $version_info['error'] . "\n";
            $version_info = null;
            $has_errors = true;
        }

        $backup_status = $api->getBackupStatus();
        if (isset($backup_status['error'])) {
            echo " - Error fetching backup status: " . $backup_status['error'] . "\n";
            $backup_status = null;
            $has_errors = true;
        }

        // Prepare data for insertion
        $load_str = isset($load_avg['data']['one']) ? "1m: {$load_avg['data']['one']}, 5m: {$load_avg['data']['five']}, 15m: {$load_avg['data']['fifteen']}" : null;
        $cpu_usage_str = null; // Not implemented
        $disk_usage_json = isset($disk_usage['data']['partitions']) ? json_encode($disk_usage['data']['partitions']) : null;
        $whm_version_str = isset($version_info['data']['version']) ? $version_info['data']['version'] : null;
        $backup_status_json = isset($backup_status['data']['backup_config']) ? json_encode($backup_status['data']['backup_config']) : null;

        // Insert whatever data we have
        $stmt = $conn->prepare("INSERT INTO server_metrics (server_id, load_avg, cpu_usage, disk_usage, whm_version, backup_status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("isssss", $server['id'], $load_str, $cpu_usage_str, $disk_usage_json, $whm_version_str, $backup_status_json);
        $stmt->execute();
        $stmt->close();

        // Fetch and store SSL certs
        $ssl_certs = $api->getInstalledSslCerts(null);
        if (isset($ssl_certs['error'])) {
            echo " - Error fetching SSL certs: " . $ssl_certs['error'] . "\n";
            $ssl_certs = null;
            $has_errors = true;
        }

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

        if ($has_errors) {
            echo "Finished fetching data for server: {$server['name']} with some errors.\n";
        } else {
            echo "Successfully fetched all data for server: {$server['name']}\n";
        }
    }
} else {
    echo "No active servers found.\n";
}

$conn->close();
?>
