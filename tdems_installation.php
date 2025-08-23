<?php
require __DIR__ . '/config/db.php';
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
      <form method="post" action="lib/install.php" style="display:flex;flex-direction:column;gap:8px;">
        <label>서버
          <input class="input" type="text" name="server" value="<?= h($host) ?>" required>
        </label>
        <label>포트
          <input class="input" type="text" name="port" value="<?= h($port) ?>">
        </label>
        <label>ID
          <input class="input" type="text" name="id" value="<?= h($user) ?>" required>
        </label>
        <label>PW
          <input class="input" type="password" name="pw" value="<?= h($pass) ?>">
        </label>
        <label>DB
          <input class="input" type="text" name="db" value="<?= h($dbname) ?>" required>
        </label>
        <button class="btn primary" type="submit">저장</button>
      </form>
    </section>
  </main>
</body>
</html>