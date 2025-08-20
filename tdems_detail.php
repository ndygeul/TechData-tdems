<?php
// tdems_detail.php (삭제 상태 시 수정 버튼 숨김 + 이력 리치 렌더링)
require __DIR__ . '/lib/read.php';   // $asset 로드
require __DIR__ . '/config/csrf.php';
require __DIR__ . '/config/user.php';

$csrf = csrf_token();
$include_deleted = (int)($_GET['include_deleted'] ?? 0);

if (!function_exists('fmt_dt')) {
  date_default_timezone_set('Asia/Seoul');
  function fmt_dt($ts){ return $ts ? date('Y-m-d H:i:s', strtotime($ts)) : ''; }
}
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* 자산 이력 렌더링 */
function render_history($text) {
  $text = (string)$text;
  if ($text === '') return '<div class="history-rich"><div class="empty">이력이 없습니다.</div></div>';

  $text = str_replace(["\r\n","\r"], "\n", $text);
  $lines = explode("\n", $text);

  $out=''; $inCode=false; $code=''; $inList=false; $para=[];
  $flushPara=function()use(&$out,&$para){ if($para){ $p=implode("\n",$para); $out.='<p>'.nl2br(h($p)).'</p>'; $para=[]; } };
  $closeList=function()use(&$out,&$inList){ if($inList){ $out.='</ul>'; $inList=false; } };
  $closeCode=function()use(&$out,&$inCode,&$code){ if($inCode){ $out.='<pre class="history-code">'.h(rtrim($code,"\n")).'</pre>'; $inCode=false; $code=''; } };

  foreach($lines as $lineRaw){
    $line=$lineRaw;

    if(trim($line)==='```'){ $flushPara(); $closeList(); if($inCode){ $closeCode(); } else { $inCode=true; $code=''; } continue; }
    if($inCode){ $code.=$line."\n"; continue; }
    if(trim($line)==='-----'){ $flushPara(); $closeList(); $out.='<hr class="divider">'; continue; }
    if(preg_match('/^\[(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\]\s*(.+)$/',$line,$m)){
      $flushPara(); $closeList();
      $out.='<div class="history-head"><span class="ts">'.h($m[1]).'</span><span class="by">'.h($m[2]).'</span></div>';
      continue;
    }
    if(preg_match('/^\s*>\s?(.*)$/',$line,$m)){ $flushPara(); $closeList(); $out.='<blockquote class="history-quote">'.nl2br(h($m[1])).'</blockquote>'; continue; }
    if(preg_match('/^\s*[-*]\s+(.*)$/',$line,$m)){ $flushPara(); if(!$inList){ $out.='<ul class="history-list">'; $inList=true; } $out.='<li>'.h($m[1]).'</li>'; continue; }
    else { if($inList && trim($line)!==''){ $closeList(); } }

    if(trim($line)===''){ $flushPara(); } else { $para[]=$line; }
  }
  $closeCode(); $closeList(); $flushPara();
  return '<div class="history-rich">'.$out.'</div>';
}

$assetId   = (int)($asset['asset_id'] ?? 0);
$isDeleted = (($asset['del_yn'] ?? 'N') === 'Y');

$createdBy = isset($asset['created_ip']) ? ip_to_user($asset['created_ip']) : '';
$updatedBy = isset($asset['updated_ip']) ? ip_to_user($asset['updated_ip']) : '';

if ($assetId <= 0) {
  http_response_code(404);
  echo "<!doctype html><meta charset='utf-8'><link rel='stylesheet' href='css/style.css'>";
  echo "<div class='container'><div class='alert'>대상을 찾을 수 없습니다.</div><p><a class='btn' href='tdems_main.php'>목록</a></p></div>";
  exit;
}

$barcode = trim($asset['equip_barcode'] ?? '');
$barcodeUrl = '/ezk/barcode/barcode_generator.php?barcode=' . rawurlencode($barcode);
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
  <h1 class="title">자산 상세</h1>
  <nav class="actions" style="display:flex;gap:8px;">
    <a class="btn" href="tdems_main.php">자산 목록</a>

    <?php if (!$isDeleted): ?>
      <a class="btn primary" href="tdems_edit.php?id=<?= $assetId ?>">수정</a>

      <form id="detailDeleteForm" method="post" action="lib/delete.php" style="display:inline;">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="id" value="<?= $assetId ?>">
        <input type="hidden" name="reason" value="">
        <button type="button" id="detailDeleteBtn" class="btn danger">삭제</button>
      </form>
    <?php endif; ?>
  </nav>
