<?php
// 에러 리포팅 설정 (개발 환경에서만)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// 성공 메시지 처리
$success = $_SESSION['success_message'] ?? null;
if ($success) {
    unset($_SESSION['success_message']);
}

// 디버깅: POST 데이터 전체 확인
if (isset($_GET['debug']) && !empty($_POST)) {
    echo "<div style='background: #fff3cd; padding: 10px; margin: 10px; border-radius: 5px; border: 1px solid #ffeaa7;'>";
    echo "<h4>전체 POST 데이터 디버깅:</h4>";
    echo "<pre>" . htmlspecialchars(print_r($_POST, true)) . "</pre>";
    echo "<p><strong>요청된 액션:</strong> " . ($_POST['action'] ?? '없음') . "</p>";
    echo "</div>";
}

// 세션 상태 확인 함수
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// 데이터베이스 연결 설정 (SQLite 사용)
$dbFile = 'personality_test.db';

try {
    $pdo = new PDO("sqlite:$dbFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("데이터베이스 연결 실패: " . $e->getMessage());
}

// 테이블 생성 (처음 실행시)
$createUsersTable = "
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    name VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";

$createTestResultsTable = "
CREATE TABLE IF NOT EXISTS test_results (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    test_type VARCHAR(50) NOT NULL DEFAULT 'MBTI',
    result_data TEXT,
    score_data TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

$createQuestionsTable = "
CREATE TABLE IF NOT EXISTS questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    question_text TEXT NOT NULL,
    category VARCHAR(50) NOT NULL,
    dimension VARCHAR(10) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";

// 기존 웹사이트에 테이블이 이미 존재하므로 테이블 생성 생략
// $pdo->exec($createUsersTable);
// $pdo->exec($createTestResultsTable);
// $pdo->exec($createQuestionsTable);

// 기본 관리자 계정 생성 (항상 비밀번호 재설정)
try {
    $hashedAdminPassword = password_hash('admin123!', PASSWORD_DEFAULT);
    $checkAdmin = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $checkAdmin->execute();
    if ($checkAdmin->fetchColumn() == 0) {
        $insertAdmin = $pdo->prepare("INSERT INTO users (username, password, email, name) VALUES ('admin', ?, 'admin@test.com', '관리자')");
        $insertAdmin->execute([$hashedAdminPassword]);
    } else {
        // 기존 admin 계정의 비밀번호 업데이트
        $updateAdmin = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
        $updateAdmin->execute([$hashedAdminPassword]);
    }
} catch(PDOException $e) {
    // 기본 계정 생성 실패 시 무시 (이미 존재하거나 권한 문제)
}

// 테스트 사용자 계정 생성 (항상 비밀번호 재설정)
try {
    $hashedTestPassword = password_hash('test123!', PASSWORD_DEFAULT);
    $checkTestUser = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'testuser'");
    $checkTestUser->execute();
    if ($checkTestUser->fetchColumn() == 0) {
        $insertTestUser = $pdo->prepare("INSERT INTO users (username, password, email, name) VALUES ('testuser', ?, 'test@example.com', '테스트사용자')");
        $insertTestUser->execute([$hashedTestPassword]);
    } else {
        // 기존 testuser 계정의 비밀번호 업데이트
        $updateTestUser = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'testuser'");
        $updateTestUser->execute([$hashedTestPassword]);
    }
} catch(PDOException $e) {
    // 기본 계정 생성 실패 시 무시 (이미 존재하거나 권한 문제)
}

// 기본 질문 데이터 삽입
try {
    $checkQuestions = $pdo->prepare("SELECT COUNT(*) FROM questions");
    $checkQuestions->execute();
    if ($checkQuestions->fetchColumn() == 0) {
    $questions = [
        // E/I (외향/내향)
        ['나는 새로운 사람들과 만나는 것을 즐긴다', 'social', 'EI'],
        ['큰 그룹에서 에너지를 얻는다', 'social', 'EI'],
        ['혼자 시간을 보내는 것을 선호한다', 'introversion', 'EI'],
        ['사고 과정을 말로 표현하는 것을 좋아한다', 'communication', 'EI'],
        
        // S/N (감각/직관)
        ['구체적인 사실과 세부사항에 집중한다', 'detail', 'SN'],
        ['실용적인 해결책을 선호한다', 'practical', 'SN'],
        ['새로운 아이디어와 가능성을 탐구한다', 'innovation', 'SN'],
        ['미래의 가능성에 대해 생각하는 것을 좋아한다', 'future', 'SN'],
        
        // T/F (사고/감정)
        ['논리적 분석을 통해 결정을 내린다', 'logic', 'TF'],
        ['객관적인 기준으로 판단한다', 'objective', 'TF'],
        ['다른 사람의 감정을 고려한다', 'empathy', 'TF'],
        ['조화와 협력을 중시한다', 'harmony', 'TF'],
        
        // J/P (판단/인식)
        ['계획을 세우고 체계적으로 일한다', 'planning', 'JP'],
        ['마감일을 지키는 것을 중요하게 생각한다', 'deadline', 'JP'],
        ['유연하고 적응력이 있다', 'flexibility', 'JP'],
        ['새로운 정보에 열려있다', 'openness', 'JP']
    ];
    
        $insertQuestion = $pdo->prepare("INSERT INTO questions (question_text, category, dimension) VALUES (?, ?, ?)");
        foreach ($questions as $question) {
            $insertQuestion->execute($question);
        }
    }
} catch(PDOException $e) {
    // 질문 데이터 삽입 실패 시 무시 (테이블이 없거나 권한 문제)
}

// 로그인 처리
if ($_POST['action'] ?? '' === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // 디버깅: 로그인 시도 로그
    if (isset($_GET['debug'])) {
        echo "<div style='background: #e8f4f8; padding: 10px; margin: 10px; border-radius: 5px;'>";
        echo "<h4>로그인 디버깅 정보:</h4>";
        echo "<p>액션: " . ($_POST['action'] ?? 'NULL') . "</p>";
        echo "<p>아이디: " . htmlspecialchars($username) . "</p>";
        echo "<p>비밀번호 길이: " . strlen($password) . "</p>";
        echo "<p>POST 데이터: " . htmlspecialchars(json_encode($_POST)) . "</p>";
        echo "</div>";
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user) {
            // 디버깅 정보 표시
            if (isset($_GET['debug'])) {
                echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px; border-radius: 5px;'>";
                echo "<h4>디버깅 정보:</h4>";
                echo "<p>사용자: " . htmlspecialchars($username) . "</p>";
                echo "<p>입력 비밀번호: " . htmlspecialchars($password) . "</p>";
                echo "<p>DB 해시: " . substr($user['password'], 0, 30) . "...</p>";
                echo "<p>해시 길이: " . strlen($user['password']) . "</p>";
                echo "<p>password_verify 결과: " . (password_verify($password, $user['password']) ? 'TRUE' : 'FALSE') . "</p>";
                echo "</div>";
            }
            
            if (password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['success_message'] = "로그인에 성공했습니다! 환영합니다, {$user['name']}님!";
                header('Location: test_selection.html');
                exit;
            } else {
                $error = "비밀번호가 일치하지 않습니다. <a href='?debug=1' style='font-size: 12px;'>디버그 보기</a>";
            }
        } else {
            $error = "존재하지 않는 아이디입니다. <a href='?debug=1' style='font-size: 12px;'>디버그 보기</a>";
        }
    } catch(PDOException $e) {
        $error = "로그인 처리 중 오류가 발생했습니다: " . $e->getMessage();
    }
}

// 회원가입 처리  
elseif ($_POST['action'] ?? '' === 'register') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $email = $_POST['email'] ?? '';
    $name = $_POST['name'] ?? '';
    
    // 디버깅: 회원가입 시도 로그
    if (isset($_GET['debug'])) {
        echo "<div style='background: #f8e8e8; padding: 10px; margin: 10px; border-radius: 5px;'>";
        echo "<h4>회원가입 디버깅 정보:</h4>";
        echo "<p>액션: " . ($_POST['action'] ?? 'NULL') . "</p>";
        echo "<p>아이디: " . htmlspecialchars($username) . "</p>";
        echo "<p>이메일: " . htmlspecialchars($email) . "</p>";
        echo "<p>이름: " . htmlspecialchars($name) . "</p>";
        echo "<p>비밀번호 길이: " . strlen($password) . "</p>";
        echo "<p>비밀번호 확인 길이: " . strlen($password_confirm) . "</p>";
        echo "</div>";
    }
    
    // 입력값 검증
    if (empty($username) || empty($password) || empty($email) || empty($name)) {
        $error = "모든 필수 필드를 입력해주세요. (디버깅: username=" . ($username ? 'O' : 'X') . ", password=" . ($password ? 'O' : 'X') . ", email=" . ($email ? 'O' : 'X') . ", name=" . ($name ? 'O' : 'X') . ")";
    } elseif ($password !== $password_confirm) {
        $error = "비밀번호가 일치하지 않습니다.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "올바른 이메일 주소를 입력해주세요.";
    } elseif (strlen($password) < 8) {
        $error = "비밀번호는 8자 이상이어야 합니다.";
    } elseif (!preg_match('/^[a-zA-Z0-9]{4,20}$/', $username)) {
        $error = "아이디는 영문, 숫자만 사용하여 4-20자로 입력해주세요.";
    } else {
        try {
            // 중복 확인
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                $error = "이미 사용 중인 아이디 또는 이메일입니다.";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email, name) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $hashedPassword, $email, $name]);
                
                // 회원가입 성공 후 자동 로그인
                $userId = $pdo->lastInsertId();
                session_regenerate_id(true);
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $username;
                $_SESSION['name'] = $name;
                $_SESSION['success_message'] = "회원가입이 완료되었습니다! 환영합니다, {$name}님!";
                
                // 검사 선택 페이지로 리다이렉션
                header('Location: test_selection.html');
                exit;
            }
        } catch(PDOException $e) {
            $error = "회원가입 중 오류가 발생했습니다: " . $e->getMessage();
        }
    }
}

