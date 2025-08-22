<?php
// lib/delete.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/csrf.php';

csrf_check_or_die();

// DB 핸들
$db = null;
if (isset($mysqli) && $mysqli instanceof mysqli) { $db = $mysqli; }
elseif (isset($conn) && $conn instanceof mysqli) { $db = $conn; }
if (!$db) { http_response_code(500); exit('DB connection is not initialized.'); }

$mode   = isset($_POST['mode']) ? $_POST['mode'] : '';
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
$ip     = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';

function redirect_with($url, $msg) {
  header('Location: ' . $url . (strpos($url,'?')!==false ? '&' : '?') . 'msg=' . urlencode($msg));
  exit;
}

if ($reason === '') {
  redirect_with('../tdems_main.php', '삭제 사유가 필요합니다.');
}

if ($mode === 'bulk') {
  // ids 정규화 (정수 변환, 중복 제거, 0 제거)
  $ids_raw = isset($_POST['ids']) ? $_POST['ids'] : array();
  $map = array();
  foreach ($ids_raw as $v) {
    $iv = (int)$v;
    if ($iv > 0) { $map[$iv] = true; }
  }
  $ids = array_keys($map);
  if (count($ids) === 0) {
    redirect_with('../tdems_main.php', '선택된 항목이 없습니다.');
  }

  // 트랜잭션
  $db->begin_transaction();
  try {
    $sql = "UPDATE asset
              SET del_yn='Y',
                  deleted_at=NOW(),
                  deleted_reason=?,
                  updated_at=NOW(),
                  updated_ip=?
            WHERE asset_id=? AND del_yn='N'";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
      throw new Exception($db->error ? $db->error : 'prepare failed', (int)$db->errno);
    }

    $affected = 0;
    foreach ($ids as $id) {
      $stmt->bind_param('ssi', $reason, $ip, $id);
      if (!$stmt->execute()) {
        $msg  = $stmt->error;
        $stmt->close();
        $db->rollback();
        redirect_with('../tdems_main.php', 'DB 오류: ' . ($msg ? $msg : '오류'));
      }
      $affected += $stmt->affected_rows;
    }
    $stmt->close();

    $db->commit();
    redirect_with('../tdems_main.php', '삭제 완료(' . $affected . '건)');
  } catch (Exception $e) {
    $db->rollback();
    redirect_with('../tdems_main.php', 'DB 오류: ' . ($e->getMessage() ? $e->getMessage() : '오류'));
  }

} else {
  // 단건 삭제
  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  if ($id <= 0) {
    redirect_with('../tdems_main.php', '대상이 없습니다.');
  }

  $db->begin_transaction();
  try {
    $sql = "UPDATE asset
              SET del_yn='Y',
                  deleted_at=NOW(),
                  deleted_reason=?,
                  updated_at=NOW(),
                  updated_ip=?
            WHERE asset_id=? AND del_yn='N'";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
      throw new Exception($db->error ? $db->error : 'prepare failed', (int)$db->errno);
    }
    $stmt->bind_param('ssi', $reason, $ip, $id);
    if (!$stmt->execute()) {
      $msg  = $stmt->error;
      $stmt->close();
      $db->rollback();
      redirect_with('../tdems_main.php', 'DB 오류: ' . ($msg ? $msg : '오류'));
    }
    $affected = $stmt->affected_rows;
    $stmt->close();

    $db->commit();

    $back = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../tdems_main.php';
    redirect_with($back, ($affected > 0 ? '삭제 완료(1건)' : '이미 삭제된 항목입니다.'));
  } catch (Exception $e) {
    $db->rollback();
    redirect_with('../tdems_main.php', 'DB 오류: ' . ($e->getMessage() ? $e->getMessage() : '오류'));
  }
}

