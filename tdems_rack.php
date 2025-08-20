<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/config/user.php';

$mysqli = isset($mysqli) ? $mysqli : (isset($conn) ? $conn : null);
if (!$mysqli instanceof mysqli) {
  http_response_code(500);
  echo 'DB not initialized';
  exit;
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$rack = trim($_GET['rack'] ?? '');
$assetId = (int)($_GET['id'] ?? 0);

if ($rack === '') {
  http_response_code(400);
  echo 'rack parameter required';
  exit;
}

/**
 * Build an array of rack units and placed assets for a given rack.
 * Returns an associative array keyed by rack unit number (top unit index).
 */
if (!function_exists('fmt_dt')) {
  date_default_timezone_set('Asia/Seoul');
  function fmt_dt($ts) { return $ts ? date('Y-m-d H:i:s', strtotime($ts)) : ''; }
}

function loadRackAssets(mysqli $mysqli, string $rack): array {
  $sql = 'SELECT asset_id, hostname, mounted_location, equip_barcode, ip, asset_type, manufacturer, model_name, serial_number,'
       . ' rack_location, receipt_ym, os, cpu_type, cpu_qty, cpu_core, swap_size, ma, status, facility_status, purpose,'
       . ' purpose_detail, own_team, standard_service, unit_service, created_at, updated_at, created_ip, updated_ip '
       . 'FROM asset WHERE rack_location = ? AND del_yn = "N"';
  $stmt = $mysqli->prepare($sql);
  $stmt->bind_param('s', $rack);
  $stmt->execute();
  $res = $stmt->get_result();
  $assets = [];
  while ($row = $res->fetch_assoc()) {
    $loc = strtoupper(trim($row['mounted_location'] ?? ''));
    if ($loc === 'ALL') {
      $bottom = 1;
      $top = 42;
    } elseif (preg_match('/^(\d{2})(?:-(\d{2}))?$/', $loc, $m)) {
      $start = (int)$m[1];
      $end = isset($m[2]) ? (int)$m[2] : $start;
      $bottom = min($start, $end);
      $top = max($start, $end);
    } else {
      continue;
    }

    // Tooltip text (multi-line) assembled from asset details
    $parts = [];
    if (!empty($row['equip_barcode'])) $parts[] = '설비바코드: ' . $row['equip_barcode'];
    $parts[] = '랙/장착: ' . $row['rack_location'] . ' ' . $row['mounted_location'];
    if (!empty($row['hostname']))      $parts[] = '호스트명: '   . $row['hostname'];
    if (!empty($row['ip']))            $parts[] = 'IP: '         . $row['ip'];
    if (!empty($row['asset_type']))    $parts[] = '종류: '       . $row['asset_type'];
    if (!empty($row['manufacturer']))  $parts[] = '제조사: '     . $row['manufacturer'];
    if (!empty($row['model_name']))    $parts[] = '모델명: '     . $row['model_name'];
    if (!empty($row['serial_number'])) $parts[] = 'S/N: '       . $row['serial_number'];
    if (!empty($row['receipt_ym']))    $parts[] = '입고년월: '   . $row['receipt_ym'];
    if (!empty($row['os']))            $parts[] = 'OS: '        . $row['os'];
    if (!empty($row['cpu_type']))      $parts[] = 'CPU종류: '    . $row['cpu_type'];
    if (!empty($row['cpu_qty']))       $parts[] = 'CPU수량: '    . $row['cpu_qty'];
    if (!empty($row['cpu_core']))      $parts[] = 'CPU코어: '    . $row['cpu_core'];
    if (!empty($row['swap_size']))     $parts[] = 'SWAP: '       . $row['swap_size'];

    // MEMORY
    $barcode = $row['equip_barcode'];
    if ($barcode !== '') {
      $mem = [];
      $stmtMem = $mysqli->prepare('SELECT capacity, quantity FROM asset_memory WHERE equip_barcode = ? ORDER BY id');
      if ($stmtMem) {
        $stmtMem->bind_param('s', $barcode);
        $stmtMem->execute();
        $resMem = $stmtMem->get_result();
        while ($m = $resMem->fetch_assoc()) {
          $total = (int)$m['capacity'] * (int)$m['quantity'];
          $mem[] = $m['capacity'] . ' x ' . $m['quantity'] . ' = ' . $total;
        }
        $stmtMem->close();
      }
      if ($mem) $parts[] = 'MEMORY: ' . implode(', ', $mem);

      // SSD
      $ssd = [];
      $stmtSsd = $mysqli->prepare('SELECT capacity, quantity FROM asset_ssd WHERE equip_barcode = ? ORDER BY id');
      if ($stmtSsd) {
        $stmtSsd->bind_param('s', $barcode);
        $stmtSsd->execute();
        $resSsd = $stmtSsd->get_result();
        while ($s = $resSsd->fetch_assoc()) {
          $ssd[] = $s['capacity'] . ' x ' . $s['quantity'];
        }
        $stmtSsd->close();
      }
      if ($ssd) $parts[] = 'SSD: ' . implode(', ', $ssd);

      // HDD
      $hdd = [];
      $stmtHdd = $mysqli->prepare('SELECT capacity, quantity FROM asset_hdd WHERE equip_barcode = ? ORDER BY id');
      if ($stmtHdd) {
        $stmtHdd->bind_param('s', $barcode);
        $stmtHdd->execute();
        $resHdd = $stmtHdd->get_result();
        while ($h = $resHdd->fetch_assoc()) {
          $hdd[] = $h['capacity'] . ' x ' . $h['quantity'];
        }
        $stmtHdd->close();
      }
      if ($hdd) $parts[] = 'HDD: ' . implode(', ', $hdd);
    }

    if (!empty($row['ma']))             $parts[] = 'MA: '          . $row['ma'];
    if (!empty($row['status']))         $parts[] = '상태: '         . $row['status'];
    if (!empty($row['facility_status']))$parts[] = '설비상태: '     . $row['facility_status'];
    if (!empty($row['purpose']))        $parts[] = '용도: '         . $row['purpose'];
    if (!empty($row['purpose_detail'])) $parts[] = '상세용도: '     . $row['purpose_detail'];
    if (!empty($row['own_team']))       $parts[] = '자산보유팀: '   . $row['own_team'];
    if (!empty($row['standard_service']))$parts[] = '표준서비스: '  . $row['standard_service'];
    if (!empty($row['unit_service']))   $parts[] = '단위서비스: '   . $row['unit_service'];
    if (!empty($row['created_at']))     $parts[] = '최초 등록일시: ' . fmt_dt($row['created_at']);
    $createdBy = ip_to_user($row['created_ip'] ?? '');
    if ($createdBy !== '')              $parts[] = '등록자: '       . $createdBy;
    if (!empty($row['updated_at']))     $parts[] = '최종 수정일시: ' . fmt_dt($row['updated_at']);
    $updatedBy = ip_to_user($row['updated_ip'] ?? '');
    if ($updatedBy !== '')              $parts[] = '수정자: '       . $updatedBy;

    $row['tooltip'] = implode("\n", $parts);

    $row['rowspan'] = $top - $bottom + 1;
    $assets[$top] = $row;
    for ($u = $bottom; $u < $top; $u++) {
      $assets[$u] = false;
    }
  }
  $stmt->close();
  return $assets;
}

// Determine rack group prefix (letters at the start, e.g. AC from AC04).
if (preg_match('/^([A-Za-z]+)/', $rack, $m)) {
  $prefix = strtoupper($m[1]);
} else {
  $prefix = $rack;
}

$like = $prefix . '%';
$stmt = $mysqli->prepare('SELECT DISTINCT rack_location FROM asset WHERE rack_location LIKE ? AND del_yn = "N" ORDER BY rack_location');
$stmt->bind_param('s', $like);
$stmt->execute();
$res = $stmt->get_result();
$rackAssets = [];
while ($row = $res->fetch_assoc()) {
  $r = $row['rack_location'];
  $rackAssets[$r] = loadRackAssets($mysqli, $r);
}
$stmt->close();

?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>랙 정보 - <?= h($rack) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <header class="topbar">
    <h1 class="title">랙/장착 정보</h1>
    <nav class="actions">
      <a class="btn" href="tdems_main.php">자산 목록</a>
    </nav>
  </header>

  <main class="container narrow">
    <div class="rack-list">
      <?php foreach ($rackAssets as $rackName => $assets): ?>
        <section class="card">
          <h2 style="text-align:center;margin-top:0;"><?= h($rackName) ?></h2>
          <table class="table rack-table">
            <tbody>
              <?php for ($u = 42; $u >= 1; $u--): ?>
                <?php if (array_key_exists($u, $assets) && $assets[$u] === false): ?>
                  <tr>
                    <th><?= sprintf('%02d', $u) ?>U</th>
                  </tr>
                  <?php continue; ?>
                <?php endif; ?>
                <?php $row = $assets[$u] ?? null; ?>
                <tr class="<?= ($row && $row['asset_id'] == $assetId) ? 'rack-selected' : '' ?>">
                  <th><?= sprintf('%02d', $u) ?>U</th>
                  <?php if ($row): ?>
                    <td rowspan="<?= $row['rowspan'] ?>" title="<?= h($row['tooltip']) ?>"><?= h($row['hostname']) ?></td>
                  <?php else: ?>
                    <td></td>
                  <?php endif; ?>
                </tr>
              <?php endfor; ?>
            </tbody>
          </table>
        </section>
      <?php endforeach; ?>
    </div>
  </main>
</body>
</html>
