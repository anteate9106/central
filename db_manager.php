<?php
// 데이터베이스 원격 관리 API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// OPTIONS 요청 처리 (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 보안 토큰 (실제 운영 시에는 환경변수나 별도 설정 파일에서 관리)
define('API_TOKEN', 'cursor_ai_token_2024');

// 인증 확인
function authenticate() {
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? $_GET['token'] ?? $_POST['token'] ?? '';
    $token = str_replace('Bearer ', '', $token);
    
    if ($token !== API_TOKEN) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized access', 'message' => '인증이 필요합니다.']);
        exit;
    }
}

// 데이터베이스 연결
function getDatabase() {
    $dbFile = 'personality_test.db';
    try {
        $pdo = new PDO("sqlite:$dbFile");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed', 'message' => $e->getMessage()]);
        exit;
    }
}

// 인증 확인
authenticate();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $pdo = getDatabase();
    
    switch ($action) {
        case 'get_users':
            // 사용자 목록 조회
            $stmt = $pdo->prepare("SELECT id, username, name, email, created_at FROM users ORDER BY created_at DESC");
            $stmt->execute();
            $users = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $users]);
            break;
            
        case 'get_user':
            // 특정 사용자 조회
            $id = $_GET['id'] ?? $_POST['id'] ?? '';
            if (empty($id)) {
                throw new Exception('사용자 ID가 필요합니다.');
            }
            
            $stmt = $pdo->prepare("SELECT id, username, name, email, created_at FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            
            if (!$user) {
                throw new Exception('사용자를 찾을 수 없습니다.');
            }
            
            echo json_encode(['success' => true, 'data' => $user]);
            break;
            
        case 'create_user':
            // 사용자 생성
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $email = $_POST['email'] ?? '';
            $name = $_POST['name'] ?? '';
            
            if (empty($username) || empty($password) || empty($email) || empty($name)) {
                throw new Exception('모든 필수 필드를 입력해주세요.');
            }
            
            // 중복 확인
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('이미 사용 중인 아이디 또는 이메일입니다.');
            }
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, name) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $hashedPassword, $email, $name]);
            
            $userId = $pdo->lastInsertId();
            echo json_encode(['success' => true, 'message' => '사용자가 생성되었습니다.', 'user_id' => $userId]);
            break;
            
        case 'update_user':
            // 사용자 정보 수정
            $id = $_POST['id'] ?? '';
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $name = $_POST['name'] ?? '';
            
            if (empty($id)) {
                throw new Exception('사용자 ID가 필요합니다.');
            }
            
            $updates = [];
            $params = [];
            
            if (!empty($username)) {
                $updates[] = "username = ?";
                $params[] = $username;
            }
            if (!empty($email)) {
                $updates[] = "email = ?";
                $params[] = $email;
            }
            if (!empty($name)) {
                $updates[] = "name = ?";
                $params[] = $name;
            }
            
            if (empty($updates)) {
                throw new Exception('수정할 데이터가 없습니다.');
            }
            
            $params[] = $id;
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode(['success' => true, 'message' => '사용자 정보가 수정되었습니다.']);
            break;
            
        case 'delete_user':
            // 사용자 삭제
            $id = $_POST['id'] ?? $_GET['id'] ?? '';
            if (empty($id)) {
                throw new Exception('사용자 ID가 필요합니다.');
            }
            
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => '사용자가 삭제되었습니다.']);
            break;
            
        case 'reset_password':
            // 비밀번호 재설정
            $id = $_POST['id'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            
            if (empty($id) || empty($newPassword)) {
                throw new Exception('사용자 ID와 새 비밀번호가 필요합니다.');
            }
            
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $id]);
            
            echo json_encode(['success' => true, 'message' => '비밀번호가 재설정되었습니다.']);
            break;
            
        case 'get_stats':
            // 통계 정보
            $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $testResultCount = $pdo->query("SELECT COUNT(*) FROM test_results")->fetchColumn();
            
            // 최근 가입자
            $stmt = $pdo->prepare("SELECT username, name, created_at FROM users ORDER BY created_at DESC LIMIT 5");
            $stmt->execute();
            $recentUsers = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'user_count' => $userCount,
                    'test_result_count' => $testResultCount,
                    'recent_users' => $recentUsers
                ]
            ]);
            break;
            
        case 'execute_sql':
            // SQL 쿼리 직접 실행 (주의: 보안상 위험할 수 있음)
            $sql = $_POST['sql'] ?? '';
            if (empty($sql)) {
                throw new Exception('SQL 쿼리가 필요합니다.');
            }
            
            // SELECT 쿼리만 허용 (보안)
            if (!preg_match('/^\s*SELECT\s+/i', trim($sql))) {
                throw new Exception('SELECT 쿼리만 허용됩니다.');
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $results]);
            break;
            
        default:
            throw new Exception('알 수 없는 액션입니다.');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
