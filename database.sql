CREATE TABLE `servers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `host` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `api_token` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `server_metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` int(11) NOT NULL,
  `load_avg` varchar(255) DEFAULT NULL,
  `cpu_usage` varchar(255) DEFAULT NULL,
  `disk_usage` text,
  `whm_version` varchar(255) DEFAULT NULL,
  `backup_status` text,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `server_id` (`server_id`),
  CONSTRAINT `server_metrics_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `ssl_certificates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` int(11) NOT NULL,
  `domain` varchar(255) NOT NULL,
  `issuer` varchar(255) DEFAULT NULL,
  `expires` date DEFAULT NULL,
  `days_remaining` int(11) DEFAULT NULL,
  `is_valid` tinyint(1) DEFAULT '1',
  `last_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `server_id` (`server_id`),
  CONSTRAINT `ssl_certificates_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
