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

    $tables = [
        "CREATE TABLE IF NOT EXISTS asset (
            asset_id INT AUTO_INCREMENT PRIMARY KEY,
            equip_barcode VARCHAR(255) DEFAULT NULL,
            hostname VARCHAR(255) DEFAULT NULL,
            ip VARCHAR(45) DEFAULT NULL,
            rack_location VARCHAR(255) DEFAULT NULL,
            mounted_location VARCHAR(255) DEFAULT NULL,
            asset_type VARCHAR(100) DEFAULT NULL,
            own_team VARCHAR(100) DEFAULT NULL,
            standard_service VARCHAR(100) DEFAULT NULL,
            unit_service VARCHAR(100) DEFAULT NULL,
            manufacturer VARCHAR(100) DEFAULT NULL,
            model_name VARCHAR(100) DEFAULT NULL,
            serial_number VARCHAR(255) DEFAULT NULL,
            receipt_ym VARCHAR(20) DEFAULT NULL,
            os VARCHAR(100) DEFAULT NULL,
            cpu_type VARCHAR(100) DEFAULT NULL,
            cpu_qty INT DEFAULT 0,
            cpu_core INT DEFAULT 0,
            swap_size VARCHAR(100) DEFAULT NULL,
            ma VARCHAR(100) DEFAULT NULL,
            status VARCHAR(100) DEFAULT NULL,
            purpose VARCHAR(255) DEFAULT NULL,
            purpose_detail TEXT,
            facility_status VARCHAR(100) DEFAULT NULL,
            asset_history TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_ip VARCHAR(45) DEFAULT NULL,
            updated_ip VARCHAR(45) DEFAULT NULL,
            del_yn CHAR(1) DEFAULT 'N'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS asset_memory (
            id INT AUTO_INCREMENT PRIMARY KEY,
            equip_barcode VARCHAR(255) NOT NULL,
            capacity VARCHAR(100) NOT NULL,
            quantity INT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS asset_ssd (
            id INT AUTO_INCREMENT PRIMARY KEY,
            equip_barcode VARCHAR(255) NOT NULL,
            capacity VARCHAR(100) NOT NULL,
            quantity INT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS asset_hdd (
            id INT AUTO_INCREMENT PRIMARY KEY,
            equip_barcode VARCHAR(255) NOT NULL,
            capacity VARCHAR(100) NOT NULL,
            quantity INT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];

    foreach ($tables as $sql) {
        if (!$mysqli->query($sql)) {
            $mysqli->close();
            header('Location: ../tdems_installation.php?msg=' . urlencode('TABLE 생성 실패'));
            exit;
        }
    }
}

$mysqli->close();
header('Location: ../tdems_main.php');
exit;