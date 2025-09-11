<?php
// SQLite 데이터베이스 초기화 스크립트
// https://anteate2044.dothome.co.kr/sqlite_setup.php 에서 실행

header('Content-Type: text/html; charset=utf-8');
echo "<h1>SQLite 데이터베이스 초기화</h1>";

try {
    // 데이터베이스 연결
    $pdo = new PDO('sqlite:personality_test.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ 데이터베이스 연결 성공</p>";
    
    // 1. 기존 테이블들 삭제
    $pdo->exec("DROP TABLE IF EXISTS test_results");
    $pdo->exec("DROP TABLE IF EXISTS questions");
    $pdo->exec("DROP TABLE IF EXISTS users");
    echo "<p>✅ 기존 테이블 삭제 완료</p>";
    
    // 2. users 테이블 생성 (SQLite용)
    $pdo->exec("CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        name VARCHAR(100) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p>✅ users 테이블 생성 완료</p>";
    
    // 3. questions 테이블 생성
    $pdo->exec("CREATE TABLE questions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        question_text TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p>✅ questions 테이블 생성 완료</p>";
    
    // 4. test_results 테이블 생성
    $pdo->exec("CREATE TABLE test_results (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        result_data TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
    echo "<p>✅ test_results 테이블 생성 완료</p>";
    
    // 5. 기본 관리자 계정 생성
    $adminPassword = password_hash('admin123!', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, name) VALUES (?, ?, ?, ?)");
    $stmt->execute(['admin', $adminPassword, 'admin@test.com', '관리자']);
    echo "<p>✅ 관리자 계정 생성 완료 (admin / admin123!)</p>";
    
    // 6. 테스트 사용자 계정 생성
    $testPassword = password_hash('test123!', PASSWORD_DEFAULT);
    $stmt->execute(['testuser', $testPassword, 'test@test.com', '테스트사용자']);
    echo "<p>✅ 테스트 계정 생성 완료 (testuser / test123!)</p>";
    
    // 7. 기본 질문들 삽입
    $questions = [
        '새로운 환경에 적응하는 것이 어렵다',
        '다른 사람들과 함께 일하는 것을 선호한다',
        '혼자만의 시간이 필요하다',
        '변화를 두려워한다',
        '새로운 아이디어를 제안하는 것을 좋아한다',
        '규칙을 엄격히 지키는 것을 선호한다',
        '감정을 표현하는 것이 어렵다',
        '다른 사람의 의견을 듣는 것을 좋아한다',
        '위험을 감수하는 것을 두려워한다',
        '완벽한 결과를 추구한다'
    ];
    
    $stmt = $pdo->prepare("INSERT INTO questions (question_text) VALUES (?)");
    foreach ($questions as $question) {
        $stmt->execute([$question]);
    }
    echo "<p>✅ 기본 질문 10개 삽입 완료</p>";
    
    // 8. 결과 확인
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM questions");
    $questionCount = $stmt->fetch()['count'];
    
    echo "<h2>📊 초기화 결과</h2>";
    echo "<p>👥 사용자 수: {$userCount}명</p>";
    echo "<p>❓ 질문 수: {$questionCount}개</p>";
    
    echo "<h2>🔑 테스트 계정</h2>";
    echo "<p><strong>관리자:</strong> admin / admin123!</p>";
    echo "<p><strong>테스트:</strong> testuser / test123!</p>";
    
    echo "<h2>✅ 데이터베이스 초기화 완료!</h2>";
    echo "<p><a href='index.php'>메인 페이지로 이동</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ 오류 발생: " . $e->getMessage() . "</p>";
}
?>
