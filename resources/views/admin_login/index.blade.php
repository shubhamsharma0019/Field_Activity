<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Field Activity</title>
    <style>
        /* Page layout */
        * { box-sizing: border-box; }
        html, body { min-height: 100%; }
        body { min-height: 100vh; margin: 0; padding: 18px; overflow: hidden; font-family: Arial, sans-serif; color: #0b1224; background: #eaf2fd; }
        button, input { font: inherit; }
        button { cursor: pointer; }
        .login-page { display: flex; width: min(100%, 1376px); height: calc(100vh - 36px); min-height: 620px; margin: 0 auto; border-radius: 16px; overflow: hidden; background: #f3f7fe; box-shadow: 0 9px 30px #254e7810; }
        .icon { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }
        .icon-library { position: absolute; width: 0; height: 0; overflow: hidden; }
        /* Left panel */
        .brand-panel { position: relative; width: 43%; min-height: 100%; padding: 28px 41px; color: white; background: linear-gradient(160deg, #0b1b30f5 0%, #214c78de 42%, #214c7830 80%), url('{{ asset('images/admin-construction.jpg') }}') center bottom / cover; }
        .version { position: absolute; top: 20px; right: 28px; font-size: 12px; }
        .brand-heading { text-align: center; }
        .brand-logo { width: 64px; height: 66px; margin-bottom: 8px; }
        .brand-heading h1 { margin: 0; font-size: 32px; line-height: 1.15; }
        .brand-heading h2 { margin: 4px 0 16px; font-size: 19px; font-weight: 400; }
        .brand-heading p { font-size: 15px; line-height: 1.4; }
        .features { max-width: 292px; margin: 18px auto; }
        .feature { display: flex; align-items: center; gap: 17px; margin-bottom: 12px; }
        .feature-icon { display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; flex-shrink: 0; border: 2px solid #8cbcd37a; border-radius: 50%; background: #638f9e20; }
        .feature h3 { font-size: 13px; margin: 0 0 4px; }
        .feature p { margin: 0; font-size: 12px; line-height: 1.4; }
        .map-art { position: absolute; width: 65%; bottom: 62px; right: 15px; opacity: .85; }
        /* Login card and inputs */
        .form-panel { display: flex; flex-direction: column; justify-content: center; width: 57%; min-height: 100%; padding: 18px 46px; background: linear-gradient(135deg, #eaf3ff, #f9fbff 50%, #eaf3ff); }
        .secure-access { display: flex; justify-content: flex-end; align-items: center; gap: 8px; margin: 0 52px 20px 0; color: #52637c; font-size: 12px; }
        .secure-access .icon { width: 14px; height: 14px; }
        .login-card { width: min(100%, 690px); margin: 0 auto; padding: 30px 48px 16px; border: 2px solid white; border-radius: 15px; background: #ffffffed; box-shadow: 0 9px 26px #5276ad0b; }
        .welcome { color: #405cf1; font-size: 16px; font-weight: bold; margin: 0 0 12px; }
        .login-card h2 { margin: 0 0 10px; font-size: clamp(24px, 2vw, 30px); line-height: 1.16; letter-spacing: 0; }
        .description { color: #5d6c85; font-size: 14px; line-height: 1.45; margin: 0 0 24px; }
        .field-label { display: block; font-size: 14px; font-weight: bold; margin: 0 0 7px; }
        .input-box { display: flex; align-items: center; gap: 14px; height: 44px; padding: 0 15px; border: 1px solid #d0d6e1; border-radius: 7px; background: #f7f9ff; color: #506078; margin-bottom: 18px; }
        .input-box:focus-within { border-color: #4163ff; outline: 3px solid #4163ff15; }
        .input-box input { width: 100%; min-width: 0; border: 0; outline: 0; background: transparent; color: #152038; font-size: 13px; }
        .input-box input::placeholder { color: #7c89a0; }
        .password-toggle { display: flex; border: 0; padding: 3px; color: #506078; background: transparent; }
        .form-options { display: flex; justify-content: space-between; align-items: center; gap: 9px; margin: -2px 0 20px; font-size: 13px; }
        .remember { display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .remember input { width: 18px; height: 18px; margin: 0; accent-color: #4261fa; }
        .forgot { border: 0; padding: 0; background: transparent; color: #4059ed; font-weight: bold; }
        .login-button { width: 100%; height: 48px; display: flex; align-items: center; justify-content: center; gap: 16px; border: 0; border-radius: 8px; color: white; background: linear-gradient(110deg, #386bf9, #455ffc); font-size: 16px; font-weight: bold; }
        .login-button:hover { background: #3152e2; }
        button:focus-visible, input:focus-visible { outline: 3px solid #8aa8ff; outline-offset: 3px; }
        .divider { display: flex; align-items: center; gap: 12px; margin: 18px 0 14px; color: #687991; font-size: 12px; }
        .divider::before, .divider::after { content: ''; height: 1px; background: #e0e5ed; flex: 1; }
        .roles { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .role { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 10px 4px; border: 1px solid #e7dff8; border-radius: 7px; background: #f8f5ff; text-align: center; }
        .role .icon { color: #963eee; }
        .role strong { font-size: 12px; }
        .role span { font-size: 12px; color: #53617a; }
        .role.admin { background: #f5f9ff; border-color: #dfe9fa; }
        .role.admin .icon { color: #2e73eb; }
        .role.company { background: #f3fcf7; border-color: #dcefe3; }
        .role.company .icon { color: #20ae69; }
        .role.worker { background: #fff9f3; border-color: #f5e5d7; }
        .role.worker .icon { color: #fa8614; }
        .security-note { display: flex; align-items: center; gap: 8px; margin: 14px 0 0; padding: 10px 13px; border-radius: 7px; background: #eaf2ff; color: #245bc6; font-size: 12px; line-height: 1.4; }
        .security-note .icon { width: 15px; height: 15px; }
        .form-message { margin: 12px 0 0; color: #53617a; font-size: 12px; line-height: 1.5; }
        .form-message.error { color: #d93025; }
        .form-message:empty { display: none; }
        /* Mobile and tablet layout */
        @media (max-width: 1200px) {
            body { padding: 14px; }
            .login-page { height: calc(100vh - 28px); }
            .brand-panel { padding: 38px 19px; }
            .brand-heading h1 { font-size: 28px; }
            .form-panel { padding: 16px 24px; }
            .secure-access { margin-right: 20px; }
            .login-card { padding: 26px 32px 16px; }
            .roles { gap: 6px; }
        }
        @media (max-width: 850px) {
            body { overflow: auto; }
            .login-page { flex-direction: column; height: auto; min-height: auto; }
            .brand-panel, .form-panel { width: 100%; }
            .brand-panel { padding: 26px 19px; }
            .brand-logo { width: 49px; height: 49px; }
            .brand-heading h1 { font-size: 26px; }
            .brand-heading h2 { font-size: 17px; }
            .features, .map-art { display: none; }
            .brand-heading p { margin-bottom: 0; font-size: 13px; }
            .secure-access { margin: 0 0 15px; }
        }
        @media (max-width: 480px) {
            body { padding: 8px; }
            .form-panel { padding: 15px 9px; }
            .login-card { padding: 20px 14px 15px; }
            .welcome { font-size: 15px; }
            .login-card h2 { font-size: 22px; }
            .description { font-size: 12px; margin-bottom: 19px; }
            .input-box { padding: 0 9px; gap: 8px; }
            .input-box input { font-size: 12px; }
            .form-options { font-size: 12px; }
            .roles { grid-template-columns: repeat(2, 1fr); }
            .version { top: 12px; right: 14px; font-size: 12px; }
        }
    </style>
</head>
<body>
    <!-- Icons are kept in this file so no icon library is needed. -->
    <svg class="icon-library" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <symbol id="lock" viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V6a4 4 0 0 1 8 0v4"/></symbol>
        <symbol id="mail" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m3 5 9 8 9-8"/></symbol>
        <symbol id="eye" viewBox="0 0 24 24"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></symbol>
        <symbol id="arrow" viewBox="0 0 24 24"><path d="M3 12h18m-8-8 8 8-8 8"/></symbol>
        <symbol id="clipboard" viewBox="0 0 24 24"><path d="M9 4H4v18h16V4h-5M8 12h8m-8 5h8"/><rect x="8" y="2" width="8" height="5" rx="1"/></symbol>
        <symbol id="pin" viewBox="0 0 24 24"><path d="M20 9c0 6-8 13-8 13S4 15 4 9a8 8 0 1 1 16 0Z"/><circle cx="12" cy="9" r="3"/></symbol>
        <symbol id="chart" viewBox="0 0 24 24"><path d="M4 20v-6h3v6Zm7 0V8h3v12Zm7 0V2h3v18Z" fill="currentColor" stroke="none"/></symbol>
        <symbol id="crown" viewBox="0 0 24 24"><path d="m2 6 5 4 5-7 5 7 5-4-3 12H5Zm4 16h12" fill="currentColor"/></symbol>
        <symbol id="shield" viewBox="0 0 24 24"><path d="m12 2 9 4v6c0 5-9 10-9 10S3 17 3 12V6Z" fill="currentColor" stroke="none"/><path d="m9 12 2 2 4-5" stroke="white"/></symbol>
        <symbol id="building" viewBox="0 0 24 24"><path d="M4 22V3h12v19m0-14h5v14M2 22h21M8 7h1m3 0h1m-5 4h1m3 0h1m-5 4h1m3 0h1m-2 7v-3"/></symbol>
        <symbol id="person" viewBox="0 0 24 24"><circle cx="12" cy="7" r="5" fill="currentColor" stroke="none"/><path d="M2 22v-3c0-8 20-8 20 0v3Z" fill="currentColor" stroke="none"/></symbol>
    </svg>
    <main class="login-page">
        <section class="brand-panel" aria-label="Field Activity Management System">
            <span class="version">v1.0.0</span>
            <div class="brand-heading">
                <svg class="brand-logo" viewBox="0 0 100 110" aria-hidden="true">
                    <defs><linearGradient id="green"><stop stop-color="#79e2bd"/><stop offset="1" stop-color="#009e7a"/></linearGradient></defs>
                    <path d="M50 105S10 61 10 40a40 40 0 0 1 80 0c0 21-40 65-40 65" fill="url(#green)"/>
                    <path d="M50 0v105S72 62 72 37 50 0 50 0" fill="#008c7280"/>
                    <circle cx="50" cy="38" r="14" fill="white"/>
                    <path d="M48 108C18 109 3 91 1 78c24-14 42 9 47 30M53 108c29 0 44-16 46-30-23-15-40 8-46 30" fill="#46d493"/>
                    <path d="m20 87 28 21m32-21-27 21" stroke="#c4ffe1" stroke-width="2"/>
                </svg>
                <h1>Field Activity</h1>
                <h2>Management System</h2>
                <p>Monitor. Track. Manage.<br>Field Activities Efficiently.</p>
            </div>
            <div class="features">
                <div class="feature">
                    <div class="feature-icon"><svg class="icon" aria-hidden="true"><use href="#clipboard"/></svg></div>
                    <div><h3>Project Management</h3><p>Plan and organize field activities</p></div>
                </div>
                <div class="feature">
                    <div class="feature-icon"><svg class="icon" aria-hidden="true"><use href="#pin"/></svg></div>
                    <div><h3>Real-time Tracking</h3><p>Monitor field teams with GPS</p></div>
                </div>
                <div class="feature">
                    <div class="feature-icon"><svg class="icon" aria-hidden="true"><use href="#chart"/></svg></div>
                    <div><h3>Reports &amp; Analytics</h3><p>Get insights and track progress</p></div>
                </div>
            </div>
            <svg class="map-art" viewBox="0 0 400 200" aria-hidden="true">
                <path d="m30 160 80-50 70 60 70-105 105 35" stroke="white" stroke-width="3" stroke-dasharray="4 8" fill="none"/>
                <g fill="#bfdeff"><path d="M110 110s-19-23-19-35a19 19 0 1 1 38 0c0 12-19 35-19 35Z"/><path d="M250 65s-19-23-19-35a19 19 0 1 1 38 0c0 12-19 35-19 35Z"/><path d="M355 100s-19-23-19-35a19 19 0 1 1 38 0c0 12-19 35-19 35Z"/></g>
                <g fill="#447ba3"><circle cx="110" cy="75" r="10"/><circle cx="250" cy="30" r="10"/><circle cx="355" cy="65" r="10"/></g>
            </svg>
        </section>
        <section class="form-panel" aria-labelledby="login-title">
            <p class="secure-access"><svg class="icon" aria-hidden="true"><use href="#lock"/></svg>Secure Admin Access</p>
            <div class="login-card">
                <p class="welcome">Welcome Back 👋</p>
                <h2 id="login-title">Sign in to your Admin Account</h2>
                <p class="description">Access the Field Activity Management System to manage companies, projects, workers and activities.</p>
                <form id="login-form" method="POST" action="{{ route('admin.login.submit') }}">
                    @csrf
                    <label class="field-label" for="email">Email Address</label>
                    <div class="input-box">
                        <svg class="icon" aria-hidden="true"><use href="#mail"/></svg>
                        <input type="email" id="email" name="email" value="{{ old('email', $defaultEmail ?? 'admin@gmail.com') }}" placeholder="Enter your email address" autocomplete="username" required>
                    </div>
                    <label class="field-label" for="password">Password</label>
                    <div class="input-box">
                        <svg class="icon" aria-hidden="true"><use href="#lock"/></svg>
                        <input type="password" id="password" name="password" value="{{ $defaultPassword ?? '12345678' }}" placeholder="Enter your password" autocomplete="current-password" required>
                        <button class="password-toggle" type="button" id="toggle-password" aria-label="Show password" aria-pressed="false"><svg class="icon" aria-hidden="true"><use href="#eye"/></svg></button>
                    </div>
                    <div class="form-options">
                        <label class="remember"><input type="checkbox" name="remember" checked>Remember me</label>
                        <button class="forgot" type="button" id="forgot-password">Forgot Password?</button>
                    </div>
                    <button class="login-button" type="submit"><svg class="icon" aria-hidden="true"><use href="#arrow"/></svg>Login</button>
                    <p class="form-message @if ($errors->any()) error @endif" id="form-message" role="status">
                        @if ($errors->any())
                            {{ $errors->first() }}
                        @endif
                    </p>
                </form>
                <div class="divider">Or continue with</div>
                <div class="roles" aria-label="System roles">
                    <div class="role"><svg class="icon" aria-hidden="true"><use href="#crown"/></svg><strong>Super Admin</strong><span>Full Access</span></div>
                    <div class="role admin"><svg class="icon" aria-hidden="true"><use href="#shield"/></svg><strong>Admin</strong><span>Manage System</span></div>
                    <div class="role company"><svg class="icon" aria-hidden="true"><use href="#building"/></svg><strong>Company</strong><span>Monitor Projects</span></div>
                    <div class="role worker"><svg class="icon" aria-hidden="true"><use href="#person"/></svg><strong>Worker</strong><span>Field Activities</span></div>
                </div>
                <p class="security-note"><svg class="icon" aria-hidden="true"><use href="#shield"/></svg>Authorized access only. All activities are logged and monitored.</p>
            </div>
        </section>
    </main>
    <script>
        // Show or hide the password.
        const password = document.getElementById('password');
        const togglePassword = document.getElementById('toggle-password');

        togglePassword.addEventListener('click', function () {
            const showPassword = password.type === 'password';
            password.type = showPassword ? 'text' : 'password';
            togglePassword.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
            togglePassword.setAttribute('aria-pressed', showPassword ? 'true' : 'false');
        });

        document.getElementById('forgot-password').addEventListener('click', function () {
            const message = document.getElementById('form-message');
            message.classList.remove('error');
            message.textContent = 'Please contact your administrator to reset your password.';
        });
    </script>
</body>
</html>
