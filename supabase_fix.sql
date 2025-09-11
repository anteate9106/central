-- Supabase 테이블 수정 및 데이터 삽입

-- 1. questions 테이블에 필요한 컬럼 추가
ALTER TABLE questions ADD COLUMN IF NOT EXISTS category VARCHAR(50) DEFAULT 'general';
ALTER TABLE questions ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE;

-- 2. 기존 데이터가 있다면 category 업데이트
UPDATE questions SET category = 'general' WHERE category IS NULL;

-- 3. MBTI 질문 데이터 삽입 (기존 데이터가 있다면 무시)
INSERT INTO questions (question_text, category, dimension) VALUES 
-- E/I (외향/내향) 질문들
('나는 새로운 사람들과 만나는 것을 즐긴다', 'social', 'EI'),
('큰 그룹에서 에너지를 얻는다', 'social', 'EI'),
('혼자 시간을 보내는 것을 선호한다', 'introversion', 'EI'),
('사고 과정을 말로 표현하는 것을 좋아한다', 'communication', 'EI'),

-- S/N (감각/직관) 질문들
('구체적인 사실과 세부사항에 집중한다', 'detail', 'SN'),
('실용적인 해결책을 선호한다', 'practical', 'SN'),
('새로운 아이디어와 가능성을 탐구한다', 'innovation', 'SN'),
('미래의 가능성에 대해 생각하는 것을 좋아한다', 'future', 'SN'),

-- T/F (사고/감정) 질문들
('논리적 분석을 통해 결정을 내린다', 'logic', 'TF'),
('객관적인 기준으로 판단한다', 'objective', 'TF'),
('다른 사람의 감정을 고려한다', 'empathy', 'TF'),
('조화와 협력을 중시한다', 'harmony', 'TF'),

-- J/P (판단/인식) 질문들
('계획을 세우고 체계적으로 일한다', 'planning', 'JP'),
('마감일을 지키는 것을 중요하게 생각한다', 'deadline', 'JP'),
('유연하고 적응력이 있다', 'flexibility', 'JP'),
('새로운 정보에 열려있다', 'openness', 'JP')
ON CONFLICT (question_text) DO NOTHING;

-- 4. users 테이블이 없다면 생성
CREATE TABLE IF NOT EXISTS users (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) DEFAULT '',
    team VARCHAR(50) DEFAULT '',
    organization VARCHAR(100) DEFAULT '',
    privacy_agree BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- 5. test_results 테이블이 없다면 생성
CREATE TABLE IF NOT EXISTS test_results (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    test_type VARCHAR(50) NOT NULL DEFAULT 'MBTI',
    result_data JSONB,
    score_data JSONB,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- 6. 기본 관리자 계정 생성 (비밀번호: admin123!)
INSERT INTO users (username, password, email, name) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@test.com', '관리자'),
('testuser', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'test@example.com', '테스트사용자')
ON CONFLICT (username) DO NOTHING;
