-- 청년들 성격유형 검사 시스템 데이터베이스 설정
-- phpMyAdmin에서 실행할 SQL 코드

-- 1. 사용자 테이블 생성
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    team VARCHAR(50) NOT NULL,
    organization VARCHAR(100) NOT NULL,
    privacy_agree BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. 성격유형 검사 결과 테이블 생성
CREATE TABLE IF NOT EXISTS test_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    test_type VARCHAR(50) NOT NULL DEFAULT 'MBTI',
    mbti_type VARCHAR(4) NOT NULL,
    ei_score INT NOT NULL,
    sn_score INT NOT NULL,
    tf_score INT NOT NULL,
    jp_score INT NOT NULL,
    raw_answers JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. 질문 테이블 생성
CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_text TEXT NOT NULL,
    category VARCHAR(50) NOT NULL,
    dimension VARCHAR(10) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. 관리자 테이블 생성
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    name VARCHAR(100) NOT NULL,
    role ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. 직원 관리 테이블 생성 (관리자 페이지용)
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    position VARCHAR(100) NOT NULL,
    department VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    hire_date DATE NOT NULL,
    salary DECIMAL(10,2),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. 기본 관리자 계정 생성
INSERT INTO admins (username, password, email, name, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@test.com', '시스템 관리자', 'super_admin')
ON DUPLICATE KEY UPDATE username=username;

-- 7. 기본 질문 데이터 삽입
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
ON DUPLICATE KEY UPDATE question_text=question_text;

-- 8. 샘플 사용자 데이터 생성 (테스트용)
INSERT INTO users (username, password, email, name) VALUES 
('user1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user1@test.com', '테스트 사용자1'),
('user2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user2@test.com', '테스트 사용자2')
ON DUPLICATE KEY UPDATE username=username;

-- 9. 샘플 직원 데이터 생성 (테스트용)
INSERT INTO employees (name, email, position, department, phone, hire_date, salary) VALUES 
('김철수', 'kim@company.com', '개발자', 'IT팀', '010-1234-5678', '2023-01-15', 5000000),
('이영희', 'lee@company.com', '디자이너', '디자인팀', '010-2345-6789', '2023-02-20', 4500000),
('박민수', 'park@company.com', '마케터', '마케팅팀', '010-3456-7890', '2023-03-10', 4000000)
ON DUPLICATE KEY UPDATE email=email;

-- 10. 인덱스 생성 (성능 최적화)
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_test_results_user_id ON test_results(user_id);
CREATE INDEX idx_test_results_created_at ON test_results(created_at);
CREATE INDEX idx_questions_dimension ON questions(dimension);
CREATE INDEX idx_questions_is_active ON questions(is_active);
CREATE INDEX idx_employees_email ON employees(email);
CREATE INDEX idx_employees_department ON employees(department);
CREATE INDEX idx_employees_status ON employees(status);

-- 11. 뷰 생성 (자주 사용하는 쿼리들을 뷰로 생성)
CREATE OR REPLACE VIEW user_test_summary AS
SELECT 
    u.id,
    u.username,
    u.name,
    u.email,
    COUNT(tr.id) as total_tests,
    MAX(tr.created_at) as last_test_date,
    GROUP_CONCAT(DISTINCT tr.mbti_type ORDER BY tr.created_at DESC) as mbti_types
FROM users u
LEFT JOIN test_results tr ON u.id = tr.user_id
GROUP BY u.id, u.username, u.name, u.email;

CREATE OR REPLACE VIEW mbti_statistics AS
SELECT 
    mbti_type,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM test_results), 2) as percentage
FROM test_results
GROUP BY mbti_type
ORDER BY count DESC;

-- 12. 저장 프로시저 생성 (MBTI 타입 분석)
DELIMITER //
CREATE PROCEDURE GetUserMBTIAnalysis(IN user_id_param INT)
BEGIN
    SELECT 
        u.username,
        u.name,
        tr.mbti_type,
        tr.ei_score,
        tr.sn_score,
        tr.tf_score,
        tr.jp_score,
        tr.created_at
    FROM users u
    JOIN test_results tr ON u.id = tr.user_id
    WHERE u.id = user_id_param
    ORDER BY tr.created_at DESC;
END //
DELIMITER ;

-- 13. 트리거 생성 (사용자 삭제 시 관련 데이터 정리)
DELIMITER //
CREATE TRIGGER before_user_delete
BEFORE DELETE ON users
FOR EACH ROW
BEGIN
    DELETE FROM test_results WHERE user_id = OLD.id;
END //
DELIMITER ;

-- 14. 권한 설정 (보안)
-- 실제 운영 환경에서는 적절한 사용자 권한을 설정해야 합니다
-- GRANT SELECT, INSERT, UPDATE, DELETE ON anteate2044.* TO 'web_user'@'localhost' IDENTIFIED BY 'secure_password';

-- 15. 데이터베이스 설정 확인
SELECT 'Database setup completed successfully!' as status;
SELECT COUNT(*) as total_users FROM users;
SELECT COUNT(*) as total_questions FROM questions;
SELECT COUNT(*) as total_employees FROM employees;
