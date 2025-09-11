// 전역 변수
let currentAdmin = null;
let users = [];
let questions = [];
let results = [];

// Supabase 클라이언트
class AdminSupabaseClient {
    constructor() {
        this.url = 'https://lghwdvpnbvkihzgvwzpz.supabase.co';
        this.anonKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImxnaHdkdnBuYnZraWh6Z3Z3enB6Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTc0OTM5ODksImV4cCI6MjA3MzA2OTk4OX0.7ae1Cz706NOThj8lbJAfbHZW7nYWng8aZ4RJ9EDujMs';
        this.headers = {
            'Content-Type': 'application/json',
            'apikey': this.anonKey,
            'Authorization': `Bearer ${this.anonKey}`
        };
    }

    async request(endpoint, method = 'GET', data = null) {
        const url = `${this.url}/rest/v1/${endpoint}`;
        
        const options = {
            method: method,
            headers: this.headers
        };

        if (data && ['POST', 'PUT', 'PATCH'].includes(method)) {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, options);
            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.message || 'API 요청 실패');
            }
            
            return result;
        } catch (error) {
            console.error('Supabase API 오류:', error);
            throw error;
        }
    }

    // 사용자 관리
    async getUsers() {
        return await this.request('users');
    }

    async createUser(userData) {
        return await this.request('users', 'POST', userData);
    }

    async updateUser(id, userData) {
        return await this.request(`users?id=eq.${id}`, 'PATCH', userData);
    }

    async deleteUser(id) {
        return await this.request(`users?id=eq.${id}`, 'DELETE');
    }

    // 문항 관리
    async getQuestions() {
        return await this.request('questions');
    }

    async createQuestion(questionData) {
        return await this.request('questions', 'POST', questionData);
    }

    async updateQuestion(id, questionData) {
        return await this.request(`questions?id=eq.${id}`, 'PATCH', questionData);
    }

    async deleteQuestion(id) {
        return await this.request(`questions?id=eq.${id}`, 'DELETE');
    }

    // 검사 결과 관리
    async getResults() {
        return await this.request('test_results');
    }

    async deleteResult(id) {
        return await this.request(`test_results?id=eq.${id}`, 'DELETE');
    }

    removeToken() {
        localStorage.removeItem('supabase_token');
    }

    isLoggedIn() {
        return !!localStorage.getItem('supabase_token');
    }

    async getCurrentUser() {
        const token = localStorage.getItem('supabase_token');
        if (!token) return null;

        try {
            const decodedString = atob(token);
            const jsonString = decodeURIComponent(decodedString);
            const tokenData = JSON.parse(jsonString);
            if (tokenData.exp > Date.now()) {
                return tokenData;
            }
        } catch (error) {
            console.error('토큰 파싱 오류:', error);
            try {
                const decodedString = atob(token);
                const tokenData = JSON.parse(decodedString);
                if (tokenData.exp > Date.now()) {
                    return tokenData;
                }
            } catch (fallbackError) {
                console.error('대체 토큰 파싱도 실패:', fallbackError);
            }
        }
        
        return null;
    }
}

// 전역 인스턴스 생성
window.supabase = new AdminSupabaseClient();

// DOM 로드 완료 후 실행
document.addEventListener('DOMContentLoaded', function() {
    initializeAdminApp();
});

// 관리자 앱 초기화
async function initializeAdminApp() {
    // 관리자 권한 확인
    await checkAdminAuth();
    
    // 이벤트 리스너 등록
    setupEventListeners();
    
    // 데이터 로드
    await loadAllData();
}

// 관리자 권한 확인
async function checkAdminAuth() {
    try {
        if (window.supabase && window.supabase.isLoggedIn()) {
            currentAdmin = await window.supabase.getCurrentUser();
            if (currentAdmin && currentAdmin.username === 'admin') {
                showAdminInfo();
            } else {
                showAlert('관리자 권한이 필요합니다.', 'danger');
                redirectToLogin();
            }
        } else {
            redirectToLogin();
        }
    } catch (error) {
        console.error('관리자 권한 확인 오류:', error);
        redirectToLogin();
    }
}

