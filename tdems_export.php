<?php
require __DIR__ . '/lib/excel.php';

list($rows, $filename) = fetch_export_rows();

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename=' . $filename);
echo "\xEF\xBB\xBF"; // UTF-8 BOM
?>
<meta charset="UTF-8">
<table border="1">
  <tr>
    <th>설비바코드</th>
    <th>랙/장착</th>
    <th>호스트명</th>
    <th>IP</th>
    <th>종류</th>
    <th>제조사</th>
    <th>모델명</th>
    <th>S/N</th>
    <th>입고년월</th>
    <th>OS</th>
    <th>CPU종류</th>
    <th>CPU수량</th>
    <th>CPU코어</th>
    <th>SWAP</th>
    <th>MEMORY</th>
    <th>SSD</th>
    <th>HDD</th>
    <th>MA</th>
    <th>상태</th>
    <th>설비상태</th>
    <th>용도</th>
    <th>상세용도</th>
    <th>자산보유팀</th>
    <th>표준서비스</th>
    <th>단위서비스</th>
    <th>자산 이력</th>
  </tr>
<?php foreach ($rows as $r): ?>
  <tr>
    <td style="mso-number-format:'\\@';"><?= h($r['equip_barcode'] ?? '') ?></td>
    <td><?= h(trim(($r['rack_location'] ?? '') . ' ' . ($r['mounted_location'] ?? ''))) ?></td>
    <td><?= h($r['hostname'] ?? '') ?></td>
    <td><?= h($r['ip'] ?? '') ?></td>
    <td><?= h($r['asset_type'] ?? '') ?></td>
    <td><?= h($r['manufacturer'] ?? '') ?></td>
    <td><?= h($r['model_name'] ?? '') ?></td>
    <td><?= h($r['serial_number'] ?? '') ?></td>
    <td><?= h(format_receipt_ym($r['receipt_ym'] ?? '')) ?></td>
    <td><?= h($r['os'] ?? '') ?></td>
    <td><?= h($r['cpu_type'] ?? '') ?></td>
    <td><?= h($r['cpu_qty'] ?? '') ?></td>
    <td><?= h($r['cpu_core'] ?? '') ?></td>
    <td><?= h($r['swap_size'] ?? '') ?></td>
    <td><?= $r['mem_list'] ? xls_multiline($r['mem_list']) : '' ?></td>
    <td><?= $r['ssd_list'] ? xls_multiline($r['ssd_list']) : '' ?></td>
    <td><?= $r['hdd_list'] ? xls_multiline($r['hdd_list']) : '' ?></td>
    <td><?= h($r['ma'] ?? '') ?></td>
    <td><?= h($r['status'] ?? '') ?></td>
    <td><?= h($r['facility_status'] ?? '') ?></td>
    <td><?= h($r['purpose'] ?? '') ?></td>
    <td><?= $r['purpose_detail'] ? xls_multiline($r['purpose_detail']) : '' ?></td>
    <td><?= h($r['own_team'] ?? '') ?></td>
    <td><?= h($r['standard_service'] ?? '') ?></td>
    <td><?= h($r['unit_service'] ?? '') ?></td>
    <td><?= $r['asset_history'] ? xls_multiline($r['asset_history']) : '' ?></td>
  </tr>
<?php endforeach; ?>
</table>
