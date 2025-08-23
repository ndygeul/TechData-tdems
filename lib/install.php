<?php
// DB 설치 스크립트
$host = trim($_POST['host'] ?? '');
$port = (int)($_POST['port'] ?? 0);
$user = trim($_POST['user'] ?? '');
$pass = trim($_POST['pass'] ?? '');
$dbname = trim($_POST['dbname'] ?? '');

if ($host === '' || $port <= 0 || $user === '' || $dbname === '') {
  echo '필수 항목이 누락되었습니다.';
  return;
}

$conn = @new mysqli($host, $user, $pass, '', $port);
if ($conn->connect_errno) {
  echo 'DB 서버 연결 실패: ' . $conn->connect_error;
  return;
}
$conn->set_charset('utf8mb4');

if (!$conn->query("CREATE DATABASE IF NOT EXISTS `" . $conn->real_escape_string($dbname) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
  echo 'DB 생성 실패: ' . $conn->error;
  return;
}
$conn->select_db($dbname);

$tables = [];
$tables[] = "CREATE TABLE IF NOT EXISTS asset (
  asset_id INT AUTO_INCREMENT PRIMARY KEY,
  equip_barcode VARCHAR(255) NULL,
  hostname VARCHAR(255) NULL,
  ip VARCHAR(45) NULL,
  rack_location VARCHAR(255) NULL,
  mounted_location VARCHAR(255) NULL,
  asset_type VARCHAR(255) NULL,
  own_team VARCHAR(255) NULL,
  standard_service VARCHAR(255) NULL,
  unit_service VARCHAR(255) NULL,
  manufacturer VARCHAR(255) NULL,
  model_name VARCHAR(255) NULL,
  serial_number VARCHAR(255) NULL,
  receipt_ym VARCHAR(6) NULL,
  os VARCHAR(255) NULL,
  cpu_type VARCHAR(255) NULL,
  cpu_qty INT NULL,
  cpu_core INT NULL,
  swap_size VARCHAR(255) NULL,
  ma VARCHAR(255) NULL,
  status VARCHAR(255) NULL,
  purpose VARCHAR(255) NULL,
  purpose_detail VARCHAR(255) NULL,
  facility_status VARCHAR(255) NULL,
  asset_history TEXT NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  created_ip VARCHAR(45) NULL,
  updated_ip VARCHAR(45) NULL,
  del_yn CHAR(1) DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$tables[] = "CREATE TABLE IF NOT EXISTS asset_memory (
  id INT AUTO_INCREMENT PRIMARY KEY,
  equip_barcode VARCHAR(255) NULL,
  capacity VARCHAR(255) NULL,
  quantity INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$tables[] = "CREATE TABLE IF NOT EXISTS asset_ssd (
  id INT AUTO_INCREMENT PRIMARY KEY,
  equip_barcode VARCHAR(255) NULL,
  capacity VARCHAR(255) NULL,
  quantity INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$tables[] = "CREATE TABLE IF NOT EXISTS asset_hdd (
  id INT AUTO_INCREMENT PRIMARY KEY,
  equip_barcode VARCHAR(255) NULL,
  capacity VARCHAR(255) NULL,
  quantity INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

foreach ($tables as $sql) {
  if (!$conn->query($sql)) {
    echo '테이블 생성 실패: ' . $conn->error;
    return;
  }
}

$configTemplate = <<<'PHP'
<?php
$host = %s;
$port = %d;
$user = %s;
$pass = %s;
$dbname = %s;

$conn = @new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_errno) {
    $conn = null;
} else {
    $conn->set_charset("utf8mb4");
}
PHP;

$configContent = sprintf(
  $configTemplate,
  var_export($host, true),
  $port,
  var_export($user, true),
  var_export($pass, true),
  var_export($dbname, true)
);

file_put_contents(__DIR__ . '/../config/db.php', $configContent);

header('Location: ../tdems_main.php');
exit;
?>