<?php
// CSRF Utilities for TDEMS (PHP 7+)

if (session_status() !== PHP_SESSION_ACTIVE) {
    // 세션이 아직 시작되지 않았다면 시작
    session_start();
}

/**
 * CSRF 토큰 생성/반환
 * - 세션당 1개 토큰을 유지
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // 64 hex chars
    }
    return $_SESSION['csrf_token'];
}

/**
 * POST 요청 시 CSRF 토큰 검증
 * - 실패하면 400으로 종료
 * - GET/HEAD 등은 통과(검증 대상 아님)
 */
function csrf_check_or_die(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return; // 검증 대상 아님
    }

    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $postedToken  = $_POST['csrf_token'] ?? '';

    if (!is_string($postedToken) || $postedToken === '' || !hash_equals($sessionToken, $postedToken)) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('Bad Request: Invalid CSRF token');
    }
}

/**
 * POST 요청 시 전달받은 토큰 검증 (boolean 반환)
 * - 토큰이 유효하면 true, 아니면 false
 */
function csrf_validate(string $token): bool
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    return is_string($token) && $token !== '' && hash_equals($sessionToken, $token);
}

/**
 * (선택) 폼에 넣을 hidden 필드 HTML 반환
 */
function csrf_field(): string
{
    $t = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="'.$t.'">';
}

