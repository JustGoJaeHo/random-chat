# Architecture

## Components

### CodeIgniter

담당:

- HTTP API
- Authentication
- Admin
- Configuration
- Database Access

### Workerman

담당:

- WebSocket Server
- Matching
- Room Management
- Message Relay
- Connection Management

### Redis

담당:

- Waiting Queue
- Match State
- Room State
- Connection State

### MySQL

담당:

- Persistent Data
- Logs
- User Data

---

## High Level Flow

User

↓

CodeIgniter

↓

WebSocket Connect

↓

Workerman

↓

Redis Matching

↓

Chat Room

↓

Disconnect / Rematch