<?php

require_once __DIR__ . '/vendor/autoload.php';

use Workerman\Worker;

// ─── Redis key helpers ───────────────────────────────────────────────────────
const RC_QUEUE    = 'rc:queue';    // LIST  – waiting connection IDs (RPUSH/LPOP)
const RC_IN_QUEUE = 'rc:in_queue'; // SET   – quick membership check

function connRoomKey(int $id): string       { return "rc:conn:{$id}:room"; }
function roomConnsKey(string $roomId): string { return "rc:room:{$roomId}:conns"; }

// ─── Global state (single-process worker) ───────────────────────────────────
$redis  = null;   // Redis connection
$worker = null;   // Worker instance (set in onWorkerStart)

function newRedisConnection(): Redis
{
    $r = new Redis();
    $r->connect(
        getenv('REDIS_HOST') ?: 'redis',
        (int)(getenv('REDIS_PORT') ?: 6379)
    );
    return $r;
}

function getRedis(): Redis
{
    global $redis;
    if ($redis === null) {
        $redis = newRedisConnection();
    }
    return $redis;
}

// ─── Connection lookup ───────────────────────────────────────────────────────
function getConn(int $id): ?object
{
    global $worker;
    return $worker->connections[$id] ?? null;
}

// ─── Send helper ─────────────────────────────────────────────────────────────
function sendTo(object $conn, string $type, array $extra = []): void
{
    $conn->send(json_encode(
        array_merge(['type' => $type], $extra),
        JSON_UNESCAPED_UNICODE
    ));
}

// ─── Queue helpers ───────────────────────────────────────────────────────────
function isInQueue(int $connId): bool
{
    return (bool)getRedis()->sIsMember(RC_IN_QUEUE, (string)$connId);
}

function isInRoom(int $connId): bool
{
    return (bool)getRedis()->get(connRoomKey($connId));
}

function removeFromQueue(int $connId): void
{
    $r = getRedis();
    $r->lRem(RC_QUEUE, (string)$connId, 0);
    $r->sRem(RC_IN_QUEUE, (string)$connId);
}

// ─── Room cleanup ────────────────────────────────────────────────────────────
/**
 * Clears all Redis state for the room the given connection is in,
 * and optionally notifies the partner with 'partner_left'.
 */
function cleanupRoom(int $leaverId, bool $notifyPartner = true): void
{
    $r      = getRedis();
    $roomId = $r->get(connRoomKey($leaverId));
    if (!$roomId) {
        return;
    }

    $members = $r->sMembers(roomConnsKey($roomId));

    foreach ($members as $rawId) {
        $mid = (int)$rawId;

        $r->del(connRoomKey($mid));

        if ($mid === $leaverId) {
            continue;
        }

        if ($notifyPartner) {
            $partnerConn = getConn($mid);
            if ($partnerConn) {
                sendTo($partnerConn, 'partner_left', [
                    'message' => '상대방이 채팅을 떠났습니다.',
                ]);
            }
        }
    }

    $r->del(roomConnsKey($roomId));
}

// ─── Matching logic ──────────────────────────────────────────────────────────
/**
 * Tries to match the given connection with a waiting partner.
 * If no partner is available, adds the connection to the waiting queue.
 */
