/* TDEMS Frontend App JS (공통)
   - 삭제 사유 프롬프트
   - 메인 목록: 전체선택/선택삭제/단건삭제
   - 상세 페이지: 단건삭제(상/하단 버튼 지원)
   - 바코드 팝업 오픈 (자산 목록 설비바코드 클릭)
   - 제조사 입력: 드롭다운 + 직접입력 지원
   - 종류 입력: 드롭다운 + 직접입력 지원
   - IP 입력: IPv4 형식 검증(blur/submit), 시각 피드백
*/
(function () {
  'use strict';

  // ===== 공통 유틸 =====
  function $(sel, root) { return (root || document).querySelector(sel); }
  function $all(sel, root) { return Array.from((root || document).querySelectorAll(sel)); }

  // 삭제 사유 입력 프롬프트
  window.requestReason = function (defaultText) {
    const r = prompt('삭제 사유를 입력하세요:', defaultText || '');
    if (r === null) return null;
    const s = r.trim();
    if (!s) { alert('삭제 사유를 입력해야 합니다.'); return null; }
    return s;
  };

  // ===== IPv4 검증 =====
  const IPV4_REGEX = /^(?:(?:25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)\.){3}(?:25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)$/;
  function isIPv4(v){ return IPV4_REGEX.test(v); }
  function bindIPv4Validation(input) {
    if (!input) return;
    // HTML 속성 보강
    input.setAttribute('pattern', IPV4_REGEX.source);
    input.setAttribute('title', 'IPv4 형식만 허용 (예: 10.1.23.45)');
    input.setAttribute('maxlength', '15');
    input.setAttribute('inputmode', 'numeric');
    // blur 시 검증 (빈 값 허용)
    input.addEventListener('blur', function(){
      const v = input.value.trim();
      input.classList.toggle('invalid', !!v && !isIPv4(v));
    });
    // 입력 중 invalid 클래스 제거
    input.addEventListener('input', function(){
      if (input.classList.contains('invalid')) {
        const v = input.value.trim();
        if (!v || isIPv4(v)) input.classList.remove('invalid');
      }
    });
    // 폼 submit 시 최종 검증
    const form = input.closest('form');
    if (form) {
      form.addEventListener('submit', function(e){
        const v = input.value.trim();
        if (v && !isIPv4(v)) {
          input.classList.add('invalid');
          alert('IP 형식이 올바르지 않습니다. 예: 10.1.23.45');
          input.focus();
          e.preventDefault();
        }
      });
    }
  }

  // ===== 드롭다운 + 직접입력 공통 초기화 =====
  function initSelectWithCustom(field, cfg) {
    if (!field) return;
    const select = field.querySelector(`select[name="${cfg.selectName}"]`);
    const custom = field.querySelector(`input[name="${cfg.customName}"]`);
    const hidden = field.querySelector(`input[name="${cfg.hiddenName}"]`);
    const form   = field.closest('form');
    const current = (field.getAttribute('data-current') || '').trim();
    const presets = cfg.presets || [];

    function setMode(mode, fill) {
      if (mode === 'custom') {
        custom.style.display = '';
        if (typeof fill === 'string') custom.value = fill;
        select.value = '__custom__';
      } else {
        custom.style.display = 'none';
        if (typeof fill === 'string') select.value = fill;
        custom.value = '';
      }
    }

    if (current) {
      if (presets.includes(current)) {
        setMode('preset', current);
        hidden.value = current;
      } else {
        setMode('custom', current);
        hidden.value = current;
      }
    } else {
      setMode('preset', '');
      hidden.value = '';
    }

    select.addEventListener('change', function () {
      if (this.value === '__custom__') {
        setMode('custom');
        custom.focus();
      } else {
        setMode('preset', this.value);
      }
    });

    if (form) {
      form.addEventListener('submit', function () {
        if (select.value === '__custom__') {
          hidden.value = custom.value.trim();
        } else {
          hidden.value = select.value.trim();
        }
      });
    }
  }

  function initManufacturerField(field) {
    initSelectWithCustom(field, {
      selectName: 'manufacturer_select',
      customName: 'manufacturer_custom',
      hiddenName: 'manufacturer',
      presets: ['DELL', 'HP', 'IBM', 'SUN']
    });
  }

  function initAssetTypeField(field) {
    initSelectWithCustom(field, {
      selectName: 'asset_type_select',
      customName: 'asset_type_custom',
      hiddenName: 'asset_type',
      presets: ['서버', '스토리지', '라우터', '스위치', '방화벽', '콘솔', '랙']
    });
  }

  function initOwnTeamField(field) {
    initSelectWithCustom(field, {
      selectName: 'own_team_select',
      customName: 'own_team_custom',
      hiddenName: 'own_team',
      presets: ['OSS데이터혁신팀', 'OSS혁신팀', '무선망관제팀']
    });
  }

  function initStandardServiceField(field) {
    initSelectWithCustom(field, {
      selectName: 'standard_service_select',
      customName: 'standard_service_custom',
      hiddenName: 'standard_service',
      presets: ['통합NMS', '지능형네트워크통합관제']
    });
  }

  function initUnitServiceField(field) {
    initSelectWithCustom(field, {
      selectName: 'unit_service_select',
      customName: 'unit_service_custom',
      hiddenName: 'unit_service',
      presets: ['통합NMS', '지능형네트워크통합관제']
    });
  }

  function initPurposeField(field) {
    initSelectWithCustom(field, {
      selectName: 'purpose_select',
      customName: 'purpose_custom',
      hiddenName: 'purpose',
      presets: ['AP', 'DB', 'DEV', 'GW', 'WAS', 'WEB']
    });
  }

  function initFacilityStatusField(field) {
    initSelectWithCustom(field, {
      selectName: 'facility_status_select',
      customName: 'facility_status_custom',
      hiddenName: 'facility_status',
      presets: ['운용', '예비', '유휴', '입고', '고장', '불용대기', '불용확정']
    });
  }

  // ===== 동적 입력 필드 (MEMORY/SSD/HDD) =====
  function initDynamicFields(container, itemClass, addClass, removeClass, onAdd) {
    if (!container) return;

    function refresh() {
      const rows = $all('.' + itemClass, container);
      rows.forEach(row => {
        const rm = row.querySelector('.' + removeClass);
        if (rm) rm.style.display = (rows.length > 1) ? '' : 'none';
      });
    }

    container.addEventListener('click', function (e) {
      if (e.target.classList.contains(addClass)) {
        const row = e.target.closest('.' + itemClass);
        if (!row) return;
        const clone = row.cloneNode(true);
        $all('input', clone).forEach(inp => {
          inp.value = '';
          if (typeof onAdd === 'function') onAdd(inp);
        });
        container.appendChild(clone);
        refresh();
      } else if (e.target.classList.contains(removeClass)) {
        const row = e.target.closest('.' + itemClass);
        if (!row) return;
        row.remove();
        refresh();
      }
    });

    refresh();
  }

  // ===== 페이지 로드 후 바인딩 =====
  document.addEventListener('DOMContentLoaded', function () {
    // ---- 상세 페이지: 삭제 버튼(상/하단) ----
    ['detailDeleteBtn', 'detailDeleteBtnBottom'].forEach(function (btnId) {
      const btn = document.getElementById(btnId);
      if (!btn) return;

      const formId = (btnId === 'detailDeleteBtn') ? 'detailDeleteForm' : 'detailDeleteFormBottom';
      const form = document.getElementById(formId);
      if (!form) return;

      btn.addEventListener('click', function () {
        const reason = window.requestReason('상세화면 삭제');
        if (reason === null) return;
        if (!confirm('이 자산을 삭제하시겠습니까?')) return;
        const input = form.querySelector('input[name="reason"]');
        if (input) input.value = reason;
        form.submit();
      });
    });

    // ---- 메인 목록: 체크박스/삭제 ----
    const activeRowChecks = () => $all('.row-check').filter(c => !c.disabled);

    const checkAll = $('#checkAll');
    if (checkAll) {
      checkAll.addEventListener('change', () => activeRowChecks().forEach(c => { c.checked = checkAll.checked; }));
    }

    const bulkBtn = $('#bulkDeleteBtn');
    const bulkForm = $('#bulkDeleteForm');
    if (bulkBtn && bulkForm) {
      const bulkIdsBox = $('#bulkIds');
      const bulkReason = $('#bulkReason');

      bulkBtn.addEventListener('click', function () {
        const ids = $all('.row-check:checked').map(c => c.value);
        if (ids.length === 0) { alert('삭제할 항목을 선택하세요.'); return; }

        const reason = window.requestReason('선택 삭제');
        if (reason === null) return;

        if (bulkIdsBox) bulkIdsBox.innerHTML = '';
        ids.forEach(id => {
          const inp = document.createElement('input');
          inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = id;
          bulkIdsBox.appendChild(inp);
        });
        if (bulkReason) bulkReason.value = reason;

        bulkForm.submit();
      });
    }

    $all('.single-delete-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const form = btn.closest('form');
        if (!form) return;
        const reason = window.requestReason('단건 삭제');
        if (reason === null) return;
        const input = form.querySelector('input[name="reason"]');
        if (input) input.value = reason;
        form.submit();
      });
    });

    // ---- 바코드 팝업: 설비바코드 클릭 시 새 창 열기 ----
    document.addEventListener('click', function (e) {
      const a = e.target.closest('a.barcode-link');
      if (!a) return;
      e.preventDefault();
      const url = a.getAttribute('href');
      window.open(url, 'barcodeWin', 'width=720,height=420,scrollbars=yes,resizable=yes');
    });

    // ---- 동적 입력 초기화 ----
    initDynamicFields(document.getElementById('memFields'), 'mem-item', 'mem-add', 'mem-remove');
    initDynamicFields(document.getElementById('ssdFields'), 'ssd-item', 'ssd-add', 'ssd-remove');
    initDynamicFields(document.getElementById('hddFields'), 'hdd-item', 'hdd-add', 'hdd-remove');
    initDynamicFields(document.getElementById('ipFields'), 'ip-item', 'ip-add', 'ip-remove', bindIPv4Validation);

    // ---- 드롭다운 + 직접입력 컨트롤 초기화 ----
    $all('.manufacturer-field').forEach(initManufacturerField);
    $all('.asset-type-field').forEach(initAssetTypeField);
    $all('.own-team-field').forEach(initOwnTeamField);
    $all('.standard-service-field').forEach(initStandardServiceField);
    $all('.unit-service-field').forEach(initUnitServiceField);
    $all('.purpose-field').forEach(initPurposeField);
    $all('.facility-status-field').forEach(initFacilityStatusField);

    // ---- IP 입력 검증 바인딩 ----
    $all('input[name^="ip"]').forEach(bindIPv4Validation);

    // ---- 랙 리스트 좌/우 이동 버튼 ----
    const rackList = document.querySelector('.rack-list');
    if (rackList) {
      const prevBtn = document.querySelector('.rack-nav.prev');
      const nextBtn = document.querySelector('.rack-nav.next');
      const card = rackList.querySelector('.card');
      const step = card ? card.offsetWidth + 16 : 160;

      function setupNav(btn, dir) {
        if (!btn) return;
        let timer;
        const scroll = () => rackList.scrollBy({ left: dir * step, behavior: 'smooth' });
        const start = () => {
          scroll();
          timer = setInterval(scroll, 200);
        };
        const stop = () => clearInterval(timer);
        btn.addEventListener('mousedown', start);
        btn.addEventListener('touchstart', start);
        ['mouseup', 'mouseleave', 'touchend', 'touchcancel'].forEach(ev => {
          btn.addEventListener(ev, stop);
        });
      }

      setupNav(prevBtn, -1);
      setupNav(nextBtn, 1);
    }
  });
})();

