// 청년들 성격유형 검사 시스템 - 메인 JavaScript

// 전역 변수
let currentAnswers = {};
let currentResult = null;
let currentUser = null;

// DOM 로드 완료 후 실행
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// 앱 초기화
function initializeApp() {
    // 사용자 로그인 상태 확인
    checkLoginStatus();
    
    // 이벤트 리스너 등록
    setupEventListeners();
    
    // 질문 로드
    loadQuestions();
}

// 로그인 상태 확인
async function checkLoginStatus() {
    try {
        if (window.supabase && window.supabase.isLoggedIn()) {
            currentUser = await window.supabase.getCurrentUser();
            if (currentUser) {
                showUserInterface();
            } else {
                showLoginInterface();
            }
        } else {
            showLoginInterface();
        }
    } catch (error) {
        console.error('로그인 상태 확인 오류:', error);
        showLoginInterface();
    }
}

// 이벤트 리스너 설정
function setupEventListeners() {
    // 로그인 폼
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    
    // 회원가입 폼
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }
    
    // 답변 선택
    setupAnswerListeners();
    
    // 탭 전환
    setupTabListeners();
}

// 답변 선택 이벤트 리스너
function setupAnswerListeners() {
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

// 로그인 처리
async function handleLogin(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const username = formData.get('username');
    const password = formData.get('password');
    
    try {
        showLoading(true);
        
        // Supabase를 통한 로그인
        const result = await window.supabase.signIn(username, password);
        
        if (result && result.access_token) {
            window.supabase.setToken(result.access_token);
            currentUser = result.user;
            showUserInterface();
            showAlert('로그인에 성공했습니다!', 'success');
        } else {
            showAlert('로그인에 실패했습니다. 아이디와 비밀번호를 확인해주세요.', 'danger');
        }
    } catch (error) {
        console.error('로그인 오류:', error);
        showAlert('로그인 중 오류가 발생했습니다.', 'danger');
    } finally {
        showLoading(false);
    }
}

// 회원가입 처리
async function handleRegister(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const userData = {
        username: formData.get('username'),
        password: formData.get('password'),
        email: formData.get('email'),
        name: formData.get('name')
    };
    
    // 비밀번호 확인
    if (userData.password !== formData.get('password_confirm')) {
        showAlert('비밀번호가 일치하지 않습니다.', 'danger');
        return;
    }
    
    try {
        showLoading(true);
        
        // Supabase를 통한 회원가입
        const result = await window.supabase.signUp(userData.email, userData.password, userData);
        
        if (result && result.user) {
            showAlert('회원가입이 완료되었습니다!', 'success');
            showLoginInterface();
        } else {
            showAlert('회원가입에 실패했습니다.', 'danger');
        }
    } catch (error) {
        console.error('회원가입 오류:', error);
        showAlert('회원가입 중 오류가 발생했습니다.', 'danger');
    } finally {
        showLoading(false);
    }
}

// 질문 로드
async function loadQuestions() {
    try {
        if (window.supabase) {
            const questions = await window.supabase.getQuestions();
            displayQuestions(questions);
        }
    } catch (error) {
        console.error('질문 로드 오류:', error);
        showAlert('질문을 불러오는 중 오류가 발생했습니다.', 'danger');
    }
}

// 질문 표시
function displayQuestions(questions) {
    const container = document.getElementById('test-questions');
    if (!container) return;
    
    container.innerHTML = '';
    
    questions.forEach((question, index) => {
        const questionCard = document.createElement('div');
        questionCard.className = 'question-card';
        questionCard.dataset.question = index + 1;
        questionCard.dataset.dimension = question.dimension;
        
        questionCard.innerHTML = `
            <div class="question-text">
                <strong>${index + 1}.</strong> ${question.question_text}
            </div>
            <div class="answer-options">
                <div class="answer-option" data-value="1">전혀 그렇지 않다</div>
                <div class="answer-option" data-value="2">그렇지 않다</div>
                <div class="answer-option" data-value="3">보통이다</div>
                <div class="answer-option" data-value="4">그렇다</div>
                <div class="answer-option" data-value="5">매우 그렇다</div>
            </div>
        `;
        
        container.appendChild(questionCard);
    });
    
    // 답변 선택 이벤트 리스너 재설정
    setupAnswerListeners();
}

// 결과 계산
function calculateResult() {
    // 모든 질문에 답했는지 확인
    const totalQuestions = document.querySelectorAll('.question-card').length;
    if (Object.keys(currentAnswers).length < totalQuestions) {
        showAlert('모든 질문에 답해주세요.', 'warning');
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
    displayResult(mbtiType, scores);
    showTab('results');
}

// 결과 표시
function displayResult(mbtiType, scores) {
    const personalityType = document.getElementById('personality-type');
    const dimensionResult = document.getElementById('dimension-result');
    
    if (personalityType) {
        personalityType.textContent = mbtiType;
    }
    
    if (dimensionResult) {
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
    }
    
    const testResult = document.getElementById('test-result');
    if (testResult) {
        testResult.classList.remove('hidden');
    }
}

// 결과 저장
async function saveResult() {
    if (!currentResult || !currentUser) {
        showAlert('저장할 결과가 없습니다.', 'warning');
        return;
    }
    
    try {
        showLoading(true);
        
        const resultData = {
            user_id: currentUser.id,
            test_type: 'MBTI',
            result_data: currentResult,
            score_data: currentResult.scores
        };
        
        await window.supabase.createTestResult(resultData);
        showAlert('검사 결과가 저장되었습니다.', 'success');
        
        // 결과 목록 새로고침
        loadUserResults();
    } catch (error) {
        console.error('결과 저장 오류:', error);
        showAlert('결과 저장 중 오류가 발생했습니다.', 'danger');
    } finally {
        showLoading(false);
    }
}

// 사용자 결과 로드
async function loadUserResults() {
    if (!currentUser) return;
    
    try {
        const results = await window.supabase.getTestResults(currentUser.id);
        displayUserResults(results);
    } catch (error) {
        console.error('결과 로드 오류:', error);
    }
}

// 사용자 결과 표시
function displayUserResults(results) {
    const container = document.getElementById('saved-results');
    if (!container) return;
    
    if (results.length === 0) {
        container.innerHTML = `
            <div class="result-card">
                <p class="text-center">저장된 검사 결과가 없습니다.</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = results.map(result => {
        const resultData = result.result_data;
        const scoreData = result.score_data;
        
        return `
            <div class="result-card">
                <h3>검사 결과 (${new Date(result.created_at).toLocaleDateString()})</h3>
                <div class="personality-type">${resultData.type}</div>
                <div class="dimension-result">
                    ${Object.entries(scoreData).map(([dim, score]) => `
                        <div class="dimension">
                            <div class="dimension-label">${dim}</div>
                            <div class="dimension-value">${score}</div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }).join('');
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
    
    // 결과 탭인 경우 사용자 결과 로드
    if (tabName === 'results') {
        loadUserResults();
    }
}

// 로그인 인터페이스 표시
function showLoginInterface() {
    const loginPage = document.getElementById('loginPage');
    const userPage = document.getElementById('userPage');
    
    if (loginPage) loginPage.classList.remove('hidden');
    if (userPage) userPage.classList.add('hidden');
}

// 사용자 인터페이스 표시
function showUserInterface() {
    const loginPage = document.getElementById('loginPage');
    const userPage = document.getElementById('userPage');
    
    if (loginPage) loginPage.classList.add('hidden');
    if (userPage) userPage.classList.remove('hidden');
    
    // 사용자 이름 표시
    const userName = document.getElementById('userName');
    if (userName && currentUser) {
        userName.textContent = currentUser.name || currentUser.username;
    }
}

// 로그아웃
function logout() {
    if (window.supabase) {
        window.supabase.signOut();
        window.supabase.removeToken();
    }
    
    currentUser = null;
    currentAnswers = {};
    currentResult = null;
    
    showLoginInterface();
    showAlert('로그아웃되었습니다.', 'info');
}

// 로딩 표시
function showLoading(show) {
    const loadingElements = document.querySelectorAll('.loading');
    loadingElements.forEach(el => {
        el.style.display = show ? 'inline-block' : 'none';
    });
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
    const container = document.querySelector('.container');
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

// 개인정보처리방침 모달 표시
function showPrivacyPolicy() {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">개인정보처리방침</h2>
                <button class="modal-close" onclick="this.closest('.modal').remove()">&times;</button>
            </div>
            <div class="modal-body">
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
                    <li>필수항목: 이름, 아이디, 비밀번호, 이메일</li>
                    <li>선택항목: 검사 결과 데이터</li>
                </ul>
                
                <h3>3. 개인정보 보유 및 이용 기간</h3>
                <p>회원 탈퇴 시까지 보유하며, 탈퇴 후 즉시 삭제합니다.</p>
                
                <h3>4. 개인정보 제3자 제공</h3>
                <p>원칙적으로 개인정보를 제3자에게 제공하지 않습니다.</p>
                
                <h3>5. 개인정보 보호책임자</h3>
                <p>연락처: admin@test.com</p>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // 배경 클릭시 닫기
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

// 전역 함수로 노출
window.calculateResult = calculateResult;
window.saveResult = saveResult;
window.showTab = showTab;
window.logout = logout;
window.showPrivacyPolicy = showPrivacyPolicy;
