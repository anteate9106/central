<?php
// 로그인 문제 해결을 위한 임시 스크립트

$dbFile = 'personality_test.db';

try {
    $pdo = new PDO("sqlite:$dbFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== 데이터베이스 연결 성공 ===\n";
    
    // 1. 현재 사용자 목록 확인
    echo "\n=== 현재 사용자 목록 ===\n";
    $stmt = $pdo->prepare("SELECT id, username, name, email FROM users");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "사용자가 없습니다. 테스트 사용자를 생성합니다.\n";
        
        // 테스트 사용자 생성
        $testPassword = password_hash('test123!', PASSWORD_DEFAULT);
        $adminPassword = password_hash('admin123!', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, name, phone, team, organization) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(['testuser', $testPassword, 'test@example.com', '테스트사용자', '010-1234-5678', '개발팀', '테스트회사']);
        $stmt->execute(['admin', $adminPassword, 'admin@test.com', '관리자', '010-0000-0000', '관리팀', '시스템']);
        
        echo "테스트 사용자가 생성되었습니다.\n";
        echo "아이디: testuser, 비밀번호: test123!\n";
        echo "아이디: admin, 비밀번호: admin123!\n";
    } else {
        foreach ($users as $user) {
            echo "ID: {$user['id']}, 아이디: {$user['username']}, 이름: {$user['name']}, 이메일: {$user['email']}\n";
        }
    }
    
    // 2. 비밀번호 해시 확인 및 재설정
    echo "\n=== 비밀번호 해시 재설정 ===\n";
    
    // testuser 비밀번호를 test123!으로 재설정
    $newPassword = password_hash('test123!', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'testuser'");
    $stmt->execute([$newPassword]);
    echo "testuser 비밀번호가 'test123!'으로 재설정되었습니다.\n";
    
    // admin 비밀번호를 admin123!으로 재설정
    $newPassword = password_hash('admin123!', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$newPassword]);
    echo "admin 비밀번호가 'admin123!'으로 재설정되었습니다.\n";
    
    // 3. 회원가입한 사용자들의 비밀번호도 확인
    echo "\n=== 모든 사용자 비밀번호 해시 확인 ===\n";
    $stmt = $pdo->prepare("SELECT username, password FROM users");
    $stmt->execute();
    $allUsers = $stmt->fetchAll();
    
    foreach ($allUsers as $user) {
        $hashLength = strlen($user['password']);
        echo "사용자: {$user['username']}, 해시길이: {$hashLength}\n";
        
        // 해시가 너무 짧으면 문제가 있을 수 있음
        if ($hashLength < 50) {
            echo "  ⚠️ 비밀번호 해시가 짧습니다. 재해시가 필요할 수 있습니다.\n";
        }
    }
    
    echo "\n=== 수정 완료 ===\n";
    echo "이제 다음 계정으로 로그인을 시도해보세요:\n";
    echo "- 아이디: testuser, 비밀번호: test123!\n";
    echo "- 아이디: admin, 비밀번호: admin123!\n";
    
} catch(PDOException $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
?>
