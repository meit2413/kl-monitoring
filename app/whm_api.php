<?php

class WhmApi {
    private $host;
    private $username;
    private
     $api_token;
    private $curl;
    private $ssl_verify;

    public function __construct($host, $username, $api_token, $ssl_verify = true) {
        $this->host = $host;
        $this->username = $username;
        $this->api_token = $api_token;
        $this->ssl_verify = (bool) $ssl_verify;
    }

    private function call($function, $params = []) {
        $url = "https://{$this->host}:2087/json-api/{$function}?";
        $params['api.version'] = 1;
        $url .= http_build_query($params);

        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);

        if ($this->ssl_verify) {
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            // This is insecure and should only be used for servers with known SSL certificate issues.
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        curl_setopt($this->curl, CURLOPT_HTTPHEADER, [
            "Authorization: whm {$this->username}:{$this->api_token}"
        ]);

        $result = curl_exec($this->curl);

        if (curl_errno($this->curl)) {
            // Handle curl error
            $error_msg = curl_error($this->curl);
            curl_close($this->curl);
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