// 관리자 정보 표시
function showAdminInfo() {
    const adminName = document.getElementById('adminName');
    if (adminName && currentAdmin) {
        adminName.textContent = currentAdmin.name || currentAdmin.username;
    }
}

// 이벤트 리스너 설정
function setupEventListeners() {
    // 탭 전환
    setupTabListeners();
    
    // 폼 제출
    setupFormListeners();
}

// 탭 전환 이벤트 리스너
function setupTabListeners() {
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabName = this.dataset.tab;
            showTab(tabName);
        });
    });
}

// 폼 이벤트 리스너
function setupFormListeners() {
    // 사용자 폼
    const userForm = document.getElementById('userForm');
    if (userForm) {
        userForm.addEventListener('submit', handleUserSubmit);
    }
    
    // 문항 폼
    const questionForm = document.getElementById('questionForm');
    if (questionForm) {
        questionForm.addEventListener('submit', handleQuestionSubmit);
    }
}

// 모든 데이터 로드
async function loadAllData() {
    try {
        await Promise.all([
            loadUsers(),
            loadQuestions(),
            loadResults(),
            loadStats()
        ]);
    } catch (error) {
        console.error('데이터 로드 오류:', error);
        showAlert('데이터를 불러오는 중 오류가 발생했습니다.', 'danger');
    }
}

// 사용자 데이터 로드
async function loadUsers() {
    try {
        showLoading('usersLoading', true);
        users = await window.supabase.getUsers();
        displayUsers(users);
    } catch (error) {
        console.error('사용자 로드 오류:', error);
        showAlert('사용자 목록을 불러오는 중 오류가 발생했습니다.', 'danger');
    } finally {
        showLoading('usersLoading', false);
    }
}

// 문항 데이터 로드
async function loadQuestions() {
    try {
        showLoading('questionsLoading', true);
        questions = await window.supabase.getQuestions();
        displayQuestions(questions);
    } catch (error) {
        console.error('문항 로드 오류:', error);
        showAlert('문항 목록을 불러오는 중 오류가 발생했습니다.', 'danger');
    } finally {
        showLoading('questionsLoading', false);
    }
}

// 검사 결과 데이터 로드
async function loadResults() {
    try {
        showLoading('resultsLoading', true);
        results = await window.supabase.getResults();
        displayResults(results);
    } catch (error) {
        console.error('검사 결과 로드 오류:', error);
        showAlert('검사 결과를 불러오는 중 오류가 발생했습니다.', 'danger');
    } finally {
        showLoading('resultsLoading', false);
    }
}

// 통계 데이터 로드
async function loadStats() {
    try {
        document.getElementById('totalUsers').textContent = users.length;
        document.getElementById('totalQuestions').textContent = questions.length;
        document.getElementById('totalResults').textContent = results.length;
        document.getElementById('activeUsers').textContent = users.filter(u => u.is_active !== false).length;
    } catch (error) {
        console.error('통계 로드 오류:', error);
    }
}

