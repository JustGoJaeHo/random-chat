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
        #status-badge.blocked    .dot { background: #f85149; }

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
            min-height: 0;
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
            align-items: stretch;
            padding: 0;
        }

        #messages {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            padding: 16px 16px 4px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            scroll-behavior: smooth;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        #messages::-webkit-scrollbar { display: none; }

        .msg-row {
            display: flex;
            flex-direction: column;
        }

        .msg-row.me    { align-items: flex-end; }
        .msg-row.partner { align-items: flex-start; }
        .msg-row.system { align-items: center; }

        .bubble {
            max-width: 75%;
            padding: 9px 14px;
            border-radius: 16px;
            font-size: 14px;
            line-height: 1.5;
            word-break: break-word;
            text-align: left;
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

        /* ── Register / Login screen ─────────────────────────────────────── */
        #screen-register,
        #screen-login {
            justify-content: flex-start;
            align-items: stretch;
            padding: 0;
            overflow-y: auto;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        #screen-register::-webkit-scrollbar,
        #screen-login::-webkit-scrollbar { display: none; }

        #register-inner,
        #login-inner {
            padding: 20px 20px 28px;
            display: flex;
            flex-direction: column;
        }

        #register-inner h2,
        #login-inner h2 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            text-align: center;
        }

        /* 묶음 컨테이너 */
        .field-section {
            background: rgba(255,255,255,.02);
            border: 1px solid #21262d;
            border-radius: 10px;
            padding: 10px 12px 6px;
            margin-bottom: 20px;
        }

        /* 가로 레이아웃: label | input-wrap */
        .field-group {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 6px;
        }

        .field-group label {
            width: 76px;
            flex-shrink: 0;
            font-size: 12px;
            font-weight: 500;
            color: #8b949e;
            padding-top: 10px;
            text-align: right;
            line-height: 1.3;
        }

        .field-input-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .field-input-wrap input,
        .field-input-wrap .gender-row {
            width: 100%;
        }

        .field-input-wrap input {
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 13px;
            color: #e6edf3;
            outline: none;
            transition: border-color .15s;
        }

        .field-input-wrap input:focus { border-color: #58a6ff; }
        .field-input-wrap input::placeholder { color: #6e7681; }

        .gender-row {
            display: flex;
            gap: 6px;
        }

        .gender-btn {
            flex: 1;
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 8px;
            padding: 8px 0;
            font-size: 13px;
            color: #8b949e;
            cursor: pointer;
            transition: all .15s;
            font-weight: 500;
        }

        .gender-btn.selected {
            border-color: #58a6ff;
            color: #58a6ff;
            background: rgba(88,166,255,.08);
        }

        .field-error {
            font-size: 11px;
            color: #f85149;
            min-height: 20px;
            padding: 2px 2px 0;
        }

        .register-actions {
            display: flex;
            gap: 8px;
            margin-top: 6px;
        }
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
            <button id="btn-show-login" class="btn btn-blue btn-full" style="max-width:240px; margin-top:10px">
                로그인
            </button>
            <button id="btn-show-register" class="btn btn-subtle btn-full" style="max-width:240px; margin-top:10px">
                회원가입
            </button>
            <button id="btn-logout" class="btn btn-subtle btn-full hidden" style="max-width:240px; margin-top:10px">
                로그아웃
            </button>
        </div>

        <!-- Login screen -->
        <div id="screen-login" class="screen">
            <div id="login-inner">
                <h2>로그인</h2>

                <div class="field-section">
                    <div class="field-group">
                        <label for="login-user-id">아이디</label>
                        <div class="field-input-wrap">
                            <input id="login-user-id" type="text" placeholder="아이디를 입력해주세요." maxlength="20" autocomplete="username">
                            <div class="field-error" id="err-login-user-id"></div>
                        </div>
                    </div>
                    <div class="field-group">
                        <label for="login-password">패스워드</label>
                        <div class="field-input-wrap">
                            <input id="login-password" type="password" placeholder="패스워드를 입력해주세요." maxlength="16" autocomplete="current-password">
                            <div class="field-error" id="err-login-password"></div>
                        </div>
                    </div>
                </div>

                <div class="field-error" id="err-login-general" style="text-align:center; margin-bottom:6px"></div>

                <div class="register-actions">
                    <button id="btn-login-cancel" class="btn btn-subtle" style="flex:1">취소</button>
                    <button id="btn-login-submit" class="btn btn-primary" style="flex:2">로그인</button>
                </div>
            </div>
        </div>

        <!-- Register screen -->
        <div id="screen-register" class="screen">
            <div id="register-inner">
                <h2>회원가입</h2>

                <!-- 계정 정보 묶음 -->
                <div class="field-section">
                    <div class="field-group">
                        <label for="reg-user-id">아이디</label>
                        <div class="field-input-wrap">
                            <input id="reg-user-id" type="text" placeholder="6~20자의 영문 소문자, 숫자 (영문 시작)" maxlength="20" autocomplete="user-id">
                            <div class="field-error" id="err-user-id"></div>
                        </div>
                    </div>
                    <div class="field-group">
                        <label for="reg-password">패스워드</label>
                        <div class="field-input-wrap">
                            <input id="reg-password" type="password" placeholder="8~16자의 영문+숫자+특수문자" maxlength="16" autocomplete="new-password">
                            <div class="field-error" id="err-password"></div>
                        </div>
                    </div>
                    <div class="field-group">
                        <label for="reg-password-confirm">패스워드 확인</label>
                        <div class="field-input-wrap">
                            <input id="reg-password-confirm" type="password" placeholder="패스워드를 다시 입력해주세요." maxlength="16" autocomplete="new-password">
                            <div class="field-error" id="err-password-confirm"></div>
                        </div>
                    </div>
                    <div class="field-group">
                        <label for="reg-email">이메일</label>
                        <div class="field-input-wrap">
                            <input id="reg-email" type="email" placeholder="example@email.com" maxlength="100" autocomplete="email">
                            <div class="field-error" id="err-email"></div>
                        </div>
                    </div>
                </div>

                <!-- 개인 정보 묶음 -->
                <div class="field-section">
                    <div class="field-group">
                        <label for="reg-name">이름</label>
                        <div class="field-input-wrap">
                            <input id="reg-name" type="text" placeholder="2~20자의 한글, 영문 (실명)" maxlength="20" autocomplete="name">
                            <div class="field-error" id="err-name"></div>
                        </div>
                    </div>
                    <div class="field-group">
                        <label for="reg-birth-date">생년월일</label>
                        <div class="field-input-wrap">
                            <input id="reg-birth-date" type="date" min="1900-01-01" max="" autocomplete="bday" style="cursor:pointer">
                            <div class="field-error" id="err-birth-date"></div>
                        </div>
                    </div>
                    <div class="field-group">
                        <label>성별</label>
                        <div class="field-input-wrap">
                            <div class="gender-row">
                                <button type="button" class="gender-btn" data-value="M">남성</button>
                                <button type="button" class="gender-btn" data-value="F">여성</button>
                            </div>
                            <input type="hidden" id="reg-gender" value="">
                            <div class="field-error" id="err-gender"></div>
                        </div>
                    </div>
                    <div class="field-group">
                        <label for="reg-phone">휴대전화번호</label>
                        <div class="field-input-wrap">
                            <input id="reg-phone" type="tel" placeholder="010-0000-0000" maxlength="13" autocomplete="tel">
                            <div class="field-error" id="err-phone"></div>
                        </div>
                    </div>
                </div>

                <!-- 닉네임 묶음 -->
                <div class="field-section">
                    <div class="field-group">
                        <label for="reg-nickname">닉네임</label>
                        <div class="field-input-wrap">
                            <input id="reg-nickname" type="text" placeholder="2~16자의 한글, 영문, 숫자" maxlength="16" autocomplete="off">
                            <div class="field-error" id="err-nickname"></div>
                        </div>
                    </div>
                </div>

                <div class="field-error" id="err-general" style="text-align:center; margin-bottom:6px"></div>

                <div class="register-actions">
                    <button id="btn-register-cancel" class="btn btn-subtle" style="flex:1">취소</button>
                    <button id="btn-register-submit" class="btn btn-primary" style="flex:2">가입</button>
                </div>
            </div>
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

        <!-- Blocked screen (duplicate tab) -->
        <div id="screen-blocked" class="screen">
            <div class="icon">🚫</div>
            <h2>다른 탭에서 이용 중</h2>
            <p>이미 다른 탭에서 채팅 서비스를<br>이용하고 있습니다.<br>해당 탭을 닫으면 이 탭에서 이용할 수 있습니다.</p>
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
    // 'disconnected' | 'idle' | 'waiting' | 'chatting' | 'partner_left' | 'blocked' | 'register' | 'login'
    let appState = 'disconnected';
    let ws = null;
    let reconnectDelay = 1000;
    let loggedInUserId = <?= json_encode($loggedInUserId ?? null) ?>;

    // ── Browser ID (shared across tabs via localStorage) ───────────────────
    function getBrowserId() {
        let bid = localStorage.getItem('rc_browser_id');
        if (!bid) {
            bid = 'b' + Math.random().toString(36).slice(2) + Date.now().toString(36);
            localStorage.setItem('rc_browser_id', bid);
        }
        return bid;
    }
    const browserId = getBrowserId();

    // ── Elements ───────────────────────────────────────────────────────────
    const el = {
        statusBadge:         document.getElementById('status-badge'),
        statusText:          document.getElementById('status-text'),
        screenIdle:          document.getElementById('screen-idle'),
        screenWaiting:       document.getElementById('screen-waiting'),
        screenBlocked:       document.getElementById('screen-blocked'),
        screenChat:          document.getElementById('screen-chat'),
        screenRegister:      document.getElementById('screen-register'),
        messages:            document.getElementById('messages'),
        msgInput:            document.getElementById('msg-input'),
        btnMatch:            document.getElementById('btn-match'),
        btnShowLogin:        document.getElementById('btn-show-login'),
        btnShowRegister:     document.getElementById('btn-show-register'),
        btnLogout:           document.getElementById('btn-logout'),
        btnLoginCancel:      document.getElementById('btn-login-cancel'),
        btnLoginSubmit:      document.getElementById('btn-login-submit'),
        screenLogin:         document.getElementById('screen-login'),
        loginUserId:         document.getElementById('login-user-id'),
        loginPassword:       document.getElementById('login-password'),
        errLoginUserId:      document.getElementById('err-login-user-id'),
        errLoginPassword:    document.getElementById('err-login-password'),
        errLoginGeneral:     document.getElementById('err-login-general'),
        btnCancel:           document.getElementById('btn-cancel'),
        btnSend:             document.getElementById('btn-send'),
        btnLeave:            document.getElementById('btn-leave'),
        btnRematch:          document.getElementById('btn-rematch'),
        btnRegisterCancel:   document.getElementById('btn-register-cancel'),
        btnRegisterSubmit:   document.getElementById('btn-register-submit'),
        footer:              document.getElementById('footer'),
        inputArea:           document.getElementById('input-area'),
        regUserId:           document.getElementById('reg-user-id'),
        regPassword:         document.getElementById('reg-password'),
        regPasswordConfirm:  document.getElementById('reg-password-confirm'),
        regEmail:            document.getElementById('reg-email'),
        regName:             document.getElementById('reg-name'),
        regBirthDate:        document.getElementById('reg-birth-date'),
        regGender:           document.getElementById('reg-gender'),
        regPhone:            document.getElementById('reg-phone'),
        regNickname:         document.getElementById('reg-nickname'),
        genderBtns:          document.querySelectorAll('.gender-btn'),
    };

    // ── Auth button visibility ─────────────────────────────────────────────
    function updateAuthButtons() {
        var loggedIn = loggedInUserId !== null;
        el.btnShowLogin.classList.toggle('hidden',    loggedIn);
        el.btnShowRegister.classList.toggle('hidden', loggedIn);
        el.btnLogout.classList.toggle('hidden',       !loggedIn);
    }

    // ── UI state machine ───────────────────────────────────────────────────
    function setState(s) {
        appState = s;

        // Screens
        el.screenIdle.classList.toggle('active',     s === 'idle');
        el.screenWaiting.classList.toggle('active',  s === 'waiting');
        el.screenBlocked.classList.toggle('active',  s === 'blocked');
        el.screenChat.classList.toggle('active',     s === 'chatting' || s === 'partner_left');
        el.screenRegister.classList.toggle('active', s === 'register');
        el.screenLogin.classList.toggle('active',    s === 'login');

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
            blocked:      ['blocked',    '다른 탭 이용 중'],
            register:     ['idle',       '회원가입'],
            login:        ['idle',       '로그인'],
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
                send('session_init', { browserId });
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

            case 'duplicate_session':
                setState('blocked');
                break;

            case 'session_released':
                if (appState === 'blocked') {
                    setState('idle');
                }
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

    // ── Register screen ────────────────────────────────────────────────────
    // 생년월일 max를 오늘 날짜로 제한, 직접 입력 차단 (캘린더만 허용)
    el.regBirthDate.setAttribute('max', new Date().toISOString().slice(0, 10));
    el.regBirthDate.addEventListener('keydown', function (e) {
        if (e.key !== 'Tab') e.preventDefault();
    });

    // 성별 토글
    el.genderBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            el.genderBtns.forEach(function (b) { b.classList.remove('selected'); });
            btn.classList.add('selected');
            el.regGender.value = btn.dataset.value;
        });
    });

    // 휴대전화번호 자동 하이픈
    el.regPhone.addEventListener('input', function () {
        let v = el.regPhone.value.replace(/\D/g, '');
        if (v.length > 11) v = v.slice(0, 11);
        if (v.length > 7) {
            v = v.slice(0, 3) + '-' + v.slice(3, 7) + '-' + v.slice(7);
        } else if (v.length > 3) {
            v = v.slice(0, 3) + '-' + v.slice(3);
        }
        el.regPhone.value = v;
    });

    function clearRegisterErrors() {
        ['user-id', 'password', 'password-confirm', 'email', 'name', 'birth-date', 'gender', 'phone', 'nickname', 'general'].forEach(function (k) {
            var el2 = document.getElementById('err-' + k);
            if (el2) el2.textContent = '';
        });
    }

    function resetRegisterForm() {
        el.regUserId.value          = '';
        el.regPassword.value        = '';
        el.regPasswordConfirm.value = '';
        el.regEmail.value           = '';
        el.regName.value            = '';
        el.regBirthDate.value       = '';
        el.regGender.value          = '';
        el.regPhone.value           = '';
        el.regNickname.value        = '';
        el.genderBtns.forEach(function (b) { b.classList.remove('selected'); });
        clearRegisterErrors();
    }

    el.btnShowLogin.addEventListener('click', function () {
        resetLoginForm();
        setState('login');
    });

    el.btnLoginCancel.addEventListener('click', function () {
        setState('idle');
    });

    el.btnLoginSubmit.addEventListener('click', submitLogin);

    el.loginPassword.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); submitLogin(); }
    });

    function clearLoginErrors() {
        el.errLoginUserId.textContent  = '';
        el.errLoginPassword.textContent = '';
        el.errLoginGeneral.textContent  = '';
    }

    function resetLoginForm() {
        el.loginUserId.value   = '';
        el.loginPassword.value = '';
        clearLoginErrors();
    }

    function submitLogin() {
        clearLoginErrors();

        var userId   = el.loginUserId.value.trim();
        var password = el.loginPassword.value;

        if (!userId) {
            el.errLoginUserId.textContent = '아이디를 입력해주세요.';
            el.loginUserId.focus();
            return;
        }

        if (!password) {
            el.errLoginPassword.textContent = '패스워드를 입력해주세요.';
            el.loginPassword.focus();
            return;
        }

        el.btnLoginSubmit.disabled = true;
        el.btnLoginSubmit.textContent = '로그인 중...';

        var formData = new FormData();
        formData.append('user_id',  userId);
        formData.append('password', password);

        fetch('/auth/login', {
            method: 'POST',
            body: formData,
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) {
                loggedInUserId = userId;
                resetLoginForm();
                setState('idle');
                updateAuthButtons();
            } else {
                el.errLoginGeneral.textContent = data.message || '로그인에 실패했습니다.';
            }
        })
        .catch(function () {
            el.errLoginGeneral.textContent = '네트워크 오류가 발생했습니다. 다시 시도해주세요.';
        })
        .finally(function () {
            el.btnLoginSubmit.disabled = false;
            el.btnLoginSubmit.textContent = '로그인';
        });
    }

    el.btnShowRegister.addEventListener('click', function () {
        resetRegisterForm();
        setState('register');
    });

    el.btnRegisterCancel.addEventListener('click', function () {
        setState('idle');
    });

    el.btnRegisterSubmit.addEventListener('click', function () {
        clearRegisterErrors();

        // 패스워드 확인 클라이언트 검증
        if (el.regPassword.value !== el.regPasswordConfirm.value) {
            document.getElementById('err-password-confirm').textContent = '패스워드가 일치하지 않습니다.';
            el.regPasswordConfirm.focus();
            return;
        }

        // 패스워드 복잡도 클라이언트 검증 (영문+숫자+특수문자)
        var pwVal = el.regPassword.value;
        if (!/[a-zA-Z]/.test(pwVal) || !/[0-9]/.test(pwVal) || !/[^a-zA-Z0-9]/.test(pwVal)) {
            document.getElementById('err-password').textContent = '패스워드는 영문, 숫자, 특수문자를 모두 포함해야 합니다.';
            el.regPassword.focus();
            return;
        }

        el.btnRegisterSubmit.disabled = true;
        el.btnRegisterSubmit.textContent = '가입 중...';

        var formData = new FormData();
        formData.append('user_id',    el.regUserId.value.trim());
        formData.append('password',   el.regPassword.value);
        formData.append('email',      el.regEmail.value.trim());
        formData.append('name',       el.regName.value.trim());
        formData.append('birth_date', el.regBirthDate.value);
        formData.append('gender',     el.regGender.value);
        formData.append('phone',      el.regPhone.value.trim());
        formData.append('nickname',   el.regNickname.value.trim());

        fetch('/auth/register', {
            method: 'POST',
            body: formData,
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) {
                resetRegisterForm();
                setState('idle');
                // 간단한 성공 알림
                var notice = document.createElement('div');
                notice.style.cssText = 'position:fixed;top:20px;left:50%;transform:translateX(-50%);background:#238636;color:#fff;padding:10px 20px;border-radius:8px;font-size:14px;z-index:9999;';
                notice.textContent = '회원가입이 완료되었습니다!';
                document.body.appendChild(notice);
                setTimeout(function () { notice.remove(); }, 3000);
            } else {
                var errors = data.errors || {};
                var fieldMap = {
                    user_id:    'err-user-id',
                    password:   'err-password',
                    email:      'err-email',
                    name:       'err-name',
                    birth_date: 'err-birth-date',
                    gender:     'err-gender',
                    phone:      'err-phone',
                    nickname:   'err-nickname',
                    general:    'err-general',
                };
                Object.keys(errors).forEach(function (key) {
                    var elId = fieldMap[key] || 'err-general';
                    var errEl = document.getElementById(elId);
                    if (errEl) errEl.textContent = errors[key];
                });
            }
        })
        .catch(function () {
            document.getElementById('err-general').textContent = '네트워크 오류가 발생했습니다. 다시 시도해주세요.';
        })
        .finally(function () {
            el.btnRegisterSubmit.disabled = false;
            el.btnRegisterSubmit.textContent = '가입';
        });
    });

    // ── Boot ───────────────────────────────────────────────────────────────
    updateAuthButtons();
    setState('disconnected');
    connect();

}());
</script>

</body>
</html>