// 로그아웃 처리
elseif ($_GET['action'] ?? '' === 'logout') {
    session_destroy();
    header('Location: index.php');
    exit;
}

// 검사 결과 저장
elseif ($_POST['action'] ?? '' === 'save_test_result') {
    if (!isLoggedIn()) {
        // 로그인하지 않은 경우 조용히 무시
    } else {
        $testType = $_POST['test_type'] ?? '';
        $resultData = $_POST['result_data'] ?? '';
        $scoreData = $_POST['score_data'] ?? '';
        
        try {
            $stmt = $pdo->prepare("INSERT INTO test_results (user_id, test_type, result_data, score_data) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $testType, $resultData, $scoreData]);
            $success = "검사 결과가 저장되었습니다.";
        } catch(PDOException $e) {
            $error = "검사 결과 저장 중 오류가 발생했습니다: " . $e->getMessage();
        }
    }
}

// 사용자 검사 결과 조회
$userResults = [];
if (isLoggedIn()) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM test_results WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $userResults = $stmt->fetchAll();
    } catch(PDOException $e) {
        $error = "검사 결과 조회 중 오류가 발생했습니다: " . $e->getMessage();
    }
}

// 질문 조회
$questions = [];
$stmt = $pdo->prepare("SELECT * FROM questions ORDER BY dimension, id");
$stmt->execute();
$questions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>청년들 성격유형 검사 시스템</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        /* 배경 장식 요소 */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 20% 20%, rgba(120, 119, 198, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(255, 119, 198, 0.1) 0%, transparent 50%);
            z-index: -1;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            min-height: 100vh;
            position: relative;
        }
        
        /* 메인 페이지 스타일 */
        #mainPage {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .main-header {
            padding: 20px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .logo-text {
            font-size: 24px;
            font-weight: 700;
            color: #2d3748;
        }
        
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 60px 20px;
        }
        
        .main-title {
            font-size: 3.5rem;
            font-weight: 800;
            color: #2d3748;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        .main-subtitle {
            font-size: 1.5rem;
            color: #4a5568;
            margin-bottom: 16px;
            font-weight: 600;
        }
        
        .main-description {
            font-size: 1.1rem;
            color: #718096;
            margin-bottom: 60px;
            line-height: 1.6;
            max-width: 600px;
        }
        
        .content {
            padding: 30px;
        }
        
        .auth-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .auth-form {
            background: #f8f9fa;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #4facfe;
        }
        
        .btn {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.2s;
            width: 100%;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #51cf66 0%, #40c057 100%);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #ffd43b 0%, #fab005 100%);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .test-container {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .question-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .question-text {
            font-size: 1.2em;
            margin-bottom: 20px;
            color: #333;
        }
        
        .answer-options {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
        }
        
        .answer-option {
            text-align: center;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .answer-option:hover {
            border-color: #4facfe;
            background: #f8f9ff;
        }
        
        .answer-option.selected {
            border-color: #4facfe;
            background: #4facfe;
            color: white;
        }
        
        .result-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .personality-type {
            font-size: 2em;
            font-weight: bold;
            color: #4facfe;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .dimension-result {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .dimension {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            background: #f8f9fa;
        }
        
        .dimension-label {
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .dimension-value {
            font-size: 1.5em;
            color: #4facfe;
        }
        
        .hidden {
            display: none;
        }
        
        .logout-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
        }
        
        .logout-btn:hover {
            background: #c82333;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .stat-card p {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .tab-container {
            margin-bottom: 30px;
        }
        
        .tab-buttons {
            display: flex;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 5px;
            margin-bottom: 20px;
        }
        
        .tab-button {
            flex: 1;
            padding: 15px;
            border: none;
            background: transparent;
            cursor: pointer;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .tab-button.active {
            background: white;
            color: #4facfe;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        /* 반응형 디자인 */
        @media (max-width: 768px) {
            .main-title {
                font-size: 2.5rem;
            }
            
            .main-subtitle {
                font-size: 1.2rem;
            }
            
            .main-description {
                font-size: 1rem;
            }
            
            .main-content {
                padding: 40px 15px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .main-title {
                font-size: 2rem;
            }
            
            .logo-text {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <?php if (!isLoggedIn() && ($_GET['action'] ?? '') !== 'register'): ?>
        <!-- 로그인 페이지 -->
        <div class="container">
            <!-- 헤더 -->
            <div class="main-header">
                <a href="index.html" class="logo" style="text-decoration: none; color: inherit;">
                    <div class="logo-icon">🧠</div>
                    <span class="logo-text">성격유형검사</span>
                </a>
            </div>
            
            <!-- 메인 콘텐츠 -->
            <div class="main-content">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" style="max-width: 400px; margin: 0 auto 30px;"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success" style="max-width: 400px; margin: 0 auto 30px;"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                
                <!-- 로그인 카드 -->
                <div style="background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); max-width: 400px; width: 100%;">
                    <h2 style="text-align: center; margin-bottom: 30px; color: #2d3748;">로그인</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="login">
                        <div class="form-group">
                            <label for="login_username">아이디</label>
                            <input type="text" id="login_username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="login_password">비밀번호</label>
                            <input type="password" id="login_password" name="password" required>
                        </div>
                        <button type="submit" class="btn">로그인</button>
                    </form>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <p style="color: #666; font-size: 14px;">계정이 없으신가요?</p>
                        <a href="?action=register" style="color: #4facfe; text-decoration: none; font-weight: 600;">회원가입</a>
                    </div>
                    
                    <div style="text-align: center; margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; font-size: 12px; color: #666;">
                        <p><strong>테스트 계정:</strong></p>
                        <p>아이디: testuser / 비밀번호: test123!</p>
                        <p>아이디: admin / 비밀번호: admin123!</p>
                        <div style="margin-top: 10px;">
                            <a href="admin.html" style="color: #4facfe; text-decoration: none; font-size: 11px; margin-right: 10px;">관리자 페이지</a>
                            <a href="debug_login.php" style="color: #ff6b6b; text-decoration: none; font-size: 11px; margin-right: 10px;">로그인 진단</a>
                            <a href="?debug=1" style="color: #ffa500; text-decoration: none; font-size: 11px;">디버그 모드</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif (!isLoggedIn() && ($_GET['action'] ?? '') === 'register'): ?>
        <!-- 회원가입 페이지 -->
        <div class="container">
            <!-- 헤더 -->
            <div class="main-header">
                <a href="index.html" class="logo" style="text-decoration: none; color: inherit;">
                    <div class="logo-icon">🧠</div>
                    <span class="logo-text">성격유형검사</span>
                </a>
            </div>
            
            <!-- 메인 콘텐츠 -->
            <div class="main-content">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" style="max-width: 600px; margin: 0 auto 30px;"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success" style="max-width: 600px; margin: 0 auto 30px;"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                
                <!-- 회원가입 카드 -->
                <div style="background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); max-width: 600px; width: 100%;">
                    <h2 style="text-align: center; margin-bottom: 30px; color: #2d3748;">회원가입</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="register">
                        <div class="form-group">
                            <label for="reg_name">이름 *</label>
                            <input type="text" id="reg_name" name="name" placeholder="실명을 입력하세요" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="reg_username">아이디 *</label>
                                <input type="text" id="reg_username" name="username" placeholder="영문, 숫자 4-20자" required>
                                <small style="color: #666; font-size: 12px;">영문, 숫자만 사용 가능 (4-20자)</small>
                            </div>
                            <div class="form-group">
                                <label for="reg_password">비밀번호 *</label>
                                <input type="password" id="reg_password" name="password" placeholder="8자 이상" required>
                                <small style="color: #666; font-size: 12px;">8자 이상, 영문, 숫자, 특수문자 포함</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="reg_password_confirm">비밀번호 확인 *</label>
                                <input type="password" id="reg_password_confirm" name="password_confirm" placeholder="비밀번호를 다시 입력하세요" required>
                            </div>
                            <div class="form-group">
                                <label for="reg_email">이메일 *</label>
                                <input type="email" id="reg_email" name="email" placeholder="example@company.com" required>
                            </div>
                        </div>
                        
                        
                        <button type="submit" class="btn btn-success">회원가입</button>
                    </form>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="index.php" style="color: #4facfe; text-decoration: none; font-size: 14px;">로그인으로 돌아가기</a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- 사용자 페이지 -->
        <div class="container">
            <!-- 헤더 -->
            <div class="main-header">
                <a href="index.html" class="logo" style="text-decoration: none; color: inherit;">
                    <div class="logo-icon">🧠</div>
                    <span class="logo-text">성격유형검사</span>
                </a>
                <a href="?action=logout" class="btn btn-danger" style="padding: 10px 20px; text-decoration: none;">로그아웃</a>
            </div>
            
            <!-- 메인 콘텐츠 -->
            <div class="main-content">
                <h1 class="main-title">안녕하세요, <?= htmlspecialchars($_SESSION['name']) ?>님!</h1>
                <p class="main-subtitle">성격유형 검사를 시작해보세요</p>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success" style="max-width: 600px; margin: 0 auto 30px;"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" style="max-width: 600px; margin: 0 auto 30px;"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <!-- 탭 컨테이너 -->
                <div class="tab-container" style="max-width: 800px; width: 100%;">
                    <div class="tab-buttons">
                        <button class="tab-button active" onclick="showTab('test')">성격유형 검사</button>
                        <button class="tab-button" onclick="showTab('results')">검사 결과</button>
                        <button class="tab-button" onclick="showTab('profile')">프로필</button>
                    </div>
                    
                    <!-- 성격유형 검사 탭 -->
                    <div id="test-tab" class="tab-content active">
                        <div class="test-container">
                            <h2>MBTI 성격유형 검사</h2>
                            <p>총 16개의 질문에 답해주세요. 각 질문에 대해 가장 적절한 답을 선택해주세요.</p>
                            
                            <div id="test-questions">
                                <?php foreach ($questions as $index => $question): ?>
                                    <div class="question-card" data-question="<?= $index + 1 ?>" data-dimension="<?= $question['dimension'] ?>">
                                        <div class="question-text">
                                            <strong><?= $index + 1 ?>.</strong> <?= htmlspecialchars($question['question_text']) ?>
                                        </div>
                                        <div class="answer-options">
                                            <div class="answer-option" data-value="1">전혀 그렇지 않다</div>
                                            <div class="answer-option" data-value="2">그렇지 않다</div>
                                            <div class="answer-option" data-value="3">보통이다</div>
                                            <div class="answer-option" data-value="4">그렇다</div>
                                            <div class="answer-option" data-value="5">매우 그렇다</div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div style="text-align: center; margin-top: 30px;">
                                <button class="btn btn-success" onclick="calculateResult()">검사 완료</button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 검사 결과 탭 -->
                    <div id="results-tab" class="tab-content">
                        <h2>나의 검사 결과</h2>
                        <div id="test-result" class="hidden">
                            <div class="result-card">
                                <div class="personality-type" id="personality-type"></div>
                                <div class="dimension-result" id="dimension-result"></div>
                                <div style="text-align: center;">
                                    <button class="btn btn-success" onclick="saveResult()">결과 저장</button>
                                </div>
                            </div>
                        </div>
                        
                        <div id="saved-results">
                            <?php if (empty($userResults)): ?>
                                <div class="result-card">
                                    <p style="text-align: center; color: #666;">저장된 검사 결과가 없습니다.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($userResults as $result): ?>
                                    <div class="result-card">
                                        <h3>검사 결과 (<?= $result['created_at'] ?>)</h3>
                                        <div class="personality-type"><?= json_decode($result['result_data'], true)['type'] ?? 'N/A' ?></div>
                                        <div class="dimension-result">
                                            <?php 
                                            $scores = json_decode($result['score_data'], true);
                                            if ($scores):
                                                foreach ($scores as $dim => $score): 
                                            ?>
                                                <div class="dimension">
                                                    <div class="dimension-label"><?= $dim ?></div>
                                                    <div class="dimension-value"><?= $score ?></div>
                                                </div>
                                            <?php 
                                                endforeach; 
                                            endif;
                                            ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- 프로필 탭 -->
                    <div id="profile-tab" class="tab-content">
                        <div class="result-card">
                            <h2>프로필 정보</h2>
                            <p><strong>아이디:</strong> <?= htmlspecialchars($_SESSION['username']) ?></p>
                            <p><strong>이름:</strong> <?= htmlspecialchars($_SESSION['name']) ?></p>
                            <p><strong>총 검사 횟수:</strong> <?= count($userResults) ?>회</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
        let currentAnswers = {};
        let currentResult = null;
        
        
        // 답변 선택
        document.addEventListener('DOMContentLoaded', function() {
            const answerOptions = document.querySelectorAll('.answer-option');
            answerOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const questionCard = this.closest('.question-card');
                    const questionNum = questionCard.dataset.question;
                    const dimension = questionCard.dataset.dimension;
                    
                    // 같은 질문의 다른 옵션들 해제
                    questionCard.querySelectorAll('.answer-option').forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    
                    // 선택된 옵션 활성화
                    this.classList.add('selected');
                    
                    // 답변 저장
                    currentAnswers[questionNum] = {
                        value: parseInt(this.dataset.value),
                        dimension: dimension
                    };
                });
            });
        });
        
        // 탭 전환
        function showTab(tabName) {
            // 모든 탭 버튼과 콘텐츠 비활성화
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // 선택된 탭 활성화
            document.querySelector(`[onclick="showTab('${tabName}')"]`).classList.add('active');
            document.getElementById(`${tabName}-tab`).classList.add('active');
        }
        
        // 결과 계산
        function calculateResult() {
            // 모든 질문에 답했는지 확인
            const totalQuestions = document.querySelectorAll('.question-card').length;
            if (Object.keys(currentAnswers).length < totalQuestions) {
                alert('모든 질문에 답해주세요.');
                return;
            }
            
            // 점수 계산
            const scores = {
                'E': 0, 'I': 0,
                'S': 0, 'N': 0,
                'T': 0, 'F': 0,
                'J': 0, 'P': 0
            };
            
            Object.values(currentAnswers).forEach(answer => {
                const dimension = answer.dimension;
                const value = answer.value;
                
                if (dimension === 'EI') {
                    scores['E'] += value;
                } else if (dimension === 'SN') {
                    scores['S'] += value;
                } else if (dimension === 'TF') {
                    scores['T'] += value;
                } else if (dimension === 'JP') {
                    scores['J'] += value;
                }
            });
            
            // MBTI 타입 결정
            const mbtiType = 
                (scores['E'] > scores['I'] ? 'E' : 'I') +
                (scores['S'] > scores['N'] ? 'S' : 'N') +
                (scores['T'] > scores['F'] ? 'T' : 'F') +
                (scores['J'] > scores['P'] ? 'J' : 'P');
            
            // 결과 저장
            currentResult = {
                type: mbtiType,
                scores: scores
            };
            
            // 결과 표시
            document.getElementById('personality-type').textContent = mbtiType;
            
            const dimensionResult = document.getElementById('dimension-result');
            dimensionResult.innerHTML = `
                <div class="dimension">
                    <div class="dimension-label">외향/내향</div>
                    <div class="dimension-value">${scores['E'] > scores['I'] ? 'E' : 'I'}</div>
                </div>
                <div class="dimension">
                    <div class="dimension-label">감각/직관</div>
                    <div class="dimension-value">${scores['S'] > scores['N'] ? 'S' : 'N'}</div>
                </div>
                <div class="dimension">
                    <div class="dimension-label">사고/감정</div>
                    <div class="dimension-value">${scores['T'] > scores['F'] ? 'T' : 'F'}</div>
                </div>
                <div class="dimension">
                    <div class="dimension-label">판단/인식</div>
                    <div class="dimension-value">${scores['J'] > scores['P'] ? 'J' : 'P'}</div>
                </div>
            `;
            
            document.getElementById('test-result').classList.remove('hidden');
            showTab('results');
        }
        
        // 결과 저장
        function saveResult() {
            if (!currentResult) {
                alert('저장할 결과가 없습니다.');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'save_test_result');
            formData.append('test_type', 'MBTI');
            formData.append('result_data', JSON.stringify(currentResult));
            formData.append('score_data', JSON.stringify(currentResult.scores));
            
            fetch('index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                alert('검사 결과가 저장되었습니다.');
                location.reload();
            })
            .catch(error => {
                alert('저장 중 오류가 발생했습니다.');
            });
        }
        
        // 개인정보처리방침 모달 표시
        function showPrivacyPolicy() {
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(0,0,0,0.5); display: flex; align-items: center;
                justify-content: center; z-index: 10000;
            `;
            
            const content = document.createElement('div');
            content.style.cssText = `
                background: white; padding: 30px; border-radius: 10px;
                max-width: 600px; max-height: 80vh; overflow-y: auto;
                margin: 20px;
            `;
            
            content.innerHTML = `
                <h2>개인정보처리방침</h2>
                <div style="margin: 20px 0; line-height: 1.6;">
                    <h3>1. 개인정보 수집 및 이용 목적</h3>
                    <p>청년들 성격유형 검사 시스템은 다음과 같은 목적으로 개인정보를 수집 및 이용합니다:</p>
                    <ul>
                        <li>성격유형 검사 서비스 제공</li>
                        <li>검사 결과 저장 및 관리</li>
                        <li>사용자 식별 및 본인 확인</li>
                        <li>서비스 개선 및 통계 분석</li>
                    </ul>
                    
                    <h3>2. 수집하는 개인정보 항목</h3>
                    <ul>
                        <li>필수항목: 이름, 아이디, 비밀번호, 이메일, 연락처, 팀, 소속</li>
                        <li>선택항목: 검사 결과 데이터</li>
                    </ul>
                    
                    <h3>3. 개인정보 보유 및 이용 기간</h3>
                    <p>회원 탈퇴 시까지 보유하며, 탈퇴 후 즉시 삭제합니다.</p>
                    
                    <h3>4. 개인정보 제3자 제공</h3>
                    <p>원칙적으로 개인정보를 제3자에게 제공하지 않습니다.</p>
                    
                    <h3>5. 개인정보 보호책임자</h3>
                    <p>연락처: admin@test.com</p>
                </div>
                <div style="text-align: center;">
                    <button onclick="this.closest('[style*=\"position: fixed\"]').remove()" 
                            style="background: #4facfe; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
                        닫기
                    </button>
                </div>
            `;
            
            modal.appendChild(content);
            document.body.appendChild(modal);
            
            // 배경 클릭시 닫기
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }
    </script>
</body>
</html>