function doMatch(int $connId, object $connection): void
{
    $r = getRedis();

    if (isInRoom($connId)) {
        sendTo($connection, 'error', ['message' => '이미 채팅 중입니다.']);
        return;
    }

    if (isInQueue($connId)) {
        sendTo($connection, 'error', ['message' => '이미 매칭 대기 중입니다.']);
        return;
    }

    // Try to pop a waiting partner
    $rawPartnerId = $r->lPop(RC_QUEUE);

    if ($rawPartnerId === false || $rawPartnerId === null || $rawPartnerId === '') {
        // No one waiting → join queue
        $r->rPush(RC_QUEUE, (string)$connId);
        $r->sAdd(RC_IN_QUEUE, (string)$connId);
        sendTo($connection, 'waiting', ['message' => '상대방을 기다리는 중...']);
        return;
    }

    $partnerId = (int)$rawPartnerId;
    $r->sRem(RC_IN_QUEUE, (string)$partnerId);

    // Guard: matched with self (shouldn't happen, but be safe)
    if ($partnerId === $connId) {
        $r->rPush(RC_QUEUE, (string)$connId);
        $r->sAdd(RC_IN_QUEUE, (string)$connId);
        sendTo($connection, 'waiting', ['message' => '상대방을 기다리는 중...']);
        return;
    }

    // Guard: partner connection is stale (already disconnected)
    $partnerConn = getConn($partnerId);
    if (!$partnerConn) {
        $r->rPush(RC_QUEUE, (string)$connId);
        $r->sAdd(RC_IN_QUEUE, (string)$connId);
        sendTo($connection, 'waiting', ['message' => '상대방을 기다리는 중...']);
        return;
    }

    // Create room
    $roomId = 'room_' . bin2hex(random_bytes(8));
    $r->set(connRoomKey($connId),    $roomId);
    $r->set(connRoomKey($partnerId), $roomId);
    $r->sAdd(roomConnsKey($roomId), (string)$connId, (string)$partnerId);

    sendTo($connection,  'matched', ['roomId' => $roomId, 'message' => '상대방과 연결되었습니다!']);
    sendTo($partnerConn, 'matched', ['roomId' => $roomId, 'message' => '상대방과 연결되었습니다!']);
}

// ─── Worker setup ────────────────────────────────────────────────────────────
$ws        = new Worker('websocket://0.0.0.0:8081');
$ws->count = 1;
$ws->name  = 'RandomChat';

$ws->onWorkerStart = function (Worker $w): void {
    global $redis, $worker;
    $worker = $w;
    try {
        $redis = newRedisConnection();
        echo "[RandomChat] Worker started – WebSocket listening on ws://0.0.0.0:8081\n";
    } catch (\Throwable $e) {
        echo "[RandomChat] Redis connection failed: " . $e->getMessage() . "\n";
    }
};

$ws->onConnect = function (object $connection): void {
    sendTo($connection, 'connected', ['message' => 'WebSocket 연결됨']);
};

$ws->onMessage = function (object $connection, string $rawMessage): void {
    $data = json_decode($rawMessage, true);
    if ($data === null) {
        sendTo($connection, 'error', ['message' => '잘못된 JSON 형식입니다.']);
        return;
    }

    $type   = $data['type'] ?? '';
    $connId = $connection->id;

    try {
        switch ($type) {

            case 'match_start':
                doMatch($connId, $connection);
                break;

            case 'match_cancel':
                removeFromQueue($connId);
                sendTo($connection, 'match_cancelled', ['message' => '매칭을 취소했습니다.']);
                break;

            case 'chat_message':
                $r      = getRedis();
                $roomId = $r->get(connRoomKey($connId));
                if (!$roomId) {
                    sendTo($connection, 'error', ['message' => '채팅방에 없습니다.']);
                    break;
                }
                $text = trim($data['message'] ?? '');
                if ($text === '') {
                    sendTo($connection, 'error', ['message' => '메시지가 비어 있습니다.']);
                    break;
                }
                $members = $r->sMembers(roomConnsKey($roomId));
                foreach ($members as $rawId) {
                    $mid  = (int)$rawId;
                    $conn = getConn($mid);
                    if ($conn) {
                        sendTo($conn, 'message', [
                            'from'      => ($mid === $connId) ? 'me' : 'partner',
                            'message'   => $text,
                            'timestamp' => date('H:i'),
                        ]);
                    }
                }
                break;

            case 'chat_leave':
                cleanupRoom($connId, true);
                sendTo($connection, 'match_cancelled', ['message' => '채팅을 종료했습니다.']);
                break;

            case 'rematch':
                cleanupRoom($connId, true);
                doMatch($connId, $connection);
                break;

            default:
                sendTo($connection, 'error', ['message' => "알 수 없는 이벤트: {$type}"]);
        }
    } catch (\Throwable $e) {
        error_log('[RandomChat] onMessage error: ' . $e->getMessage());
        sendTo($connection, 'error', ['message' => '서버 오류가 발생했습니다.']);
    }
};

$ws->onClose = function (object $connection): void {
    $connId = $connection->id;

    try {
        removeFromQueue($connId);
        cleanupRoom($connId, true);
    } catch (\Throwable $e) {
        error_log('[RandomChat] onClose error: ' . $e->getMessage());
    }
};

Worker::runAll();
