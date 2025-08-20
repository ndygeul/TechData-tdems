<?php
function get_search_params() {
    $field   = trim($_GET['field'] ?? 'all');
    $q       = trim($_GET['q'] ?? '');
    $per     = (int)($_GET['per'] ?? 20);
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $include = (int)($_GET['include_deleted'] ?? 0);
    // 기본 정렬을 랙/장착 위치 오름차순으로 설정
    $sort    = trim($_GET['sort'] ?? 'rack');
    $dir     = strtolower(trim($_GET['dir'] ?? 'asc')) === 'asc' ? 'ASC' : 'DESC';
    return compact('field','q','per','page','include','sort','dir');
}

function build_where_clause($field, $q, $include) {
    $where = [];
    $params = [];
    $types  = '';

    if (!$include) {
        $where[] = "a.del_yn = 'N'";
    }

    if ($q !== '') {
        switch ($field) {
            case 'equip_barcode':
            case 'hostname':
            case 'ip':
            case 'asset_type':
            case 'own_team':
            case 'standard_service':
            case 'unit_service':
            case 'manufacturer':
            case 'os':
            case 'ma':
            case 'status':
            case 'purpose':
            case 'facility_status':
                $where[] = "a.$field LIKE CONCAT('%', ?, '%')";
                $types  .= 's';
                $params[] = $q;
                break;
            default:
                $where[] = "(a.equip_barcode LIKE CONCAT('%', ?, '%')"
                         . " OR a.hostname LIKE CONCAT('%', ?, '%')"
                         . " OR a.ip LIKE CONCAT('%', ?, '%')"
                         . " OR a.asset_type LIKE CONCAT('%', ?, '%')"
                         . " OR a.own_team LIKE CONCAT('%', ?, '%')"
                         . " OR a.standard_service LIKE CONCAT('%', ?, '%')"
                         . " OR a.unit_service LIKE CONCAT('%', ?, '%')"
                         . " OR a.manufacturer LIKE CONCAT('%', ?, '%')"
                         . " OR a.os LIKE CONCAT('%', ?, '%')"
                         . " OR a.ma LIKE CONCAT('%', ?, '%')"
                         . " OR a.status LIKE CONCAT('%', ?, '%')"
                         . " OR a.purpose LIKE CONCAT('%', ?, '%')"
                         . " OR a.facility_status LIKE CONCAT('%', ?, '%'))";
                $types  .= 'sssssssssssss';
                array_push($params, $q, $q, $q, $q, $q, $q, $q, $q, $q, $q, $q, $q, $q);
                break;
        }
    }

    $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';
    return [$whereSql, $types, $params];
}

function count_assets(mysqli $mysqli, $whereSql, $types, $params) {
    $sqlCnt = "SELECT COUNT(*) AS c FROM asset a {$whereSql}";
    $stmt = $mysqli->prepare($sqlCnt);
    if ($types) { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    $res = $stmt->get_result();
    $total = (int)$res->fetch_assoc()['c'];
    $stmt->close();
    return $total;
}

function paginate($total, $per, $page) {
    $per = in_array($per, [10,20,30,50,100,200,300,500,1000]) ? $per : 20;
    $pages = max(1, (int)ceil($total / $per));
    $page = min($page, $pages);
    $offset = ($page - 1) * $per;
    return [$per, $page, $pages, $offset];
}

function fetch_asset_list(mysqli $mysqli, $whereSql, $sort, $dir, $per, $offset, $types, $params) {
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
    if (!array_key_exists($sort, $sortable)) { $sort = 'updated_at'; }

    $orderBy = ($sort === 'rack')
        ? "a.rack_location {$dir}, a.mounted_location {$dir}"
        : $sortable[$sort] . " {$dir}";
    $orderBy .= ", a.asset_id DESC";

    $sql = "SELECT a.asset_id, a.equip_barcode, a.hostname, a.ip,
                   a.own_team, a.standard_service, a.unit_service, a.asset_type,
                   a.rack_location, a.mounted_location,
                   a.manufacturer, a.model_name, a.os,
                   a.ma, a.status, a.purpose, a.facility_status,
                   a.del_yn,
                   m.mem_list, s.ssd_list, h.hdd_list
            FROM asset a
            LEFT JOIN (
                SELECT equip_barcode,
                       GROUP_CONCAT(CAST(capacity AS UNSIGNED) * CAST(quantity AS UNSIGNED)
                           ORDER BY CAST(capacity AS UNSIGNED) ASC
                           SEPARATOR '\n') AS mem_list
                FROM asset_memory
                GROUP BY equip_barcode
            ) m ON a.equip_barcode = m.equip_barcode
            LEFT JOIN (
                SELECT equip_barcode,
                       GROUP_CONCAT(CONCAT(capacity, ' x ', quantity)
                           ORDER BY CAST(capacity AS UNSIGNED) ASC
                           SEPARATOR '\n') AS ssd_list
                FROM asset_ssd
                GROUP BY equip_barcode
            ) s ON a.equip_barcode = s.equip_barcode
            LEFT JOIN (
                SELECT equip_barcode,
                       GROUP_CONCAT(CONCAT(capacity, ' x ', quantity)
                           ORDER BY CAST(capacity AS UNSIGNED) ASC
                           SEPARATOR '\n') AS hdd_list
                FROM asset_hdd
                GROUP BY equip_barcode
            ) h ON a.equip_barcode = h.equip_barcode
            {$whereSql}
            GROUP BY a.asset_id, a.equip_barcode, a.hostname, a.ip,
                     a.own_team, a.standard_service, a.unit_service, a.asset_type,
                     a.rack_location, a.mounted_location,
                     a.manufacturer, a.model_name, a.os,
                     a.ma, a.status, a.purpose, a.facility_status,
                     a.del_yn
            ORDER BY {$orderBy}
            LIMIT ? OFFSET ?";

    $stmt = $mysqli->prepare($sql);
    if ($types) {
        $bindTypes = $types . 'ii';
        $bindParams = array_merge($params, [$per, $offset]);
        $stmt->bind_param($bindTypes, ...$bindParams);
    } else {
        $stmt->bind_param('ii', $per, $offset);
    }
    $stmt->execute();
    $list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $list;
}
