<?php
/**
 * 커서 AI용 데이터베이스 클라이언트
 * 이 파일을 통해 커서 AI에서 웹사이트 데이터베이스를 관리할 수 있습니다.
 */

class CursorDBClient {
    private $apiUrl;
    private $token;
    
    public function __construct($baseUrl = null) {
        // 웹사이트 URL을 자동으로 감지하거나 수동 설정
        $this->apiUrl = ($baseUrl ?: 'https://anteate2044.dothome.co.kr') . '/db_manager.php';
        $this->token = 'cursor_ai_token_2024';
    }
    
    /**
     * API 호출
     */
    private function apiCall($action, $data = []) {
        $data['action'] = $action;
        $data['token'] = $this->token;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false) {
            return ['success' => false, 'error' => 'API 호출 실패'];
        }
        
        $result = json_decode($response, true);
        if ($result === null) {
            return ['success' => false, 'error' => 'Invalid JSON response', 'raw' => $response];
        }
        
        return $result;
    }
    
    /**
     * 모든 사용자 조회
     */
    public function getUsers() {
        echo "🔍 사용자 목록 조회 중...\n";
        $result = $this->apiCall('get_users');
        
        if ($result['success']) {
            echo "✅ 사용자 목록 조회 성공\n";
            echo "총 " . count($result['data']) . "명의 사용자가 있습니다.\n\n";
            
            printf("%-5s %-15s %-15s %-25s %-20s\n", 'ID', '아이디', '이름', '이메일', '가입일');
            echo str_repeat('-', 80) . "\n";
            
            foreach ($result['data'] as $user) {
                printf("%-5s %-15s %-15s %-25s %-20s\n", 
                    $user['id'], 
                    $user['username'], 
                    $user['name'], 
                    $user['email'], 
                    $user['created_at']
                );
            }
            return $result['data'];
        } else {
            echo "❌ 오류: " . $result['error'] . "\n";
            return false;
        }
    }
    
    /**
     * 특정 사용자 조회
     */
    public function getUser($id) {
        echo "🔍 사용자 ID {$id} 조회 중...\n";
        $result = $this->apiCall('get_user', ['id' => $id]);
        
        if ($result['success']) {
            echo "✅ 사용자 조회 성공\n";
            $user = $result['data'];
            echo "ID: {$user['id']}\n";
            echo "아이디: {$user['username']}\n";
            echo "이름: {$user['name']}\n";
            echo "이메일: {$user['email']}\n";
            echo "가입일: {$user['created_at']}\n";
            return $user;
        } else {
            echo "❌ 오류: " . $result['error'] . "\n";
            return false;
        }
    }
    
    /**
     * 새 사용자 생성
     */
    public function createUser($username, $password, $email, $name) {
        echo "👤 새 사용자 생성 중...\n";
        $result = $this->apiCall('create_user', [
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'name' => $name
        ]);
        
        if ($result['success']) {
            echo "✅ 사용자 생성 성공\n";
            echo "사용자 ID: " . $result['user_id'] . "\n";
            echo "아이디: {$username}\n";
            echo "이름: {$name}\n";
            return $result['user_id'];
        } else {
            echo "❌ 오류: " . $result['error'] . "\n";
            return false;
        }
    }
    
    /**
     * 사용자 정보 수정
     */
    public function updateUser($id, $data) {
        echo "✏️ 사용자 ID {$id} 수정 중...\n";
        $data['id'] = $id;
        $result = $this->apiCall('update_user', $data);
        
        if ($result['success']) {
            echo "✅ 사용자 정보 수정 성공\n";
            return true;
        } else {
            echo "❌ 오류: " . $result['error'] . "\n";
            return false;
        }
    }
    
    /**
     * 사용자 삭제
     */
    public function deleteUser($id) {
        echo "🗑️ 사용자 ID {$id} 삭제 중...\n";
        $result = $this->apiCall('delete_user', ['id' => $id]);
        
        if ($result['success']) {
            echo "✅ 사용자 삭제 성공\n";
            return true;
        } else {
            echo "❌ 오류: " . $result['error'] . "\n";
            return false;
        }
    }
    
    /**
     * 비밀번호 재설정
     */
    public function resetPassword($id, $newPassword) {
        echo "🔑 사용자 ID {$id} 비밀번호 재설정 중...\n";
        $result = $this->apiCall('reset_password', [
            'id' => $id,
            'new_password' => $newPassword
        ]);
        
        if ($result['success']) {
            echo "✅ 비밀번호 재설정 성공\n";
            return true;
        } else {
            echo "❌ 오류: " . $result['error'] . "\n";
            return false;
        }
    }
    
    /**
     * 통계 정보 조회
     */
    public function getStats() {
        echo "📊 통계 정보 조회 중...\n";
        $result = $this->apiCall('get_stats');
        
        if ($result['success']) {
            echo "✅ 통계 조회 성공\n";
            $stats = $result['data'];
            echo "총 사용자 수: {$stats['user_count']}\n";
            echo "검사 결과 수: {$stats['test_result_count']}\n";
            
            echo "\n최근 가입자:\n";
            foreach ($stats['recent_users'] as $user) {
                echo "- {$user['name']} ({$user['username']}) - {$user['created_at']}\n";
            }
            
            return $stats;
        } else {
            echo "❌ 오류: " . $result['error'] . "\n";
            return false;
        }
    }
    
    /**
     * SQL 쿼리 실행 (SELECT만)
     */
    public function executeSQL($sql) {
        echo "🔍 SQL 쿼리 실행 중...\n";
        echo "쿼리: {$sql}\n";
        
        $result = $this->apiCall('execute_sql', ['sql' => $sql]);
        
        if ($result['success']) {
            echo "✅ 쿼리 실행 성공\n";
            
            if (empty($result['data'])) {
                echo "결과가 없습니다.\n";
                return [];
            }
            
            // 테이블 형태로 출력
            $headers = array_keys($result['data'][0]);
            $widths = [];
            
            // 컬럼 너비 계산
            foreach ($headers as $header) {
                $widths[$header] = max(strlen($header), 10);
                foreach ($result['data'] as $row) {
                    $widths[$header] = max($widths[$header], strlen($row[$header]));
                }
            }
            
            // 헤더 출력
            foreach ($headers as $header) {
                printf("%-{$widths[$header]}s ", $header);
            }
            echo "\n";
            echo str_repeat('-', array_sum($widths) + count($widths)) . "\n";
            
            // 데이터 출력
            foreach ($result['data'] as $row) {
                foreach ($headers as $header) {
                    printf("%-{$widths[$header]}s ", $row[$header]);
                }
                echo "\n";
            }
            
            return $result['data'];
        } else {
            echo "❌ 오류: " . $result['error'] . "\n";
            return false;
        }
    }
}

