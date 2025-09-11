<?php
// 데이터베이스 연결 테스트 및 디버깅

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>데이터베이스 연결 테스트</h2>";

$dbFile = 'personality_test.db';

try {
    echo "<p>1. SQLite 데이터베이스 파일 확인...</p>";
    if (file_exists($dbFile)) {
        echo "<p style='color: green;'>✅ 데이터베이스 파일 존재: $dbFile</p>";
        echo "<p>파일 크기: " . filesize($dbFile) . " bytes</p>";
        echo "<p>파일 권한: " . substr(sprintf('%o', fileperms($dbFile)), -4) . "</p>";
    } else {
        echo "<p style='color: red;'>❌ 데이터베이스 파일이 존재하지 않습니다!</p>";
    }

    echo "<p>2. PDO 연결 테스트...</p>";
    $pdo = new PDO("sqlite:$dbFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    echo "<p style='color: green;'>✅ PDO 연결 성공</p>";

    echo "<p>3. 테이블 존재 확인...</p>";
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll();
    if (empty($tables)) {
        echo "<p style='color: red;'>❌ 테이블이 존재하지 않습니다!</p>";
    } else {
        echo "<p style='color: green;'>✅ 테이블 목록:</p>";
        foreach ($tables as $table) {
            echo "<li>" . $table['name'] . "</li>";
        }
    }

    echo "<p>4. users 테이블 구조 확인...</p>";
    try {
        $columns = $pdo->query("PRAGMA table_info(users)")->fetchAll();
        if (empty($columns)) {
            echo "<p style='color: red;'>❌ users 테이블이 존재하지 않습니다!</p>";
        } else {
            echo "<p style='color: green;'>✅ users 테이블 구조:</p>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>컬럼명</th><th>타입</th><th>NULL허용</th><th>기본값</th><th>PK</th></tr>";
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td>" . $col['name'] . "</td>";
                echo "<td>" . $col['type'] . "</td>";
                echo "<td>" . ($col['notnull'] ? 'NO' : 'YES') . "</td>";
                echo "<td>" . ($col['dflt_value'] ?? 'NULL') . "</td>";
                echo "<td>" . ($col['pk'] ? 'YES' : 'NO') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ users 테이블 조회 실패: " . $e->getMessage() . "</p>";
    }

    echo "<p>5. 사용자 데이터 확인...</p>";
    try {
        $stmt = $pdo->query("SELECT id, username, name, email, created_at FROM users ORDER BY id");
        $users = $stmt->fetchAll();
        
        if (empty($users)) {
            echo "<p style='color: orange;'>⚠️ 사용자 데이터가 없습니다.</p>";
        } else {
            echo "<p style='color: green;'>✅ 사용자 목록 (총 " . count($users) . "명):</p>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>아이디</th><th>이름</th><th>이메일</th><th>가입일</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . $user['id'] . "</td>";
                echo "<td>" . htmlspecialchars($user['username']) . "</td>";
                echo "<td>" . htmlspecialchars($user['name']) . "</td>";
                echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                echo "<td>" . $user['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ 사용자 데이터 조회 실패: " . $e->getMessage() . "</p>";
    }

    echo "<p>6. 비밀번호 해시 확인...</p>";
    try {
        $stmt = $pdo->query("SELECT username, password FROM users LIMIT 5");
        $users = $stmt->fetchAll();
        
        foreach ($users as $user) {
            $hashLength = strlen($user['password']);
            $isValidHash = password_get_info($user['password']);
            echo "<p>사용자: " . htmlspecialchars($user['username']) . "</p>";
            echo "<ul>";
            echo "<li>해시 길이: $hashLength</li>";
            echo "<li>해시 알고리즘: " . ($isValidHash['algoName'] ?? '알 수 없음') . "</li>";
            echo "<li>해시 샘플: " . substr($user['password'], 0, 20) . "...</li>";
            echo "</ul>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ 비밀번호 해시 확인 실패: " . $e->getMessage() . "</p>";
    }

} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ 데이터베이스 연결 실패: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>해결 방법:</strong></p>";
echo "<ol>";
echo "<li>위 정보를 확인하여 문제점을 파악합니다.</li>";
echo "<li>필요시 데이터베이스를 초기화합니다.</li>";
echo "<li>테스트 계정으로 로그인을 시도합니다.</li>";
echo "</ol>";

echo "<p><a href='index.php'>메인 페이지로 돌아가기</a></p>";
?>
