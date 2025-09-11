<?php
// 로그인 문제 진단 스크립트
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>로그인 문제 진단</title>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} .warning{color:orange;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;}</style>";
echo "</head><body>";
echo "<h1>🔍 로그인 문제 진단</h1>";

$dbFile = 'personality_test.db';

// 1. 데이터베이스 연결 테스트
echo "<h2>1. 데이터베이스 연결</h2>";
try {
    $pdo = new PDO("sqlite:$dbFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    echo "<p class='success'>✅ 데이터베이스 연결 성공</p>";
} catch(PDOException $e) {
    echo "<p class='error'>❌ 데이터베이스 연결 실패: " . $e->getMessage() . "</p>";
    exit;
}

// 2. 사용자 목록 확인
echo "<h2>2. 등록된 사용자 확인</h2>";
try {
    $stmt = $pdo->prepare("SELECT id, username, name, email, created_at FROM users ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "<p class='warning'>⚠️ 등록된 사용자가 없습니다.</p>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>아이디</th><th>이름</th><th>이메일</th><th>가입일</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p class='info'>총 " . count($users) . "명의 사용자가 등록되어 있습니다.</p>";
    }
} catch(PDOException $e) {
    echo "<p class='error'>❌ 사용자 조회 실패: " . $e->getMessage() . "</p>";
}

// 3. 테스트 로그인 시뮬레이션
echo "<h2>3. 테스트 로그인 시뮬레이션</h2>";
$testAccounts = [
    ['username' => 'testuser', 'password' => 'test123!'],
    ['username' => 'admin', 'password' => 'admin123!']
];

foreach ($testAccounts as $account) {
    echo "<h3>계정 테스트: {$account['username']}</h3>";
    
    try {
        // 사용자 조회
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$account['username']]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "<p class='success'>✅ 사용자 존재</p>";
            echo "<ul>";
            echo "<li>ID: {$user['id']}</li>";
            echo "<li>이름: {$user['name']}</li>";
            echo "<li>이메일: {$user['email']}</li>";
            echo "<li>해시 길이: " . strlen($user['password']) . "</li>";
            echo "</ul>";
            
            // 비밀번호 검증
            if (password_verify($account['password'], $user['password'])) {
                echo "<p class='success'>✅ 비밀번호 검증 성공</p>";
            } else {
                echo "<p class='error'>❌ 비밀번호 검증 실패</p>";
                echo "<p>입력 비밀번호: {$account['password']}</p>";
                echo "<p>저장된 해시: " . substr($user['password'], 0, 60) . "...</p>";
                
                // 비밀번호 재설정
                $newHash = password_hash($account['password'], PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
                $updateStmt->execute([$newHash, $account['username']]);
                echo "<p class='warning'>⚠️ 비밀번호를 재설정했습니다. 다시 테스트해보세요.</p>";
            }
        } else {
            echo "<p class='error'>❌ 사용자가 존재하지 않습니다</p>";
            
            // 테스트 사용자 생성
            $hashedPassword = password_hash($account['password'], PASSWORD_DEFAULT);
            $insertStmt = $pdo->prepare("INSERT INTO users (username, password, email, name, phone, team, organization) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insertStmt->execute([
                $account['username'], 
                $hashedPassword, 
                $account['username'] . '@test.com', 
                $account['username'] === 'admin' ? '관리자' : '테스트사용자',
                '010-0000-0000',
                $account['username'] === 'admin' ? '관리팀' : '개발팀',
                '인삼안사'
            ]);
            echo "<p class='success'>✅ 테스트 사용자를 생성했습니다.</p>";
        }
    } catch(PDOException $e) {
        echo "<p class='error'>❌ 오류: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
}

// 4. 실제 로그인 폼 테스트
echo "<h2>4. 실제 로그인 테스트</h2>";
echo "<p class='info'>아래 폼으로 직접 로그인을 테스트해보세요:</p>";

echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; max-width: 400px;'>";
echo "<form method='POST' action='index.php?debug=1'>";
echo "<input type='hidden' name='action' value='login'>";
echo "<div style='margin-bottom: 15px;'>";
echo "<label>아이디:</label><br>";
echo "<input type='text' name='username' value='testuser' style='width: 100%; padding: 8px; margin-top: 5px;'>";
echo "</div>";
echo "<div style='margin-bottom: 15px;'>";
echo "<label>비밀번호:</label><br>";
echo "<input type='password' name='password' value='test123!' style='width: 100%; padding: 8px; margin-top: 5px;'>";
echo "</div>";
echo "<button type='submit' style='background: #4facfe; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>로그인 테스트</button>";
echo "</form>";
echo "</div>";

// 5. 로그인 처리 결과 확인
if ($_POST['action'] ?? '' === 'login') {
    echo "<h2>5. 로그인 처리 결과</h2>";
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    echo "<div style='background: #e8f4f8; padding: 15px; border-radius: 8px;'>";
    echo "<h4>입력된 데이터:</h4>";
    echo "<p>아이디: " . htmlspecialchars($username) . "</p>";
    echo "<p>비밀번호: " . htmlspecialchars($password) . "</p>";
    echo "<p>액션: " . ($_POST['action'] ?? 'NULL') . "</p>";
    
    if (empty($username) || empty($password)) {
        echo "<p class='error'>❌ 아이디 또는 비밀번호가 비어있습니다</p>";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user) {
                echo "<p class='success'>✅ 사용자 조회 성공</p>";
                if (password_verify($password, $user['password'])) {
                    echo "<p class='success'>✅ 비밀번호 검증 성공 - 로그인 가능</p>";
                    echo "<p><a href='index.php' style='background: #51cf66; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px;'>메인 페이지로 이동</a></p>";
                } else {
                    echo "<p class='error'>❌ 비밀번호 불일치</p>";
                }
            } else {
                echo "<p class='error'>❌ 사용자를 찾을 수 없습니다</p>";
            }
        } catch(PDOException $e) {
            echo "<p class='error'>❌ 데이터베이스 오류: " . $e->getMessage() . "</p>";
        }
    }
    echo "</div>";
}

echo "<hr>";
echo "<h2>결론 및 해결방법</h2>";
echo "<ol>";
echo "<li>위의 테스트 결과를 확인하여 계정 상태를 점검하세요.</li>";
echo "<li>비밀번호가 재설정된 경우, 새로운 비밀번호로 로그인을 시도하세요.</li>";
echo "<li>문제가 지속되면 <a href='fix_login.php'>fix_login.php</a>를 실행하세요.</li>";
echo "<li>메인 페이지에서 로그인할 때 <code>?debug=1</code>을 URL에 추가하여 디버깅 정보를 확인하세요.</li>";
echo "</ol>";

echo "<div style='margin-top: 20px;'>";
echo "<a href='index.php' style='background: #4facfe; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>메인 페이지</a>";
echo "<a href='fix_login.php' style='background: #ff6b6b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>로그인 수정</a>";
echo "<a href='test_registration.php' style='background: #51cf66; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>회원가입 테스트</a>";
echo "</div>";

echo "</body></html>";
?>
