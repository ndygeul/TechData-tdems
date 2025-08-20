<?php
require __DIR__ . '/config/db.php';

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$rack = trim($_GET['rack'] ?? '');
$assetId = (int)($_GET['id'] ?? 0);

if ($rack === '') {
  http_response_code(400);
  echo 'rack parameter required';
  exit;
}

if (!$mysqli instanceof mysqli) {
  http_response_code(500);
  echo 'DB not initialized';
  exit;
}

$stmt = $mysqli->prepare('SELECT asset_id, hostname, mounted_location FROM asset WHERE rack_location = ? AND del_yn = "N"');
$stmt->bind_param('s', $rack);
$stmt->execute();
$res = $stmt->get_result();
$assets = [];
while ($row = $res->fetch_assoc()) {
  if (preg_match('/(\d+)/', $row['mounted_location'] ?? '', $m)) {
    $u = (int)$m[1];
    $assets[$u] = $row;
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
            <?php $row = $assets[$u] ?? null; ?>
            <tr class="<?= ($row && $row['asset_id'] == $assetId) ? 'rack-selected' : '' ?>">
              <th><?= $u ?>U</th>
              <td>
                <?php if ($row): ?>
                  <?= h($row['hostname']) ?>
                  <?php if ($row['asset_id'] == $assetId): ?>
                    // 선택 장비
                  <?php else: ?>
                    // 동일한 랙에 장착된 장비
                  <?php endif; ?>
                <?php endif; ?>
              </td>
            </tr>
          <?php endfor; ?>
        </tbody>
      </table>
    </section>
  </main>
</body>
</html>
