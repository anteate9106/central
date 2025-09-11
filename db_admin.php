<?php
// 간단한 인증
session_start();
$admin_password = 'admin123!';

if ($_POST['admin_password'] ?? '' === $admin_password) {
    $_SESSION['admin_authenticated'] = true;
}

if ($_GET['logout'] ?? '' === '1') {
    session_destroy();
    header('Location: db_admin.php');
    exit;
}

$authenticated = $_SESSION['admin_authenticated'] ?? false;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>데이터베이스 관리 - 성격유형검사 시스템</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            color: #2d3748;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .btn {
            background: #4facfe;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover { background: #3182ce; }
        .btn-danger { background: #e53e3e; }
        .btn-danger:hover { background: #c53030; }
        .btn-success { background: #38a169; }
        .btn-success:hover { background: #2f855a; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th { background: #f7fafc; font-weight: 600; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal-content {
            background: white;
            margin: 50px auto;
            padding: 20px;
            border-radius: 10px;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .close {
            float: right;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        .success { color: #38a169; }
        .error { color: #e53e3e; }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-card h3 { font-size: 2em; margin-bottom: 10px; }
        .tabs {
            display: flex;
            background: #f7fafc;
            border-radius: 10px;
            padding: 5px;
            margin-bottom: 20px;
        }
        .tab {
            flex: 1;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .tab.active {
            background: white;
            color: #4facfe;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!$authenticated): ?>
            <div class="card" style="max-width: 400px; margin: 100px auto;">
                <h2 style="text-align: center; margin-bottom: 20px;">관리자 로그인</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>관리자 비밀번호</label>
                        <input type="password" name="admin_password" required>
                    </div>
                    <button type="submit" class="btn" style="width: 100%;">로그인</button>
                </form>
            </div>
        <?php else: ?>
            <div class="header">
                <h1>🗄️ 데이터베이스 관리</h1>
                <div>
                    <a href="index.php" class="btn">메인 페이지</a>
                    <a href="?logout=1" class="btn btn-danger">로그아웃</a>
                </div>
            </div>

            <!-- 통계 -->
            <div class="stats" id="stats">
                <div class="stat-card">
                    <h3 id="userCount">-</h3>
                    <p>총 사용자</p>
                </div>
                <div class="stat-card">
                    <h3 id="testCount">-</h3>
                    <p>검사 결과</p>
                </div>
            </div>

            <!-- 탭 메뉴 -->
            <div class="tabs">
                <div class="tab active" onclick="showTab('users')">사용자 관리</div>
                <div class="tab" onclick="showTab('sql')">SQL 쿼리</div>
                <div class="tab" onclick="showTab('api')">API 테스트</div>
            </div>

            <!-- 사용자 관리 탭 -->
            <div id="users-tab" class="tab-content active">
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2>사용자 목록</h2>
                        <button class="btn btn-success" onclick="showModal('createUserModal')">새 사용자 추가</button>
                    </div>
                    <div id="userList">로딩 중...</div>
                </div>
            </div>

            <!-- SQL 쿼리 탭 -->
            <div id="sql-tab" class="tab-content">
                <div class="card">
                    <h2>SQL 쿼리 실행</h2>
                    <div class="form-group">
                        <label>SQL 쿼리 (SELECT만 허용)</label>
                        <textarea id="sqlQuery" rows="5" placeholder="SELECT * FROM users LIMIT 10"></textarea>
                    </div>
                    <button class="btn" onclick="executeSql()">쿼리 실행</button>
                    <div id="sqlResults"></div>
                </div>
            </div>

            <!-- API 테스트 탭 -->
            <div id="api-tab" class="tab-content">
                <div class="card">
                    <h2>API 엔드포인트 테스트</h2>
                    <p><strong>API URL:</strong> <code><?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?><?= dirname($_SERVER['REQUEST_URI']) ?>/db_manager.php</code></p>
                    <p><strong>인증 토큰:</strong> <code>cursor_ai_token_2024</code></p>
                    
                    <h3>사용 가능한 액션:</h3>
                    <ul>
                        <li><code>get_users</code> - 모든 사용자 조회</li>
                        <li><code>get_user</code> - 특정 사용자 조회 (id 파라미터)</li>
                        <li><code>create_user</code> - 새 사용자 생성</li>
                        <li><code>update_user</code> - 사용자 정보 수정</li>
                        <li><code>delete_user</code> - 사용자 삭제</li>
                        <li><code>reset_password</code> - 비밀번호 재설정</li>
                        <li><code>get_stats</code> - 통계 정보</li>
                        <li><code>execute_sql</code> - SQL 쿼리 실행</li>
                    </ul>

                    <h3>cURL 예제:</h3>
                    <pre style="background: #f7fafc; padding: 15px; border-radius: 5px; overflow-x: auto;">
# 사용자 목록 조회
curl -X GET "<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?><?= dirname($_SERVER['REQUEST_URI']) ?>/db_manager.php?action=get_users&token=cursor_ai_token_2024"

# 새 사용자 생성
curl -X POST "<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?><?= dirname($_SERVER['REQUEST_URI']) ?>/db_manager.php" \
  -H "Authorization: Bearer cursor_ai_token_2024" \
  -d "action=create_user&username=newuser&password=password123&email=new@example.com&name=새사용자"
                    </pre>
                </div>
            </div>

            <!-- 모달들 -->
            <div id="createUserModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('createUserModal')">&times;</span>
                    <h2>새 사용자 추가</h2>
                    <form onsubmit="createUser(event)">
                        <div class="form-group">
                            <label>아이디</label>
                            <input type="text" id="newUsername" required>
                        </div>
                        <div class="form-group">
                            <label>비밀번호</label>
                            <input type="password" id="newPassword" required>
                        </div>
                        <div class="form-group">
                            <label>이메일</label>
                            <input type="email" id="newEmail" required>
                        </div>
                        <div class="form-group">
                            <label>이름</label>
                            <input type="text" id="newName" required>
                        </div>
                        <button type="submit" class="btn btn-success">생성</button>
                        <button type="button" class="btn" onclick="closeModal('createUserModal')">취소</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const API_URL = 'db_manager.php';
        const API_TOKEN = 'cursor_ai_token_2024';

        // API 호출 함수
        async function apiCall(action, data = {}) {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('token', API_TOKEN);
            
            for (const [key, value] of Object.entries(data)) {
                formData.append(key, value);
            }

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    body: formData
                });
                return await response.json();
            } catch (error) {
                return { success: false, error: error.message };
            }
        }

        // 통계 로드
        async function loadStats() {
            const result = await apiCall('get_stats');
            if (result.success) {
                document.getElementById('userCount').textContent = result.data.user_count;
                document.getElementById('testCount').textContent = result.data.test_result_count;
            }
        }

        // 사용자 목록 로드
        async function loadUsers() {
            const result = await apiCall('get_users');
            const userList = document.getElementById('userList');
            
            if (result.success) {
                let html = '<table><tr><th>ID</th><th>아이디</th><th>이름</th><th>이메일</th><th>가입일</th><th>액션</th></tr>';
                result.data.forEach(user => {
                    html += `<tr>
                        <td>${user.id}</td>
                        <td>${user.username}</td>
                        <td>${user.name}</td>
                        <td>${user.email}</td>
                        <td>${user.created_at}</td>
                        <td>
                            <button class="btn" onclick="resetUserPassword(${user.id})">비밀번호 재설정</button>
                            <button class="btn btn-danger" onclick="deleteUser(${user.id})">삭제</button>
                        </td>
                    </tr>`;
                });
                html += '</table>';
                userList.innerHTML = html;
            } else {
                userList.innerHTML = `<p class="error">오류: ${result.error}</p>`;
            }
        }

        // 새 사용자 생성
        async function createUser(event) {
            event.preventDefault();
            
            const userData = {
                username: document.getElementById('newUsername').value,
                password: document.getElementById('newPassword').value,
                email: document.getElementById('newEmail').value,
                name: document.getElementById('newName').value
            };

            const result = await apiCall('create_user', userData);
            
            if (result.success) {
                alert('사용자가 생성되었습니다.');
                closeModal('createUserModal');
                loadUsers();
                loadStats();
            } else {
                alert('오류: ' + result.error);
            }
        }

        // 사용자 삭제
        async function deleteUser(id) {
            if (!confirm('정말 이 사용자를 삭제하시겠습니까?')) return;
            
            const result = await apiCall('delete_user', { id });
            
            if (result.success) {
                alert('사용자가 삭제되었습니다.');
                loadUsers();
                loadStats();
            } else {
                alert('오류: ' + result.error);
            }
        }

        // 비밀번호 재설정
        async function resetUserPassword(id) {
            const newPassword = prompt('새 비밀번호를 입력하세요:');
            if (!newPassword) return;
            
            const result = await apiCall('reset_password', { id, new_password: newPassword });
            
            if (result.success) {
                alert('비밀번호가 재설정되었습니다.');
            } else {
                alert('오류: ' + result.error);
            }
        }

        // SQL 쿼리 실행
        async function executeSql() {
            const sql = document.getElementById('sqlQuery').value;
            if (!sql.trim()) {
                alert('SQL 쿼리를 입력하세요.');
                return;
            }

            const result = await apiCall('execute_sql', { sql });
            const resultsDiv = document.getElementById('sqlResults');
            
            if (result.success) {
                let html = '<h3>쿼리 결과:</h3>';
                if (result.data.length > 0) {
                    html += '<table><tr>';
                    Object.keys(result.data[0]).forEach(key => {
                        html += `<th>${key}</th>`;
                    });
                    html += '</tr>';
                    
                    result.data.forEach(row => {
                        html += '<tr>';
                        Object.values(row).forEach(value => {
                            html += `<td>${value}</td>`;
                        });
                        html += '</tr>';
                    });
                    html += '</table>';
                } else {
                    html += '<p>결과가 없습니다.</p>';
                }
                resultsDiv.innerHTML = html;
            } else {
                resultsDiv.innerHTML = `<p class="error">오류: ${result.error}</p>`;
            }
        }

        // 탭 전환
        function showTab(tabName) {
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            event.target.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
        }

        // 모달 제어
        function showModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // 페이지 로드 시 초기화
        <?php if ($authenticated): ?>
        document.addEventListener('DOMContentLoaded', function() {
            loadStats();
            loadUsers();
        });
        <?php endif; ?>
    </script>
</body>
</html>
