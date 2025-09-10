<?php
// Supabase 설정 파일

// Supabase 프로젝트 설정
define('SUPABASE_URL', 'https://your-project-id.supabase.co');
define('SUPABASE_ANON_KEY', 'your-anon-key-here');
define('SUPABASE_SERVICE_KEY', 'your-service-key-here');

// 데이터베이스 테이블명
define('USERS_TABLE', 'users');
define('QUESTIONS_TABLE', 'questions');
define('TEST_RESULTS_TABLE', 'test_results');

// Supabase 클라이언트 초기화
function getSupabaseClient() {
    return [
        'url' => SUPABASE_URL,
        'anon_key' => SUPABASE_ANON_KEY,
        'service_key' => SUPABASE_SERVICE_KEY
    ];
}

// API 요청 헤더 생성
function getSupabaseHeaders($useServiceKey = false) {
    $key = $useServiceKey ? SUPABASE_SERVICE_KEY : SUPABASE_ANON_KEY;
    
    return [
        'Content-Type: application/json',
        'apikey: ' . $key,
        'Authorization: Bearer ' . $key
    ];
}

// Supabase API 요청 함수
function supabaseRequest($endpoint, $method = 'GET', $data = null, $useServiceKey = false) {
    $url = SUPABASE_URL . '/rest/v1/' . $endpoint;
    $headers = getSupabaseHeaders($useServiceKey);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'data' => json_decode($response, true),
        'status' => $httpCode
    ];
}
?>
