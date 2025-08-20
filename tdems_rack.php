<?php
require __DIR__ . '/config/db.php';

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
function loadRackAssets(mysqli $mysqli, string $rack): array {
  $sql = 'SELECT asset_id, hostname, mounted_location, equip_barcode, ip, asset_type, manufacturer, model_name, serial_number '
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
    if (!empty($row['hostname']))      $parts[] = '호스트명: '   . $row['hostname'];
    if (!empty($row['ip']))            $parts[] = 'IP: '         . $row['ip'];
    if (!empty($row['asset_type']))    $parts[] = '종류: '       . $row['asset_type'];
    if (!empty($row['manufacturer']))  $parts[] = '제조사: '     . $row['manufacturer'];
    if (!empty($row['model_name']))    $parts[] = '모델명: '     . $row['model_name'];
    if (!empty($row['serial_number'])) $parts[] = 'S/N: '       . $row['serial_number'];
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
