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
    <section class="card">
      <h2 style="text-align:center;margin-top:0;"><?= h($rack) ?></h2>
      <table class="table rack-table">
        <tbody>
          <?php for ($u = 42; $u >= 1; $u--): ?>
            <?php if (array_key_exists($u, $assets) && $assets[$u] === false): ?>
              <tr>
                <th><?= $u ?>U</th>
              </tr>
              <?php continue; ?>
            <?php endif; ?>
            <?php $row = $assets[$u] ?? null; ?>
            <tr class="<?= ($row && $row['asset_id'] == $assetId) ? 'rack-selected' : '' ?>">
              <th><?= $u ?>U</th>
              <?php if ($row): ?>
                <td rowspan="<?= $row['rowspan'] ?>"><?= h($row['hostname']) ?></td>
              <?php else: ?>
                <td></td>
              <?php endif; ?>
            </tr>
          <?php endfor; ?>
        </tbody>
      </table>
    </section>
  </main>
</body>
</html>
