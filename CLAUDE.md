# Random Chat

## Overview

1:1 랜덤 채팅 서비스

Tech Stack

- PHP 8.4
- CodeIgniter 4
- Workerman 5
- MySQL 8
- Redis
- Docker
- Nginx
- Vanilla JavaScript

---

## Development Rules

항상 다음 순서로 작업한다.

1. 프로젝트 구조 분석
2. 관련 파일 읽기
3. 기존 코드 스타일 파악
4. 수정 범위 결정
5. 구현

파일을 읽기 전 구조를 추정하여 구현하지 않는다.

---

## Architecture

- HTTP API → CodeIgniter
- Realtime/WebSocket → Workerman
- State Management → Redis
- Persistent Data → MySQL

세부 내용은 docs 디렉토리 문서를 참고한다.

---

## Modification Policy

- 기존 구조 유지
- 기존 네이밍 유지
- 최소 변경 원칙
- 불필요한 리팩토링 금지
- 라이브러리 교체 금지

---

## Output Format

작업 완료 후 반드시:

1. Structure Analysis
2. Modified Files
3. Changes
4. Run Commands
5. Test Scenario
6. Notes

형식으로 결과를 정리한다.