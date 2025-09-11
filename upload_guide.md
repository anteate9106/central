# 인삼안사 성격유형검사 시스템 업로드 가이드

## 🚀 웹사이트 업로드 방법

### 1. FTP/SFTP를 통한 업로드
- **호스트**: anteate2044.dothome.co.kr
- **포트**: 21 (FTP) 또는 22 (SFTP)
- **사용자명**: anteate2044
- **비밀번호**: [설정한 비밀번호]

### 2. 업로드할 파일 목록
```
/public_html/
├── index.php               (메인 시스템 파일) ⭐ 필수
├── fix_login.php           (로그인 수정 스크립트)
├── test_registration.php   (회원가입 테스트 스크립트) 🆕
├── migrate_database.php    (데이터베이스 마이그레이션) 🆕
├── debug_login.php         (로그인 문제 진단 스크립트) 🆕
├── db_manager.php          (데이터베이스 원격 관리 API) 🆕
├── db_admin.php            (웹 기반 DB 관리 인터페이스) 🆕
├── cursor_db_client.php    (커서 AI용 DB 클라이언트) 🆕
├── personality_test.db     (SQLite 데이터베이스)
├── admin.html              (관리자 페이지)
├── database_setup.sql      (데이터베이스 백업용)
└── README.md
```

> ⚠️ **중요**: `index.php`와 `personality_test.db` 파일을 반드시 업로드하세요!

### 3. 업로드 후 확인사항

#### A. 파일 권한 설정
- HTML 파일: 644 권한
- PHP 파일: 644 권한
- 폴더: 755 권한

#### B. 파일명 확인
- 대소문자 구분 (Linux 서버)
- 특수문자 사용 금지
- 공백 사용 금지

#### C. 웹 접속 테스트
1. **메인 페이지**: `https://anteate2044.dothome.co.kr/`
2. **관리자 페이지**: `https://anteate2044.dothome.co.kr/admin.html`
3. **로그인 수정**: `https://anteate2044.dothome.co.kr/fix_login.php`
4. **회원가입 테스트**: `https://anteate2044.dothome.co.kr/test_registration.php` 🆕
5. **DB 마이그레이션**: `https://anteate2044.dothome.co.kr/migrate_database.php` 🆕
6. **로그인 진단**: `https://anteate2044.dothome.co.kr/debug_login.php` 🆕
7. **DB 관리 인터페이스**: `https://anteate2044.dothome.co.kr/db_admin.php` 🆕
8. **DB API 엔드포인트**: `https://anteate2044.dothome.co.kr/db_manager.php` 🆕

## 문제 해결 방법

### 1. 404 오류 발생 시
- 파일이 올바른 위치에 업로드되었는지 확인
- 파일명이 정확한지 확인 (대소문자 구분)
- 브라우저 캐시 삭제 후 재시도

### 2. 권한 오류 발생 시
- 파일 권한을 644로 설정
- 폴더 권한을 755로 설정

### 3. PHP 오류 발생 시
- PHP 버전 확인 (7.4 이상 권장)
- SQLite 확장 모듈 확인
- 파일 권한 확인 (데이터베이스 파일: 666)

### 4. 로그인 문제 발생 시
1. **로그인 수정 스크립트 실행**: `https://anteate2044.dothome.co.kr/fix_login.php`
2. 테스트 계정으로 로그인 시도
3. 새 계정으로 회원가입 후 로그인

## 📊 데이터베이스 정보

### SQLite 데이터베이스 사용
- **파일명**: `personality_test.db`
- **위치**: `/public_html/personality_test.db`
- **권한**: 666 (읽기/쓰기 가능)

> 💡 **팁**: phpMyAdmin이 아닌 SQLite를 사용하므로 별도 데이터베이스 설정이 불필요합니다!

## 🔐 테스트 계정

### 일반 사용자
- **아이디**: `testuser`
- **비밀번호**: `test123!`

### 관리자
- **아이디**: `admin`
- **비밀번호**: `admin123!`

## ⚠️ 주의사항 및 추가 정보

### 업로드 순서
1. **index.php** 파일 업로드 (메인 시스템)
2. **personality_test.db** 파일 업로드 (데이터베이스)
3. **migrate_database.php** 파일 업로드 후 실행 (DB 구조 업데이트) 🆕
4. **fix_login.php** 파일 업로드 (로그인 수정용)
5. **test_registration.php** 파일 업로드 (테스트용) 🆕
6. 기타 파일들 업로드

### 보안 설정
1. **기본 비밀번호 변경**: 실제 운영 시에는 테스트 계정 비밀번호를 변경하세요
2. **개발 모드 해제**: `index.php` 상단의 `error_reporting` 설정을 주석 처리하세요
3. **정기 백업**: `personality_test.db` 파일을 정기적으로 백업하세요

### 개선사항
✅ **회원가입 폼 개선**: 팀 선택, 비밀번호 확인, 개인정보 동의 추가
✅ **로그인 보안 강화**: 세션 재생성, 입력값 검증 강화
✅ **사용자 경험 개선**: 테스트 계정 정보 표시, 회원가입 링크 추가
✅ **개인정보처리방침**: 모달 팝업으로 표시
✅ **회원가입 후 자동 로그인**: 회원가입 완료 후 바로 로그인 처리 🆕
✅ **DB 구조 개선**: 이메일 UNIQUE 제약조건, updated_at 컬럼 추가 🆕
✅ **테스트 도구**: 회원가입/로그인 테스트 및 DB 마이그레이션 스크립트 🆕
✅ **원격 DB 관리**: 커서 AI에서 웹사이트 DB를 직접 관리할 수 있는 API 시스템 🆕
✅ **웹 기반 관리**: 브라우저에서 사용자, 데이터를 관리할 수 있는 인터페이스 🆕

## 🤖 커서 AI에서 데이터베이스 관리하기

### API 사용법

커서 AI에서 다음 코드를 사용하여 웹사이트 데이터베이스를 직접 관리할 수 있습니다:

```php
// 데이터베이스 클라이언트 사용
require_once 'cursor_db_client.php';

$client = new CursorDBClient();

// 사용자 목록 조회
$users = $client->getUsers();

// 새 사용자 생성
$client->createUser('newuser', 'password123', 'new@example.com', '새사용자');

// 통계 조회
$stats = $client->getStats();

// SQL 쿼리 실행
$client->executeSQL("SELECT * FROM users WHERE created_at > '2024-01-01'");
```

### cURL 명령어 예제

```bash
# 사용자 목록 조회
curl "https://anteate2044.dothome.co.kr/db_manager.php?action=get_users&token=cursor_ai_token_2024"

# 새 사용자 생성
curl -X POST "https://anteate2044.dothome.co.kr/db_manager.php" \
  -d "action=create_user&token=cursor_ai_token_2024&username=testuser2&password=test123&email=test2@example.com&name=테스트2"

# 통계 조회
curl "https://anteate2044.dothome.co.kr/db_manager.php?action=get_stats&token=cursor_ai_token_2024"
```

### 웹 관리 인터페이스

브라우저에서 `https://anteate2044.dothome.co.kr/db_admin.php`에 접속하여 시각적으로 데이터베이스를 관리할 수 있습니다.

**로그인 정보:**
- 비밀번호: `admin123!`

### 보안 토큰

API 인증 토큰: `cursor_ai_token_2024`

### 문의사항
시스템 관련 문의: admin@test.com
