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
  $stmt = $mysqli->prepare('SELECT asset_id, hostname, mounted_location FROM asset WHERE rack_location = ? AND del_yn = "N"');
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

// Ensure racks are displayed with gaps for missing numbers
$numbers = [];
foreach (array_keys($rackAssets) as $name) {
  if (preg_match('/^' . preg_quote($prefix, '/') . '(\d+)/i', $name, $m)) {
    $numbers[] = (int)$m[1];
  }
}
sort($numbers);
$ordered = [];
if ($numbers) {
  $min = min($numbers);
  $max = max($numbers);
  for ($i = $min; $i <= $max; $i++) {
    $name = $prefix . sprintf('%02d', $i);
    $ordered[$name] = $rackAssets[$name] ?? [];
  }
}
$rackAssets = $ordered;

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
        <?php if (empty($assets)): ?>
          <div class="rack-gap"></div>
          <?php continue; ?>
        <?php endif; ?>
        <table class="rack-table">
          <thead>
            <tr><th><?= h($rackName) ?></th></tr>
          </thead>
          <tbody>
            <?php for ($u = 42; $u >= 1; $u--): ?>
              <?php if (array_key_exists($u, $assets) && $assets[$u] === false): ?>
                <tr></tr>
                <?php continue; ?>
              <?php endif; ?>
              <?php $row = $assets[$u] ?? null; ?>
              <tr class="<?= ($row && $row['asset_id'] == $assetId) ? 'rack-selected' : '' ?>">
                <?php if ($row): ?>
                  <td rowspan="<?= $row['rowspan'] ?>"><?= h($row['hostname']) ?></td>
                <?php else: ?>
                  <td><?= sprintf('%02d', $u) ?>U</td>
                <?php endif; ?>
              </tr>
            <?php endfor; ?>
          </tbody>
        </table>
      <?php endforeach; ?>
    </div>
  </main>
</body>
</html>
