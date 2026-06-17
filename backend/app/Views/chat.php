<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>랜덤 채팅</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #0d1117;
            color: #e6edf3;
            height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ── Layout ─────────────────────────────────────────────────────── */
        #app {
            width: 100%;
            max-width: 480px;
            height: 100dvh;
            max-height: 780px;
            background: #161b22;
            border-radius: 16px;
            border: 1px solid #30363d;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 24px 64px rgba(0,0,0,.5);
        }

        /* ── Header ─────────────────────────────────────────────────────── */
        #header {
            padding: 16px 20px;
            border-bottom: 1px solid #30363d;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }

        #header h1 {
            font-size: 18px;
            font-weight: 600;
            letter-spacing: -.3px;
        }

        #status-badge {
            font-size: 12px;
            font-weight: 500;
            padding: 4px 10px;
            border-radius: 20px;
            background: #21262d;
            color: #8b949e;
            border: 1px solid #30363d;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all .2s;
        }

        #status-badge .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #8b949e;
            transition: background .3s;
        }

        #status-badge.connecting .dot { background: #e3b341; }
        #status-badge.idle       .dot { background: #3fb950; }
        #status-badge.waiting    .dot { background: #e3b341; animation: pulse 1.2s infinite; }
        #status-badge.chatting   .dot { background: #58a6ff; }
        #status-badge.left       .dot { background: #f85149; }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: .3; }
        }

        /* ── Main content area ───────────────────────────────────────────── */
        #main {
            flex: 1;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* ── Screens ─────────────────────────────────────────────────────── */
        .screen {
            display: none;
            flex: 1;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 32px 24px;
            text-align: center;
        }

        .screen.active { display: flex; }

        /* idle */
        #screen-idle .icon {
            font-size: 56px;
            margin-bottom: 20px;
        }

        #screen-idle h2 {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        #screen-idle p {
            color: #8b949e;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        /* waiting */
        #screen-waiting .spinner {
            width: 48px;
            height: 48px;
            border: 3px solid #30363d;
            border-top-color: #58a6ff;
            border-radius: 50%;
            animation: spin .8s linear infinite;
            margin-bottom: 20px;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        #screen-waiting h2 {
            font-size: 18px;
            margin-bottom: 8px;
        }

        #screen-waiting p {
            color: #8b949e;
            font-size: 14px;
            margin-bottom: 28px;
        }

        /* chatting – messages fill vertical space */
        #screen-chat {
            justify-content: flex-end;
            padding: 0;
        }

        #messages {
            flex: 1;
            overflow-y: auto;
            padding: 16px 16px 4px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            scroll-behavior: smooth;
        }

        #messages::-webkit-scrollbar { width: 4px; }
        #messages::-webkit-scrollbar-track { background: transparent; }
        #messages::-webkit-scrollbar-thumb { background: #30363d; border-radius: 4px; }

        .msg-row {
            display: flex;
            flex-direction: column;
        }

        .msg-row.me    { align-items: flex-end; }
        .msg-row.partner { align-items: flex-start; }
        .msg-row.system { align-items: center; }

        .bubble {
            max-width: 72%;
            padding: 9px 14px;
            border-radius: 16px;
            font-size: 14px;
            line-height: 1.5;
            word-break: break-word;
        }

        .me .bubble {
            background: #1f6feb;
            color: #fff;
            border-bottom-right-radius: 4px;
        }

        .partner .bubble {
            background: #21262d;
            color: #e6edf3;
            border: 1px solid #30363d;
            border-bottom-left-radius: 4px;
        }

        .system .bubble {
            background: transparent;
            color: #8b949e;
            font-size: 12px;
            padding: 4px 0;
        }

        .msg-time {
            font-size: 11px;
            color: #6e7681;
            margin-top: 3px;
            padding: 0 4px;
        }

        /* ── Input area ──────────────────────────────────────────────────── */
        #input-area {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            border-top: 1px solid #30363d;
            flex-shrink: 0;
        }

        #msg-input {
            flex: 1;
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 20px;
            padding: 9px 16px;
            font-size: 14px;
            color: #e6edf3;
            outline: none;
            transition: border-color .15s;
        }

        #msg-input:focus { border-color: #58a6ff; }
        #msg-input::placeholder { color: #6e7681; }

        /* ── Footer / action buttons ─────────────────────────────────────── */
        #footer {
            display: flex;
            gap: 8px;
            padding: 10px 12px;
            border-top: 1px solid #30363d;
            flex-shrink: 0;
        }

        /* ── Buttons ─────────────────────────────────────────────────────── */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            padding: 9px 18px;
            cursor: pointer;
            transition: opacity .15s, transform .1s;
            user-select: none;
        }

        .btn:active { transform: scale(.96); }
        .btn:disabled { opacity: .45; cursor: not-allowed; }

        .btn-primary  { background: #238636; color: #fff; }
        .btn-primary:not(:disabled):hover  { background: #2ea043; }

        .btn-danger   { background: #da3633; color: #fff; }
        .btn-danger:not(:disabled):hover   { background: #f85149; }

        .btn-warning  { background: #e3b341; color: #000; }
        .btn-warning:not(:disabled):hover  { background: #ffa657; }

        .btn-subtle   { background: #21262d; color: #e6edf3; border: 1px solid #30363d; }
        .btn-subtle:not(:disabled):hover   { background: #30363d; }

        .btn-blue     { background: #1f6feb; color: #fff; }
        .btn-blue:not(:disabled):hover     { background: #388bfd; }

        .btn-full { width: 100%; }

        .hidden { display: none !important; }
    </style>
</head>
<body>

<div id="app">

    <!-- Header -->
    <div id="header">
        <h1>🎲 랜덤 채팅</h1>
        <div id="status-badge" class="connecting">
            <span class="dot"></span>
            <span id="status-text">연결 중...</span>
        </div>
    </div>

    <!-- Main content -->
    <div id="main">

        <!-- Idle screen -->
        <div id="screen-idle" class="screen">
            <div class="icon">💬</div>
            <h2>익명으로 채팅하기</h2>
            <p>버튼을 누르면 랜덤한 상대방과<br>1:1 채팅이 시작됩니다.</p>
            <button id="btn-match" class="btn btn-primary btn-full" style="max-width:240px">
                채팅 시작
            </button>
        </div>

        <!-- Waiting screen -->
        <div id="screen-waiting" class="screen">
            <div class="spinner"></div>
            <h2>상대방 찾는 중...</h2>
            <p>잠시만 기다려 주세요.</p>
            <button id="btn-cancel" class="btn btn-subtle">
                매칭 취소
            </button>
        </div>

        <!-- Chat screen -->
        <div id="screen-chat" class="screen active">
            <div id="messages"></div>
            <div id="input-area">
                <input
                    id="msg-input"
                    type="text"
                    placeholder="메시지를 입력하세요..."
                    maxlength="500"
                    autocomplete="off"
                >
                <button id="btn-send" class="btn btn-blue" style="padding:9px 16px">
                    전송
                </button>
            </div>
        </div>

    </div><!-- /main -->

    <!-- Footer action buttons (shown only during chat) -->
    <div id="footer" class="hidden">
        <button id="btn-leave"   class="btn btn-danger"  style="flex:1">끝내기</button>
        <button id="btn-rematch" class="btn btn-warning" style="flex:1">재매칭</button>
    </div>

</div><!-- /app -->

<script>
(function () {
    'use strict';

    // ── State ──────────────────────────────────────────────────────────────
    // 'disconnected' | 'idle' | 'waiting' | 'chatting' | 'partner_left'
    let appState = 'disconnected';
    let ws = null;
    let reconnectDelay = 1000;

    // ── Elements ───────────────────────────────────────────────────────────
    const el = {
        statusBadge:   document.getElementById('status-badge'),
        statusText:    document.getElementById('status-text'),
        screenIdle:    document.getElementById('screen-idle'),
        screenWaiting: document.getElementById('screen-waiting'),
        screenChat:    document.getElementById('screen-chat'),
        messages:      document.getElementById('messages'),
        msgInput:      document.getElementById('msg-input'),
        btnMatch:      document.getElementById('btn-match'),
        btnCancel:     document.getElementById('btn-cancel'),
        btnSend:       document.getElementById('btn-send'),
        btnLeave:      document.getElementById('btn-leave'),
        btnRematch:    document.getElementById('btn-rematch'),
        footer:        document.getElementById('footer'),
        inputArea:     document.getElementById('input-area'),
    };

    // ── UI state machine ───────────────────────────────────────────────────
    function setState(s) {
        appState = s;

        // Screens
        el.screenIdle.classList.toggle('active',    s === 'idle');
        el.screenWaiting.classList.toggle('active', s === 'waiting');
        el.screenChat.classList.toggle('active',    s === 'chatting' || s === 'partner_left');

        // Footer (only during chatting)
        el.footer.classList.toggle('hidden', s !== 'chatting');

        // Input area
        el.inputArea.style.display = s === 'chatting' ? 'flex' : 'none';

        // Status badge
        el.statusBadge.className = 'connecting'; // reset
        const statusMap = {
            disconnected: ['connecting', '연결 중...'],
            idle:         ['idle',       '대기 중'],
            waiting:      ['waiting',    '매칭 대기'],
            chatting:     ['chatting',   '채팅 중'],
            partner_left: ['left',       '상대방 퇴장'],
        };
        const [cls, txt] = statusMap[s] || ['connecting', '연결 중...'];
        el.statusBadge.className = cls;
        el.statusText.textContent = txt;

        // Focus input on chat start
        if (s === 'chatting') {
            setTimeout(() => el.msgInput.focus(), 50);
        }
    }

    // ── Message rendering ──────────────────────────────────────────────────
    function addMessage(from, text, time) {
        const row  = document.createElement('div');
        row.className = `msg-row ${from}`;

        const bubble = document.createElement('div');
        bubble.className = 'bubble';
        bubble.textContent = text;

        row.appendChild(bubble);

        if (time && from !== 'system') {
            const ts = document.createElement('div');
            ts.className = 'msg-time';
            ts.textContent = time;
            row.appendChild(ts);
        }

        el.messages.appendChild(row);
        el.messages.scrollTop = el.messages.scrollHeight;
    }

    function clearMessages() {
        el.messages.innerHTML = '';
    }

    function addSystem(text) {
        addMessage('system', text);
    }

    // ── Server event handlers ──────────────────────────────────────────────
    function handleEvent(data) {
        switch (data.type) {

            case 'connected':
                setState('idle');
                reconnectDelay = 1000;
                break;

            case 'waiting':
                setState('waiting');
                break;

            case 'matched':
                clearMessages();
                setState('chatting');
                addSystem('상대방과 연결되었습니다. 대화를 시작하세요! 👋');
                break;

            case 'message':
                if (data.from === 'me') {
                    addMessage('me', data.message, data.timestamp);
                } else {
                    addMessage('partner', data.message, data.timestamp);
                }
                break;

            case 'partner_left':
                addSystem('상대방이 채팅을 떠났습니다.');
                setState('partner_left');
                // 끝내기/재매칭 버튼 텍스트를 상황에 맞게 변경
                el.footer.classList.remove('hidden');
                el.btnLeave.textContent   = '나가기';
                el.btnRematch.textContent = '다시 매칭';
                break;

            case 'match_cancelled':
                setState('idle');
                el.btnLeave.textContent   = '끝내기';
                el.btnRematch.textContent = '재매칭';
                break;

            case 'error':
                console.warn('[RC] Server error:', data.message);
                break;

            default:
                console.log('[RC] Unknown event:', data.type);
        }
    }

    // ── WebSocket ──────────────────────────────────────────────────────────
    function connect() {
        const proto = location.protocol === 'https:' ? 'wss:' : 'ws:';
        const url   = `${proto}//${location.host}/ws`;

        setState('disconnected');

        try {
            ws = new WebSocket(url);
        } catch (e) {
            scheduleReconnect();
            return;
        }

        ws.onopen = function () {
            console.log('[RC] WebSocket connected');
        };

        ws.onmessage = function (evt) {
            let data;
            try { data = JSON.parse(evt.data); } catch { return; }
            handleEvent(data);
        };

        ws.onclose = function () {
            console.log('[RC] WebSocket closed – reconnecting...');
            setState('disconnected');
            scheduleReconnect();
        };

        ws.onerror = function (err) {
            console.error('[RC] WebSocket error', err);
        };
    }

    function scheduleReconnect() {
        setTimeout(connect, reconnectDelay);
        reconnectDelay = Math.min(reconnectDelay * 1.5, 8000);
    }

    function send(type, extra) {
        if (!ws || ws.readyState !== WebSocket.OPEN) return;
        ws.send(JSON.stringify(Object.assign({ type }, extra)));
    }

    // ── Button handlers ────────────────────────────────────────────────────
    el.btnMatch.addEventListener('click', function () {
        send('match_start');
    });

    el.btnCancel.addEventListener('click', function () {
        send('match_cancel');
    });

    el.btnSend.addEventListener('click', sendMessage);

    el.msgInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    el.btnLeave.addEventListener('click', function () {
        if (appState === 'chatting') {
            send('chat_leave');
        } else {
            // partner_left 상태에서 나가기
            setState('idle');
            el.btnLeave.textContent   = '끝내기';
            el.btnRematch.textContent = '재매칭';
        }
    });

    el.btnRematch.addEventListener('click', function () {
        if (appState === 'chatting') {
            send('rematch');
        } else {
            // partner_left 상태에서 다시 매칭
            el.btnLeave.textContent   = '끝내기';
            el.btnRematch.textContent = '재매칭';
            send('match_start');
        }
    });

    function sendMessage() {
        const text = el.msgInput.value.trim();
        if (!text || appState !== 'chatting') return;
        send('chat_message', { message: text });
        el.msgInput.value = '';
    }

    // ── Boot ───────────────────────────────────────────────────────────────
    setState('disconnected');
    connect();

}());
</script>

</body>
</html>
