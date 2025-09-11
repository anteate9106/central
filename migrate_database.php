<?php
// 데이터베이스 마이그레이션 스크립트
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>데이터베이스 마이그레이션</title>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";
echo "</head><body>";
echo "<h1>데이터베이스 마이그레이션</h1>";

$dbFile = 'personality_test.db';

try {
    $pdo = new PDO("sqlite:$dbFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    echo "<p class='success'>✅ 데이터베이스 연결 성공</p>";
    
    // 1. 기존 테이블 구조 확인
    echo "<h2>1. 기존 테이블 구조 확인</h2>";
    $stmt = $pdo->query("PRAGMA table_info(users)");
    $columns = $stmt->fetchAll();
    
    $existingColumns = [];
    foreach ($columns as $col) {
        $existingColumns[] = $col['name'];
    }
    
    echo "<p class='info'>기존 컬럼: " . implode(', ', $existingColumns) . "</p>";
    
    // 2. 필요한 컬럼 추가
    echo "<h2>2. 테이블 구조 업데이트</h2>";
    
    // email UNIQUE 제약조건 추가를 위해 새 테이블 생성 후 데이터 이주
    if (!in_array('updated_at', $existingColumns)) {
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP");
            echo "<p class='success'>✅ updated_at 컬럼 추가 완료</p>";
        } catch (Exception $e) {
            echo "<p class='info'>ℹ️ updated_at 컬럼이 이미 존재하거나 추가할 수 없습니다: " . $e->getMessage() . "</p>";
        }
    }
    
    // 3. 기존 데이터 검증
    echo "<h2>3. 기존 데이터 검증</h2>";
    
    // 중복 이메일 확인
    $stmt = $pdo->query("SELECT email, COUNT(*) as count FROM users GROUP BY email HAVING count > 1");
    $duplicateEmails = $stmt->fetchAll();
    
    if (!empty($duplicateEmails)) {
        echo "<p class='error'>❌ 중복된 이메일 발견:</p>";
        foreach ($duplicateEmails as $dup) {
            echo "<p>- {$dup['email']} ({$dup['count']}개)</p>";
        }
        
        // 중복 이메일 수정
        foreach ($duplicateEmails as $dup) {
            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ? ORDER BY id");
            $stmt->execute([$dup['email']]);
            $users = $stmt->fetchAll();
            
            // 첫 번째는 그대로 두고, 나머지는 고유한 이메일로 변경
            for ($i = 1; $i < count($users); $i++) {
                $newEmail = $users[$i]['username'] . '_' . $users[$i]['id'] . '@temp.com';
                $updateStmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                $updateStmt->execute([$newEmail, $users[$i]['id']]);
                echo "<p class='success'>✅ 사용자 {$users[$i]['username']} 이메일을 {$newEmail}로 변경</p>";
            }
        }
    } else {
        echo "<p class='success'>✅ 중복된 이메일이 없습니다</p>";
    }
    
    // 4. NULL 값 처리
    echo "<h2>4. NULL 값 처리</h2>";
    
    // name이 NULL인 경우 처리
    $stmt = $pdo->prepare("UPDATE users SET name = username WHERE name IS NULL OR name = ''");
    $result = $stmt->execute();
    $affectedRows = $stmt->rowCount();
    if ($affectedRows > 0) {
        echo "<p class='success'>✅ {$affectedRows}개 레코드의 name 필드를 username으로 설정</p>";
    }
    
    // email이 NULL인 경우 처리
    $stmt = $pdo->prepare("UPDATE users SET email = username || '@temp.com' WHERE email IS NULL OR email = ''");
    $result = $stmt->execute();
    $affectedRows = $stmt->rowCount();
    if ($affectedRows > 0) {
        echo "<p class='success'>✅ {$affectedRows}개 레코드의 email 필드를 임시 값으로 설정</p>";
    }
    
    // 5. 인덱스 생성
    echo "<h2>5. 인덱스 생성</h2>";
    try {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_username ON users(username)");
        echo "<p class='success'>✅ username 인덱스 생성 완료</p>";
    } catch (Exception $e) {
        echo "<p class='info'>ℹ️ username 인덱스: " . $e->getMessage() . "</p>";
    }
    
    try {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)");
        echo "<p class='success'>✅ email 인덱스 생성 완료</p>";
    } catch (Exception $e) {
        echo "<p class='info'>ℹ️ email 인덱스: " . $e->getMessage() . "</p>";
    }
    
    // 6. 최종 테이블 구조 확인
    echo "<h2>6. 최종 테이블 구조</h2>";
    $stmt = $pdo->query("PRAGMA table_info(users)");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse:collapse;width:100%;'>";
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
    
    // 7. 사용자 수 확인
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    echo "<p class='info'>총 사용자 수: {$userCount}명</p>";
    
    echo "<h2>✅ 마이그레이션 완료</h2>";
    echo "<p class='success'>데이터베이스 구조가 성공적으로 업데이트되었습니다.</p>";
    
} catch(PDOException $e) {
    echo "<p class='error'>❌ 데이터베이스 오류: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='test_registration.php' style='background:#4facfe;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>회원가입 테스트</a></p>";
echo "<p><a href='index.php' style='background:#51cf66;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-left:10px;'>메인 페이지로 이동</a></p>";

echo "</body></html>";
?>
