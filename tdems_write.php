<?php
require __DIR__ . '/config/csrf.php';
$csrf = csrf_token();
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
  <h1 class="title">자산 등록</h1>
  <nav class="actions">
    <a class="btn ghost" href="tdems_main.php">목록</a>
  </nav>
</header>

<main class="container narrow">
  <section class="card">
    <form class="form" method="post" action="lib/insert.php" autocomplete="off">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

      <!-- 1행: 설비바코드 | 랙 위치 | 장착 위치 | 호스트명 | IP -->
      <div class="form-grid grid-5">
        <div class="field">
          <label class="label">설비바코드</label>
          <input class="input" type="text" name="equip_barcode">
        </div>

        <div class="field">
          <label class="label">랙 위치</label>
          <input class="input" type="text" name="rack_location">
        </div>

        <div class="field">
          <label class="label">장착 위치</label>
          <input class="input" type="text" name="mounted_location">
        </div>

        <div class="field">
          <label class="label">호스트명</label>
          <input class="input" type="text" name="hostname">
        </div>

        <div class="field">
          <label class="label">IP <span class="label-sub">(빈 값 허용 • IPv4만 허용)</span></label>
          <div id="ipFields">
            <div class="ip-item">
              <input class="input" type="text" name="ip[]"
                     placeholder="예: 10.1.23.45"
                     title="IPv4 형식만 허용 (예: 10.1.23.45)"
                     inputmode="numeric" maxlength="15">
              <button type="button" class="btn xs ghost ip-add">+추가</button>
              <button type="button" class="btn xs ghost ip-remove">-삭제</button>
            </div>
          </div>
        </div>
      </div>

      <!-- 2행: 종류 | 제조사 | 모델명 | S/N | 입고년월 -->
      <div class="form-grid grid-5" style="margin-top:10px;">
        <div class="field asset-type-field" data-current="">
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

        <div class="field manufacturer-field" data-current="">
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
          <input class="input" type="text" name="model_name">
        </div>

        <div class="field">
          <label class="label">S/N</label>
          <input class="input" type="text" name="serial_number">
        </div>

        <div class="field">
          <label class="label">입고년월</label>
          <input class="input" type="month" name="receipt_ym">
        </div>
      </div>

      <!-- 3행: OS | CPU종류 | CPU수량 | CPU코어 | SWAP -->
      <div class="form-grid grid-5" style="margin-top:10px;">
        <div class="field">
          <label class="label">OS</label>
          <input class="input" type="text" name="os">
        </div>

        <div class="field">
          <label class="label">CPU종류</label>
          <input class="input" type="text" name="cpu_type">
        </div>

        <div class="field">
          <label class="label">CPU수량</label>
          <input class="input" type="number" name="cpu_qty" min="0">
        </div>

        <div class="field">
          <label class="label">CPU코어</label>
          <input class="input" type="number" name="cpu_core" min="0">
        </div>

        <div class="field">
          <label class="label">SWAP</label>
          <input class="input" type="text" name="swap_size">
        </div>
      </div>

      <!-- MEMORY | SSD | HDD -->
      <div class="form-grid grid-3">
        <div class="field">
          <label class="label">MEMORY</label>
          <div id="memFields">
            <div class="mem-item">
              <input class="input" type="text" name="mem_capacity[]" placeholder="개별 용량">
              <input class="input" type="number" name="mem_qty[]" placeholder="수량" min="1">
              <button type="button" class="btn xs ghost mem-add">+추가</button>
              <button type="button" class="btn xs ghost mem-remove">-삭제</button>
            </div>
          </div>
        </div>
        <div class="field">
          <label class="label">SSD</label>
          <div id="ssdFields">
            <div class="ssd-item">
              <input class="input" type="number" name="ssd_capacity[]" placeholder="개별 용량" min="0">
              <select class="select" name="ssd_unit[]">
                <option value="GB">GB</option>
                <option value="TB">TB</option>
              </select>
              <input class="input" type="number" name="ssd_qty[]" placeholder="수량" min="1">
              <button type="button" class="btn xs ghost ssd-add">+추가</button>
              <button type="button" class="btn xs ghost ssd-remove">-삭제</button>
            </div>
          </div>
        </div>
        <div class="field">
          <label class="label">HDD</label>
          <div id="hddFields">
            <div class="hdd-item">
              <input class="input" type="number" name="hdd_capacity[]" placeholder="개별 용량" min="0">
              <select class="select" name="hdd_unit[]">
                <option value="GB">GB</option>
                <option value="TB">TB</option>
              </select>
              <input class="input" type="number" name="hdd_qty[]" placeholder="수량" min="1">
              <button type="button" class="btn xs ghost hdd-add">+추가</button>
              <button type="button" class="btn xs ghost hdd-remove">-삭제</button>
            </div>
          </div>
        </div>
      </div>

      <!-- MA | 상태 | 용도 | 상세용도 | 설비상태 -->
      <div class="form-grid grid-5" style="margin-top:10px;">
        <div class="field">
          <label class="label">MA</label>
          <select class="select" name="ma">
            <option value="">선택</option>
            <option value="O">O</option>
            <option value="X">X</option>
          </select>
        </div>
        <div class="field">
          <label class="label">상태</label>
          <select class="select" name="status">
            <option value="">선택</option>
            <option value="ON">ON</option>
            <option value="OFF">OFF</option>
          </select>
        </div>
        <div class="field facility-status-field" data-current="">
          <label class="label">설비상태</label>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <select class="select" name="facility_status_select">
              <option value="">선택</option>
              <option value="운용">운용</option>
              <option value="예비">예비</option>
              <option value="유휴">유휴</option>
              <option value="입고">입고</option>
              <option value="고장">고장</option>
              <option value="미운용">미운용</option>
              <option value="불용대기">불용대기</option>
              <option value="불용확정">불용확정</option>
              <option value="__custom__">직접 입력</option>
            </select>
            <input class="input" type="text" name="facility_status_custom" placeholder="설비상태 직접 입력" style="display:none;min-width:140px;">
          </div>
          <input type="hidden" name="facility_status" value="">
        </div>
        <div class="field purpose-field" data-current="">
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
          <input class="input" type="text" name="purpose_detail">
        </div>
      </div>

      <!-- 자산보유팀 | 표준서비스 | 단위서비스 -->
      <div class="form-grid grid-5" style="margin-top:10px;">
        <div class="field own-team-field" data-current="">
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
        <div class="field standard-service-field" data-current="">
          <label class="label">표준서비스</label>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <select class="select" name="standard_service_select">
              <option value="">선택</option>
              <option value="지능형네트워크통합관제">지능형네트워크통합관제</option>
              <option value="5G공동망연동시스템">5G공동망연동시스템</option>
              <option value="통합NMS">통합NMS</option>
              <option value="__custom__">직접 입력</option>
            </select>
            <input class="input" type="text" name="standard_service_custom"
                   placeholder="표준서비스 직접 입력" style="display:none;min-width:240px;">
          </div>
          <input type="hidden" name="standard_service" value="">
        </div>
        <div class="field unit-service-field" data-current="">
          <label class="label">단위서비스</label>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <select class="select" name="unit_service_select">
              <option value="">선택</option>
              <option value="지능형네트워크통합관제">지능형네트워크통합관제</option>
              <option value="5G공동망연동시스템">5G공동망연동시스템</option>
              <option value="무선VOC응대">무선VOC응대</option>
              <option value="현장작업지원시스템">현장작업지원시스템</option>
              <option value="통합NMS">통합NMS</option>
              <option value="__custom__">직접 입력</option>
            </select>
            <input class="input" type="text" name="unit_service_custom"
                   placeholder="단위서비스 직접 입력" style="display:none;min-width:240px;">
          </div>
          <input type="hidden" name="unit_service" value="">
        </div>
      </div>

      <hr class="divider">

      <!-- 3행: 자산 이력 (초기) -->
      <div class="form-row">
        <label class="label">자산 이력 (초기)</label>
        <textarea class="input" name="asset_history" rows="8" placeholder="초기 설치/반입 정보 등 기록"></textarea>
      </div>

      <div class="form-actions">
        <button class="btn primary" type="submit">등록</button>
        <a class="btn ghost" href="tdems_main.php">취소</a>
      </div>
    </form>
  </section>
</main>
</body>
</html>

