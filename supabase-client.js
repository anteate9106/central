// Supabase 클라이언트 라이브러리
class SupabaseClient {
    constructor() {
        this.url = 'https://your-project-id.supabase.co';
        this.anonKey = 'your-anon-key-here';
        this.headers = {
            'Content-Type': 'application/json',
            'apikey': this.anonKey,
            'Authorization': `Bearer ${this.anonKey}`
        };
    }

    // API 요청 메서드
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

    // 사용자 관련 메서드
    async getUsers() {
        return await this.request('users');
    }

    async getUserById(id) {
        return await this.request(`users?id=eq.${id}`);
    }

    async getUserByUsername(username) {
        return await this.request(`users?username=eq.${username}`);
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

    // 질문 관련 메서드
    async getQuestions() {
        return await this.request('questions?is_active=eq.true');
    }

    async createQuestion(questionData) {
        return await this.request('questions', 'POST', questionData);
    }

    // 검사 결과 관련 메서드
    async getTestResults(userId) {
        return await this.request(`test_results?user_id=eq.${userId}`);
    }

    async createTestResult(resultData) {
        return await this.request('test_results', 'POST', resultData);
    }

    // 인증 관련 메서드
    async signUp(email, password, userData) {
        const authData = {
            email: email,
            password: password,
            user_metadata: userData
        };
        
        return await this.request('auth/v1/signup', 'POST', authData);
    }

    async signIn(email, password) {
        const authData = {
            email: email,
            password: password
        };
        
        return await this.request('auth/v1/token?grant_type=password', 'POST', authData);
    }

    async signOut() {
        return await this.request('auth/v1/logout', 'POST');
    }

    // 현재 사용자 정보 가져오기
    async getCurrentUser() {
        const token = localStorage.getItem('supabase_token');
        if (!token) return null;

        try {
            const response = await fetch(`${this.url}/auth/v1/user`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'apikey': this.anonKey
                }
            });
            
            if (response.ok) {
                return await response.json();
            }
        } catch (error) {
            console.error('사용자 정보 조회 오류:', error);
        }
        
        return null;
    }

    // 토큰 저장
    setToken(token) {
        localStorage.setItem('supabase_token', token);
    }

    // 토큰 제거
    removeToken() {
        localStorage.removeItem('supabase_token');
    }

    // 로그인 상태 확인
    isLoggedIn() {
        return !!localStorage.getItem('supabase_token');
    }
}

// 전역 인스턴스 생성
window.supabase = new SupabaseClient();
