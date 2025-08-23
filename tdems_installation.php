<?php
function h($s) {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$configPath = __DIR__ . '/config/db.php';
$defaultHost = '172.17.0.4';
$defaultPort = 30701;
$defaultUser = 'ezk';
$defaultPass = 'dlwlzpdl';
$defaultDb   = 'tdems';

if (file_exists($configPath)) {
  require $configPath;
  $mysqli = isset($mysqli) ? $mysqli : (isset($conn) ? $conn : null);
  if ($mysqli instanceof mysqli && !$mysqli->connect_errno) {
    header('Location: tdems_main.php');
    exit;
  }
  $defaultHost = $host ?? $defaultHost;
  $defaultPort = $port ?? $defaultPort;
  $defaultUser = $user ?? $defaultUser;
  $defaultPass = $pass ?? $defaultPass;
  $defaultDb   = $dbname ?? $defaultDb;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require __DIR__ . '/lib/install.php';
  exit;
}
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
  <main class="container" style="max-width:500px;">
    <h1 style="text-align:center;">DB 설정</h1>
    <form method="post" action="">
      <div class="field">
        <label>서버</label>
        <input class="input" type="text" name="host" value="<?= h($defaultHost) ?>" required>
      </div>
      <div class="field">
        <label>포트</label>
        <input class="input" type="number" name="port" value="<?= h($defaultPort) ?>" required>
      </div>
      <div class="field">
        <label>ID</label>
        <input class="input" type="text" name="user" value="<?= h($defaultUser) ?>" required>
      </div>
      <div class="field">
        <label>PW</label>
        <input class="input" type="password" name="pass" value="<?= h($defaultPass) ?>" required>
      </div>
      <div class="field">
        <label>DB</label>
        <input class="input" type="text" name="dbname" value="<?= h($defaultDb) ?>" required>
      </div>
      <div style="margin-top:20px;text-align:center;">
        <button class="btn primary" type="submit">설치</button>
      </div>
    </form>
  </main>
</body>
</html>