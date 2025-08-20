<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/csrf.php';

csrf_check_or_die();

// DB 핸들
$__db = null;
if (isset($mysqli) && $mysqli instanceof mysqli) { $__db = $mysqli; }
elseif (isset($conn) && $conn instanceof mysqli) { $__db = $conn; }
if (!$__db) { http_response_code(500); exit('DB connection is not initialized.'); }

// 입력
$equip_barcode    = trim($_POST['equip_barcode'] ?? '');
$hostname         = trim($_POST['hostname'] ?? '');
$ip               = trim($_POST['ip'] ?? '');
$rack_location    = trim($_POST['rack_location'] ?? '');
$mounted_location = trim($_POST['mounted_location'] ?? '');
$asset_type       = trim($_POST['asset_type'] ?? '');
$own_team         = trim($_POST['own_team'] ?? '');
$standard_service = trim($_POST['standard_service'] ?? '');
$unit_service     = trim($_POST['unit_service'] ?? '');
$manufacturer     = trim($_POST['manufacturer'] ?? '');
$model_name       = trim($_POST['model_name'] ?? '');
$serial_number    = trim($_POST['serial_number'] ?? '');
$receipt_ym       = trim($_POST['receipt_ym'] ?? '');
$os               = trim($_POST['os'] ?? '');
$cpu_type         = trim($_POST['cpu_type'] ?? '');
$cpu_qty          = (int)($_POST['cpu_qty'] ?? 0);
$cpu_core         = (int)($_POST['cpu_core'] ?? 0);
$swap_size        = trim($_POST['swap_size'] ?? '');
$ma               = trim($_POST['ma'] ?? '');
$status           = trim($_POST['status'] ?? '');
$purpose          = trim($_POST['purpose'] ?? '');
$purpose_detail   = trim($_POST['purpose_detail'] ?? '');
$facility_status  = trim($_POST['facility_status'] ?? '');
$asset_history    = trim($_POST['asset_history'] ?? '');
$created_ip       = $_SERVER['REMOTE_ADDR'] ?? '';

if ($equip_barcode === '') {
  header('Location: ../tdems_write.php?msg=' . urlencode('설비바코드는 필수입니다.'));
  exit;
}
// IPv4 검증 (빈 값 허용)
if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
  header('Location: ../tdems_write.php?msg=' . urlencode('IP 형식이 올바르지 않습니다(IPv4만 허용).'));
  exit;
}

// 중복 체크
$chk = $__db->prepare("SELECT 1 FROM asset WHERE equip_barcode = ? LIMIT 1");
if (!$chk) { header('Location: ../tdems_write.php?msg=' . urlencode('DB 오류(중복확인): '.$__db->error)); exit; }
$chk->bind_param('s', $equip_barcode);
$chk->execute(); $chk->store_result();
if ($chk->num_rows > 0) { $chk->close(); header('Location: ../tdems_write.php?msg=' . urlencode('중복된 설비바코드입니다.')); exit; }
$chk->close();

// INSERT
$sql = "INSERT INTO asset
        (equip_barcode, hostname, ip, rack_location, mounted_location,
         asset_type, own_team, standard_service, unit_service, manufacturer, model_name, serial_number, receipt_ym,
         os, cpu_type, cpu_qty, cpu_core, swap_size,
         ma, status, purpose, purpose_detail, facility_status,
         asset_history, created_at, updated_at, created_ip, updated_ip, del_yn)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, NOW(), NOW(), ?, ?, 'N')";
$stmt = $__db->prepare($sql);
if (!$stmt) { header('Location: ../tdems_write.php?msg=' . urlencode('DB 오류(prepare): '.$__db->error)); exit; }
$stmt->bind_param(
  'sssssssssssssssiisssssssss',
  $equip_barcode, $hostname, $ip, $rack_location, $mounted_location,
  $asset_type, $own_team, $standard_service, $unit_service, $manufacturer, $model_name, $serial_number, $receipt_ym,
  $os, $cpu_type, $cpu_qty, $cpu_core, $swap_size,
  $ma, $status, $purpose, $purpose_detail, $facility_status,
  $asset_history, $created_ip, $created_ip
);
if (!$stmt->execute()) {
  $err = $stmt->error ?: $__db->error; $stmt->close();
  header('Location: ../tdems_write.php?msg=' . urlencode('DB 오류(execute): '.$err)); exit;
}
$stmt->close();

// MEMORY 정보 저장
$mem_caps = $_POST['mem_capacity'] ?? [];
$mem_qtys = $_POST['mem_qty'] ?? [];
if (is_array($mem_caps) && is_array($mem_qtys)) {
  $sql2 = "INSERT INTO asset_memory (equip_barcode, capacity, quantity) VALUES (?,?,?)";
  $stmt2 = $__db->prepare($sql2);
  if ($stmt2) {
    $cap = $qty = null;
    $stmt2->bind_param('ssi', $equip_barcode, $cap, $qty);
    $cnt = min(count($mem_caps), count($mem_qtys));
    for ($i = 0; $i < $cnt; $i++) {
      $cap = trim($mem_caps[$i]);
      $qty = (int)$mem_qtys[$i];
      if ($cap === '' || $qty <= 0) continue;
      $stmt2->execute();
    }
    $stmt2->close();
  }
}

// SSD 정보 저장
$ssd_caps = $_POST['ssd_capacity'] ?? [];
$ssd_qtys = $_POST['ssd_qty'] ?? [];
if (is_array($ssd_caps) && is_array($ssd_qtys)) {
  $sql2 = "INSERT INTO asset_ssd (equip_barcode, capacity, quantity) VALUES (?,?,?)";
  $stmt2 = $__db->prepare($sql2);
  if ($stmt2) {
    $cap = $qty = null;
    $stmt2->bind_param('ssi', $equip_barcode, $cap, $qty);
    $cnt = min(count($ssd_caps), count($ssd_qtys));
    for ($i = 0; $i < $cnt; $i++) {
      $cap = trim($ssd_caps[$i]);
      $qty = (int)$ssd_qtys[$i];
      if ($cap === '' || $qty <= 0) continue;
      $stmt2->execute();
    }
    $stmt2->close();
  }
}

// HDD 정보 저장
$hdd_caps = $_POST['hdd_capacity'] ?? [];
$hdd_qtys = $_POST['hdd_qty'] ?? [];
if (is_array($hdd_caps) && is_array($hdd_qtys)) {
  $sql2 = "INSERT INTO asset_hdd (equip_barcode, capacity, quantity) VALUES (?,?,?)";
  $stmt2 = $__db->prepare($sql2);
  if ($stmt2) {
    $cap = $qty = null;
    $stmt2->bind_param('ssi', $equip_barcode, $cap, $qty);
    $cnt = min(count($hdd_caps), count($hdd_qtys));
    for ($i = 0; $i < $cnt; $i++) {
      $cap = trim($hdd_caps[$i]);
      $qty = (int)$hdd_qtys[$i];
      if ($cap === '' || $qty <= 0) continue;
      $stmt2->execute();
    }
    $stmt2->close();
  }
}

header('Location: ../tdems_main.php?msg=' . urlencode('등록 완료')); exit;
