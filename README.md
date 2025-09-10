# 청년들 성격유형 검사 시스템

MBTI 기반 성격유형 검사 웹 애플리케이션입니다.

## 🚀 기술 스택

- **Frontend**: HTML5, CSS3, JavaScript
- **Backend**: PHP
- **Database**: Supabase (PostgreSQL)
- **Hosting**: GitHub Pages + Supabase

## 📋 주요 기능

- ✅ 사용자 회원가입/로그인
- ✅ MBTI 성격유형 검사
- ✅ 검사 결과 저장 및 조회
- ✅ 관리자 페이지
- ✅ 반응형 디자인

## 🛠️ 설치 및 실행

### 1. 저장소 클론
```bash
git clone [저장소 URL]
cd insamansa
```

### 2. Supabase 설정
1. [Supabase](https://supabase.com)에서 새 프로젝트 생성
2. 데이터베이스 설정
3. 환경 변수 설정

### 3. 로컬 서버 실행
```bash
# PHP 내장 서버 사용
php -S localhost:8000
```

## 📁 프로젝트 구조

```
insamansa/
├── index.html          # 메인 페이지
├── index.php           # PHP 백엔드
├── admin.html          # 관리자 페이지
├── css/                # 스타일시트
├── js/                 # JavaScript 파일
├── assets/             # 이미지, 아이콘
├── supabase/           # Supabase 설정
└── README.md           # 프로젝트 설명
```

## 🔧 환경 변수 설정

`.env` 파일을 생성하고 다음 내용을 추가하세요:

```env
SUPABASE_URL=your_supabase_url
SUPABASE_ANON_KEY=your_supabase_anon_key
```

## 📝 라이선스

MIT License

## 👥 기여자

- 박지훈 (개발자)

## 📞 문의

프로젝트에 대한 문의사항이 있으시면 이슈를 생성해주세요.