<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/lib/list.php';

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function format_receipt_ym($ym) {
    if (!$ym) {
        return '';
    }
    $dt = DateTime::createFromFormat('Y-m', $ym);
    return $dt ? $dt->format('Y년 m월') : $ym;
}

function xls_multiline($s) {
    $s = h($s ?? '');
    return str_replace(["\r\n", "\n", "\r"], '<br style="mso-data-placement:same-cell;">', $s);
}

$mysqli = isset($mysqli) ? $mysqli : (isset($conn) ? $conn : null);
if (!$mysqli instanceof mysqli) {
    http_response_code(500);
    echo 'DB not initialized';
    exit;
}

$params = get_search_params();
extract($params);

list($whereSql, $types, $bindParams) = build_where_clause($field, $q, $include);

$sortable = [
    'equip_barcode'    => 'a.equip_barcode',
    'rack'             => 'a.rack_location',
    'hostname'         => 'a.hostname',
    'ip'               => 'a.ip',
    'own_team'         => 'a.own_team',
    'standard_service' => 'a.standard_service',
    'unit_service'     => 'a.unit_service',
    'asset_type'       => 'a.asset_type',
    'manufacturer'     => 'a.manufacturer',
    'model_name'       => 'a.model_name',
    'os'               => 'a.os',
    'ma'               => 'a.ma',
    'status'           => 'a.status',
    'purpose'          => 'a.purpose',
    'facility_status'  => 'a.facility_status',
    'updated_at'       => 'a.updated_at'
];
if (!array_key_exists($sort, $sortable)) { $sort = 'rack'; }
$orderBy = ($sort === 'rack')
    ? "a.rack_location {$dir}, a.mounted_location {$dir}"
    : $sortable[$sort] . " {$dir}";
$orderBy .= ", a.asset_id DESC";

$sql = "SELECT a.equip_barcode, a.rack_location, a.mounted_location, a.hostname, a.ip,
               a.asset_type, a.manufacturer, a.model_name, a.serial_number, a.receipt_ym,
               a.os, a.cpu_type, a.cpu_qty, a.cpu_core, a.swap_size,
               m.mem_list, s.ssd_list, h.hdd_list,
               a.ma, a.status, a.facility_status, a.purpose, a.purpose_detail,
               a.own_team, a.standard_service, a.unit_service, a.asset_history
        FROM asset a
        LEFT JOIN (
            SELECT equip_barcode,
                   GROUP_CONCAT(CAST(capacity AS UNSIGNED) * CAST(quantity AS UNSIGNED)
                       ORDER BY CAST(capacity AS UNSIGNED) ASC SEPARATOR '\n') AS mem_list
            FROM asset_memory
            GROUP BY equip_barcode
        ) m ON a.equip_barcode = m.equip_barcode
        LEFT JOIN (
            SELECT equip_barcode,
                   GROUP_CONCAT(CONCAT(capacity, ' x ', quantity)
                       ORDER BY CAST(capacity AS UNSIGNED) ASC SEPARATOR '\n') AS ssd_list
            FROM asset_ssd
            GROUP BY equip_barcode
        ) s ON a.equip_barcode = s.equip_barcode
        LEFT JOIN (
            SELECT equip_barcode,
                   GROUP_CONCAT(CONCAT(capacity, ' x ', quantity)
                       ORDER BY CAST(capacity AS UNSIGNED) ASC SEPARATOR '\n') AS hdd_list
            FROM asset_hdd
            GROUP BY equip_barcode
        ) h ON a.equip_barcode = h.equip_barcode
        {$whereSql}
        GROUP BY a.asset_id, a.equip_barcode, a.hostname, a.ip, a.asset_type, a.manufacturer,
                 a.model_name, a.serial_number, a.receipt_ym, a.os, a.cpu_type, a.cpu_qty,
                 a.cpu_core, a.swap_size, a.ma, a.status, a.facility_status, a.purpose,
                 a.purpose_detail, a.own_team, a.standard_service, a.unit_service,
                 a.asset_history, a.rack_location, a.mounted_location, m.mem_list, s.ssd_list, h.hdd_list
        ORDER BY {$orderBy}";

$stmt = $mysqli->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$bindParams);
}
$stmt->execute();
$res = $stmt->get_result();
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

date_default_timezone_set('Asia/Seoul');
$filename = 'asset_list_' . date('Ymd_His') . '.xls';
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
    <td style="mso-number-format:'\@';"><?= h($r['equip_barcode'] ?? '') ?></td>
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
