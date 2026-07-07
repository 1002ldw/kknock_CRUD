# kknock CRUD

Apache, PHP 8.3, and MySQL 8.4로 구성된 로그인 기반 게시판입니다.

## 기능

- 일반 게시판과 자유 게시판
- 게시물 및 댓글 작성, 조회, 수정, 삭제
- 최신순, 오래된순, 제목순, 작성자순 정렬
- 작성자 이름 부분 검색
- 게시물당 다중 첨부파일 업로드, 다운로드, 추가, 삭제
- 파일당 10MB, 요청당 64MB, 요청당 최대 20개 제한
- CSRF 검증과 작성자 권한 확인
- 환경 변수 기반 IP 블랙리스트

## 실행

Docker Engine과 Docker Compose 플러그인이 필요합니다.

```bash
cp .env.example .env
chmod 600 .env
nano .env
docker compose up --build -d
docker compose ps
```

브라우저에서 `http://localhost:8080`으로 접속합니다. 원격 Ubuntu VM에서는
`http://<vm-ip>:8080`을 사용합니다. `APP_PORT`를 변경했다면 해당 포트를
사용해야 합니다.

초기 로그인 계정은 `.env`의 `ADMIN_USERNAME`, `ADMIN_PASSWORD`에서 읽습니다.
예제 비밀번호는 첫 실행 전에 반드시 변경해야 합니다. 기존 DB에 같은 사용자명이
있으면 비밀번호를 덮어쓰지 않습니다.

특정 IP만 차단하려면 `.env`의 `BLOCKED_IPS`에 쉼표 또는 공백으로 구분해
입력합니다. 단일 IP와 CIDR 대역을 지원합니다.

```env
BLOCKED_IPS=203.0.113.10,198.51.100.0/24
```

## 데이터

- MySQL 데이터: Docker `mysql_data` 볼륨
- 첨부파일: Docker `uploads` 볼륨
- 기존 DB에는 시작 시 `board_type` 컬럼과 인덱스를 자동 보완

컨테이너만 삭제하면 데이터는 유지됩니다. 다음 명령은 DB와 첨부파일을 모두
영구 삭제하므로 초기화가 필요한 경우에만 사용합니다.

```bash
docker compose down -v
docker compose up --build -d
```

## 점검

```bash
./scripts/check.sh
docker compose build
docker compose logs --tail=100 web db-init
```

## 운영 명령

```bash
docker compose logs -f
docker compose ps
docker compose down
```
