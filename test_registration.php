<?php
// 회원가입 및 로그인 테스트 스크립트
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>회원가입 및 로그인 테스트</title>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;}</style>";
echo "</head><body>";
echo "<h1>회원가입 및 로그인 테스트</h1>";

$dbFile = 'personality_test.db';

try {
    $pdo = new PDO("sqlite:$dbFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    echo "<p class='success'>✅ 데이터베이스 연결 성공</p>";
    
    // 1. 현재 사용자 목록 조회
    echo "<h2>1. 현재 사용자 목록</h2>";
    $stmt = $pdo->prepare("SELECT id, username, name, email, phone, team, organization, created_at FROM users ORDER BY created_at DESC");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "<p class='error'>❌ 사용자가 없습니다.</p>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>아이디</th><th>이름</th><th>이메일</th><th>연락처</th><th>팀</th><th>소속</th><th>가입일</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['phone']}</td>";
            echo "<td>{$user['team']}</td>";
            echo "<td>{$user['organization']}</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p class='info'>총 " . count($users) . "명의 사용자가 등록되어 있습니다.</p>";
    }
    
    // 2. 테스트 회원가입
    echo "<h2>2. 테스트 회원가입</h2>";
    $testUsername = 'test_' . date('His');
    $testPassword = 'testpass123!';
    $testEmail = $testUsername . '@test.com';
    $testName = '테스트사용자' . date('His');
    $testPhone = '010-1111-2222';
    $testTeam = '개발팀';
    
    try {
        // 중복 확인
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$testUsername, $testEmail]);
        if ($stmt->fetchColumn() > 0) {
            echo "<p class='error'>❌ 이미 사용 중인 아이디 또는 이메일입니다.</p>";
        } else {
            $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, name, phone, team, organization) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([$testUsername, $hashedPassword, $testEmail, $testName, $testPhone, $testTeam, '인삼안사']);
            
            if ($result) {
                echo "<p class='success'>✅ 테스트 회원가입 성공</p>";
                echo "<ul>";
                echo "<li><strong>아이디:</strong> $testUsername</li>";
                echo "<li><strong>비밀번호:</strong> $testPassword</li>";
                echo "<li><strong>이메일:</strong> $testEmail</li>";
                echo "<li><strong>이름:</strong> $testName</li>";
                echo "</ul>";
                
                // 3. 테스트 로그인
                echo "<h2>3. 테스트 로그인</h2>";
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$testUsername]);
                $user = $stmt->fetch();
                
                if ($user) {
                    echo "<p class='success'>✅ 사용자 조회 성공</p>";
                    echo "<p><strong>DB에서 조회된 정보:</strong></p>";
                    echo "<ul>";
                    echo "<li>ID: {$user['id']}</li>";
                    echo "<li>아이디: {$user['username']}</li>";
                    echo "<li>이름: {$user['name']}</li>";
                    echo "<li>해시 길이: " . strlen($user['password']) . "</li>";
                    echo "</ul>";
                    
                    // 비밀번호 검증
                    if (password_verify($testPassword, $user['password'])) {
                        echo "<p class='success'>✅ 비밀번호 검증 성공 - 로그인 가능</p>";
                    } else {
                        echo "<p class='error'>❌ 비밀번호 검증 실패</p>";
                        echo "<p>입력 비밀번호: $testPassword</p>";
                        echo "<p>저장된 해시: " . substr($user['password'], 0, 50) . "...</p>";
                    }
                } else {
                    echo "<p class='error'>❌ 사용자 조회 실패</p>";
                }
            } else {
                echo "<p class='error'>❌ 테스트 회원가입 실패</p>";
            }
        }
    } catch(PDOException $e) {
        echo "<p class='error'>❌ 회원가입 오류: " . $e->getMessage() . "</p>";
    }
    
    // 4. 기존 계정 로그인 테스트
    echo "<h2>4. 기존 계정 로그인 테스트</h2>";
    $testAccounts = [
        ['username' => 'admin', 'password' => 'admin123!'],
        ['username' => 'testuser', 'password' => 'test123!']
    ];
    
    foreach ($testAccounts as $account) {
        echo "<h3>계정: {$account['username']}</h3>";
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$account['username']]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "<p class='success'>✅ 사용자 존재</p>";
            if (password_verify($account['password'], $user['password'])) {
                echo "<p class='success'>✅ 비밀번호 검증 성공</p>";
            } else {
                echo "<p class='error'>❌ 비밀번호 검증 실패</p>";
                echo "<p>예상 비밀번호: {$account['password']}</p>";
                echo "<p>해시: " . substr($user['password'], 0, 50) . "...</p>";
            }
        } else {
            echo "<p class='error'>❌ 사용자 없음</p>";
        }
    }
    
    // 5. 데이터베이스 구조 확인
    echo "<h2>5. 데이터베이스 구조 확인</h2>";
    $stmt = $pdo->query("PRAGMA table_info(users)");
    $columns = $stmt->fetchAll();
    
    echo "<table>";
    echo "<tr><th>컬럼명</th><th>타입</th><th>NULL 허용</th><th>기본값</th><th>Primary Key</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['name']}</td>";
        echo "<td>{$col['type']}</td>";
        echo "<td>" . ($col['notnull'] ? 'NO' : 'YES') . "</td>";
        echo "<td>" . ($col['dflt_value'] ?? 'NULL') . "</td>";
        echo "<td>" . ($col['pk'] ? 'YES' : 'NO') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch(PDOException $e) {
    echo "<p class='error'>❌ 데이터베이스 오류: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>결론 및 권장사항</h2>";
echo "<ol>";
echo "<li>위 테스트 결과를 확인하여 문제점을 파악하세요.</li>";
echo "<li>회원가입이 성공했다면 새 계정으로 메인 페이지에서 로그인을 시도하세요.</li>";
echo "<li>기존 계정 로그인이 실패한다면 fix_login.php를 실행하세요.</li>";
echo "</ol>";

echo "<p><a href='index.php' style='background:#4facfe;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>메인 페이지로 이동</a></p>";
echo "<p><a href='fix_login.php' style='background:#ff6b6b;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-left:10px;'>로그인 수정 스크립트 실행</a></p>";

echo "</body></html>";
?>
