<?php
require __DIR__ . '/config/db.php';
if (!isset($conn) || !$conn instanceof mysqli) {
  header('Location: tdems_installation.php');
  exit;
}
require __DIR__ . '/config/csrf.php';
require __DIR__ . '/lib/list.php';

function h($s)
{
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
function val($arr, $k, $d = '')
{
  return isset($arr[$k]) ? $arr[$k] : $d;
}

$mysqli = $conn;

/* ===== 목록/페이지 ===== */
$params = get_search_params();
extract($params);

list($whereSql, $types, $bindParams) = build_where_clause($field, $q, $include);
$total = count_assets($mysqli, $whereSql, $types, $bindParams);
list($per, $page, $pages, $offset) = paginate($total, $per, $page);
$list = fetch_asset_list($mysqli, $whereSql, $sort, $dir, $per, $offset, $types, $bindParams);

/* ===== CSRF ===== */
$csrf = csrf_token();

/* ===== 유틸 ===== */
function build_qs($extra = [])
{
  $base = $_GET;
  foreach ($extra as $k => $v) {
    $base[$k] = $v;
  }
  return http_build_query($base);
}

function sort_link($field, $label)
{
  global $sort, $dir;
  $currentDir = strtolower($dir) === 'asc' ? 'asc' : 'desc';
  $nextDir = ($sort === $field && $currentDir === 'asc') ? 'desc' : 'asc';
  $arrow = ($sort === $field) ? ($currentDir === 'asc' ? '▲' : '▼') : '';
  $qs = build_qs(['sort' => $field, 'dir' => $nextDir, 'page' => 1]);
  return '<a href="tdems_main.php?' . h($qs) . '">' . $label . $arrow . '</a>';
}
?>
<!DOCTYPE html>
<html lang="ko">

<head>
  <meta charset="UTF-8">
  <title>TDEMS : TechData Equipment Management System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="css/style.css">
  <script src="js/app.js" defer></script>
</head>

<body>
  <header class="topbar">
    <h1 class="title">자산 목록</h1>
    <nav class="actions">
      <a class="btn primary" href="tdems_write.php">등록</a>
    </nav>
  </header>

  <main class="container">
    <?php if (!empty($_GET['msg'])): ?>
      <div class="alert"><?= h($_GET['msg']) ?></div>
    <?php endif; ?>

    <!-- 검색/옵션: GET 폼 -->
    <section class="card">
      <form class="search-form" method="get" action="tdems_main.php" style="align-items:center;">
        <select class="select" name="field">
          <option value="all" <?= $field === 'all' ? 'selected' : '' ?>>전체</option>
          <option value="equip_barcode" <?= $field === 'equip_barcode' ? 'selected' : '' ?>>설비바코드</option>
          <option value="hostname" <?= $field === 'hostname' ? 'selected' : '' ?>>호스트명</option>
          <option value="ip" <?= $field === 'ip' ? 'selected' : '' ?>>IP</option>
          <option value="asset_type" <?= $field === 'asset_type' ? 'selected' : '' ?>>종류</option>
          <option value="ma" <?= $field === 'ma' ? 'selected' : '' ?>>MA</option>
          <option value="status" <?= $field === 'status' ? 'selected' : '' ?>>상태</option>
          <option value="purpose" <?= $field === 'purpose' ? 'selected' : '' ?>>용도</option>
          <option value="facility_status" <?= $field === 'facility_status' ? 'selected' : '' ?>>설비상태</option>
          <option value="own_team" <?= $field === 'own_team' ? 'selected' : '' ?>>자산보유팀</option>
          <option value="standard_service" <?= $field === 'standard_service' ? 'selected' : '' ?>>표준서비스</option>
          <option value="unit_service" <?= $field === 'unit_service' ? 'selected' : '' ?>>단위서비스</option>
          <option value="manufacturer" <?= $field === 'manufacturer' ? 'selected' : '' ?>>제조사</option>
          <option value="os" <?= $field === 'os' ? 'selected' : '' ?>>OS</option>
        </select>

        <input class="input" type="text" name="q" placeholder="검색어 입력" value="<?= h($q) ?>">

        <select class="select" name="per" title="페이지 당">
          <option value="10" <?= $per === 10 ? 'selected' : '' ?>>10/페이지</option>
          <option value="20" <?= $per === 20 ? 'selected' : '' ?>>20/페이지</option>
          <option value="30" <?= $per === 30 ? 'selected' : '' ?>>30/페이지</option>
          <option value="50" <?= $per === 50 ? 'selected' : '' ?>>50/페이지</option>
          <option value="100" <?= $per === 100 ? 'selected' : '' ?>>100/페이지</option>
          <option value="200" <?= $per === 200 ? 'selected' : '' ?>>200/페이지</option>
          <option value="300" <?= $per === 300 ? 'selected' : '' ?>>300/페이지</option>
          <option value="500" <?= $per === 500 ? 'selected' : '' ?>>500/페이지</option>
          <option value="1000" <?= $per === 1000 ? 'selected' : '' ?>>1000/페이지</option>
        </select>

        <label style="display:inline-flex;align-items:center;gap:6px;">
          <input type="checkbox" name="include_deleted" value="1" <?= $include ? 'checked' : '' ?>> 삭제 포함
        </label>

        <button class="btn" type="submit">검색</button>
        <a class="btn ghost" href="tdems_main.php">초기화</a>
      </form>

      <!-- 선택 삭제: POST 폼 (검색 폼과 분리) -->
      <div style="display:flex;justify-content:flex-end;margin-top:6px;">
        <form id="bulkDeleteForm" method="post" action="lib/delete.php"
          style="display:flex;gap:8px;align-items:center;">
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
          <input type="hidden" name="mode" value="bulk">
          <input type="hidden" id="bulkReason" name="reason" value="">
          <span id="bulkIds"></span>
          <button class="btn danger" type="button" id="bulkDeleteBtn">선택 삭제</button>
        </form>
      </div>
    </section>

    <!-- 결과 테이블 -->
    <section class="card" style="margin-top:10px;">
      <!-- ★ 총 건수 표시 -->
      <div class="card-header" style="justify-content:space-between;">
        <div class="label">총 <?= number_format($total) ?>건</div>
        <a class="badge" href="tdems_export.php?<?= h(build_qs()) ?>">엑셀 추출</a>
      </div>

      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th style="width:44px;"><input type="checkbox" id="checkAll"></th>
              <th><?= sort_link('equip_barcode', '설비바코드') ?></th>
              <th><?= sort_link('rack', '랙/장착') ?></th>
              <th><?= sort_link('hostname', '호스트명') ?></th>
              <th><?= sort_link('ip', 'IP') ?></th>
              <th><?= sort_link('asset_type', '종류') ?></th>
              <th><?= sort_link('manufacturer', '제조사') ?></th>
              <th><?= sort_link('model_name', '모델명') ?></th>
              <th><?= sort_link('os', 'OS') ?></th>
              <th>MEMORY</th>
              <th>SSD</th>
              <th>HDD</th>
              <th><?= sort_link('ma', 'MA') ?></th>
              <th><?= sort_link('status', '상태') ?></th>
              <th><?= sort_link('facility_status', '설비상태') ?></th>
              <th><?= sort_link('purpose', '용도') ?></th>
              <th><?= sort_link('own_team', '자산보유팀') ?></th>
              <th><?= sort_link('standard_service', '표준서비스') ?></th>
              <th><?= sort_link('unit_service', '단위서비스') ?></th>
              <th style="width:140px;">작업</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$list): ?>
              <tr>
                <td colspan="20" class="empty">데이터가 없습니다.</td>
              </tr>
              <?php else: foreach ($list as $row):
                $isDeleted = ($row['del_yn'] === 'Y');
                $id = (int)$row['asset_id'];
                $barcode = trim($row['equip_barcode']);
                $barcodeUrl = '/ezk/barcode/barcode_generator.php?barcode=' . rawurlencode($barcode);
                $detailUrl  = 'tdems_detail.php?id=' . $id . ($isDeleted ? '&include_deleted=1' : '');
              ?>
                <tr<?= $isDeleted ? ' style="opacity:.72;"' : '' ?>>
                  <td>
                    <input type="checkbox" class="row-check" value="<?= $id ?>"
                      <?= $isDeleted ? 'disabled' : '' ?>>
                  </td>
                  <td>
                    <?php if ($barcode !== ''): ?>
                      <a class="barcode-link" href="<?= h($barcodeUrl) ?>"><code
                          class="mono"><?= h($barcode) ?></code></a>
                    <?php else: ?>
                      <code class="mono">-</code>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php $rackLoc = trim($row['rack_location'] ?? '');
                      $mountLoc = trim($row['mounted_location'] ?? '');
                      if ($rackLoc !== ''): ?>
                      <a href="tdems_rack.php?rack=<?= urlencode($rackLoc) ?>&id=<?= $id ?>">
                        <?= h(trim($rackLoc . ' ' . $mountLoc)) ?>
                      </a>
                    <?php else: ?>
                      <?= h(trim($rackLoc . ' ' . $mountLoc)) ?>
                    <?php endif; ?>
                  </td>
                  <td><a href="<?= h($detailUrl) ?>"><?= h($row['hostname'] ?? '') ?></a></td>
                  <td><?= h($row['ip'] ?? '') ?></td>
                  <td><?= h($row['asset_type'] ?? '') ?></td>
                  <td><?= h($row['manufacturer'] ?? '') ?></td>
                  <td><?= h($row['model_name'] ?? '') ?></td>
                  <td><?= h($row['os'] ?? '') ?></td>
                  <td><?= $row['mem_list'] ? nl2br(h($row['mem_list'])) : '' ?></td>
                  <td><?= $row['ssd_list'] ? nl2br(h($row['ssd_list'])) : '' ?></td>
                  <td><?= $row['hdd_list'] ? nl2br(h($row['hdd_list'])) : '' ?></td>
                  <td><?= h($row['ma'] ?? '') ?></td>
                  <td><?= h($row['status'] ?? '') ?></td>
                  <td><?= h($row['facility_status'] ?? '') ?></td>
                  <td><?= h($row['purpose'] ?? '') ?></td>
                  <td><?= h($row['own_team'] ?? '') ?></td>
                  <td><?= h($row['standard_service'] ?? '') ?></td>
                  <td><?= h($row['unit_service'] ?? '') ?></td>
                  <td>
                    <?php if (!$isDeleted): ?>
                      <a class="btn xs" href="tdems_edit.php?id=<?= $id ?>">수정</a>
                      <form method="post" action="lib/delete.php" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <input type="hidden" name="reason" value="">
