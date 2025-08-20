<?php
// 사용자 IP ↔ 이름 매핑 및 유틸 함수 모음
if (!defined('USER_MAP_PHP')) {
    define('USER_MAP_PHP', true);

    // 고정 매핑: 필요 시 확장
    function user_name_by_ip(?string $ip): string {
        static $map = [
            '172.21.100.22' => '한상진',
            '172.21.100.23' => '신익현',
            '172.21.100.24' => '박경오',
            '172.21.100.25' => '윤준성',
            '172.21.100.26' => '이승렬',
            '172.21.100.27' => '이남수',
            '172.21.100.28' => '남승윤',
        ];
        if (!$ip) return '-';
        return $map[$ip] ?? $ip;
    }

    function ip_to_user(?string $ip): string {
        return user_name_by_ip($ip);
    }

    function client_ip(): string {
        $keys = ['HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'];
        foreach ($keys as $k) {
            if (!empty($_SERVER[$k])) {
                $v = $_SERVER[$k];
                if ($k === 'HTTP_X_FORWARDED_FOR') {
                    $parts = array_map('trim', explode(',', $v));
                    return $parts[0] ?? $v;
                }
                return $v;
            }
        }
        return '';
    }
}

