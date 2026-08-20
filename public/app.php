<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Control Stock — New Project</title>
  <link rel="icon" type="image/png" href="bti.png">

  <!-- Tabler CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tabler/1.0.0-beta20/css/tabler.min.css">
  <!-- Tabler Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- Chart.js -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
  <!-- SheetJS -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <!-- Vue 3 -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/vue/3.4.21/vue.global.prod.min.js"></script>

  <style>
    :root {
      --font: 'Plus Jakarta Sans', sans-serif;
      --primary: #5f6fff;
      --primary-dark: #4a57e8;
      --success: #2fb344;
      --danger: #d63939;
      --warning: #f76707;
      --surface: #ffffff;
      --surface2: #f4f6fa;
      --border: #e6e9ef;
      --text: #1a1f2e;
      --text-muted: #6c7a99;
      --radius: 12px;
      --nav-h: 68px;
      --sidebar-w: 260px;
    }
    [data-theme="dark"] {
      --surface: #1a1f2e;
      --surface2: #111827;
      --border: #262a46;
      --text: #e2e8f0;
      --text-muted: #8892a4;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { height: 100%; font-family: var(--font); background: var(--surface2); color: var(--text); transition: background 0.3s, color 0.3s; }

    /* ===== LAYOUT ===== */
    .app-layout { display: flex; min-height: 100vh; }

    /* ===== SIDEBAR ===== */
    .sidebar {
      width: var(--sidebar-w);
      background: var(--surface);
      border-right: 1px solid var(--border);
      position: fixed; left: 0; top: 0; height: 100vh;
      z-index: 100;
      overflow-y: auto;
      display: flex; flex-direction: column;
      transition: transform 0.3s;
    }
    .sidebar-brand { padding: 20px 20px 16px; border-bottom: 1px solid var(--border); }
    .sidebar-brand img { width: 100px; display: block; }
    .sidebar-brand small { font-size: 11px; color: var(--text-muted); margin-top: 4px; display: block; }
    .nav-section { padding: 16px 12px 0; }
    .nav-section-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); padding: 0 10px; margin-bottom: 6px; }
    .nav-link {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 12px;
      border-radius: 9px;
      color: var(--text-muted);
      cursor: pointer;
      transition: all 0.2s;
      font-size: 13.5px;
      font-weight: 500;
      margin-bottom: 2px;
      border: none; background: transparent; width: 100%; text-align: left;
    }
    .nav-link:hover { background: var(--surface2); color: var(--text); }
    .nav-link.active { background: rgba(95,111,255,0.1); color: var(--primary); font-weight: 700; }
    .nav-link i { font-size: 18px; line-height: 1; }
    .sidebar-footer { margin-top: auto; padding: 16px 12px; border-top: 1px solid var(--border); }

    /* ===== MAIN ===== */
    .main { flex: 1; margin-left: var(--sidebar-w); min-height: 100vh; }

    /* ===== TOPBAR ===== */
    .topbar {
      background: var(--surface);
      border-bottom: 1px solid var(--border);
      padding: 0 20px;
      height: 60px;
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 50;
    }
    .topbar-title { font-size: 17px; font-weight: 800; color: var(--text); }
    .topbar-right { display: flex; align-items: center; gap: 10px; }
    .datetime-pill {
      background: var(--surface2);
      border: 1px solid var(--border);
      padding: 5px 12px;
      border-radius: 8px;
      font-size: 12px;
      text-align: right;
      line-height: 1.3;
    }
    .datetime-pill .time { font-weight: 700; color: var(--primary); font-size: 13px; }
    .datetime-pill .date { color: var(--text-muted); font-size: 10px; }
    .icon-btn {
      width: 36px; height: 36px;
      border-radius: 8px;
      border: 1px solid var(--border);
      background: var(--surface2);
      color: var(--text-muted);
      cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      font-size: 16px;
      transition: all 0.2s;
    }
    .icon-btn:hover { background: var(--surface); color: var(--primary); }
    .icon-btn i { font-size: 17px; line-height: 1; }
    .role-badge {
      background: rgba(95,111,255,0.1);
      color: var(--primary);
      font-size: 11px;
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 6px;
      text-transform: uppercase;
    }
    .greeting { font-size: 12px; color: var(--text-muted); }

    /* ===== PAGE ===== */
    .page { padding: 20px; }

    /* ===== STAT CARDS ===== */
    .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px; }
    .stat-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 16px;
      display: flex; align-items: center; gap: 14px;
    }
    .stat-icon {
      width: 44px; height: 44px;
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; line-height: 1; font-size: 22px;
    }
    .stat-icon i { font-size: 22px; line-height: 1; }
    .stat-icon.blue { background: rgba(95,111,255,0.12); color: var(--primary); }
    .stat-icon.green { background: rgba(47,179,68,0.12); color: var(--success); }
    .stat-icon.orange { background: rgba(247,103,7,0.12); color: var(--warning); }
    .stat-icon.red { background: rgba(214,57,57,0.12); color: var(--danger); }
    .stat-icon.cyan { background: rgba(23,162,184,0.12); color: #17a2b8; }
    .stat-icon.purple { background: rgba(111,66,193,0.12); color: #6f42c1; }
    .stat-body .label { font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-body .value { font-size: 26px; font-weight: 800; color: var(--text); line-height: 1.2; }

    /* ===== CARD ===== */
    .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 16px; }
    .card-header { padding: 14px 18px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
    .card-title { font-size: 14px; font-weight: 700; color: var(--text); }
    .card-title i { font-size: 15px; }
    .card-body { padding: 16px 18px; }

    /* ===== CHART ===== */
    .chart-wrap { position: relative; height: 220px; }

    /* ===== ALERT STRIP ===== */
    .alert-strip { border-radius: 9px; padding: 12px 14px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; }
    .alert-strip.warning { background: rgba(247,103,7,0.08); border-left: 3px solid var(--warning); }
    .alert-strip.danger { background: rgba(214,57,57,0.08); border-left: 3px solid var(--danger); }
    .alert-strip .pn { font-size: 13px; font-weight: 700; }
    .alert-strip .name { font-size: 11px; color: var(--text-muted); }
    .alert-strip .stok { font-size: 13px; font-weight: 700; }

    /* ===== FORMS ===== */
    .form-group { margin-bottom: 14px; }
    .form-label { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin-bottom: 6px; display: block; }
    .form-ctrl {
      width: 100%; padding: 11px 14px;
      border: 1.5px solid var(--border); border-radius: 10px;
      background: var(--surface2); color: var(--text);
      font-size: 14px; font-family: var(--font);
      transition: border 0.2s, box-shadow 0.2s; -webkit-appearance: none;
    }
    .form-ctrl:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(95,111,255,0.15); }
    .form-ctrl[disabled] { opacity: 0.6; cursor: not-allowed; }
    .input-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .form-section-bar { height: 5px; border-radius: 3px; margin-bottom: 14px; }
    .bar-green { background: linear-gradient(90deg, var(--success), #27a03b); }
    .bar-red { background: linear-gradient(90deg, var(--danger), #b02e2e); }

    /* ===== SUGGEST ===== */
    .suggest-wrap { position: relative; }
    .suggestions {
      position: absolute; top: calc(100% + 4px); left: 0; right: 0;
      background: var(--surface); border: 1.5px solid var(--border);
      border-radius: 10px; max-height: 160px; overflow-y: auto;
      z-index: 200; box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    }
    .suggest-item { padding: 10px 14px; font-size: 13px; cursor: pointer; border-bottom: 1px solid var(--border); transition: background 0.15s; }
    .suggest-item:last-child { border-bottom: none; }
    .suggest-item:hover { background: var(--surface2); color: var(--primary); }
    .suggest-item .pn { font-weight: 700; }
    .suggest-item .nm { font-size: 11px; color: var(--text-muted); }

    /* ===== TABLE ===== */
    .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
    th { background: var(--surface2); padding: 10px 12px; text-align: left; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); border-bottom: 1px solid var(--border); white-space: nowrap; }
    td { padding: 10px 12px; border-bottom: 1px solid var(--border); color: var(--text); }
    tbody tr:hover { background: rgba(95,111,255,0.03); }
    .badge { display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 5px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
    .badge-ok { background: rgba(47,179,68,0.12); color: var(--success); }
    .badge-warning { background: rgba(247,103,7,0.12); color: var(--warning); }
    .badge-danger { background: rgba(214,57,57,0.12); color: var(--danger); }
    .badge-masuk { background: rgba(47,179,68,0.12); color: var(--success); }
    .badge-keluar { background: rgba(214,57,57,0.12); color: var(--danger); }
    .model-tag { background: rgba(95,111,255,0.1); color: var(--primary); padding: 2px 7px; border-radius: 5px; font-size: 10px; font-weight: 700; }

    /* ===== BUTTONS ===== */
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; border: none; transition: all 0.2s; font-family: var(--font); }
    .btn i { font-size: 14px; }
    .btn-primary { background: var(--primary); color: #fff; }
    .btn-primary:hover { background: var(--primary-dark); }
    .btn-success { background: var(--success); color: #fff; }
    .btn-success:hover { background: #27a03b; }
    .btn-danger-soft { background: rgba(214,57,57,0.1); color: var(--danger); }
    .btn-danger-soft:hover { background: rgba(214,57,57,0.2); }
    .btn-outline { background: var(--surface2); color: var(--text); border: 1px solid var(--border); }
    .btn-outline:hover { border-color: var(--primary); color: var(--primary); }
    .btn-full { width: 100%; justify-content: center; padding: 11px; }
    .btn:disabled { opacity: 0.55; cursor: not-allowed; }
    .btn-sm { padding: 5px 10px; font-size: 11px; border-radius: 6px; }
    .btn-wa { background: #25d366; color: #fff; }
    .btn-wa:hover { background: #1da851; transform: scale(1.05); }
    .btn-wa:active { transform: scale(0.97); }
    .btn-pdf { background: var(--danger); color: #fff; }
    .btn-pdf:hover { background: #b32d2d; transform: scale(1.05); }
    .btn-pdf:active { transform: scale(0.97); }

    /* ===== FILTER ROW ===== */
    .filter-row { display: flex; gap: 10px; margin-bottom: 14px; }
    .filter-row .form-ctrl { flex: 1; }
    .filter-row select.form-ctrl { max-width: 140px; }

    /* ===== MOBILE NAV ===== */
    .mobile-nav {
      display: none;
      position: fixed; bottom: 0; left: 0; right: 0;
      height: var(--nav-h);
      background: var(--surface);
      border-top: 1px solid var(--border);
      z-index: 100;
      padding-bottom: env(safe-area-inset-bottom);
    }
    .mobile-nav-inner { display: flex; height: 100%; }
    .nav-tab {
      flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
      gap: 3px; cursor: pointer; color: var(--text-muted); font-size: 10px; font-weight: 600;
      transition: color 0.2s; padding: 8px 4px;
    }
    .nav-tab i { font-size: 22px; line-height: 1; }
    .nav-tab.active { color: var(--primary); }
    .nav-tab.active i { transform: translateY(-1px); }

    /* ===== TOAST ===== */
    .toast-wrap {
      position: fixed; top: 16px; left: 50%; transform: translateX(-50%);
      z-index: 9999; width: calc(100% - 32px); max-width: 400px;
      pointer-events: none;
    }
    .toast {
      padding: 12px 16px; border-radius: 10px; font-size: 13px; font-weight: 600;
      display: flex; align-items: center; gap: 8px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.15);
      animation: toastIn 0.3s ease; pointer-events: all;
    }
    .toast i { font-size: 16px; }
    .toast-success { background: var(--success); color: #fff; }
    .toast-error { background: var(--danger); color: #fff; }
    @keyframes toastIn { from { opacity:0; transform:translateY(-12px); } to { opacity:1; transform:translateY(0); } }

    /* ===== ICON FIX ===== */
    .ti { font-family: "tabler-icons" !important; font-style: normal; font-weight: normal; speak: none;
      display: inline-block; text-decoration: inherit; width: 1em; text-align: center;
      font-variant: normal; text-transform: none; line-height: 1em; }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
      .sidebar { display: none !important; }
      .main { margin-left: 0; padding-bottom: var(--nav-h); }
      .mobile-nav { display: block; }
      .topbar { padding: 0 12px; }
      .topbar-title { font-size: 14px; }
      .topbar-right { gap: 6px; }
      .role-badge { display: none; }
      .page { padding: 10px; }
      .stats-grid { grid-template-columns: 1fr 1fr; gap: 8px; }
      .stat-card { padding: 10px; gap: 8px; }
      .stat-body .value { font-size: 20px; }
      .stat-icon { width: 36px; height: 36px; }
      .stat-icon i { font-size: 18px; }
      .input-row { grid-template-columns: 1fr; gap: 10px; }
      .input-forms-grid { grid-template-columns: 1fr !important; }
      .chart-wrap { height: 180px; }
      .datetime-pill .date { display: none; }
      .table-wrap { max-width: calc(100vw - 20px); }
      table { font-size: 11px; min-width: 480px; }
      th { padding: 8px 6px; font-size: 9px; }
      td { padding: 8px 6px; }
      .hide-mobile { display: none !important; }
      td.td-name { max-width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
      td.td-date { white-space: nowrap; font-size: 10px; }
      .card-header { padding: 12px 14px; }
      .card-body { padding: 12px 14px; }
      .filter-row { flex-wrap: wrap; gap: 8px; }
      .filter-row select.form-ctrl { max-width: 110px; }
    }
    @media (min-width: 769px) {
      .mobile-nav { display: none !important; }
    }

    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; }
    td.no-data { text-align: center; color: var(--text-muted); padding: 32px; }
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

  <!-- TOAST -->
  <div class="toast-wrap" v-if="toast.show">
    <div class="toast" :class="'toast-' + toast.type">
      <i :class="toast.type === 'success' ? 'ti ti-circle-check' : 'ti ti-alert-circle'"></i>
      {{ toast.msg }}
    </div>
  </div>

  <div class="app-layout">

    <!-- SIDEBAR -->
    <nav class="sidebar">
      <div class="sidebar-brand">
        <img src="bti.png" alt="Logo" onerror="this.style.display='none'">
        <small>Inventory Management</small>
      </div>
      <div class="nav-section">
        <div class="nav-section-label">Menu Utama</div>
        <button class="nav-link" :class="{active: page==='dashboard'}" @click="goPage('dashboard')">
          <i class="ti ti-layout-dashboard"></i> Dashboard
        </button>
        <button class="nav-link" :class="{active: page==='input'}" @click="goPage('input')" v-if="isAdmin">
          <i class="ti ti-circle-plus"></i> Input Transaksi
        </button>
        <button class="nav-link" :class="{active: page==='partlist'}" @click="goPage('partlist')">
          <i class="ti ti-box"></i> Daftar Part
        </button>
        <button class="nav-link" :class="{active: page==='history'}" @click="goPage('history')">
          <i class="ti ti-history"></i> History
        </button>
      </div>
      <div class="nav-section" style="margin-top:8px;">
        <div class="nav-section-label">Tools</div>
        <button class="nav-link" @click="exportCSV">
          <i class="ti ti-file-type-csv"></i> Export CSV
        </button>
        <button class="nav-link" @click="exportExcelStok">
          <i class="ti ti-file-spreadsheet"></i> Export Stok Excel
        </button>
      </div>
      <div class="sidebar-footer">
        <button class="nav-link" style="color:var(--danger);" @click="logout">
          <i class="ti ti-logout"></i> Logout
        </button>
      </div>
    </nav>

    <!-- MAIN -->
    <div class="main">

      <!-- TOPBAR -->
      <div class="topbar">
        <div>
          <div class="topbar-title">{{ pageTitle }}</div>
          <div class="greeting">{{ greeting }}, {{ currentUser?.name || '' }}! 👋</div>
        </div>
        <div class="topbar-right">
          <div class="datetime-pill">
            <div class="time">{{ timeStr }}</div>
            <div class="date">{{ dateStr }}</div>
          </div>
          <span class="role-badge">{{ currentUser?.role || 'user' }}</span>
          <button class="icon-btn" @click="toggleTheme" :title="isDark ? 'Light Mode' : 'Dark Mode'">
            <i :class="isDark ? 'ti ti-sun' : 'ti ti-moon'"></i>
          </button>
          <button class="icon-btn" @click="logout" title="Logout" style="color:var(--danger);">
            <i class="ti ti-logout"></i>
          </button>
        </div>
      </div>

      <!-- DASHBOARD -->
      <div class="page" v-if="page==='dashboard'">
        <div class="stats-grid">
          <div class="stat-card"><div class="stat-icon blue"><i class="ti ti-box"></i></div><div class="stat-body"><div class="label">Total Part</div><div class="value">{{ partsList.length }}</div></div></div>
          <div class="stat-card"><div class="stat-icon green"><i class="ti ti-circle-check"></i></div><div class="stat-body"><div class="label">Stok OK</div><div class="value">{{ statOk }}</div></div></div>
          <div class="stat-card"><div class="stat-icon orange"><i class="ti ti-alert-triangle"></i></div><div class="stat-body"><div class="label">Kritis</div><div class="value">{{ statCrit }}</div></div></div>
          <div class="stat-card"><div class="stat-icon red"><i class="ti ti-alert-circle"></i></div><div class="stat-body"><div class="label">Habis</div><div class="value">{{ statEmpty }}</div></div></div>
          <div class="stat-card"><div class="stat-icon cyan"><i class="ti ti-clock"></i></div><div class="stat-body"><div class="label">Before QC</div><div class="value">{{ statBeforeQC }}</div></div></div>
          <div class="stat-card"><div class="stat-icon purple"><i class="ti ti-check"></i></div><div class="stat-body"><div class="label">After QC</div><div class="value">{{ statAfterQC }}</div></div></div>
          <div class="stat-card"><div class="stat-icon red"><i class="ti ti-x"></i></div><div class="stat-body"><div class="label">Total Reject</div><div class="value">{{ statTotalReject }}</div></div></div>
        </div>
        <div class="card">
          <div class="card-header">
            <span class="card-title"><i class="ti ti-chart-bar" style="color:var(--primary);margin-right:6px;"></i>Grafik Stok 7 Hari Terakhir</span>
            <div style="display:flex;gap:12px;font-size:11px;font-weight:700;">
              <span style="color:var(--success);"><span style="display:inline-block;width:10px;height:10px;background:var(--success);border-radius:2px;margin-right:4px;"></span>Masuk</span>
              <span style="color:var(--danger);"><span style="display:inline-block;width:10px;height:10px;background:var(--danger);border-radius:2px;margin-right:4px;"></span>Keluar</span>
            </div>
          </div>
          <div class="card-body"><div class="chart-wrap"><canvas id="myChart"></canvas></div></div>
        </div>
        <div class="card" v-if="kritisItems.length > 0" style="border-left:3px solid var(--warning);">
          <div class="card-header"><span class="card-title" style="color:var(--warning);"><i class="ti ti-alert-triangle"></i> Stok Kritis — Perlu Restock</span></div>
          <div class="card-body" style="padding-bottom:8px;">
            <div class="alert-strip warning" v-for="p in kritisItems" :key="p.id">
              <div><div class="pn">{{ p.part_number }}</div><div class="name">{{ p.part_name }} <span class="model-tag" v-if="p.model">{{ p.model }}</span></div></div>
              <div class="stok" style="color:var(--warning);">{{ p.stock }}/{{ p.min_stock }}</div>
            </div>
          </div>
        </div>
        <div class="card" v-if="habisItems.length > 0" style="border-left:3px solid var(--danger);">
          <div class="card-header"><span class="card-title" style="color:var(--danger);"><i class="ti ti-alert-circle"></i> Stok Habis — Urgent!</span></div>
          <div class="card-body" style="padding-bottom:8px;">
            <div class="alert-strip danger" v-for="p in habisItems" :key="p.id">
              <div><div class="pn">{{ p.part_number }}</div><div class="name">{{ p.part_name }} <span class="model-tag" v-if="p.model">{{ p.model }}</span></div></div>
              <div class="stok" style="color:var(--danger);">HABIS</div>
            </div>
          </div>
        </div>
      </div>

      <!-- INPUT -->
      <div class="page" v-if="page==='input'">
        <div class="input-forms-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
          <div class="card">
            <div class="card-header"><span class="card-title" style="color:var(--success);"><i class="ti ti-arrow-down-circle"></i> Input Masuk</span></div>
            <div class="card-body">
              <div class="form-section-bar bar-green"></div>
              <div class="form-group suggest-wrap">
                <label class="form-label">Part Number</label>
                <input class="form-ctrl" type="text" v-model="masuk.pn" @input="searchSuggest('masuk')" placeholder="Ketik PN..." autocomplete="off">
                <div class="suggestions" v-if="suggests.masuk.length">
                  <div class="suggest-item" v-for="s in suggests.masuk" :key="s.part_number" @click="selectPart('masuk', s)">
                    <div class="pn">{{ s.part_number }}</div><div class="nm">{{ s.part_name }}</div>
                  </div>
                </div>
              </div>
              <div class="form-group"><label class="form-label">Model</label><input class="form-ctrl" :value="masuk.model" disabled placeholder="Auto"></div>
              <div class="form-group"><label class="form-label">Commodity</label><input class="form-ctrl" :value="masuk.commodity" disabled placeholder="Auto"></div>
              <div class="form-group"><label class="form-label">Part Name</label><input class="form-ctrl" :value="masuk.part_name" disabled placeholder="Auto"></div>
              <div class="input-row">
                <div class="form-group"><label class="form-label">Qty Masuk</label><input class="form-ctrl" type="number" v-model="masuk.qty" placeholder="0" min="1"></div>
                <div class="form-group"><label class="form-label">Tanggal</label><input class="form-ctrl" type="date" v-model="masuk.date"></div>
              </div>
              <div class="form-group">
                <label class="form-label">Status QC</label>
                <select class="form-ctrl" v-model="masuk.status_qc">
                  <option value="">-- Pilih Status --</option>
                  <option value="After Check QC">After Check QC</option>
                  <option value="Before Check QC">Before Check QC</option>
                </select>
              </div>
              <div class="form-group"><label class="form-label">Keterangan</label><input class="form-ctrl" type="text" v-model="masuk.keterangan" placeholder="Optional"></div>
              <div class="form-group"><label class="form-label">Supplier</label><input class="form-ctrl" :value="masuk.supplier" disabled placeholder="Auto"></div>
              <button class="btn btn-success btn-full" @click="submitMasuk" :disabled="loadingMasuk">
                <i class="ti ti-device-floppy"></i> {{ loadingMasuk ? 'Menyimpan...' : 'Simpan Masuk' }}
              </button>
            </div>
          </div>
          <div class="card">
            <div class="card-header"><span class="card-title" style="color:var(--danger);"><i class="ti ti-arrow-up-circle"></i> Input Keluar</span></div>
            <div class="card-body">
              <div class="form-section-bar bar-red"></div>
              <div class="form-group suggest-wrap">
                <label class="form-label">Part Number</label>
                <input class="form-ctrl" type="text" v-model="keluar.pn" @input="searchSuggest('keluar')" placeholder="Ketik PN..." autocomplete="off">
                <div class="suggestions" v-if="suggests.keluar.length">
                  <div class="suggest-item" v-for="s in suggests.keluar" :key="s.part_number" @click="selectPart('keluar', s)">
                    <div class="pn">{{ s.part_number }}</div><div class="nm">{{ s.part_name }}</div>
                  </div>
                </div>
              </div>
              <div class="form-group"><label class="form-label">Model</label><input class="form-ctrl" :value="keluar.model" disabled placeholder="Auto"></div>
              <div class="form-group"><label class="form-label">Commodity</label><input class="form-ctrl" :value="keluar.commodity" disabled placeholder="Auto"></div>
              <div class="form-group"><label class="form-label">Part Name</label><input class="form-ctrl" :value="keluar.part_name" disabled placeholder="Auto"></div>
              <div class="input-row">
                <div class="form-group"><label class="form-label">Qty Keluar</label><input class="form-ctrl" type="number" v-model="keluar.qty" placeholder="0" min="1"></div>
                <div class="form-group"><label class="form-label">Tanggal</label><input class="form-ctrl" type="date" v-model="keluar.date"></div>
              </div>
              <div class="form-group">
                <label class="form-label">Status QC</label>
                <select class="form-ctrl" v-model="keluar.status_qc">
                  <option value="">-- Pilih Status --</option>
                  <option value="After Check QC">After Check QC</option>
                  <option value="Before Check QC">Before Check QC</option>
                </select>
              </div>
              <div class="form-group"><label class="form-label">Keterangan</label><input class="form-ctrl" type="text" v-model="keluar.keterangan" placeholder="Optional"></div>
              <div class="form-group"><label class="form-label">Tujuan</label><input class="form-ctrl" type="text" v-model="keluar.tujuan" placeholder="Optional"></div>
              <button class="btn btn-full" @click="submitKeluar" :disabled="loadingKeluar" style="background:var(--danger);color:#fff;">
                <i class="ti ti-device-floppy"></i> {{ loadingKeluar ? 'Menyimpan...' : 'Simpan Keluar' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- PART LIST -->
      <div class="page" v-if="page==='partlist'">
        <div class="card" v-if="isAdmin" style="border-left:3px solid var(--primary);">
          <div class="card-header"><span class="card-title"><i class="ti ti-circle-plus" style="color:var(--primary);"></i> Tambah Part Baru</span></div>
          <div class="card-body">
            <div class="input-row">
              <div class="form-group"><label class="form-label">Model</label><input class="form-ctrl" type="text" v-model="newPart.model" placeholder="Contoh: 737D"></div>
              <div class="form-group"><label class="form-label">Commodity *</label><input class="form-ctrl" type="text" v-model="newPart.commodity" placeholder="Contoh: INJECTION PART"></div>
            </div>
            <div class="form-group"><label class="form-label">Part Name *</label><input class="form-ctrl" type="text" v-model="newPart.part_name" placeholder="Nama part lengkap"></div>
            <div class="form-group"><label class="form-label">Part Number *</label><input class="form-ctrl" type="text" v-model="newPart.part_number" placeholder="Contoh: 71173-X7V30"></div>
            <div class="form-group"><label class="form-label">Supplier</label><input class="form-ctrl" type="text" v-model="newPart.supplier" placeholder="Contoh: PT MAJU MUNDUR"></div>
            <div class="input-row">
              <div class="form-group"><label class="form-label">Stok Awal</label><input class="form-ctrl" type="number" v-model="newPart.stock" min="0"></div>
              <div class="form-group"><label class="form-label">Minimal Stok</label><input class="form-ctrl" type="number" v-model="newPart.min_stock" min="1"></div>
            </div>
            <button class="btn btn-primary" @click="submitTambahPart" :disabled="loadingNewPart">
              <i class="ti ti-device-floppy"></i> {{ loadingNewPart ? 'Menyimpan...' : 'Simpan Part' }}
            </button>
          </div>
        </div>
        <div class="card">
          <div class="card-header">
            <span class="card-title"><i class="ti ti-box"></i> Daftar Part</span>
            <div style="display:flex;gap:8px;">
              <input type="file" ref="importFileInput" accept=".xlsx,.xls,.csv" style="display:none" @change="handleImportFile">
              <button class="btn btn-outline btn-sm" @click="importFileInput.click()" :disabled="loadingImport">
                <i class="ti ti-upload"></i> {{ loadingImport ? 'Mengimport...' : 'Import Excel' }}
              </button>
              <button class="btn btn-success btn-sm" @click="exportExcelStok"><i class="ti ti-file-spreadsheet"></i> Export Excel</button>
            </div>
          </div>
          <div class="card-body" style="padding-bottom:8px;">
            <div class="filter-row">
              <input class="form-ctrl" type="text" v-model="partSearch" placeholder="Cari PN atau nama...">
              <select class="form-ctrl" v-model="partModelFilter">
                <option value="">Semua Model</option>
                <option v-for="m in partModels" :key="m" :value="m">{{ m }}</option>
              </select>
            </div>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th class="hide-mobile">Commodity</th><th>Part Name</th><th>PN</th>
                  <th class="hide-mobile">Model</th><th>Stok</th><th class="hide-mobile">Min</th>
                  <th class="hide-mobile">Reject</th>
                  <th>Status</th><th v-if="isAdmin" class="hide-mobile">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="filteredParts.length === 0"><td :colspan="isAdmin ? 9 : 8" class="no-data">Tidak ada data</td></tr>
                <tr v-for="p in filteredParts" :key="p.id">
                  <td class="hide-mobile td-name">{{ p.commodity }}</td>
                  <td class="td-name">{{ p.part_name }}</td>
                  <td style="font-weight:700;color:var(--primary);white-space:nowrap;">{{ p.part_number }}</td>
                  <td class="hide-mobile"><span class="model-tag" v-if="p.model">{{ p.model }}</span><span v-else>-</span></td>
                  <td style="font-weight:800;text-align:center;">{{ p.stock }}</td>
                  <td class="hide-mobile">{{ p.min_stock }}</td>
                  <td class="hide-mobile" style="text-align:center;color:var(--danger);font-weight:700;">{{ p.total_reject || 0 }}</td>
                  <td><span class="badge" :class="p.stock === 0 ? 'badge-danger' : p.stock <= p.min_stock ? 'badge-warning' : 'badge-ok'">{{ p.stock === 0 ? 'HABIS' : p.stock <= p.min_stock ? 'KRITIS' : 'OK' }}</span></td>
                  <td v-if="isAdmin" class="hide-mobile"><button class="btn btn-danger-soft btn-sm" @click="deletePart(p.id)"><i class="ti ti-trash"></i></button></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- HISTORY -->
      <div class="page" v-if="page==='history'">
        <div class="card">
          <div class="card-header"><span class="card-title"><i class="ti ti-history"></i> History Transaksi</span></div>
          <div class="card-body" style="padding-bottom:8px;">
            <div class="filter-row">
              <select class="form-ctrl" v-model="historyFilter.type" @change="loadHistory" style="max-width:130px;">
                <option value="">Semua Tipe</option><option value="masuk">Masuk</option><option value="keluar">Keluar</option>
              </select>
              <input class="form-ctrl" type="text" v-model="historyFilter.search" @input="loadHistory" placeholder="Cari PN / nama...">
              <button class="btn btn-primary" style="white-space:nowrap;" @click="openSJModal" :disabled="selectedIds.length===0">
                <i class="ti ti-file-type-pdf"></i> Surat Jalan ({{ selectedIds.length }})
              </button>
            </div>

            <!-- MODAL SURAT JALAN -->
            <div v-if="sjModal.show" style="position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999;display:flex;align-items:center;justify-content:center;">
              <div class="card" style="width:360px;margin:0;">
                <div class="card-header"><span class="card-title">Detail Surat Jalan</span></div>
                <div class="card-body">
                  <label style="font-size:12px;font-weight:600;">Delivery To</label>
                  <input class="form-ctrl" style="margin:6px 0 12px;" v-model="sjModal.delivery_to" placeholder="Nama tujuan / customer">
                  <label style="font-size:12px;font-weight:600;">Tanggal</label>
                  <input class="form-ctrl" style="margin:6px 0 16px;" type="date" v-model="sjModal.date">
                  <div style="display:flex;gap:8px;">
                    <button class="btn" style="flex:1;background:var(--surface2);color:var(--text);" @click="sjModal.show=false">Batal</button>
                    <button class="btn btn-primary" style="flex:1;" @click="submitSJModal" :disabled="sjModal.loading">
                      {{ sjModal.loading ? 'Membuat...' : 'Download' }}
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <!-- MODAL APPROVE QC -->
<div v-if="qcModal.show" style="position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999;display:flex;align-items:center;justify-content:center;">
  <div class="card" style="width:360px;margin:0;">
    <div class="card-header"><span class="card-title">Approve QC</span></div>
    <div class="card-body">
      <p style="font-size:13px;color:var(--text-muted);margin-bottom:10px;">
        PN: <b>{{ qcModal.part_number }}</b> — Qty Masuk: <b>{{ qcModal.maxQty }}</b>
      </p>
      <label style="font-size:12px;font-weight:600;">Qty OK (lolos QC)</label>
      <input class="form-ctrl" style="margin:6px 0 12px;" type="number" v-model="qcModal.qty_ok" :max="qcModal.maxQty" min="0">
      <label style="font-size:12px;font-weight:600;">Keterangan Reject (opsional)</label>
      <input class="form-ctrl" style="margin:6px 0 16px;" type="text" v-model="qcModal.keterangan_reject" placeholder="Alasan reject, kalau ada">
      <div style="display:flex;gap:8px;">
        <button class="btn" style="flex:1;background:var(--surface2);color:var(--text);" @click="qcModal.show=false">Batal</button>
        <button class="btn btn-primary" style="flex:1;" @click="submitQcModal" :disabled="qcModal.loading">
          {{ qcModal.loading ? 'Menyimpan...' : 'Approve' }}
        </button>
      </div>
    </div>
  </div>
</div>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th><input type="checkbox" @change="toggleSelectAll($event)" :checked="allSelected"></th>
                  <th>Tanggal</th><th>Tipe</th><th>PN</th><th>Part Name</th><th>Qty</th>
                  <th class="hide-mobile">QC</th><th class="hide-mobile">Supplier/Customer</th><th class="hide-mobile">Oleh</th>
                  <th v-if="isAdmin" class="hide-mobile">Aksi</th><th>QC</th><th>Share</th><th>PDF</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="historyList.length === 0"><td :colspan="isAdmin ? 12 : 11" class="no-data">Tidak ada data</td></tr>
                <tr v-for="h in historyList" :key="h.id">
                  <td><input type="checkbox" :value="h.id" v-model="selectedIds"></td>
                  <td class="td-date">{{ h.date }}<br><span style="color:var(--text-muted);font-size:10px;">{{ h.time || '' }}</span></td>
                  <td><span class="badge" :class="h.type === 'masuk' ? 'badge-masuk' : 'badge-keluar'">{{ h.type }}</span></td>
                  <td style="font-weight:700;color:var(--primary);white-space:nowrap;">{{ h.part_number }}</td>
                  <td class="td-name">{{ h.part_name }}</td>
                  <td style="font-weight:800;text-align:center;">{{ h.qty }}</td>
                  <td class="hide-mobile" style="font-size:11px;">{{ h.status_qc || '-' }}</td>
                  <td class="hide-mobile" style="font-size:11px;">{{ h.type === 'masuk' ? (h.supplier || '-') : (h.tujuan || '-') }}</td>
                  <td class="hide-mobile" style="font-size:11px;color:var(--primary);font-weight:600;">{{ h.input_by || '-' }}</td>
                  <td v-if="isAdmin" class="hide-mobile"><button class="btn btn-danger-soft btn-sm" @click="deleteHistory(h.id)"><i class="ti ti-trash"></i></button></td>
                  <td>
                      <button v-if="h.type === 'masuk' && h.status_qc === 'Before Check QC'" class="btn btn-outline btn-sm" @click="openQcModal(h)" title="Approve QC">
                        <i class="ti ti-clipboard-check"></i>
                      </button>
                      <span v-else style="color:var(--text-muted);">—</span>
                  </td>
                  <td><button class="btn btn-wa btn-sm" @click="shareWA(h)" title="Share ke WhatsApp"><i class="ti ti-brand-whatsapp"></i></button></td>
                  <td><button class="btn btn-pdf btn-sm" @click="quickDownloadSJ(h)" title="Download Surat Jalan"><i class="ti ti-file-type-pdf"></i></button></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div><!-- end .main -->

    <!-- MOBILE NAV -->
    <nav class="mobile-nav">
      <div class="mobile-nav-inner">
        <div class="nav-tab" :class="{active: page==='dashboard'}" @click="goPage('dashboard')"><i class="ti ti-layout-dashboard"></i><span>Dashboard</span></div>
        <div class="nav-tab" :class="{active: page==='input'}" @click="goPage('input')" v-if="isAdmin"><i class="ti ti-circle-plus"></i><span>Input</span></div>
        <div class="nav-tab" :class="{active: page==='partlist'}" @click="goPage('partlist')"><i class="ti ti-box"></i><span>Part</span></div>
        <div class="nav-tab" :class="{active: page==='history'}" @click="goPage('history')"><i class="ti ti-history"></i><span>History</span></div>
      </div>
    </nav>

  </div>
</div>

<script>
const { createApp, ref, computed, onMounted, nextTick } = Vue;

createApp({
  setup() {
    // Cek auth — kalau tidak ada token, redirect ke login
    const token = ref(localStorage.getItem('token') || null);
    const currentUser = ref(JSON.parse(localStorage.getItem('currentUser') || 'null'));

    if (!token.value || !currentUser.value) {
      window.location.href = 'login.html';
      return {};
    }

    const isDark = ref(localStorage.getItem('theme') === 'dark');
    const page = ref('dashboard');
    const partsList = ref([]);
    const historyList = ref([]);
    const selectedIds = ref([]);
    const sjModal = ref({ show: false, delivery_to: '', date: '', loading: false });
    const qcModal = ref({ show: false, id: null, part_number: '', maxQty: 0, qty_ok: 0, keterangan_reject: '', loading: false });
    const allSelected = computed(() => historyList.value.length > 0 && selectedIds.value.length === historyList.value.length);
    const chartInstance = ref(null);
    const toast = ref({ show: false, msg: '', type: 'success' });
    let toastTimer = null;

    function showToast(msg, type = 'success') {
      if (toastTimer) clearTimeout(toastTimer);
      toast.value = { show: true, msg, type };
      toastTimer = setTimeout(() => toast.value.show = false, 3000);
    }

    const today = () => new Date().toISOString().split('T')[0];
    const masuk = ref({ pn: '', model: '', commodity: '', part_name: '', qty: '', date: today(), status_qc: '', keterangan: '', supplier: '' });
    const keluar = ref({ pn: '', model: '', commodity: '', part_name: '', qty: '', date: today(), status_qc: '', keterangan: '', tujuan: '' });
    const suggests = ref({ masuk: [], keluar: [] });
    const loadingMasuk = ref(false);
    const loadingKeluar = ref(false);
    const newPart = ref({ model: '', commodity: '', part_name: '', part_number: '', supplier: '', stock: 0, min_stock: 1 });
    const importFileInput = ref(null);
    const loadingImport = ref(false);
    const loadingNewPart = ref(false);
    const partSearch = ref('');
    const partModelFilter = ref('');
    const historyFilter = ref({ type: '', search: '' });
    const timeStr = ref('');
    const dateStr = ref('');
    const statBeforeQC = ref(0);
    const statAfterQC = ref(0);

    function updateTime() {
      const now = new Date();
      timeStr.value = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
      dateStr.value = now.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short' });
    }
    setInterval(updateTime, 1000);
    updateTime();

    const isAdmin = computed(() => currentUser.value?.role === 'admin');
    const statOk = computed(() => partsList.value.filter(p => p.stock > p.min_stock).length);
    const statCrit = computed(() => partsList.value.filter(p => p.stock <= p.min_stock && p.stock > 0).length);
    const statEmpty = computed(() => partsList.value.filter(p => p.stock === 0).length);
    const statTotalReject = computed(() => partsList.value.reduce((sum, p) => sum + (p.total_reject || 0), 0));
    const kritisItems = computed(() => partsList.value.filter(p => p.stock <= p.min_stock && p.stock > 0));
    const habisItems = computed(() => partsList.value.filter(p => p.stock === 0));
    const partModels = computed(() => [...new Set(partsList.value.map(p => p.model).filter(Boolean))].sort());
    const filteredParts = computed(() => {
      const s = partSearch.value.toLowerCase();
      const m = partModelFilter.value.toLowerCase();
      return partsList.value.filter(p =>
        (!s || p.part_number.toLowerCase().includes(s) || p.part_name.toLowerCase().includes(s)) &&
        (!m || (p.model || '').toLowerCase() === m)
      );
    });
    const pageTitle = computed(() => ({ dashboard: 'Dashboard', input: 'Input Transaksi', partlist: 'Daftar Part', history: 'History Transaksi' })[page.value] || '');
    const greeting = computed(() => {
      const h = new Date().getHours();
      return h < 11 ? 'Selamat Pagi' : h < 15 ? 'Selamat Siang' : h < 19 ? 'Selamat Sore' : 'Selamat Malam';
    });

    function applyTheme() { document.documentElement.setAttribute('data-theme', isDark.value ? 'dark' : ''); }
    function toggleTheme() { isDark.value = !isDark.value; localStorage.setItem('theme', isDark.value ? 'dark' : ''); applyTheme(); }
    applyTheme();

    async function apiFetch(endpoint, options = {}) {
      const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
      if (token.value) headers['Authorization'] = 'Bearer ' + token.value;
      const res = await fetch(API_URL + endpoint, { ...options, headers });
      if (res.status === 401) { logout(); return; }
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Error ' + res.status);
      return data;
    }

    function logout() {
      if (token.value) apiFetch('/auth/logout', { method: 'POST' }).catch(() => {});
      localStorage.removeItem('token');
      localStorage.removeItem('currentUser');
      window.location.href = 'login.php';
    }

    async function loadAll() { await loadParts(); await loadDashboard(); }

    async function loadParts() {
      try { partsList.value = await apiFetch('/parts'); }
      catch (err) { showToast('Gagal load parts: ' + err.message, 'error'); }
    }

    async function loadDashboard() {
      try {
        const hist = await apiFetch('/transactions');
        let bqc = 0, aqcMasuk = 0, keluarTotal = 0;
        hist.forEach(h => {
          if (h.type === 'masuk' && h.status_qc === 'Before Check QC') bqc += h.qty;
          if (h.type === 'masuk' && h.status_qc === 'After Check QC') aqcMasuk += (h.qty_ok ?? h.qty);
          if (h.type === 'keluar') keluarTotal += h.qty;
        });
        statBeforeQC.value = bqc;
        statAfterQC.value = Math.max(aqcMasuk - keluarTotal, 0);
      } catch {}
      await loadChart();
    }

    async function loadChart() {
      try {
        const chartData = await apiFetch('/transactions/chart');
        await nextTick();
        const canvas = document.getElementById('myChart');
        if (!canvas) return;
        if (chartInstance.value) chartInstance.value.destroy();
        const labels = chartData.map(d => new Date(d.date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }));
        chartInstance.value = new Chart(canvas, {
          type: 'bar',
          data: {
            labels,
            datasets: [
              { label: 'Masuk', data: chartData.map(d => d.masuk), backgroundColor: 'rgba(47,179,68,0.8)', borderRadius: 6, borderSkipped: false },
              { label: 'Keluar', data: chartData.map(d => d.keluar), backgroundColor: 'rgba(214,57,57,0.8)', borderRadius: 6, borderSkipped: false }
            ]
          },
          options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, ticks: { color: '#8892a4', font: { size: 11 } } }, y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { color: '#8892a4', font: { size: 11 } } } } }
        });
      } catch {}
    }

    async function loadHistory() {
      try {
        const params = new URLSearchParams();
        if (historyFilter.value.type) params.append('type', historyFilter.value.type);
        if (historyFilter.value.search) params.append('search', historyFilter.value.search);
        historyList.value = await apiFetch('/transactions?' + params.toString());
      } catch (err) { showToast('Gagal load history: ' + err.message, 'error'); }
    }

    function goPage(p) {
      if (p === 'input' && !isAdmin.value) { showToast('Hanya admin yang bisa input transaksi!', 'error'); return; }
      page.value = p;
      if (p === 'history') loadHistory();
      if (p === 'dashboard') nextTick(() => loadChart());
    }

    function searchSuggest(type) {
      const q = (type === 'masuk' ? masuk.value.pn : keluar.value.pn).toLowerCase();
      if (!q) { suggests.value[type] = []; return; }
      suggests.value[type] = partsList.value.filter(p => p.part_number.toLowerCase().includes(q) || p.part_name.toLowerCase().includes(q)).slice(0, 10);
    }

    function selectPart(type, part) {
      if (type === 'masuk') { masuk.value.pn = part.part_number; masuk.value.model = part.model || ''; masuk.value.commodity = part.commodity; masuk.value.part_name = part.part_name; masuk.value.supplier = part.supplier || ''; suggests.value.masuk = []; }
      else { keluar.value.pn = part.part_number; keluar.value.model = part.model || ''; keluar.value.commodity = part.commodity; keluar.value.part_name = part.part_name; suggests.value.keluar = []; }
    }

    async function submitMasuk() {
      loadingMasuk.value = true;
      try {
        await apiFetch('/transactions', { method: 'POST', body: JSON.stringify({ part_number: masuk.value.pn, type: 'masuk', qty: parseInt(masuk.value.qty), date: masuk.value.date, status_qc: masuk.value.status_qc, keterangan: masuk.value.keterangan || null, supplier: masuk.value.supplier || null }) });
        showToast('Transaksi masuk berhasil disimpan!');
        masuk.value = { pn: '', model: '', commodity: '', part_name: '', qty: '', date: today(), status_qc: '', keterangan: '', supplier: '' };
        await loadParts(); loadDashboard();
      } catch (err) { showToast(err.message, 'error'); }
      finally { loadingMasuk.value = false; }
    }

    async function submitKeluar() {
      loadingKeluar.value = true;
      try {
        await apiFetch('/transactions', { method: 'POST', body: JSON.stringify({ part_number: keluar.value.pn, type: 'keluar', qty: parseInt(keluar.value.qty), date: keluar.value.date, status_qc: keluar.value.status_qc, keterangan: keluar.value.keterangan || null, tujuan: keluar.value.tujuan || null }) });
        showToast('Transaksi keluar berhasil disimpan!');
        keluar.value = { pn: '', model: '', commodity: '', part_name: '', qty: '', date: today(), status_qc: '', keterangan: '', tujuan: '' };
        await loadParts(); loadDashboard();
      } catch (err) { showToast(err.message, 'error'); }
      finally { loadingKeluar.value = false; }
    }

    async function submitTambahPart() {
      loadingNewPart.value = true;
      try {
        await apiFetch('/parts', { method: 'POST', body: JSON.stringify({ model: newPart.value.model || null, commodity: newPart.value.commodity, part_name: newPart.value.part_name, part_number: newPart.value.part_number, supplier: newPart.value.supplier || null, stock: parseInt(newPart.value.stock) || 0, min_stock: parseInt(newPart.value.min_stock) || 1 }) });
        showToast('Part berhasil ditambahkan!');
        newPart.value = { model: '', commodity: '', part_name: '', part_number: '', supplier: '', stock: 0, min_stock: 1 };
        await loadParts(); loadDashboard();
      } catch (err) { showToast(err.message, 'error'); }
      finally { loadingNewPart.value = false; }
    }

    async function handleImportFile(e) {
      const file = e.target.files[0];
      if (!file) return;

      loadingImport.value = true;
      const formData = new FormData();
      formData.append('file', file);

      try {
        const headers = {};
        if (token.value) headers['Authorization'] = 'Bearer ' + token.value;
        const res = await fetch(API_URL + '/parts/import', { method: 'POST', headers, body: formData });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Import gagal');
        showToast(data.message || 'Import berhasil!');
        await loadParts(); loadDashboard();
      } catch (err) {
        showToast(err.message, 'error');
      } finally {
        loadingImport.value = false;
        e.target.value = '';
      }
    }

    async function deletePart(id) {
      if (!confirm('Hapus part ini?')) return;
      try { await apiFetch('/parts/' + id, { method: 'DELETE' }); showToast('Part dihapus'); await loadParts(); loadDashboard(); }
      catch (err) { showToast(err.message, 'error'); }
    }

    async function deleteHistory(id) {
      if (!confirm('Hapus transaksi ini?')) return;
      try { await apiFetch('/transactions/' + id, { method: 'DELETE' }); showToast('Transaksi dihapus'); await loadParts(); loadHistory(); loadDashboard(); }
      catch (err) { showToast(err.message, 'error'); }
    }

    async function exportCSV() {
      try {
        const hist = await apiFetch('/transactions');
        let csv = 'Tanggal,Tipe,PN,Name,Qty,Qty OK,Reject,Ket Reject,Status QC,Ket\n';
        hist.forEach(h => { const ket = h.type === 'masuk' ? (h.supplier || '-') : (h.tujuan || '-'); const qtyOk = h.qty_ok ?? h.qty; const reject = h.status_qc === 'After Check QC' ? (h.qty - qtyOk) : 0; csv += `${h.date},${h.type},${h.part_number},${h.part_name},${h.qty},${qtyOk},${reject},"${h.keterangan_reject || '-'}","${h.status_qc}","${ket}"\n`; });
        const b = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const l = document.createElement('a'); l.href = URL.createObjectURL(b); l.download = 'stok-' + new Date().toISOString().split('T')[0] + '.csv'; l.click();
      } catch (err) { showToast('Gagal export: ' + err.message, 'error'); }
    }

    function exportExcelStok() {
      try {
        if (!partsList.value.length) { showToast('Data part kosong!', 'error'); return; }
        const data = partsList.value.map(p => ({ 'Model': p.model || '-', 'Commodity': p.commodity, 'Part Name': p.part_name, 'Part Number': p.part_number, 'Supplier': p.supplier || '-', 'Sisa Stok': p.stock, 'Minimal Stok': p.min_stock, 'Reject': p.total_reject || 0, 'Status': p.stock === 0 ? 'HABIS' : p.stock <= p.min_stock ? 'KRITIS' : 'OK' }));
        const ws = XLSX.utils.json_to_sheet(data);
        ws['!cols'] = [{ wch:10 },{ wch:20 },{ wch:45 },{ wch:20 },{ wch:22 },{ wch:12 },{ wch:14 },{ wch:10 },{ wch:10 }];
        const wb = XLSX.utils.book_new(); XLSX.utils.book_append_sheet(wb, ws, 'Stok Part');
        XLSX.writeFile(wb, `rekap-stok-${new Date().toISOString().split('T')[0]}.xlsx`);
        showToast('Excel berhasil didownload!');
      } catch (err) { showToast('Gagal export: ' + err.message, 'error'); }
    }

    function toggleSelectAll(e) {
      selectedIds.value = e.target.checked ? historyList.value.map(h => h.id) : [];
    }

    function openSJModal() {
      sjModal.value = { show: true, delivery_to: '', date: today(), loading: false };
    }

    function openQcModal(h) {
  qcModal.value = { show: true, id: h.id, part_number: h.part_number, maxQty: h.qty, qty_ok: h.qty, keterangan_reject: '', loading: false };
}

async function submitQcModal() {
  qcModal.value.loading = true;
  try {
    await apiFetch('/transactions/' + qcModal.value.id + '/approve-qc', {
      method: 'POST',
      body: JSON.stringify({
        qty_ok: parseInt(qcModal.value.qty_ok) || 0,
        keterangan_reject: qcModal.value.keterangan_reject || null,
      }),
    });
    showToast('QC berhasil di-approve!');
    qcModal.value.show = false;
    await loadHistory();
    await loadParts();
    loadDashboard();
  } catch (err) {
    showToast(err.message, 'error');
  } finally {
    qcModal.value.loading = false;
  }
}

    async function downloadSJ(ids, delivery_to, date) {
      try {
        const res = await fetch(API_URL + '/surat-jalan', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': 'Bearer ' + token.value },
          body: JSON.stringify({ ids, delivery_to, date })
        });
        if (!res.ok) { const e = await res.json().catch(() => ({})); throw new Error(e.message || 'Gagal generate PDF'); }
        const blob = await res.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = 'surat-jalan-' + Date.now() + '.pdf'; a.click();
        URL.revokeObjectURL(url);
      } catch (err) { showToast(err.message, 'error'); }
    }

    async function submitSJModal() {
      sjModal.value.loading = true;
      await downloadSJ(selectedIds.value, sjModal.value.delivery_to, sjModal.value.date);
      sjModal.value.loading = false;
      sjModal.value.show = false;
      selectedIds.value = [];
    }

    async function quickDownloadSJ(h) {
      await downloadSJ([h.id], '', h.date);
    }

    function shareWA(h) {
      const tipe = h.type === 'masuk' ? '📥 *BARANG MASUK*' : '📤 *BARANG KELUAR/DELIVERY*';
      const time = h.time ? ' ' + h.time : '';

      const lines = [
        tipe,
        '━━━━━━━━━━━━━━━━━━',
        '🔢 PN        : ' + h.part_number,
        '📝 Nama      : ' + h.part_name,
        h.model ? '🏷️ Model     : ' + h.model : null,
        h.commodity ? '🧩 Commodity : ' + h.commodity : null,
        '📦 Qty       : ' + h.qty + ' pcs',
        '📅 Tgl       : ' + h.date + time,
        h.type === 'masuk'
          ? (h.supplier ? '🏭 Supplier  : ' + h.supplier : null)
          : (h.tujuan ? '🎯 Tujuan    : ' + h.tujuan : null),
        h.status_qc ? '✅ QC        : ' + h.status_qc : null,
        h.keterangan ? '📌 Ket       : ' + h.keterangan : null,
        h.input_by ? '👤 By        : ' + h.input_by : null,
        '━━━━━━━━━━━━━━━━━━',
        '_System Control Stock New Project_',
      ];

      const msg = lines.filter(Boolean).join('\n');
      window.open('https://wa.me/?text=' + encodeURIComponent(msg), '_blank');
    }

    onMounted(() => { masuk.value.date = today(); keluar.value.date = today(); loadAll(); });

    return {
      token, currentUser, isDark, page, partsList, historyList, toast,
      masuk, keluar, suggests, loadingMasuk, loadingKeluar,
      newPart, loadingNewPart, partSearch, partModelFilter, historyFilter,
      importFileInput, loadingImport, handleImportFile,
      timeStr, dateStr, statBeforeQC, statAfterQC,
      isAdmin, statOk, statCrit, statEmpty, statTotalReject, kritisItems, habisItems,
      partModels, filteredParts, pageTitle, greeting,
      logout, toggleTheme, goPage, searchSuggest, selectPart,
      submitMasuk, submitKeluar, submitTambahPart,
      deletePart, deleteHistory, exportCSV, exportExcelStok, loadHistory, shareWA,
      selectedIds, sjModal, allSelected, toggleSelectAll, openSJModal, submitSJModal, quickDownloadSJ,
      qcModal, openQcModal, submitQcModal,
    };
  }
}).mount('#app');
</script>
</body>
</html>