// 사용자 표시
function displayUsers(users) {
    const tbody = document.getElementById('usersTableBody');
    tbody.innerHTML = '';
    
    users.forEach(user => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${user.id.substring(0, 8)}...</td>
            <td>${user.username}</td>
            <td>${user.name || '-'}</td>
            <td>${user.email}</td>
            <td>${new Date(user.created_at).toLocaleDateString('ko-KR')}</td>
            <td>${user.is_active !== false ? '활성' : '비활성'}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-warning btn-sm" onclick="editUser('${user.id}')">수정</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteUser('${user.id}')">삭제</button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// 문항 표시
function displayQuestions(questions) {
    const tbody = document.getElementById('questionsTableBody');
    tbody.innerHTML = '';
    
    questions.forEach(question => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${question.id.substring(0, 8)}...</td>
            <td>${question.question_text.substring(0, 50)}${question.question_text.length > 50 ? '...' : ''}</td>
            <td>${question.dimension}</td>
            <td>${question.order_index || '-'}</td>
            <td>${question.is_active ? '활성' : '비활성'}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-warning btn-sm" onclick="editQuestion('${question.id}')">수정</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteQuestion('${question.id}')">삭제</button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// 검사 결과 표시
function displayResults(results) {
    const tbody = document.getElementById('resultsTableBody');
    tbody.innerHTML = '';
    
    results.forEach(result => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${result.id.substring(0, 8)}...</td>
            <td>${result.user_id ? result.user_id.substring(0, 8) + '...' : '-'}</td>
            <td>${result.personality_type || '-'}</td>
            <td>${new Date(result.created_at).toLocaleDateString('ko-KR')}</td>
            <td>${result.scores ? JSON.stringify(result.scores).substring(0, 30) + '...' : '-'}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-danger btn-sm" onclick="deleteResult('${result.id}')">삭제</button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// 탭 전환
function showTab(tabName) {
    // 모든 탭 버튼과 콘텐츠 비활성화
    document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    // 선택된 탭 활성화
    const activeButton = document.querySelector(`[data-tab="${tabName}"]`);
    const activeContent = document.getElementById(`${tabName}-tab`);
    
    if (activeButton) activeButton.classList.add('active');
    if (activeContent) activeContent.classList.add('active');
}

// 로딩 표시
function showLoading(elementId, show) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.toggle('hidden', !show);
    }
}

// 모달 표시
function showModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

// 모달 닫기
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    // 폼 리셋
    const form = document.querySelector(`#${modalId} form`);
    if (form) {
        form.reset();
    }
}

// 사용자 추가 모달 표시
function showAddUserModal() {
    document.getElementById('userModalTitle').textContent = '사용자 추가';
    document.getElementById('userForm').reset();
    showModal('userModal');
}

// 사용자 수정 모달 표시
function editUser(userId) {
    const user = users.find(u => u.id === userId);
    if (user) {
        document.getElementById('userModalTitle').textContent = '사용자 수정';
        document.getElementById('userUsername').value = user.username;
        document.getElementById('userName').value = user.name || '';
        document.getElementById('userEmail').value = user.email;
        document.getElementById('userPassword').value = '';
        document.getElementById('userForm').dataset.userId = userId;
        showModal('userModal');
    }
}

// 문항 추가 모달 표시
function showAddQuestionModal() {
    document.getElementById('questionModalTitle').textContent = '문항 추가';
    document.getElementById('questionForm').reset();
    showModal('questionModal');
}

// 문항 수정 모달 표시
function editQuestion(questionId) {
    const question = questions.find(q => q.id === questionId);
    if (question) {
        document.getElementById('questionModalTitle').textContent = '문항 수정';
        document.getElementById('questionText').value = question.question_text;
        document.getElementById('questionDimension').value = question.dimension;
        document.getElementById('questionOrder').value = question.order_index || '';
        document.getElementById('questionActive').value = question.is_active ? 'true' : 'false';
        document.getElementById('questionForm').dataset.questionId = questionId;
        showModal('questionModal');
    }
}

// 사용자 폼 제출 처리
async function handleUserSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const userData = {
        username: formData.get('username'),
        name: formData.get('name'),
        email: formData.get('email'),
        password: formData.get('password')
    };

    try {
        const userId = e.target.dataset.userId;
        if (userId) {
            // 수정
            await window.supabase.updateUser(userId, userData);
            showAlert('사용자가 수정되었습니다.', 'success');
        } else {
            // 추가
            await window.supabase.createUser(userData);
            showAlert('사용자가 추가되었습니다.', 'success');
        }
        
        closeModal('userModal');
        await loadUsers();
        await loadStats();
    } catch (error) {
        console.error('사용자 저장 오류:', error);
        showAlert('사용자 저장 중 오류가 발생했습니다: ' + error.message, 'danger');
    }
}

// 문항 폼 제출 처리
async function handleQuestionSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const questionData = {
        question_text: formData.get('question_text'),
        dimension: formData.get('dimension'),
        order_index: parseInt(formData.get('order_index')),
        is_active: formData.get('is_active') === 'true'
    };

    try {
        const questionId = e.target.dataset.questionId;
        if (questionId) {
            // 수정
            await window.supabase.updateQuestion(questionId, questionData);
            showAlert('문항이 수정되었습니다.', 'success');
        } else {
            // 추가
            await window.supabase.createQuestion(questionData);
            showAlert('문항이 추가되었습니다.', 'success');
        }
        
        closeModal('questionModal');
        await loadQuestions();
        await loadStats();
    } catch (error) {
        console.error('문항 저장 오류:', error);
        showAlert('문항 저장 중 오류가 발생했습니다: ' + error.message, 'danger');
    }
}

// 사용자 삭제
async function deleteUser(userId) {
    if (confirm('정말로 이 사용자를 삭제하시겠습니까?')) {
        try {
            await window.supabase.deleteUser(userId);
            showAlert('사용자가 삭제되었습니다.', 'success');
            await loadUsers();
            await loadStats();
        } catch (error) {
            console.error('사용자 삭제 오류:', error);
            showAlert('사용자 삭제 중 오류가 발생했습니다: ' + error.message, 'danger');
        }
    }
}

// 문항 삭제
async function deleteQuestion(questionId) {
    if (confirm('정말로 이 문항을 삭제하시겠습니까?')) {
        try {
            await window.supabase.deleteQuestion(questionId);
            showAlert('문항이 삭제되었습니다.', 'success');
            await loadQuestions();
            await loadStats();
        } catch (error) {
            console.error('문항 삭제 오류:', error);
            showAlert('문항 삭제 중 오류가 발생했습니다: ' + error.message, 'danger');
        }
    }
}

// 검사 결과 삭제
async function deleteResult(resultId) {
    if (confirm('정말로 이 검사 결과를 삭제하시겠습니까?')) {
        try {
            await window.supabase.deleteResult(resultId);
            showAlert('검사 결과가 삭제되었습니다.', 'success');
            await loadResults();
            await loadStats();
        } catch (error) {
            console.error('검사 결과 삭제 오류:', error);
            showAlert('검사 결과 삭제 중 오류가 발생했습니다: ' + error.message, 'danger');
        }
    }
}

// 결과 내보내기
function exportResults() {
    try {
        const csvContent = generateCSV(results);
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `검사결과_${new Date().toISOString().split('T')[0]}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        showAlert('검사 결과가 내보내기되었습니다.', 'success');
    } catch (error) {
        console.error('결과 내보내기 오류:', error);
        showAlert('결과 내보내기 중 오류가 발생했습니다.', 'danger');
    }
}

// CSV 생성
function generateCSV(data) {
    const headers = ['ID', '사용자 ID', '성격유형', '검사일', '상세 점수'];
    const rows = data.map(result => [
        result.id,
        result.user_id || '',
        result.personality_type || '',
        new Date(result.created_at).toLocaleDateString('ko-KR'),
        result.scores ? JSON.stringify(result.scores) : ''
    ]);
    
    return [headers, ...rows].map(row => 
        row.map(field => `"${field}"`).join(',')
    ).join('\n');
}

// 로그아웃
function logout() {
    if (window.supabase) {
        window.supabase.removeToken();
    }
    
    currentAdmin = null;
    redirectToLogin();
}

// 로그인 페이지로 리다이렉트
function redirectToLogin() {
    window.location.href = 'index.html';
}

// 알림 표시
function showAlert(message, type = 'info') {
    // 기존 알림 제거
    const existingAlert = document.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    // 새 알림 생성
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    
    // 페이지 상단에 추가
    const container = document.querySelector('.main-content');
    if (container) {
        container.insertBefore(alert, container.firstChild);
        
        // 3초 후 자동 제거
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 3000);
    }
}

// 전역 함수로 노출
window.showAddUserModal = showAddUserModal;
window.showAddQuestionModal = showAddQuestionModal;
window.editUser = editUser;
window.editQuestion = editQuestion;
window.deleteUser = deleteUser;
window.deleteQuestion = deleteQuestion;
window.deleteResult = deleteResult;
window.exportResults = exportResults;
window.closeModal = closeModal;
window.logout = logout;
