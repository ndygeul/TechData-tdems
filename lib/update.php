<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/user.php';

csrf_check_or_die();

// DB 핸들
$__db = null;
if (isset($mysqli) && $mysqli instanceof mysqli) { $__db = $mysqli; }
elseif (isset($conn) && $conn instanceof mysqli) { $__db = $conn; }
if (!$__db) { http_response_code(500); exit('DB connection is not initialized.'); }

// 입력
$asset_id         = (int)($_POST['asset_id'] ?? 0);
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
$asset_history_edit = isset($_POST['asset_history_edit']) ? trim($_POST['asset_history_edit']) : null;
$history_append     = trim($_POST['history_append'] ?? '');
$updated_ip       = $_SERVER['REMOTE_ADDR'] ?? '';

if ($equip_barcode === '') { $equip_barcode = null; }
if ($hostname === '') { $hostname = null; }
if ($ip === '') { $ip = null; }

if ($asset_id <= 0) {
  header('Location: ../tdems_main.php?msg=' . urlencode('대상/필수값 오류')); exit;
}
// IPv4 검증 (빈 값 허용)
if ($ip !== null && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
  header('Location: ../tdems_detail.php?id='.$asset_id.'&msg=' . urlencode('IP 형식이 올바르지 않습니다(IPv4만 허용).')); exit;
}

// append 블록
$appendBlock = '';
if ($history_append !== '') {
  date_default_timezone_set('Asia/Seoul');
  $ts = date('Y-m-d H:i:s'); $user = ip_to_user($updated_ip);
  $appendBlock = "\n\n-----\n[$ts] $user\n" . $history_append;
}

// 기존 MEMORY/SSD/HDD 정보 제거 (equip_barcode 변경 대비)
$old_barcode = '';
$chk = $__db->prepare("SELECT equip_barcode FROM asset WHERE asset_id = ? LIMIT 1");
if ($chk) {
  $chk->bind_param('i', $asset_id);
  if ($chk->execute()) {
    $chk->bind_result($old_barcode);
    $chk->fetch();
  }
  $chk->close();
}
if ($old_barcode !== null && $old_barcode !== '') {
  foreach (['asset_memory','asset_ssd','asset_hdd'] as $tbl) {
    $del = $__db->prepare("DELETE FROM {$tbl} WHERE equip_barcode = ?");
    if ($del) { $del->bind_param('s', $old_barcode); $del->execute(); $del->close(); }
  }
}

// 분기
if ($asset_history_edit !== null) {
  $final_history = $asset_history_edit . ($appendBlock !== '' ? $appendBlock : '');
  $sql = "UPDATE asset
          SET equip_barcode=?, hostname=?, ip=?, rack_location=?, mounted_location=?,
              asset_type=?, own_team=?, standard_service=?, unit_service=?, manufacturer=?, model_name=?, serial_number=?, receipt_ym=?,
              os=?, cpu_type=?, cpu_qty=?, cpu_core=?, swap_size=?,
              ma=?, status=?, purpose=?, purpose_detail=?, facility_status=?,
              asset_history=?, updated_at=NOW(), updated_ip=?
          WHERE asset_id=?";
  $stmt = $__db->prepare($sql);
  if (!$stmt) { header('Location: ../tdems_detail.php?id='.$asset_id.'&msg=' . urlencode('DB 오류(prepare): '.$__db->error)); exit; }
  $stmt->bind_param(
    'sssssssssssssssiissssssssi',
    $equip_barcode, $hostname, $ip, $rack_location, $mounted_location,
    $asset_type, $own_team, $standard_service, $unit_service, $manufacturer, $model_name, $serial_number, $receipt_ym,
    $os, $cpu_type, $cpu_qty, $cpu_core, $swap_size,
    $ma, $status, $purpose, $purpose_detail, $facility_status,
    $final_history, $updated_ip, $asset_id
  );
} elseif ($appendBlock !== '') {
  $sql = "UPDATE asset
          SET equip_barcode=?, hostname=?, ip=?, rack_location=?, mounted_location=?,
              asset_type=?, own_team=?, standard_service=?, unit_service=?, manufacturer=?, model_name=?, serial_number=?, receipt_ym=?,
              os=?, cpu_type=?, cpu_qty=?, cpu_core=?, swap_size=?,
              ma=?, status=?, purpose=?, purpose_detail=?, facility_status=?,
              asset_history=CONCAT(COALESCE(asset_history,''), ?),
              updated_at=NOW(), updated_ip=?
          WHERE asset_id=?";
  $stmt = $__db->prepare($sql);
  if (!$stmt) { header('Location: ../tdems_detail.php?id='.$asset_id.'&msg=' . urlencode('DB 오류(prepare): '.$__db->error)); exit; }
  $stmt->bind_param(
    'sssssssssssssssiissssssssi',
    $equip_barcode, $hostname, $ip, $rack_location, $mounted_location,
    $asset_type, $own_team, $standard_service, $unit_service, $manufacturer, $model_name, $serial_number, $receipt_ym,
    $os, $cpu_type, $cpu_qty, $cpu_core, $swap_size,
    $ma, $status, $purpose, $purpose_detail, $facility_status,
    $appendBlock, $updated_ip, $asset_id
  );
} else {
  $sql = "UPDATE asset
          SET equip_barcode=?, hostname=?, ip=?, rack_location=?, mounted_location=?,
              asset_type=?, own_team=?, standard_service=?, unit_service=?, manufacturer=?, model_name=?, serial_number=?, receipt_ym=?,
              os=?, cpu_type=?, cpu_qty=?, cpu_core=?, swap_size=?,
              ma=?, status=?, purpose=?, purpose_detail=?, facility_status=?,
              updated_at=NOW(), updated_ip=?
          WHERE asset_id=?";
  $stmt = $__db->prepare($sql);
  if (!$stmt) { header('Location: ../tdems_detail.php?id='.$asset_id.'&msg=' . urlencode('DB 오류(prepare): '.$__db->error)); exit; }
  $stmt->bind_param(
    'sssssssssssssssiisssssssi',
    $equip_barcode, $hostname, $ip, $rack_location, $mounted_location,
    $asset_type, $own_team, $standard_service, $unit_service, $manufacturer, $model_name, $serial_number, $receipt_ym,
    $os, $cpu_type, $cpu_qty, $cpu_core, $swap_size,
    $ma, $status, $purpose, $purpose_detail, $facility_status,
    $updated_ip, $asset_id
  );
}

if (!$stmt->execute()) {
  $msg = 'DB 오류(execute): ' . ($stmt->error ?: $__db->error);
  $stmt->close();
  header('Location: ../tdems_detail.php?id='.$asset_id.'&msg=' . urlencode($msg)); exit;
}
$stmt->close();

// MEMORY/SSD/HDD 정보 저장 (설비바코드가 있는 경우만)
if ($equip_barcode !== null) {
  // MEMORY
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

  // SSD
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

  // HDD
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
}

header('Location: ../tdems_detail.php?id='.$asset_id.'&msg=' . urlencode('수정 완료')); exit;
