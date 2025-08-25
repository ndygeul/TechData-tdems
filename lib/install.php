<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../tdems_installation.php');
    exit;
}

$hostInput = trim($_POST['server'] ?? '');
$portInput = trim($_POST['port'] ?? '');
$userInput = trim($_POST['id'] ?? '');
$passInput = trim($_POST['pw'] ?? '');
$dbInput   = trim($_POST['db'] ?? '');

$configPath = __DIR__ . '/../config/db.php';

$config = "<?php\n".
          "\$host = \"{$hostInput}\";\n".
          "\$port = \"{$portInput}\";\n".
          "\$user = \"{$userInput}\";\n".
          "\$pass = \"{$passInput}\";\n".
          "\$dbname = \"{$dbInput}\";\n\n".
          "\$conn = null;\n".
          "if (\$host !== '' && \$user !== '' && \$dbname !== '') {\n".
          "    \$conn = @new mysqli(\$host, \$user, \$pass, \$dbname, \$port);\n".
          "    if (\$conn->connect_error) {\n".
          "        \$conn = null;\n".
          "    } else {\n".
          "        \$conn->set_charset('utf8mb4');\n".
          "    }\n".
          "}\n";
file_put_contents($configPath, $config);

require $configPath;
if ($host !== $hostInput || $port !== $portInput || $user !== $userInput || $pass !== $passInput || $dbname !== $dbInput) {
    header('Location: ../tdems_installation.php?msg=' . urlencode('설정 저장 실패'));
    exit;
}

$mysqli = @new mysqli($hostInput, $userInput, $passInput, '', $portInput);
if ($mysqli->connect_error) {
    header('Location: ../tdems_installation.php?msg=' . urlencode('DB 서버 접속 실패'));
    exit;
}

if (!$mysqli->select_db($dbInput)) {
    if (!$mysqli->query("CREATE DATABASE `{$dbInput}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
        $mysqli->close();
        header('Location: ../tdems_installation.php?msg=' . urlencode('DB 생성 실패'));
        exit;
    }
    $mysqli->select_db($dbInput);
}
$mysqli->set_charset('utf8mb4');

$tables = [
    <<<'SQL'
CREATE TABLE IF NOT EXISTS `asset` (
  `asset_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `equip_barcode` varchar(20) DEFAULT NULL COMMENT '설비바코드',
  `rack_location` varchar(100) DEFAULT NULL COMMENT '랙 위치',
  `mounted_location` varchar(100) DEFAULT NULL COMMENT '장착 위치',
  `hostname` varchar(255) DEFAULT NULL COMMENT '호스트명',
  `ip` varchar(255) DEFAULT NULL COMMENT '장비 IP',
  `asset_type` varchar(50) NOT NULL DEFAULT '' COMMENT '장비 종류',
  `ma` varchar(1) DEFAULT NULL COMMENT 'MA',
  `status` varchar(10) DEFAULT NULL COMMENT '상태',
  `purpose` varchar(50) DEFAULT NULL COMMENT '용도',
  `purpose_detail` varchar(100) DEFAULT NULL COMMENT '상세용도',
  `facility_status` varchar(50) DEFAULT NULL COMMENT '설비상태',
  `own_team` varchar(100) DEFAULT '' COMMENT '자산보유팀',
  `standard_service` varchar(100) DEFAULT '' COMMENT '표준서비스',
  `unit_service` varchar(100) DEFAULT '' COMMENT '단위서비스',
  `manufacturer` varchar(100) DEFAULT NULL COMMENT '장비 제조사',
  `model_name` varchar(150) DEFAULT NULL COMMENT '장비 모델명',
  `serial_number` varchar(150) DEFAULT NULL COMMENT '장비 시리얼',
  `receipt_ym` varchar(7) DEFAULT NULL COMMENT '입고시기',
  `os` varchar(100) NOT NULL DEFAULT '' COMMENT 'OS 정보',
  `cpu_type` varchar(100) NOT NULL DEFAULT '' COMMENT 'CPU 종류',
  `cpu_qty` int(11) NOT NULL DEFAULT 0 COMMENT 'CPU 수량',
  `cpu_core` int(11) NOT NULL DEFAULT 0 COMMENT 'CPU 코어',
  `swap_size` varchar(100) NOT NULL DEFAULT '' COMMENT 'SWAP',
  `created_ip` varchar(45) DEFAULT NULL COMMENT '최초 등록자 IP',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '등록일',
  `updated_ip` varchar(45) DEFAULT NULL COMMENT '최종 수정자 IP',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일',
  `del_yn` char(1) NOT NULL DEFAULT 'N' COMMENT '삭제 구분 (Y:삭제)',
  `deleted_at` datetime DEFAULT NULL COMMENT '삭제일',
  `deleted_reason` varchar(255) DEFAULT NULL COMMENT '삭제 사유',
  `asset_history` longtext DEFAULT NULL COMMENT '자산 이력',
  `hostname_active` varchar(255) GENERATED ALWAYS AS (case when (`del_yn` = 'N' and `hostname` <> '') then `hostname` else NULL end) STORED,
  `equip_barcode_active` varchar(255) GENERATED ALWAYS AS (case when (`del_yn` = 'N' and `equip_barcode` <> '') then `equip_barcode` else NULL end) STORED,
  PRIMARY KEY (`asset_id`),
  UNIQUE KEY `uq_asset_equip_barcode` (`equip_barcode`),
  UNIQUE KEY `uq_asset_barcode_active` (`equip_barcode_active`),
  KEY `idx_asset_del_yn` (`del_yn`),
  KEY `idx_asset_deleted_at` (`deleted_at`),
  KEY `idx_asset_maker` (`manufacturer`),
  KEY `idx_asset_model` (`model_name`),
  KEY `idx_asset_serial` (`serial_number`),
  KEY `idx_asset_rack_mounted` (`rack_location`,`mounted_location`),
  KEY `idx_asset_hostname` (`hostname`),
  KEY `idx_asset_hostname_del_yn` (`hostname`,`del_yn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL,
    <<<'SQL'
CREATE TABLE IF NOT EXISTS `asset_hdd` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equip_barcode` varchar(20) NOT NULL,
  `capacity` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_asset_hdd_asset` (`equip_barcode`),
  CONSTRAINT `fk_asset_hdd_asset` FOREIGN KEY (`equip_barcode`) REFERENCES `asset` (`equip_barcode`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL,
    <<<'SQL'
CREATE TABLE IF NOT EXISTS `asset_memory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equip_barcode` varchar(20) NOT NULL,
  `capacity` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_asset_memory_asset` (`equip_barcode`),
  CONSTRAINT `fk_asset_memory_asset` FOREIGN KEY (`equip_barcode`) REFERENCES `asset` (`equip_barcode`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL,
    <<<'SQL'
CREATE TABLE IF NOT EXISTS `asset_ssd` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equip_barcode` varchar(20) NOT NULL,
  `capacity` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_asset_ssd_asset` (`equip_barcode`),
  CONSTRAINT `fk_asset_ssd_asset` FOREIGN KEY (`equip_barcode`) REFERENCES `asset` (`equip_barcode`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL,
];

foreach ($tables as $sql) {
    if (!$mysqli->query($sql)) {
        $mysqli->close();
        header('Location: ../tdems_installation.php?msg=' . urlencode('TABLE 생성 실패'));
        exit;
    }
}

$mysqli->close();
header('Location: ../tdems_main.php');
exit;
