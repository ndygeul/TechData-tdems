<?php
// lib/read.php
// - PHP 7 호환 (nullsafe ?-> 미사용)
// - mysqli + config/db.php
// - 테이블명: asset
// - include_deleted=1 이면 삭제건까지 조회

require_once __DIR__ . '/../config/db.php';

// DB 핸들 결정 ($mysqli 또는 $conn)
$__db = null;
if (isset($mysqli) && $mysqli instanceof mysqli) { $__db = $mysqli; }
elseif (isset($conn) && $conn instanceof mysqli) { $__db = $conn; }

if (!$__db) {
    http_response_code(500);
    exit('DB connection is not initialized. Provide $mysqli or $conn in config/db.php');
}

// 파라미터
$asset_id        = (int)($_GET['id'] ?? $_POST['id'] ?? $_POST['asset_id'] ?? 0);
$include_deleted = (int)($_GET['include_deleted'] ?? 0);

// 조회 컬럼(명시적으로 작성: get_result 미사용 폴백용)
$cols = "asset_id,equip_barcode,hostname,ip,asset_type,own_team,standard_service,unit_service,rack_location,mounted_location,manufacturer,model_name,serial_number,receipt_ym,os,cpu_type,cpu_qty,cpu_core,swap_size,ma,status,purpose,purpose_detail,facility_status,asset_history,created_at,updated_at,created_ip,updated_ip,del_yn,deleted_at,deleted_reason";

$asset = null;
if ($asset_id > 0) {
    $sql = "SELECT $cols FROM asset WHERE asset_id = ?"
         . ($include_deleted === 1 ? "" : " AND del_yn = 'N'")
         . " LIMIT 1";

    $stmt = $__db->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('i', $asset_id);
        if ($stmt->execute()) {

            // mysqlnd가 있으면 get_result 사용
            if (function_exists('mysqli_stmt_get_result')) {
                $res = mysqli_stmt_get_result($stmt);
                if ($res) {
                    $asset = $res->fetch_assoc() ?: null;
                    $res->free();
                }
            } else {
                // mysqlnd 미설치 환경 폴백: bind_result 사용
                $stmt->bind_result(
                    $f_asset_id, $f_equip_barcode, $f_hostname, $f_ip, $f_asset_type,
                    $f_own_team, $f_standard_service, $f_unit_service,
                    $f_rack_location, $f_mounted_location, $f_manufacturer,
                    $f_model_name, $f_serial_number, $f_receipt_ym, $f_os,
                    $f_cpu_type, $f_cpu_qty, $f_cpu_core, $f_swap_size,
                    $f_ma, $f_status, $f_purpose, $f_purpose_detail, $f_facility_status,
                    $f_asset_history, $f_created_at, $f_updated_at, $f_created_ip, $f_updated_ip,
                    $f_del_yn, $f_deleted_at, $f_deleted_reason
                );
                if ($stmt->fetch()) {
                    $asset = [
                        'asset_id'        => $f_asset_id,
                        'equip_barcode'   => $f_equip_barcode,
                        'hostname'        => $f_hostname,
                        'ip'              => $f_ip,
                        'asset_type'      => $f_asset_type,
                        'own_team'        => $f_own_team,
                        'standard_service'=> $f_standard_service,
                        'unit_service'    => $f_unit_service,
                        'rack_location'   => $f_rack_location,
                        'mounted_location'=> $f_mounted_location,
                        'manufacturer'    => $f_manufacturer,
                        'model_name'      => $f_model_name,
                        'serial_number'   => $f_serial_number,
                        'receipt_ym'      => $f_receipt_ym,
                        'os'              => $f_os,
                        'cpu_type'        => $f_cpu_type,
                        'cpu_qty'         => $f_cpu_qty,
                        'cpu_core'        => $f_cpu_core,
                        'swap_size'       => $f_swap_size,
                        'ma'              => $f_ma,
                        'status'          => $f_status,
                        'purpose'         => $f_purpose,
                        'purpose_detail'  => $f_purpose_detail,
                        'facility_status' => $f_facility_status,
                        'asset_history'   => $f_asset_history,
                        'created_at'      => $f_created_at,
                        'updated_at'      => $f_updated_at,
                        'created_ip'      => $f_created_ip,
                        'updated_ip'      => $f_updated_ip,
                        'del_yn'          => $f_del_yn,
                        'deleted_at'      => $f_deleted_at,
                        'deleted_reason'  => $f_deleted_reason,
                    ];
                }
            }
        }
        $stmt->close();
    }
}

$asset_mems = $asset_ssds = $asset_hdds = [];
if ($asset && isset($asset['equip_barcode'])) {
    $barcode = $asset['equip_barcode'];

    // MEMORY
    $sql = "SELECT id, capacity, quantity FROM asset_memory WHERE equip_barcode = ? ORDER BY id";
    $stmt = $__db->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('s', $barcode);
        if ($stmt->execute()) {
            if (function_exists('mysqli_stmt_get_result')) {
                $res = mysqli_stmt_get_result($stmt);
                if ($res) {
                    while ($row = $res->fetch_assoc()) { $asset_mems[] = $row; }
                    $res->free();
                }
            } else {
                $stmt->bind_result($f_id, $f_capacity, $f_quantity);
                while ($stmt->fetch()) {
                    $asset_mems[] = [
                        'id' => $f_id,
                        'capacity' => $f_capacity,
                        'quantity' => $f_quantity,
                    ];
                }
            }
        }
        $stmt->close();
    }

    // SSD
    $sql = "SELECT id, capacity, quantity FROM asset_ssd WHERE equip_barcode = ? ORDER BY id";
    $stmt = $__db->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('s', $barcode);
        if ($stmt->execute()) {
            if (function_exists('mysqli_stmt_get_result')) {
                $res = mysqli_stmt_get_result($stmt);
                if ($res) {
                    while ($row = $res->fetch_assoc()) { $asset_ssds[] = $row; }
                    $res->free();
                }
            } else {
                $stmt->bind_result($f_id, $f_capacity, $f_quantity);
                while ($stmt->fetch()) {
                    $asset_ssds[] = [
                        'id' => $f_id,
                        'capacity' => $f_capacity,
                        'quantity' => $f_quantity,
                    ];
                }
            }
        }
        $stmt->close();
    }

    // HDD
    $sql = "SELECT id, capacity, quantity FROM asset_hdd WHERE equip_barcode = ? ORDER BY id";
    $stmt = $__db->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('s', $barcode);
        if ($stmt->execute()) {
            if (function_exists('mysqli_stmt_get_result')) {
                $res = mysqli_stmt_get_result($stmt);
                if ($res) {
                    while ($row = $res->fetch_assoc()) { $asset_hdds[] = $row; }
                    $res->free();
                }
            } else {
                $stmt->bind_result($f_id, $f_capacity, $f_quantity);
                while ($stmt->fetch()) {
                    $asset_hdds[] = [
                        'id' => $f_id,
                        'capacity' => $f_capacity,
                        'quantity' => $f_quantity,
                    ];
                }
            }
        }
        $stmt->close();
    }
}

// 페이지 전역에서 $asset, $asset_mems, $asset_ssds, $asset_hdds 사용 가능
