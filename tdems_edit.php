<?php
require __DIR__ . '/lib/read.php';     // $asset 로드 (config/db.php / table: asset)
require __DIR__ . '/config/csrf.php';
require __DIR__ . '/config/user.php';

$csrf = csrf_token();
$include_deleted = (int)($_GET['include_deleted'] ?? 0);

if (!function_exists('fmt_dt')) {
  date_default_timezone_set('Asia/Seoul');
  function fmt_dt($ts){ return $ts ? date('Y-m-d H:i:s', strtotime($ts)) : ''; }
}

$assetId   = (int)($asset['asset_id'] ?? 0);
$isDeleted = (($asset['del_yn'] ?? 'N') === 'Y');
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
  <h1 class="title">자산 수정</h1>
  <nav class="actions" style="display:flex;gap:8px;">
    <a class="btn ghost" href="tdems_detail.php?id=<?= $assetId ?><?= $include_deleted? '&include_deleted=1':'' ?>">상세</a>
    <a class="btn ghost" href="tdems_main.php">목록</a>
  </nav>
</header>

<main class="container narrow">
  <?php if ($isDeleted): ?>
    <div class="alert">이 자산은 삭제 상태입니다.</div>
  <?php endif; ?>

  <section class="card">
    <form class="form" method="post" action="lib/update.php" autocomplete="off">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="asset_id" value="<?= $assetId ?>">

      <!-- 1행: 설비바코드 | 랙 위치 | 장착 위치 | 호스트명 | IP -->
      <div class="form-grid grid-5">
        <div class="field">
          <label class="label">설비바코드 <span class="req">*</span></label>
          <input class="input" type="text" name="equip_barcode" required
                 value="<?= htmlspecialchars($asset['equip_barcode'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="field">
          <label class="label">랙 위치</label>
          <input class="input" type="text" name="rack_location"
                 value="<?= htmlspecialchars($asset['rack_location'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="field">
          <label class="label">장착 위치</label>
          <input class="input" type="text" name="mounted_location"
                 value="<?= htmlspecialchars($asset['mounted_location'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="field">
          <label class="label">호스트명</label>
          <input class="input" type="text" name="hostname"
                 value="<?= htmlspecialchars($asset['hostname'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="field">
          <label class="label">IP <span class="label-sub">(빈 값 허용 • IPv4만 허용)</span></label>
          <input class="input" type="text" name="ip"
                 placeholder="예: 10.1.23.45"
                 title="IPv4 형식만 허용 (예: 10.1.23.45)"
                 inputmode="numeric" maxlength="15"
                 value="<?= htmlspecialchars($asset['ip'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>

      <!-- 2행: 종류 | 제조사 | 모델명 | S/N | 입고년월 -->
      <div class="form-grid grid-5" style="margin-top:10px;">
        <div class="field asset-type-field"
             data-current="<?= htmlspecialchars($asset['asset_type'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <label class="label">종류</label>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <select class="select" name="asset_type_select">
              <option value="">선택</option>
              <option value="서버">서버</option>
              <option value="스토리지">스토리지</option>
              <option value="라우터">라우터</option>
              <option value="스위치">스위치</option>
              <option value="방화벽">방화벽</option>
              <option value="콘솔">콘솔</option>
              <option value="랙">랙</option>
              <option value="__custom__">직접 입력</option>
            </select>
            <input class="input" type="text" name="asset_type_custom"
                   placeholder="종류 직접 입력" style="display:none;min-width:240px;">
          </div>
          <input type="hidden" name="asset_type" value="">
        </div>
        <div class="field manufacturer-field"
             data-current="<?= htmlspecialchars($asset['manufacturer'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <label class="label">제조사</label>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <select class="select" name="manufacturer_select">
              <option value="">선택</option>
              <option value="DELL">DELL</option>
              <option value="HP">HP</option>
              <option value="IBM">IBM</option>
              <option value="SUN">SUN</option>
              <option value="__custom__">직접 입력</option>
            </select>
            <input class="input" type="text" name="manufacturer_custom"
                   placeholder="제조사 직접 입력" style="display:none;min-width:240px;">
          </div>
          <input type="hidden" name="manufacturer" value="">
        </div>

        <div class="field">
          <label class="label">모델명</label>
          <input class="input" type="text" name="model_name"
                 value="<?= htmlspecialchars($asset['model_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="field">
          <label class="label">S/N</label>
          <input class="input" type="text" name="serial_number"
                 value="<?= htmlspecialchars($asset['serial_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="field">
          <label class="label">입고년월</label>
          <input class="input" type="month" name="receipt_ym"
                 value="<?= htmlspecialchars($asset['receipt_ym'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>

      <!-- 3행: OS | CPU종류 | CPU수량 | CPU코어 | SWAP -->
      <div class="form-grid grid-5" style="margin-top:10px;">
        <div class="field">
          <label class="label">OS</label>
          <input class="input" type="text" name="os"
                 value="<?= htmlspecialchars($asset['os'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="field">
          <label class="label">CPU종류</label>
          <input class="input" type="text" name="cpu_type"
                 value="<?= htmlspecialchars($asset['cpu_type'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="field">
          <label class="label">CPU수량</label>
          <input class="input" type="number" name="cpu_qty" min="0"
                 value="<?= htmlspecialchars((string)($asset['cpu_qty'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="field">
          <label class="label">CPU코어</label>
          <input class="input" type="number" name="cpu_core" min="0"
                 value="<?= htmlspecialchars((string)($asset['cpu_core'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="field">
          <label class="label">SWAP</label>
          <input class="input" type="text" name="swap_size"
                 value="<?= htmlspecialchars($asset['swap_size'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>

      <!-- MEMORY | SSD | HDD -->
      <div class="form-grid grid-3">
        <div class="field">
          <label class="label">MEMORY</label>
          <div id="memFields">
            <?php if (!empty($asset_mems)): ?>
              <?php foreach ($asset_mems as $m): ?>
                <div class="mem-item">
                  <input class="input" type="text" name="mem_capacity[]" value="<?= htmlspecialchars($m['capacity'], ENT_QUOTES, 'UTF-8') ?>" placeholder="개별 용량">
                  <input class="input" type="number" name="mem_qty[]" value="<?= (int)$m['quantity'] ?>" placeholder="수량" min="1">
                  <button type="button" class="btn xs ghost mem-add">+추가</button>
                  <button type="button" class="btn xs ghost mem-remove">-삭제</button>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="mem-item">
                <input class="input" type="text" name="mem_capacity[]" placeholder="개별 용량">
                <input class="input" type="number" name="mem_qty[]" placeholder="수량" min="1">
                <button type="button" class="btn xs ghost mem-add">+추가</button>
                <button type="button" class="btn xs ghost mem-remove">-삭제</button>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="field">
          <label class="label">SSD</label>
          <div id="ssdFields">
            <?php if (!empty($asset_ssds)): ?>
              <?php foreach ($asset_ssds as $s): ?>
                <div class="ssd-item">
                  <input class="input" type="text" name="ssd_capacity[]" value="<?= htmlspecialchars($s['capacity'], ENT_QUOTES, 'UTF-8') ?>" placeholder="개별 용량">
                  <input class="input" type="number" name="ssd_qty[]" value="<?= (int)$s['quantity'] ?>" placeholder="수량" min="1">
                  <button type="button" class="btn xs ghost ssd-add">+추가</button>
                  <button type="button" class="btn xs ghost ssd-remove">-삭제</button>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="ssd-item">
                <input class="input" type="text" name="ssd_capacity[]" placeholder="개별 용량">
                <input class="input" type="number" name="ssd_qty[]" placeholder="수량" min="1">
                <button type="button" class="btn xs ghost ssd-add">+추가</button>
                <button type="button" class="btn xs ghost ssd-remove">-삭제</button>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="field">
          <label class="label">HDD</label>
          <div id="hddFields">
            <?php if (!empty($asset_hdds)): ?>
              <?php foreach ($asset_hdds as $h): ?>
                <div class="hdd-item">
                  <input class="input" type="text" name="hdd_capacity[]" value="<?= htmlspecialchars($h['capacity'], ENT_QUOTES, 'UTF-8') ?>" placeholder="개별 용량">
                  <input class="input" type="number" name="hdd_qty[]" value="<?= (int)$h['quantity'] ?>" placeholder="수량" min="1">
                  <button type="button" class="btn xs ghost hdd-add">+추가</button>
                  <button type="button" class="btn xs ghost hdd-remove">-삭제</button>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="hdd-item">
                <input class="input" type="text" name="hdd_capacity[]" placeholder="개별 용량">
                <input class="input" type="number" name="hdd_qty[]" placeholder="수량" min="1">
                <button type="button" class="btn xs ghost hdd-add">+추가</button>
                <button type="button" class="btn xs ghost hdd-remove">-삭제</button>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- MA | 상태 | 용도 | 상세용도 | 설비상태 -->
      <div class="form-grid grid-5" style="margin-top:10px;">
        <div class="field">
          <label class="label">MA</label>
          <select class="select" name="ma">
            <option value="">선택</option>
            <option value="O" <?= ($asset['ma'] ?? '') === 'O' ? 'selected' : '' ?>>O</option>
            <option value="X" <?= ($asset['ma'] ?? '') === 'X' ? 'selected' : '' ?>>X</option>
          </select>
        </div>
        <div class="field">
          <label class="label">상태</label>
          <select class="select" name="status">
            <option value="">선택</option>
            <option value="ON" <?= ($asset['status'] ?? '') === 'ON' ? 'selected' : '' ?>>ON</option>
            <option value="OFF" <?= ($asset['status'] ?? '') === 'OFF' ? 'selected' : '' ?>>OFF</option>
          </select>
        </div>
        <div class="field facility-status-field" data-current="<?= htmlspecialchars($asset['facility_status'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <label class="label">설비상태</label>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <select class="select" name="facility_status_select">
              <option value="">선택</option>
              <option value="운용">운용</option>
              <option value="예비">예비</option>
              <option value="유휴">유휴</option>
              <option value="입고">입고</option>
              <option value="고장">고장</option>
              <option value="불용대기">불용대기</option>
              <option value="불용확정">불용확정</option>
              <option value="__custom__">직접 입력</option>
            </select>
            <input class="input" type="text" name="facility_status_custom" placeholder="설비상태 직접 입력" style="display:none;min-width:140px;">
          </div>
          <input type="hidden" name="facility_status" value="">
        </div>
        <div class="field purpose-field" data-current="<?= htmlspecialchars($asset['purpose'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <label class="label">용도</label>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <select class="select" name="purpose_select">
              <option value="">선택</option>
              <option value="AP">AP</option>
              <option value="DB">DB</option>
              <option value="DEV">DEV</option>
              <option value="GW">GW</option>
              <option value="WAS">WAS</option>
              <option value="WEB">WEB</option>
              <option value="__custom__">직접 입력</option>
            </select>
            <input class="input" type="text" name="purpose_custom" placeholder="용도 직접 입력" style="display:none;min-width:140px;">
          </div>
          <input type="hidden" name="purpose" value="">
        </div>
        <div class="field">
          <label class="label">상세용도</label>
          <input class="input" type="text" name="purpose_detail" value="<?= htmlspecialchars($asset['purpose_detail'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>

      <!-- 자산보유팀 | 표준서비스 | 단위서비스 -->
      <div class="form-grid grid-5" style="margin-top:10px;">
        <div class="field own-team-field" data-current="<?= htmlspecialchars($asset['own_team'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <label class="label">자산보유팀</label>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <select class="select" name="own_team_select">
              <option value="">선택</option>
              <option value="OSS데이터혁신팀">OSS데이터혁신팀</option>
              <option value="OSS혁신팀">OSS혁신팀</option>
              <option value="무선망관제팀">무선망관제팀</option>
              <option value="__custom__">직접 입력</option>
            </select>
            <input class="input" type="text" name="own_team_custom"
                   placeholder="자산보유팀 직접 입력" style="display:none;min-width:240px;">
          </div>
          <input type="hidden" name="own_team" value="">
        </div>
        <div class="field standard-service-field" data-current="<?= htmlspecialchars($asset['standard_service'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <label class="label">표준서비스</label>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <select class="select" name="standard_service_select">
              <option value="">선택</option>
              <option value="통합NMS">통합NMS</option>
              <option value="지능형네트워크통합관제">지능형네트워크통합관제</option>
              <option value="__custom__">직접 입력</option>
            </select>
            <input class="input" type="text" name="standard_service_custom"
                   placeholder="표준서비스 직접 입력" style="display:none;min-width:240px;">
          </div>
          <input type="hidden" name="standard_service" value="">
        </div>
        <div class="field unit-service-field" data-current="<?= htmlspecialchars($asset['unit_service'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <label class="label">단위서비스</label>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <select class="select" name="unit_service_select">
              <option value="">선택</option>
              <option value="통합NMS">통합NMS</option>
              <option value="지능형네트워크통합관제">지능형네트워크통합관제</option>
              <option value="__custom__">직접 입력</option>
            </select>
            <input class="input" type="text" name="unit_service_custom"
                   placeholder="단위서비스 직접 입력" style="display:none;min-width:240px;">
          </div>
          <input type="hidden" name="unit_service" value="">
        </div>
      </div>

      <!-- 3행: 최초 등록일시 | 최종 수정일시 -->
      <div class="form-grid grid-2" style="margin-top:10px;">
        <div class="field">
          <label class="label">최초 등록일시</label>
          <input class="input" type="text" readonly
                 value="<?= htmlspecialchars(fmt_dt($asset['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="field">
          <label class="label">최종 수정일시</label>
          <input class="input" type="text" readonly
                 value="<?= htmlspecialchars(fmt_dt($asset['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>

      <hr class="divider">

      <!-- 자산 이력 (전체 편집) -->
      <div class="form-row">
        <label class="label">자산 이력 (전체 편집)</label>
        <textarea class="input" name="asset_history_edit" rows="10"
          placeholder="자산 이력 전체를 직접 편집합니다. 저장 시 이 내용이 그대로 저장됩니다."><?= htmlspecialchars($asset['asset_history'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
      </div>

      <!-- 추가 이력 (append, 선택) -->
      <div class="form-row">
        <label class="label">이력 추가 (선택)</label>
        <textarea class="input" name="history_append" rows="6" placeholder="변경/점검/장애 조치 등 누적 기록"></textarea>
      </div>

      <div class="form-actions">
        <button class="btn primary" type="submit">저장</button>
        <a class="btn ghost" href="tdems_detail.php?id=<?= $assetId ?><?= $include_deleted? '&include_deleted=1':'' ?>">취소</a>
      </div>
    </form>
  </section>
</main>
</body>
</html>