</header>

<main class="container narrow">
  <?php if (!empty($_GET['msg'])): ?>
    <div class="alert"><?= h($_GET['msg']) ?></div>
  <?php endif; ?>
  <?php if ($isDeleted): ?>
    <div class="alert">이 자산은 삭제 상태입니다.</div>
  <?php endif; ?>

  <section class="card">
    <!-- 1행: 설비바코드 | 랙/장착 위치 | 호스트명 | IP -->
    <div class="form-grid grid-5">
      <div class="field">
        <label class="label">설비바코드</label>
        <div class="detail-val">
          <?php if ($barcode !== ''): ?>
            <a class="barcode-link" href="<?= h($barcodeUrl) ?>">
              <code class="mono"><?= h($barcode) ?></code>
            </a>
          <?php else: ?>
            <code class="mono">-</code>
          <?php endif; ?>
        </div>
      </div>

      <div class="field">
        <label class="label">랙/장착</label>
        <div class="detail-val"><?= h(trim(($asset['rack_location'] ?? '').' '.($asset['mounted_location'] ?? ''))) ?></div>
      </div>

      <div class="field">
        <label class="label">호스트명</label>
        <div class="detail-val"><?= h($asset['hostname'] ?? '') ?></div>
      </div>

      <div class="field">
        <label class="label">IP</label>
        <div class="detail-val"><?= h($asset['ip'] ?? '') ?></div>
      </div>
    </div>

    <!-- 2행: 종류 | 제조사 | 모델명 | S/N | 입고년월 -->
    <div class="form-grid grid-5" style="margin-top:10px;">
      <div class="field">
        <label class="label">종류</label>
        <div class="detail-val"><?= h($asset['asset_type'] ?? '') ?></div>
      </div>
      <div class="field">
        <label class="label">제조사</label>
        <div class="detail-val"><?= h($asset['manufacturer'] ?? '') ?></div>
      </div>
      <div class="field">
        <label class="label">모델명</label>
        <div class="detail-val"><?= h($asset['model_name'] ?? '') ?></div>
      </div>
      <div class="field">
        <label class="label">S/N</label>
        <div class="detail-val"><?= h($asset['serial_number'] ?? '') ?></div>
      </div>
      <div class="field">
        <label class="label">입고년월</label>
        <div class="detail-val"><?= h($asset['receipt_ym'] ?? '') ?></div>
      </div>
    </div>

    <!-- 3행: OS | CPU종류 | CPU수량 | CPU코어 | SWAP -->
    <div class="form-grid grid-5" style="margin-top:10px;">
      <div class="field">
        <label class="label">OS</label>
        <div class="detail-val"><?= h($asset['os'] ?? '') ?></div>
      </div>
      <div class="field">
        <label class="label">CPU종류</label>
        <div class="detail-val"><?= h($asset['cpu_type'] ?? '') ?></div>
      </div>
      <div class="field">
        <label class="label">CPU수량</label>
        <div class="detail-val"><?= h($asset['cpu_qty'] ?? '') ?></div>
      </div>
      <div class="field">
        <label class="label">CPU코어</label>
        <div class="detail-val"><?= h($asset['cpu_core'] ?? '') ?></div>
      </div>
      <div class="field">
        <label class="label">SWAP</label>
        <div class="detail-val"><?= h($asset['swap_size'] ?? '') ?></div>
      </div>
    </div>

    <!-- MEMORY | SSD | HDD -->
    <div class="form-grid grid-5" style="margin-top:10px;">
      <div class="field">
        <label class="label">MEMORY</label>
        <div class="detail-val">
          <?php if (!empty($asset_mems)): ?>
            <?php foreach ($asset_mems as $m): ?>
              <?php $memTotal = (int)$m['capacity'] * (int)$m['quantity']; ?>
              <div><?= h($m['capacity']) ?> x <?= h($m['quantity']) ?> = <?= h($memTotal) ?></div>
            <?php endforeach; ?>
          <?php else: ?>
            -
          <?php endif; ?>
        </div>
      </div>
      <div class="field">
        <label class="label">SSD</label>
        <div class="detail-val">
          <?php if (!empty($asset_ssds)): ?>
            <?php foreach ($asset_ssds as $s): ?>
              <div><?= h($s['capacity']) ?> x <?= h($s['quantity']) ?></div>
            <?php endforeach; ?>
          <?php else: ?>
            -
          <?php endif; ?>
        </div>
      </div>
      <div class="field">
        <label class="label">HDD</label>
        <div class="detail-val">
          <?php if (!empty($asset_hdds)): ?>
            <?php foreach ($asset_hdds as $h): ?>
              <div><?= h($h['capacity']) ?> x <?= h($h['quantity']) ?></div>
            <?php endforeach; ?>
          <?php else: ?>
            -
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- MA | 상태 | 용도 | 상세용도 | 설비상태 -->
    <div class="form-grid grid-5" style="margin-top:10px;">
      <div class="field">
        <label class="label">MA</label>
        <div class="detail-val"><?= h($asset['ma'] ?? '') ?></div>
      </div>
      <div class="field">
        <label class="label">상태</label>
        <div class="detail-val"><?= h($asset['status'] ?? '') ?></div>
      </div>
      <div class="field">
        <label class="label">설비상태</label>
        <div class="detail-val"><?= h($asset['facility_status'] ?? '') ?></div>
      </div>
      <div class="field">
        <label class="label">용도</label>
        <div class="detail-val"><?= h($asset['purpose'] ?? '') ?></div>
      </div>
      <div class="field">
        <label class="label">상세용도</label>
        <div class="detail-val"><?= h($asset['purpose_detail'] ?? '') ?></div>
      </div>
    </div>

    <!-- 자산보유팀 | 표준서비스 | 단위서비스 -->
    <div class="form-grid grid-5" style="margin-top:10px;">
      <div class="field">
        <label class="label">자산보유팀</label>
        <div class="detail-val"><?= h($asset['own_team'] ?? '') ?></div>
      </div>
      <div class="field">
        <label class="label">표준서비스</label>
        <div class="detail-val"><?= h($asset['standard_service'] ?? '') ?></div>
      </div>
      <div class="field">
        <label class="label">단위서비스</label>
        <div class="detail-val"><?= h($asset['unit_service'] ?? '') ?></div>
      </div>
    </div>

    <!-- 3행: 최초 등록일시 | 등록자 | 최종 수정일시 | 수정자 -->
    <div class="form-grid grid-5" style="margin-top:10px;">
      <div class="field">
        <label class="label">최초 등록일시</label>
        <div class="detail-val"><?= h(fmt_dt($asset['created_at'] ?? '')) ?></div>
      </div>
      <div class="field">
        <label class="label">등록자</label>
        <div class="detail-val"><?= h($createdBy) ?></div>
      </div>
      <div class="field">
        <label class="label">최종 수정일시</label>
        <div class="detail-val"><?= h(fmt_dt($asset['updated_at'] ?? '')) ?></div>
      </div>
      <div class="field">
        <label class="label">수정자</label>
        <div class="detail-val"><?= h($updatedBy) ?></div>
      </div>
    </div>

    <?php if ($isDeleted): ?>
      <div class="form-grid grid-2" style="margin-top:10px;">
        <div class="field">
          <label class="label">삭제일</label>
          <div class="detail-val"><?= h(fmt_dt($asset['deleted_at'] ?? '')) ?></div>
        </div>
        <div class="field">
          <label class="label">삭제사유</label>
          <div class="detail-val"><?= nl2br(h($asset['deleted_reason'] ?? '')) ?></div>
        </div>
      </div>
    <?php endif; ?>

    <hr class="divider">

    <!-- 자산 이력 -->
    <div class="form-row">
      <label class="label">자산 이력</label>
      <div class="detail-val history-view">
        <?= render_history($asset['asset_history'] ?? '') ?>
      </div>
    </div>
  </section>
</main>
</body>
</html>

