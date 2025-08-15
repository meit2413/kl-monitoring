<?php

class WhmApi {
    private $host;
    private $username;
    private $api_token;
    private $curl;

    public function __construct($host, $username, $api_token) {
        $this->host = $host;
        $this->username = $username;
        $this->api_token = $api_token;
    }

    private function call($function, $params = []) {
        $url = "https://{$this->host}:2087/json-api/{$function}?";
        $params['api.version'] = 1;
        $url .= http_build_query($params);

        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        // SSL verification is enabled by default.
        // In a production environment, ensure the server running this script trusts the WHM server's SSL certificate.
        // You may need to add the CA to your server's trusted store.
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, [
            "Authorization: whm {$this->username}:{$this->api_token}"
        ]);

        $result = curl_exec($this->curl);

        if (curl_errno($this->curl)) {
            // Handle curl error
            $error_msg = curl_error($this->curl);
            curl_close($this->curl);
            // In a real app, you'd want to log this error
            return ['error' => "cURL Error: " . $error_msg];
        }

        curl_close($this->curl);

        return json_decode($result, true);
    }

    public function getLoadAvg() {
        return $this->call('systemloadavg');
    }

    public function getDiskUsage() {
        return $this->call('get_disk_usage');
    }

    public function getVersion() {
        return $this->call('version');
    }

    public function getBackupStatus() {
        return $this->call('backup_config_get');
    }

    public function listSslCerts() {
        return $this->call('list_ssl_certs');
    }

    public function getInstalledSslCerts($domain) {
        return $this->call('fetch_ssl_vhosts');
    }
}
?>
