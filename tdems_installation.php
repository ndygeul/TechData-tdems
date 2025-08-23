<?php
require __DIR__ . '/config/db.php';

if ($conn instanceof mysqli) {
    header('Location: tdems_main.php');
    exit;
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>TDEMS 설치</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <main class="container" style="max-width:480px;margin-top:40px;">
    <?php if ($msg): ?>
      <div class="alert"><?= h($msg) ?></div>
    <?php endif; ?>
    <section class="card">
      <form class="form" method="post" action="lib/install.php" autocomplete="off">
        <div class="form-row">
          <label class="label" for="server">서버</label>
          <input class="input" type="text" id="server" name="server" value="<?= h($host) ?>" required>
        </div>
        <div class="form-row">
          <label class="label" for="port">포트</label>
          <input class="input" type="text" id="port" name="port" value="<?= h($port) ?>">
        </div>
        <div class="form-row">
          <label class="label" for="id">ID</label>
          <input class="input" type="text" id="id" name="id" value="<?= h($user) ?>" required>
        </div>
        <div class="form-row">
          <label class="label" for="pw">PW</label>
          <input class="input" type="password" id="pw" name="pw" value="<?= h($pass) ?>">
        </div>
        <div class="form-row">
          <label class="label" for="db">DB</label>
          <input class="input" type="text" id="db" name="db" value="<?= h($dbname) ?>" required>
        </div>
        <div class="form-actions">
          <button class="btn primary" type="submit">저장</button>
        </div>
      </form>
    </section>
  </main>
</body>
</html>