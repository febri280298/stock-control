<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Control Stock — Login</title>
  <link rel="icon" type="image/png" href="bti.png">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/vue/3.4.21/vue.global.prod.min.js"></script>
  <style>
    :root {
      --blue: #2563eb;
      --blue-dark: #1d4ed8;
      --blue-light: #3b82f6;
      --font: 'Plus Jakarta Sans', sans-serif;
    }
    * { margin:0; padding:0; box-sizing:border-box; }
    html, body { height:100%; font-family:var(--font); overflow:hidden; }

    /* ===== LAYOUT ===== */
    .login-layout {
      display: flex;
      height: 100vh;
      width: 100vw;
    }

    /* ===== LEFT PANEL ===== */
    .left-panel {
      flex: 1;
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 36px 44px;
    }
    .bg-image {
      position: absolute; inset: 0;
      background: url('warehouse-bg.jpg') center/cover no-repeat;
      filter: brightness(0.35);
      z-index: 0;
    }
    .bg-overlay {
      position: absolute; inset: 0;
      background: linear-gradient(135deg, rgba(10,20,50,0.85) 0%, rgba(30,60,120,0.6) 100%);
      z-index: 1;
    }
    .left-content { position: relative; z-index: 2; }

    /* BRAND */
    .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 56px; }
    .brand img { height: 44px; }
    .brand-text { color: #fff; }
    .brand-text .name { font-size: 15px; font-weight: 800; line-height: 1.1; }
    .brand-text .sub { font-size: 11px; color: rgba(255,255,255,0.5); font-weight: 500; letter-spacing: 1px; text-transform: uppercase; }

    /* HERO TEXT */
    .hero-text { margin-bottom: 48px; }
    .hero-text h1 {
      font-size: clamp(32px, 4vw, 52px);
      font-weight: 900;
      color: #fff;
      line-height: 1.1;
      margin-bottom: 12px;
    }
    .hero-text h1 span { color: var(--blue-light); }
    .hero-text p { font-size: 15px; color: rgba(255,255,255,0.6); line-height: 1.6; max-width: 360px; }
    .hero-text .divider { width: 48px; height: 3px; background: var(--blue-light); border-radius: 2px; margin: 16px 0; }

    /* FEATURES */
    .features { display: flex; flex-direction: column; gap: 20px; }
    .feature { display: flex; align-items: flex-start; gap: 16px; }
    .feature-icon {
      width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center; font-size: 18px;
    }
    .fi-blue { background: rgba(59,130,246,0.2); color: var(--blue-light); border: 1px solid rgba(59,130,246,0.3); }
    .fi-indigo { background: rgba(99,102,241,0.2); color: #818cf8; border: 1px solid rgba(99,102,241,0.3); }
    .fi-green { background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.25); }
    .fi-purple { background: rgba(168,85,247,0.15); color: #c084fc; border: 1px solid rgba(168,85,247,0.25); }
    .feature-text h4 { font-size: 14px; font-weight: 700; color: #fff; margin-bottom: 3px; }
    .feature-text p { font-size: 12px; color: rgba(255,255,255,0.5); line-height: 1.5; }

    /* STOCK WIDGET */
    .stock-widget {
      position: absolute;
      right: -20px; top: 50%;
      transform: translateY(-50%);
      background: rgba(255,255,255,0.06);
      backdrop-filter: blur(16px);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 16px;
      padding: 20px;
      width: 200px;
      z-index: 2;
    }
    .widget-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: rgba(255,255,255,0.4); margin-bottom: 14px; }
    .widget-stat { margin-bottom: 14px; }
    .widget-stat .ws-label { font-size: 10px; color: rgba(255,255,255,0.5); margin-bottom: 2px; }
    .widget-stat .ws-val { font-size: 28px; font-weight: 900; color: #fff; line-height: 1; }
    .widget-stat .ws-unit { font-size: 11px; color: rgba(255,255,255,0.4); }
    .donut-wrap { position: relative; width: 64px; height: 64px; margin-left: auto; margin-top: -60px; }
    .donut-wrap svg { transform: rotate(-90deg); }
    .donut-wrap .pct { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800; color: #fff; }
    .widget-divider { height: 1px; background: rgba(255,255,255,0.08); margin: 14px 0; }
    .widget-stat2 .ws2-label { font-size: 10px; color: rgba(255,255,255,0.5); margin-bottom: 4px; }
    .widget-stat2 .ws2-val { font-size: 22px; font-weight: 900; color: #fff; }
    .mini-chart { margin-top: 8px; display: flex; align-items: flex-end; gap: 3px; height: 32px; }
    .mini-bar { flex: 1; background: rgba(59,130,246,0.5); border-radius: 3px 3px 0 0; }

    /* LEFT FOOTER */
    .left-footer { position: relative; z-index: 2; }
    .left-footer p { font-size: 12px; color: rgba(255,255,255,0.3); }
    .left-footer strong { color: rgba(255,255,255,0.6); }

    /* ===== RIGHT PANEL ===== */
    .right-panel {
      width: 480px;
      flex-shrink: 0;
      background: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 48px;
      position: relative;
      overflow-y: auto;
    }
    .right-inner { width: 100%; max-width: 360px; }

    /* LOGO */
    .right-logo { text-align: center; margin-bottom: 28px; }
    .right-logo img { height: 100px; margin-bottom: 1px; }

    /* WELCOME */
    .welcome { text-align: center; margin-bottom: 32px; }
    .welcome h2 { font-size: 26px; font-weight: 800; color: #0f172a; margin-bottom: 8px; }
    .welcome p { font-size: 13px; color: #64748b; line-height: 1.6; }
    .welcome-divider { width: 40px; height: 3px; background: var(--blue); border-radius: 2px; margin: 12px auto; }

    /* FORM */
    .form-group { margin-bottom: 18px; }
    .form-label { font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 8px; display: block; }
    .input-wrap { position: relative; }
    .input-icon {
      position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
      color: #94a3b8; font-size: 17px; pointer-events: none;
    }
    .form-ctrl {
      width: 100%; padding: 13px 14px 13px 44px;
      border: 1.5px solid #e2e8f0; border-radius: 12px;
      background: #f8fafc; color: #0f172a;
      font-size: 14px; font-family: var(--font);
      transition: all 0.2s; -webkit-appearance: none;
    }
    .form-ctrl:focus { outline: none; border-color: var(--blue); background: #fff; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
    .form-ctrl::placeholder { color: #cbd5e1; }
    .eye-btn {
      position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer; color: #94a3b8; font-size: 17px;
      padding: 0; display: flex; align-items: center;
    }
    .eye-btn:hover { color: var(--blue); }

    /* REMEMBER & FORGOT */
    .form-meta { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
    .remember { display: flex; align-items: center; gap: 8px; cursor: pointer; }
    .remember input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--blue); cursor: pointer; }
    .remember span { font-size: 13px; color: #64748b; font-weight: 500; }
    .forgot { font-size: 13px; font-weight: 600; color: var(--blue); text-decoration: none; }
    .forgot:hover { color: var(--blue-dark); }
    .footer-copyright { text-align: center; font-size: 11px; color: #94a3b8; margin-top: 20px; }

    /* SUBMIT */
    .btn-submit {
      width: 100%; padding: 14px;
      background: var(--blue); color: #fff;
      border: none; border-radius: 12px;
      font-weight: 800; font-size: 14px; letter-spacing: 0.5px; text-transform: uppercase;
      cursor: pointer; transition: all 0.2s; font-family: var(--font);
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-submit:hover { background: var(--blue-dark); transform: translateY(-1px); box-shadow: 0 8px 20px rgba(37,99,235,0.3); }
    .btn-submit:active { transform: translateY(0); }
    .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; box-shadow: none; }

    /* ERROR */
    .error-box {
      background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px;
      padding: 12px 14px; margin-bottom: 18px;
      display: flex; align-items: center; gap: 8px;
      font-size: 13px; color: #dc2626; font-weight: 500;
    }

    /* SECURITY NOTE */
    .security-note {
      display: flex; align-items: center; justify-content: center; gap: 6px;
      margin-top: 24px; padding-top: 20px;
      border-top: 1px solid #f1f5f9;
      font-size: 12px; color: #94a3b8;
      text-align: center; line-height: 1.5;
    }
    .security-note i { font-size: 14px; color: #94a3b8; flex-shrink: 0; }

    /* LOADER */
    .spinner {
      width: 16px; height: 16px;
      border: 2px solid rgba(255,255,255,0.3);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin 0.7s linear infinite;
      flex-shrink: 0;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ICON FIX */
    .ti { font-family: "tabler-icons" !important; font-style: normal; font-weight: normal;
      display: inline-block; text-align: center; font-variant: normal;
      text-transform: none; line-height: 1em; }

    /* RESPONSIVE */
    @media (max-width: 900px) {
      .left-panel { display: none; }
      .right-panel { width: 100%; padding: 32px 24px; }
      html, body { overflow: auto; }
    }
    @media (max-width: 480px) {
      .right-panel { padding: 24px 20px; }
    }
  </style>
</head>
<body>

<script>
  // Menyesuaikan sendiri dengan lokasi halaman ini:
  //   lokal      http://127.0.0.1:8009/login.php -> http://127.0.0.1:8009/api
  //   production https://daylistockproject.bonecomtricom.net/login.php -> .../api
  const API_URL = location.origin + location.pathname.replace(/\/[^/]*$/, '') + '/api';
</script>

<div id="app">
  <div class="login-layout">

    <!-- ===== LEFT PANEL ===== -->
    <div class="left-panel">
      <div class="bg-image"></div>
      <div class="bg-overlay"></div>

      <!-- BRAND -->
      <div class="left-content">
        <div class="brand">
          <img src="bti.png" alt="Logo" onerror="this.style.display='none'">
          <div class="brand-text">
            <div class="name">BONECOM TRICOM</div>
            <div class="sub">Inventory System New Project</div>
          </div>
        </div>

        <!-- HERO -->
        <div class="hero-text">
          <h1>STOK <span>IN </span></h1>
          <div class="divider"></div>
          <p>System Management Inventory Part & Material New Project</p>
        </div>

        <!-- FEATURES -->
        <div class="features">
          <div class="feature">
            <div class="feature-icon fi-blue"><i class="ti ti-box"></i></div>
            <div class="feature-text">
              <h4>Real-Time Inventory</h4>
              <p>Pantau stok secara real-time dan akurat setiap saat.</p>
            </div>
          </div>
          <div class="feature">
            <div class="feature-icon fi-indigo"><i class="ti ti-chart-bar"></i></div>
            <div class="feature-text">
              <h4>Stock Management</h4>
              <p>Kelola material, part, dan persediaan dengan mudah.</p>
            </div>
          </div>
          <div class="feature">
            <div class="feature-icon fi-green"><i class="ti ti-shield-check"></i></div>
            <div class="feature-text">
              <h4>Secure System</h4>
              <p>Sistem aman dengan proteksi data berlapis.</p>
            </div>
          </div>
          <div class="feature">
            <div class="feature-icon fi-purple"><i class="ti ti-plug-connected"></i></div>
            <div class="feature-text">
              <h4>Project Integration</h4>
              <p>Terintegrasi dengan project dan kebutuhan operasional.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- LEFT FOOTER -->
      <div class="left-footer">
        <p><strong>Reliable • Accurate • Integrated</strong></p>
      </div>

    </div>

    <!-- ===== RIGHT PANEL ===== -->
    <div class="right-panel">
      <div class="right-inner">

        <!-- LOGO -->
        <div class="right-logo">
          <img src="bti.png" alt="Logo" onerror="this.style.display='none'">
        </div>

        <!-- WELCOME -->
        <div class="welcome">
          <h2>Selamat Datang</h2>
          <div class="welcome-divider"></div>
          <p>Silakan masuk untuk melanjutkan<br>ke sistem Control Stock.</p>
        </div>

        <!-- ERROR -->
        <div class="error-box" v-if="loginError">
          <i class="ti ti-alert-circle"></i> {{ loginError }}
        </div>

        <!-- FORM -->
        <div class="form-group">
          <label class="form-label">Username</label>
          <div class="input-wrap">
            <i class="ti ti-user input-icon"></i>
            <input class="form-ctrl" type="text" v-model="username"
              placeholder="Masukkan username" @keyup.enter="login" autocomplete="username">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="input-wrap">
            <i class="ti ti-lock input-icon"></i>
            <input class="form-ctrl" :type="showPass ? 'text' : 'password'"
              v-model="password" placeholder="Masukkan password"
              @keyup.enter="login" autocomplete="current-password">
            <button class="eye-btn" @click="showPass = !showPass" type="button">
              <i :class="showPass ? 'ti ti-eye-off' : 'ti ti-eye'"></i>
            </button>
          </div>
        </div>

        <div class="form-meta">
          <label class="remember">
            <input type="checkbox" v-model="rememberMe">
            <span>Ingat saya</span>
          </label>
          <a href="#" class="forgot">Lupa password?</a>
        </div>

        <button class="btn-submit" @click="login" :disabled="loading">
          <div class="spinner" v-if="loading"></div>
          <span v-if="!loading">MASUK SISTEM</span>
          <span v-if="!loading"><i class="ti ti-arrow-right"></i></span>
          <span v-if="loading">Memverifikasi...</span>
        </button>

        <div class="security-note">
          <i class="ti ti-shield-lock"></i>
          <span>Sistem aman dan hanya dapat diakses<br>oleh pengguna yang berwenang.</span>
        </div>

        <div class="footer-copyright">
          © 2026 PT Bonecom Tricom. All rights reserved.<br>
          Control Stock-New Project v1.0.0
        </div>

      </div>
    </div>

  </div>
</div>

<script>
const { createApp, ref, onMounted } = Vue;

createApp({
  setup() {
    const username = ref('');
    const password = ref('');
    const loading = ref(false);
    const loginError = ref('');
    const showPass = ref(false);
    const rememberMe = ref(false);
    const partsList = ref('—');

    onMounted(() => {
      // Cek sudah login
      const token = localStorage.getItem('token');
      const user = localStorage.getItem('currentUser');
      if (token && user) { window.location.href = 'app.php'; return; }

      // Load jumlah part untuk widget
      fetch(API_URL + '/parts/count').then(r => r.json()).then(d => { partsList.value = d.count || '—'; }).catch(() => {});
    });

    async function login() {
      if (!username.value.trim() || !password.value.trim()) {
        loginError.value = 'Username dan password wajib diisi!'; return;
      }
      loading.value = true; loginError.value = '';
      try {
        const res = await fetch(API_URL + '/auth/login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({ username: username.value.trim(), password: password.value.trim() })
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Username atau password salah!');
        localStorage.setItem('token', data.token);
        localStorage.setItem('currentUser', JSON.stringify(data.user));
        window.location.href = 'app.php';
      } catch (err) {
        loginError.value = err.message;
      } finally { loading.value = false; }
    }

    return { username, password, loading, loginError, showPass, rememberMe, partsList, login };
  }
}).mount('#app');
</script>
</body>
</html>
