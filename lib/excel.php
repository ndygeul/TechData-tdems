<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/list.php';

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

function fetch_export_rows() {
    global $mysqli, $conn;

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
                           ORDER BY CAST(capacity AS UNSIGNED) ASC SEPARATOR '\\n') AS mem_list
                FROM asset_memory
                GROUP BY equip_barcode
            ) m ON a.equip_barcode = m.equip_barcode
            LEFT JOIN (
                SELECT equip_barcode,
                       GROUP_CONCAT(CONCAT(capacity, ' x ', quantity)
                           ORDER BY CAST(capacity AS UNSIGNED) ASC SEPARATOR '\\n') AS ssd_list
                FROM asset_ssd
                GROUP BY equip_barcode
            ) s ON a.equip_barcode = s.equip_barcode
            LEFT JOIN (
                SELECT equip_barcode,
                       GROUP_CONCAT(CONCAT(capacity, ' x ', quantity)
                           ORDER BY CAST(capacity AS UNSIGNED) ASC SEPARATOR '\\n') AS hdd_list
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

    return [$rows, $filename];
}