// 커맨드라인에서 직접 실행할 때의 예제
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    echo "🚀 커서 AI 데이터베이스 클라이언트 테스트\n";
    echo "=====================================\n\n";
    
    $client = new CursorDBClient();
    
    // 사용 예제
    if ($argc > 1) {
        switch ($argv[1]) {
            case 'users':
                $client->getUsers();
                break;
                
            case 'stats':
                $client->getStats();
                break;
                
            case 'create':
                if ($argc >= 6) {
                    $client->createUser($argv[2], $argv[3], $argv[4], $argv[5]);
                } else {
                    echo "사용법: php cursor_db_client.php create <username> <password> <email> <name>\n";
                }
                break;
                
            case 'sql':
                if ($argc >= 3) {
                    $client->executeSQL($argv[2]);
                } else {
                    echo "사용법: php cursor_db_client.php sql \"SELECT * FROM users LIMIT 5\"\n";
                }
                break;
                
            default:
                echo "사용 가능한 명령어:\n";
                echo "- users: 사용자 목록 조회\n";
                echo "- stats: 통계 정보 조회\n";
                echo "- create <username> <password> <email> <name>: 새 사용자 생성\n";
                echo "- sql \"<query>\": SQL 쿼리 실행\n";
        }
    } else {
        // 기본 테스트
        $client->getStats();
        echo "\n";
        $client->getUsers();
    }
}
?>
