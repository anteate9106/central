<?php
// 데이터베이스 연결 테스트 (SQLite)
$dbFile = 'personality_test.db';

echo "<h2>데이터베이스 연결 테스트 (SQLite)</h2>";

try {
    $pdo = new PDO("sqlite:$dbFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    echo "<p style='color: green;'>✅ SQLite 데이터베이스 연결 성공!</p>";
    
    // 테이블 존재 확인
    $tables = ['users', 'test_results', 'questions'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'");
        if ($stmt->fetch()) {
            echo "<p style='color: green;'>✅ 테이블 '$table' 존재</p>";
        } else {
            echo "<p style='color: red;'>❌ 테이블 '$table' 없음</p>";
        }
    }
    
    // 사용자 수 확인
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    echo "<p>총 사용자 수: $userCount</p>";
    
    // 질문 수 확인
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM questions");
    $questionCount = $stmt->fetch()['count'];
    echo "<p>총 질문 수: $questionCount</p>";
    
    echo "<p style='color: blue;'>💡 데이터베이스 파일 위치: " . realpath($dbFile) . "</p>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ 데이터베이스 연결 실패: " . $e->getMessage() . "</p>";
    echo "<p>SQLite 확장이 활성화되어 있는지 확인해주세요.</p>";
}
?>