<!--
                        <button type="button" class="btn xs danger single-delete-btn">삭제</button>
-->
                      </form>
                    <?php else: ?>
                      <span class="badge gray">삭제됨</span>
                    <?php endif; ?>
                  </td>
                  </tr>
              <?php endforeach;
            endif; ?>
          </tbody>
        </table>
      </div>

      <!-- 페이지네이션: 처음/이전/번호/다음/마지막 -->
      <?php if ($pages > 1): ?>
        <div class="pager" style="margin-top:10px;">
          <?php
          $firstUrl = 'tdems_main.php?' . build_qs(['page' => 1]);
          $prevUrl  = 'tdems_main.php?' . build_qs(['page' => max(1, $page - 1)]);
          $nextUrl  = 'tdems_main.php?' . build_qs(['page' => min($pages, $page + 1)]);
          $lastUrl  = 'tdems_main.php?' . build_qs(['page' => $pages]);

          $from = max(1, $page - 2);
          $to   = min($pages, $page + 2);
          ?>
          <a href="<?= h($firstUrl) ?>">&laquo;</a>
          <a href="<?= h($prevUrl) ?>">&lsaquo; 이전</a>
          <?php for ($p = $from; $p <= $to; $p++): ?>
            <?php if ($p == $page): ?>
              <span class="active"><?= $p ?></span>
            <?php else: ?>
              <a href="tdems_main.php?<?= build_qs(['page' => $p]) ?>"><?= $p ?></a>
            <?php endif; ?>
          <?php endfor; ?>
          <a href="<?= h($nextUrl) ?>">다음 &rsaquo;</a>
          <a href="<?= h($lastUrl) ?>">&raquo;</a>
        </div>
      <?php endif; ?>
    </section>
  </main>
</body>

</html>