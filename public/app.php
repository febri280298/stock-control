<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Control Stock — Inventory System</title>

  <!-- Cegah flash konten mentah {{ }} sebelum Vue selesai mounting -->
  <style>[v-cloak] { display: none !important; }</style>

  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="icon" type="image/png" href="stokin-icon.png">

  <!-- Tabler CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tabler/1.0.0-beta20/css/tabler.min.css">
  <!-- Tabler Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- App CSS (dulu inline <style>, sekarang file terpisah) -->
  <link rel="stylesheet" href="css/app.css?v=3">
  <!-- Chart.js -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
  <!-- SheetJS -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <!-- Vue 3 (ESM build, biar main.js bisa pake import/export) -->
  <script type="importmap">
  {
    "imports": {
      "vue": "https://cdn.jsdelivr.net/npm/vue@3.4.21/dist/vue.esm-browser.prod.js"
    }
  }
  </script>
</head>
<body>

<div id="app" v-cloak>

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
        <img src="stokin.png" alt="Logo" onerror="this.style.display='none'">
        <small>Inventory Management</small>
      </div>
      <div class="nav-section">
        <div class="nav-section-label">Menu Utama</div>
        <button class="nav-link" :class="{active: page==='dashboard'}" @click="goPage('dashboard')">
          <i class="ti ti-layout-dashboard"></i> Dashboard
        </button>
        <button class="nav-link" :class="{active: page==='input'}" @click="goPage('input')" v-if="canManage">
          <i class="ti ti-circle-plus"></i> Input Transaksi
        </button>
        <button class="nav-link" :class="{active: page==='po'}" @click="goPage('po')" v-if="isAdmin || isMarketing">
          <i class="ti ti-file-invoice"></i> Input PO
        </button>
        <button class="nav-link" :class="{active: page==='porekap'}" @click="goPage('porekap')">
          <i class="ti ti-list-details"></i> Rekap PO
        </button>
        <button class="nav-link" :class="{active: page==='partlist'}" @click="goPage('partlist')">
          <i class="ti ti-box"></i> Daftar Part
        </button>
        <button class="nav-link" :class="{active: page==='history'}" @click="goPage('history')">
          <i class="ti ti-history"></i> History
        </button>
        <button class="nav-link" :class="{active: page==='sjhistory'}" @click="goPage('sjhistory')" v-if="isAdmin || isPcd">
          <i class="ti ti-truck-delivery"></i> History Surat Jalan
        </button>
        <button class="nav-link" :class="{active: page==='auditlog'}" @click="goPage('auditlog')" v-if="isAdmin">
          <i class="ti ti-shield-lock"></i> Audit Trail
        </button>
      </div>
      <div class="nav-section" style="margin-top:8px;">
        <div class="nav-section-label">Tools</div>
        <button class="nav-link" @click="exportCSV">
          <i class="ti ti-file-spreadsheet"></i> Export History Excel
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
          <div class="stat-card" style="cursor:pointer;" @click="goPage('porekap')"><div class="stat-icon orange"><i class="ti ti-file-alert"></i></div><div class="stat-body"><div class="label">PO Belum Close</div><div class="value">{{ poBelumCloseCount }}</div></div></div>
          <div class="stat-card" style="cursor:pointer;" @click="goPage('porekap')"><div class="stat-icon green"><i class="ti ti-file-check"></i></div><div class="stat-body"><div class="label">PO Closed</div><div class="value">{{ poClosedCount }}</div></div></div>
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
        <div class="card" v-if="poBelumClose.length > 0" style="border-left:3px solid var(--warning);">
          <div class="card-header">
            <span class="card-title" style="color:var(--warning);"><i class="ti ti-file-alert"></i> PO Belum Close</span>
            <span style="font-size:12px;color:var(--text-muted);cursor:pointer;" @click="goPage('porekap')">Lihat semua &rarr;</span>
          </div>
          <div class="card-body" style="padding-bottom:8px;">
            <div class="alert-strip warning" v-for="po in poBelumClose" :key="po.id" style="cursor:pointer;" @click="viewPODetail(po.id); goPage('porekap');">
              <div><div class="pn">{{ po.po_number }}</div><div class="name">{{ po.item_count }} part &middot; {{ po.po_date }}</div></div>
              <div class="stok" :style="{color: poStatusBadge(po.status).color}">{{ poStatusBadge(po.status).label }}</div>
            </div>
          </div>
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
        <div class="input-tab-toggle">
          <button class="input-tab-btn" :class="{active: inputTab==='masuk'}" @click="inputTab='masuk'">
            <i class="ti ti-circle-plus"></i> Masuk
          </button>
          <button class="input-tab-btn" :class="{active: inputTab==='keluar'}" @click="inputTab='keluar'">
            <i class="ti ti-circle-minus"></i> Keluar
          </button>
        </div>
        <div class="input-forms-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
          <div class="card" :class="{'mobile-hide': inputTab!=='masuk'}">
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
          <div class="card" :class="{'mobile-hide': inputTab!=='keluar'}">
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
              <div class="form-group"><label class="form-label">Tujuan</label>
                <input class="form-ctrl" type="text" v-model="keluar.tujuan" list="tujuanOptions" placeholder="Pilih atau ketik manual" autocomplete="off">
                <datalist id="tujuanOptions">
                  <option value="TBINA BP"></option>
                  <option value="TBINA KP"></option>
                  <option value="PT ITSP"></option>
                  <option value="PT AHTI"></option>
                  <option value="PT MMKI"></option>
                  <option value="PT ADM"></option>
                  <option value="ENG BTI"></option>
                  <option value="QE BTI"></option>
                </datalist>
              </div>
              <div class="form-group">
                <label class="form-label">Kategori Keluar</label>
                <select class="form-ctrl" v-model="keluar.kategori_keluar" @change="keluar.po_id=''; keluar.po_item_id=''; keluar.keterangan_non_po=''; keluarPoItemQuery=''; poItemSuggests=[];">
                  <option value="po">Keluar untuk PO</option>
                  <option value="non_po">Keluar Non-PO / Internal Use</option>
                </select>
              </div>
              <template v-if="keluar.kategori_keluar === 'po'">
                <div class="form-group">
                  <label class="form-label">No. PO</label>
                  <select class="form-ctrl" v-model="keluar.po_id" @change="keluar.po_item_id=''; keluarPoItemQuery=''; searchPOItemSuggest();">
                    <option value="">-- Pilih No. PO --</option>
                    <option v-for="po in openPOList" :key="po.id" :value="po.id">{{ po.po_number }}</option>
                  </select>
                </div>
                <div class="form-group suggest-wrap" v-if="keluar.po_id" style="position:relative;">
                  <label class="form-label">Item Part (sisa)</label>
                  <input class="form-ctrl" type="text" v-model="keluarPoItemQuery" @input="searchPOItemSuggest" @focus="searchPOItemSuggest" placeholder="Ketik part number..." autocomplete="off">
                  <div v-if="poItemSuggests.length" style="position:absolute;top:100%;left:0;right:0;background:var(--surface);border:1px solid var(--border);border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);z-index:50;max-height:220px;overflow-y:auto;">
                    <div v-for="it in poItemSuggests" :key="it.id" @click="selectPOItem(it)" style="padding:8px 12px;cursor:pointer;border-bottom:1px solid var(--border);">
                      <b>{{ it.part_number }}</b> - sisa {{ it.qty_order - it.qty_delivered }}
                      <div style="font-size:12px;color:var(--text-muted);">{{ it.part_name }}</div>
                    </div>
                  </div>
                  <div v-if="keluar.po_item_id" style="margin-top:6px;font-size:12px;color:var(--success);">
                    <i class="ti ti-check"></i> Terpilih: {{ selectedPOItem?.part_number }} - sisa {{ selectedPOItem ? (selectedPOItem.qty_order - selectedPOItem.qty_delivered) : 0 }}
                  </div>
                </div>
              </template>
              <div class="form-group" v-else>
                <label class="form-label">Keterangan Non-PO *</label>
                <input class="form-ctrl" type="text" v-model="keluar.keterangan_non_po" placeholder="Contoh: Sample customer, testing internal">
              </div>
              <button class="btn btn-full" @click="submitKeluar" :disabled="loadingKeluar" style="background:var(--danger);color:#fff;">
                <i class="ti ti-device-floppy"></i> {{ loadingKeluar ? 'Menyimpan...' : 'Simpan Keluar' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- INPUT PO -->
      <div class="page" v-if="page==='po'">
        <div class="card">
          <div class="card-header">
            <span class="card-title"><i class="ti ti-file-invoice" style="color:var(--primary);"></i> Input PO Baru</span>
            <div style="display:flex;gap:8px;">
              <input type="file" ref="importPOFileInput" accept=".xlsx,.xls" style="display:none" @change="handleImportPOItems">
              <button class="btn btn-outline btn-sm" @click="importPOFileInput.click()" :disabled="loadingImportPO">
                <i class="ti ti-upload"></i> {{ loadingImportPO ? 'Membaca...' : 'Import Item dari Excel' }}
              </button>
            </div>
          </div>
          <div class="card-body">
            <div class="input-row">
              <div class="form-group"><label class="form-label">No. PO *</label><input class="form-ctrl" type="text" v-model="newPO.po_number" placeholder="Contoh: 16.08.2026-01"></div>
              <div class="form-group"><label class="form-label">Tanggal PO *</label><input class="form-ctrl" type="date" v-model="newPO.po_date"></div>
            </div>
            <div class="input-row-3">
              <div class="form-group"><label class="form-label">Target Delivery</label><input class="form-ctrl" type="date" v-model="newPO.target_delivery"></div>
              <div class="form-group"><label class="form-label">Customer ID</label><input class="form-ctrl" type="text" v-model="newPO.customer_id" placeholder="Contoh: XXX"></div>
              <div class="form-group"><label class="form-label">Project</label>
                <input class="form-ctrl" type="text" v-model="newPO.project" list="poProjectOptions" placeholder="Pilih atau ketik manual" autocomplete="off">
                <datalist id="poProjectOptions">
                  <option value="737D"></option>
                  <option value="5P45"></option>
                  <option value="5P45V"></option>
                  <option value="D40L"></option>
                </datalist>
              </div>
            </div>
            <div style="border-top:1px solid var(--border, #e5e7eb);margin:16px 0;padding-top:12px;">
              <div style="font-weight:700;margin-bottom:8px;">Item Part</div>
              <div v-for="(it, idx) in newPO.items" :key="idx" class="po-item-row">
                <div class="form-group pn" style="position:relative;"><label class="form-label">Part Number</label><input class="form-ctrl" type="text" v-model="it.part_number" @input="searchPOSuggest(idx)" placeholder="Contoh: 67796-X7A12" autocomplete="off">
                  <div v-if="poSuggests[idx] && poSuggests[idx].length" style="position:absolute;top:100%;left:0;right:0;background:var(--surface);border:1px solid var(--border);border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);z-index:50;max-height:200px;overflow-y:auto;">
                    <div v-for="s in poSuggests[idx]" :key="s.part_number" @click="selectPOPart(idx, s)" style="padding:8px 12px;cursor:pointer;border-bottom:1px solid var(--border);">
                      <b>{{ s.part_number }}</b><div style="font-size:12px;color:var(--text-soft);">{{ s.part_name }}</div>
                    </div>
                  </div>
                </div>
                <div class="form-group name"><label class="form-label">Part Name</label><input class="form-ctrl" type="text" :value="it.part_name" disabled placeholder="Auto"></div>
                <div class="form-group qty"><label class="form-label">Qty Order</label><input class="form-ctrl" type="number" v-model="it.qty_order" min="1"></div>
                <button class="btn btn-danger-soft btn-sm btn-remove-item" @click="removePOItemRow(idx)"><i class="ti ti-x"></i></button>
              </div>
              <button class="btn" @click="addPOItemRow"><i class="ti ti-plus"></i> Tambah Item</button>
            </div>
            <button class="btn btn-full" @click="submitPO" :disabled="loadingPO" style="margin-top:16px;background:var(--success);color:#fff;">
              <i class="ti ti-device-floppy"></i> {{ loadingPO ? 'Menyimpan...' : 'Simpan PO' }}
            </button>
          </div>
        </div>
      </div>

      <!-- REKAP PO -->
      <div class="page" v-if="page==='porekap'">
        <div class="card">
          <div class="card-header">
            <span class="card-title"><i class="ti ti-list-details" style="color:var(--primary);"></i> Rekap PO</span>
            <button class="btn btn-outline btn-sm" @click="exportPORekap" :disabled="loadingExportPO">
              <i class="ti ti-file-spreadsheet"></i> {{ loadingExportPO ? 'Membuat...' : 'Export Excel (PO Selesai)' }}
            </button>
          </div>
          <div class="card-body" style="padding-bottom:0;">
            <input class="form-ctrl" type="text" v-model="poSearch" @input="onPOSearchInput" placeholder="Cari No. PO..." style="margin-bottom:14px;max-width:280px;">
          </div>
          <div class="card-body" style="overflow-x:auto;padding-top:0;">
            <table class="tbl">
              <thead><tr><th>No. PO</th><th>Tanggal</th><th class="hide-mobile">Customer ID</th><th class="hide-mobile">Project</th><th class="hide-mobile">Item</th><th>Status Delivery</th><th class="hide-mobile">Approval</th><th></th></tr></thead>
              <tbody>
                <tr v-if="filteredPOList.length === 0"><td colspan="8" class="no-data">Tidak ada data</td></tr>
                <tr v-for="po in visiblePOList" :key="po.id" @click="viewPODetail(po.id)" style="cursor:pointer;">
                  <td style="font-weight:700;">{{ po.po_number }}</td>
                  <td>{{ po.po_date }}</td>
                  <td class="hide-mobile">{{ po.customer_id || '-' }}</td>
                  <td class="hide-mobile">{{ po.project || '-' }}</td>
                  <td class="hide-mobile">{{ po.item_count }} part</td>
                  <td><span :style="{background: poStatusBadge(po.status).bg, color: poStatusBadge(po.status).color, padding:'3px 10px', borderRadius:'6px', fontSize:'12px'}">{{ poStatusBadge(po.status).label }}</span></td>
                  <td class="hide-mobile"><span :style="{background: poApprovalBadge(po.approval_stage).bg, color: poApprovalBadge(po.approval_stage).color, padding:'3px 10px', borderRadius:'6px', fontSize:'12px'}">{{ poApprovalBadge(po.approval_stage).label }}</span></td>
                  <td><i class="ti ti-chevron-right"></i></td>
                </tr>
              </tbody>
            </table>
            <div v-if="poVisibleCount < filteredPOList.length" style="text-align:center;padding-top:14px;">
              <div style="color:var(--text-muted);font-size:13px;margin-bottom:8px;">Menampilkan {{ visiblePOList.length }} dari {{ filteredPOList.length }} PO</div>
              <button class="btn btn-outline btn-sm" @click="loadMorePO">Muat 10 Lagi</button>
            </div>
          </div>
        </div>
        <div class="card" id="po-detail-section" v-if="poDetail" style="margin-top:16px;">
          <div class="card-header">
            <span class="card-title">Detail PO {{ poDetail.po_number }} <span v-if="poDetail.customer_id" style="font-weight:400;color:var(--text-muted);font-size:13px;">&middot; {{ poDetail.customer_id }}</span><span v-if="poDetail.project" style="font-weight:400;color:var(--text-muted);font-size:13px;">&middot; {{ poDetail.project }}</span></span>
            <div style="display:flex;align-items:center;gap:8px;">
              <button class="btn btn-outline btn-sm" @click="createSJForPO(poDetail)" :disabled="loadingPendingSJ">
                <i class="ti ti-file-invoice"></i> {{ loadingPendingSJ ? 'Mengecek...' : 'Buat Surat Jalan' }}
              </button>
              <span :style="{background: poStatusBadge(poDetail.status).bg, color: poStatusBadge(poDetail.status).color, padding:'3px 10px', borderRadius:'6px', fontSize:'12px'}">{{ poStatusBadge(poDetail.status).label }}</span>
            </div>
          </div>
          <div class="card-body" style="overflow-x:auto;">
            <table class="tbl">
              <thead><tr><th>Part</th><th>Nama Part</th><th>Order</th><th>Terkirim</th><th>Sisa</th><th>Status</th><th v-if="poDetail.approval_stage==='delivery'">Aksi</th></tr></thead>
              <tbody>
                <tr v-for="it in poDetail.items" :key="it.id">
                  <td>{{ it.part_number }}</td>
                        <td>{{ it.part_name }}</td>
                  <td>{{ it.qty_order }}</td>
                  <td>{{ it.qty_delivered }}</td>
                  <td>{{ it.qty_order - it.qty_delivered }}</td>
                  <td><span :style="{background: poStatusBadge(it.status).bg, color: poStatusBadge(it.status).color, padding:'3px 10px', borderRadius:'6px', fontSize:'12px'}">{{ poStatusBadge(it.status).label }}</span></td>
                  <td v-if="poDetail.approval_stage==='delivery'">
                    <button v-if="it.qty_delivered === 0" class="btn btn-danger-soft btn-sm" @click="removePOItem(it)" title="Hapus part ini">
                      <i class="ti ti-trash"></i>
                    </button>
                    <span v-else style="color:var(--text-muted);font-size:11px;" title="Sudah ada pengiriman, tidak bisa dihapus">-</span>
                  </td>
                </tr>
              </tbody>
            </table>

            <div v-if="poDetail.approval_stage==='delivery'" style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border);">
              <div style="font-weight:700;margin-bottom:8px;font-size:13px;">+ Tambah Part yang terlewat</div>
              <div style="display:flex;gap:8px;align-items:flex-start;flex-wrap:wrap;">
                <div style="position:relative;flex:2;min-width:180px;">
                  <input class="form-ctrl" type="text" v-model="addPoItemForm.part_number" @input="searchAddPoItemSuggest" placeholder="Ketik Part Number..." autocomplete="off">
                  <div v-if="addPoItemSuggests.length" style="position:absolute;top:100%;left:0;right:0;background:var(--surface);border:1px solid var(--border);border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);z-index:50;max-height:200px;overflow-y:auto;">
                    <div v-for="s in addPoItemSuggests" :key="s.part_number" @click="selectAddPoItemPart(s)" style="padding:8px 12px;cursor:pointer;border-bottom:1px solid var(--border);">
                      <b>{{ s.part_number }}</b><div style="font-size:12px;color:var(--text-muted);">{{ s.part_name }}</div>
                    </div>
                  </div>
                </div>
                <input class="form-ctrl" type="number" v-model="addPoItemForm.qty_order" min="1" placeholder="Qty" style="flex:1;min-width:90px;">
                <button class="btn btn-primary" @click="submitAddPoItem" :disabled="loadingAddPoItem" style="flex:0 0 auto;">
                  <i class="ti ti-plus"></i> {{ loadingAddPoItem ? 'Menambah...' : 'Tambah' }}
                </button>
              </div>
            </div>
          </div>
          <div class="card-body" style="border-top:1px solid var(--border, #e5e7eb);">
            <div style="font-weight:700;margin-bottom:12px;">Alur Approval</div>
            <div style="display:flex;flex-direction:column;gap:10px;">
              <div style="display:flex;align-items:center;gap:10px;">
                <i class="ti" :class="poDetail.approval_mkt1_at ? 'ti-circle-check-filled' : 'ti-circle-dashed'" :style="{color: poDetail.approval_mkt1_at ? 'var(--success)' : 'var(--text-muted)', fontSize:'20px'}"></i>
                <div>
                  <div style="font-weight:600;font-size:13px;">1. Approval Marketing</div>
                  <div style="font-size:12px;color:var(--text-muted);">{{ poDetail.approval_mkt1_at ? ('Disetujui oleh ' + poDetail.approval_mkt1_by + ' • ' + poDetail.approval_mkt1_at) : 'Menunggu' }}</div>
                </div>
              </div>
              <div style="display:flex;align-items:center;gap:10px;">
                <i class="ti" :class="poDetail.approval_pcd_at ? 'ti-circle-check-filled' : 'ti-circle-dashed'" :style="{color: poDetail.approval_pcd_at ? 'var(--success)' : 'var(--text-muted)', fontSize:'20px'}"></i>
                <div>
                  <div style="font-weight:600;font-size:13px;">2. Approval PCD Project</div>
                  <div style="font-size:12px;color:var(--text-muted);">{{ poDetail.approval_pcd_at ? ('Disetujui oleh ' + poDetail.approval_pcd_by + ' • ' + poDetail.approval_pcd_at) : 'Menunggu' }}</div>
                </div>
              </div>
              <div style="display:flex;align-items:center;gap:10px;">
                <i class="ti" :class="poDetail.approval_mkt2_at ? 'ti-circle-check-filled' : 'ti-circle-dashed'" :style="{color: poDetail.approval_mkt2_at ? 'var(--success)' : 'var(--text-muted)', fontSize:'20px'}"></i>
                <div>
                  <div style="font-weight:600;font-size:13px;">3. Approval Marketing (Final)</div>
                  <div style="font-size:12px;color:var(--text-muted);">{{ poDetail.approval_mkt2_at ? ('Disetujui oleh ' + poDetail.approval_mkt2_by + ' • ' + poDetail.approval_mkt2_at) : 'Menunggu' }}</div>
                </div>
              </div>
            </div>
            <button v-if="canApprovePO(poDetail)" class="btn btn-full" @click="approvePO(poDetail.id)" :disabled="loadingApprovePO" style="margin-top:16px;background:var(--success);color:#fff;">
              <i class="ti ti-circle-check"></i> {{ loadingApprovePO ? 'Memproses...' : 'Approve Tahap Ini' }}
            </button>
            <div v-else-if="poDetail.approval_stage === 'delivery'" style="margin-top:16px;font-size:13px;color:var(--text-muted);"><i class="ti ti-info-circle"></i> Approval baru bisa mulai setelah Status Delivery jadi Closed.</div>
            <div v-else-if="poDetail.approval_stage === 'completed'" style="margin-top:16px;font-size:13px;color:var(--success);font-weight:600;"><i class="ti ti-circle-check"></i> PO ini sudah selesai sepenuhnya.</div>
          </div>
        </div>
      </div>

      <!-- PART LIST -->
      <div class="page" v-if="page==='partlist'">
        <div class="card" v-if="canManage" style="border-left:3px solid var(--primary);">
          <div class="card-header"><span class="card-title"><i class="ti ti-circle-plus" style="color:var(--primary);"></i> Tambah Part Baru</span></div>
          <div class="card-body">
            <div class="input-row">
              <div class="form-group"><label class="form-label">Model</label><input class="form-ctrl" type="text" v-model="newPart.model" placeholder="Contoh: 737D"></div>
              <div class="form-group"><label class="form-label">Commodity *</label><input class="form-ctrl" type="text" v-model="newPart.commodity" placeholder="Contoh: INJECTION PART"></div>
            </div>
            <div class="input-row-3">
              <div class="form-group"><label class="form-label">Part Name *</label><input class="form-ctrl" type="text" v-model="newPart.part_name" placeholder="Nama part lengkap"></div>
              <div class="form-group"><label class="form-label">Part Number *</label><input class="form-ctrl" type="text" v-model="newPart.part_number" placeholder="Contoh: 71173-X7V30"></div>
              <div class="form-group"><label class="form-label">Supplier</label><input class="form-ctrl" type="text" v-model="newPart.supplier" placeholder="Contoh: PT Sinar Jaya"></div>
            </div>
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
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
              <input type="file" ref="importFileInput" accept=".xlsx,.xls,.csv" style="display:none" @change="handleImportFile">
              <button class="btn btn-outline btn-sm" @click="importFileInput.click()" :disabled="loadingImport">
                <i class="ti ti-upload"></i> {{ loadingImport ? 'Mengimport...' : 'Import Excel' }}
              </button>
              <button class="btn btn-success btn-sm" @click="exportExcelStok"><i class="ti ti-file-spreadsheet"></i> Export Excel</button>
              <template v-if="canSeePrice">
                <input type="file" ref="importPriceFileInput" accept=".xlsx,.xls,.csv" style="display:none" @change="handleImportPriceExcel">
                <button class="btn btn-outline btn-sm" @click="downloadPriceTemplate"><i class="ti ti-download"></i> Template Price</button>
                <button class="btn btn-outline btn-sm" @click="importPriceFileInput.click()" :disabled="loadingImportPrice">
                  <i class="ti ti-upload"></i> {{ loadingImportPrice ? 'Mengimport...' : 'Import Price Excel' }}
                </button>
              </template>
            </div>
          </div>
          <div class="card-body" style="padding-bottom:8px;">
            <div class="filter-row">
              <input class="form-ctrl" type="text" v-model="partSearch" placeholder="Cari PN, nama, atau commodity...">
              <select class="form-ctrl" v-model="partCommodityFilter">
                <option value="">Semua Commodity</option>
                <option v-for="c in partCommodities" :key="c" :value="c">{{ c }}</option>
              </select>
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
                  <th class="hide-mobile">Model</th><th class="hide-mobile">Supplier</th><th>Stok</th><th class="hide-mobile">Min</th>
                  <th class="hide-mobile">Reject</th>
                  <th>Status</th>
                  <th v-if="canSeePrice" class="hide-mobile">Price Part</th>
                  <th v-if="canSeePrice" class="hide-mobile">Periode Price</th>
                  <th v-if="canSeePrice" class="hide-mobile">Tarikan Sales</th>
                  <th v-if="canManage || canSeePrice" class="hide-mobile">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="filteredParts.length === 0"><td :colspan="partsColspan" class="no-data">Tidak ada data</td></tr>
                <tr v-for="p in filteredParts" :key="p.id">
                  <td class="hide-mobile td-name">{{ p.commodity }}</td>
                  <td class="td-name">{{ p.part_name }}</td>
                  <td style="font-weight:700;color:var(--primary);white-space:nowrap;">{{ p.part_number }}</td>
                  <td class="hide-mobile"><span class="model-tag" v-if="p.model">{{ p.model }}</span><span v-else>-</span></td>
                  <td class="hide-mobile">{{ p.supplier || '-' }}</td>
                  <td style="font-weight:800;text-align:center;">{{ p.stock }}</td>
                  <td class="hide-mobile">{{ p.min_stock }}</td>
                  <td class="hide-mobile" style="text-align:center;color:var(--danger);font-weight:700;">{{ p.total_reject || 0 }}</td>
                  <td><span class="badge" :class="p.stock === 0 ? 'badge-danger' : p.stock <= p.min_stock ? 'badge-warning' : 'badge-ok'">{{ p.stock === 0 ? 'HABIS' : p.stock <= p.min_stock ? 'KRITIS' : 'OK' }}</span></td>
                  <td v-if="canSeePrice" class="hide-mobile">{{ p.price ? formatRupiah(p.price) : '-' }}</td>
                  <td v-if="canSeePrice" class="hide-mobile">{{ p.price_valid_from && p.price_valid_until ? formatDate(p.price_valid_from) + ' - ' + formatDate(p.price_valid_until) : '-' }}</td>
                  <td v-if="canSeePrice" class="hide-mobile">{{ p.tarikan_sales ? formatRupiah(p.tarikan_sales) : '-' }}</td>
                  <td v-if="canManage || canSeePrice" class="hide-mobile" style="white-space:nowrap;">
                    <button v-if="canSeePrice" class="btn btn-outline btn-sm" @click="openPriceModal(p)" title="Update Price"><i class="ti ti-currency-dollar"></i></button>
                    <button v-if="canManage" class="btn btn-danger-soft btn-sm" @click="deletePart(p.id)"><i class="ti ti-trash"></i></button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- MODAL UPDATE PRICE -->
        <div v-if="priceModal.show" class="modal-backdrop" @click.self="priceModal.show=false">
          <div class="modal-box">
            <div class="modal-header">
              <span><i class="ti ti-currency-dollar" style="color:var(--primary);"></i> Update Price - {{ priceModal.part?.part_number }}</span>
              <button class="modal-close" @click="priceModal.show=false"><i class="ti ti-x"></i></button>
            </div>
            <div class="modal-body">
              <div class="form-group"><label class="form-label">Price Part (Rp) *</label><input class="form-ctrl" type="number" min="0" v-model="priceModal.form.price" placeholder="Contoh: 15000"></div>
              <div class="input-row">
                <div class="form-group"><label class="form-label">Berlaku Dari *</label><input class="form-ctrl" type="date" v-model="priceModal.form.price_valid_from"></div>
                <div class="form-group"><label class="form-label">Berlaku Sampai *</label><input class="form-ctrl" type="date" v-model="priceModal.form.price_valid_until"></div>
              </div>
              <div class="form-group"><label class="form-label">Tarikan Sales (Rp)</label><input class="form-ctrl" type="number" min="0" v-model="priceModal.form.tarikan_sales" placeholder="Contoh: 5000"></div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-outline" @click="priceModal.show=false">Batal</button>
              <button class="btn btn-primary" @click="submitPriceUpdate" :disabled="priceModal.loading">
                <i class="ti ti-device-floppy"></i> {{ priceModal.loading ? 'Menyimpan...' : 'Simpan Price' }}
              </button>
            </div>
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

          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th><input type="checkbox" @change="toggleSelectAll($event)" :checked="allSelected"></th>
                  <th>Tanggal</th><th>Tipe</th><th>PN</th><th>Part Name</th><th>Qty</th>
                  <th class="hide-mobile">Pengiriman</th>
                  <th class="hide-mobile">QC</th><th class="hide-mobile">Supplier/Customer</th><th class="hide-mobile">Oleh</th>
                  <th v-if="canManage" class="hide-mobile">Aksi</th><th>QC</th><th>Share</th><th>PDF</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="historyList.length === 0"><td :colspan="canManage ? 13 : 12" class="no-data">Tidak ada data</td></tr>
                <tr v-for="h in historyList" :key="h.id">
                  <td><input type="checkbox" :value="h.id" v-model="selectedIds"></td>
                  <td class="td-date">{{ h.date }}<br><span style="color:var(--text-muted);font-size:10px;">{{ h.time || '' }}</span></td>
                  <td><span class="badge" :class="h.type === 'masuk' ? 'badge-masuk' : 'badge-keluar'">{{ h.type }}</span></td>
                  <td style="font-weight:700;color:var(--primary);white-space:nowrap;">{{ h.part_number }}</td>
                  <td class="td-name">{{ h.part_name }}</td>
                  <td style="font-weight:800;text-align:center;">{{ h.qty_ok }}</td>
                  <td class="hide-mobile">
                    <span v-if="h.delivery_sequence" class="badge badge-ok" style="white-space:nowrap;">
                      Ke-{{ h.delivery_sequence }} ({{ h.delivery_progress }} pcs)
                    </span>
                    <span v-else style="color:var(--text-muted);">-</span>
                  </td>
                  <td class="hide-mobile" style="font-size:11px;">{{ h.status_qc || '-' }}</td>
                  <td class="hide-mobile" style="font-size:11px;">{{ h.type === 'masuk' ? (h.supplier || '-') : (h.tujuan || '-') }}</td>
                  <td class="hide-mobile" style="font-size:11px;color:var(--primary);font-weight:600;">{{ h.input_by || '-' }}</td>
                  <td v-if="canManage" class="hide-mobile"><button class="btn btn-danger-soft btn-sm" @click="deleteHistory(h.id)"><i class="ti ti-trash"></i></button></td>
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

      <!-- HISTORY SURAT JALAN -->
      <div class="page" v-if="page==='sjhistory'">
        <div class="card">
          <div class="card-header"><span class="card-title"><i class="ti ti-truck-delivery"></i> History Surat Jalan</span></div>
          <div class="table-wrap">
            <table class="table">
              <thead>
                <tr><th>No. SJ</th><th>Delivery To</th><th>Tanggal</th><th class="hide-mobile">Project</th><th class="hide-mobile">No. PO</th><th>Item</th><th class="hide-mobile">Dibuat Oleh</th><th>Aksi</th></tr>
              </thead>
              <tbody>
                <tr v-if="!loadingSJHistory && sjHistory.length === 0"><td colspan="8" class="no-data">Belum ada surat jalan yang pernah dibuat</td></tr>
                <tr v-for="sj in sjHistory" :key="sj.id">
                  <td style="font-weight:700;color:var(--primary);cursor:pointer;" @click="previewSJ(sj.id)" title="Lihat preview">{{ sj.no_surat_jalan || '-' }}</td>
                  <td>{{ sj.delivery_to || '-' }}</td>
                  <td style="white-space:nowrap;">{{ formatDate(sj.date) }}</td>
                  <td class="hide-mobile">{{ sj.project || '-' }}</td>
                  <td class="hide-mobile">{{ sj.no_po || '-' }}</td>
                  <td style="text-align:center;">{{ sj.item_count }}</td>
                  <td class="hide-mobile">{{ sj.created_by }}</td>
                  <td>
                    <button class="btn btn-outline btn-sm" @click="downloadSJAgain(sj.id)" title="Download ulang">
                      <i class="ti ti-download"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- MODAL PREVIEW SURAT JALAN -->
      <div v-if="sjPreview.show" class="modal-backdrop" @click.self="closeSJPreview">
        <div class="modal-box" style="width:min(820px, 92vw);height:min(880px, 90vh);display:flex;flex-direction:column;">
          <div class="modal-header">
            <span>Preview Surat Jalan</span>
            <button class="modal-close" @click="closeSJPreview"><i class="ti ti-x"></i></button>
          </div>
          <div class="modal-body" style="flex:1;padding:0;overflow:hidden;">
            <div v-if="sjPreview.loading" style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--text-muted);">Memuat preview...</div>
            <iframe v-else :src="sjPreview.url" style="width:100%;height:100%;border:none;"></iframe>
          </div>
          <div class="modal-footer">
            <button class="btn btn-outline btn-sm" @click="closeSJPreview">Tutup</button>
            <a :href="sjPreview.url" download="surat-jalan.pdf" class="btn btn-sm" style="text-decoration:none;">
              <i class="ti ti-download"></i> Download
            </a>
          </div>
        </div>
      </div>

      <!-- AUDIT TRAIL -->
      <div class="page" v-if="page==='auditlog'">
        <div class="card">
          <div class="card-header"><span class="card-title"><i class="ti ti-shield-lock"></i> Audit Trail</span></div>
          <div class="card-body" style="padding-bottom:8px;">
            <div class="filter-row">
              <select class="form-ctrl" v-model="auditFilter.action" @change="loadActivityLogs" style="max-width:160px;">
                <option value="">Semua Aksi</option>
                <option value="create">Create</option>
                <option value="update_price">Update Price</option>
                <option value="import_price">Import Price</option>
                <option value="import">Import</option>
                <option value="delete">Delete</option>
                <option value="approve">Approve</option>
              </select>
              <button class="btn btn-outline btn-sm" @click="loadActivityLogs" :disabled="loadingAuditLogs">
                <i class="ti ti-refresh"></i> Refresh
              </button>
              <button class="btn btn-success btn-sm" @click="exportAuditExcel" :disabled="loadingExportAudit">
                <i class="ti ti-file-spreadsheet"></i> {{ loadingExportAudit ? 'Menyiapkan...' : 'Export Excel' }}
              </button>
            </div>
          </div>
          <div class="table-wrap">
            <table class="table">
              <thead>
                <tr><th>Waktu</th><th>User</th><th>Role</th><th>Aksi</th><th>Deskripsi</th></tr>
              </thead>
              <tbody>
                <tr v-if="!loadingAuditLogs && activityLogs.length === 0"><td colspan="5" class="no-data">Belum ada aktivitas tercatat</td></tr>
                <tr v-for="log in activityLogs" :key="log.id">
                  <td style="white-space:nowrap;">{{ formatDateTime(log.created_at) }}</td>
                  <td>{{ log.user_name || '-' }}</td>
                  <td><span class="badge badge-ok" style="text-transform:capitalize;">{{ log.role || '-' }}</span></td>
                  <td><span class="badge" style="text-transform:capitalize;">{{ log.action }}</span></td>
                  <td>{{ log.description }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div><!-- end .main -->

    <!-- MODAL SURAT JALAN (global, dipakai dari halaman History maupun Rekap PO) -->
    <div v-if="sjModal.show" style="position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999;display:flex;align-items:center;justify-content:center;">
      <div class="card" style="width:380px;margin:0;max-height:90vh;overflow-y:auto;">
        <div class="card-header"><span class="card-title">Detail Surat Jalan</span></div>
        <div class="card-body">
          <label style="font-size:12px;font-weight:600;">No. Surat Jalan</label>
          <input class="form-ctrl" style="margin:6px 0 12px;" v-model="sjModal.no_surat_jalan" placeholder="Contoh: SJ-001/VIII/2026">
          <label style="font-size:12px;font-weight:600;">Delivery To</label>
          <input class="form-ctrl" style="margin:6px 0 12px;" v-model="sjModal.delivery_to" placeholder="Nama tujuan / customer">
          <label style="font-size:12px;font-weight:600;">Tanggal</label>
          <input class="form-ctrl" style="margin:6px 0 12px;" type="date" v-model="sjModal.date">
          <label style="font-size:12px;font-weight:600;">Project</label>
          <input class="form-ctrl" style="margin:6px 0 12px;" v-model="sjModal.project" placeholder="Nama project (opsional)">
          <label style="font-size:12px;font-weight:600;">No. PO</label>
          <select class="form-ctrl" style="margin:6px 0 16px;" v-model="sjModal.no_po" @focus="loadPOList">
            <option value="">-- Ga terikat PO --</option>
            <option v-for="po in poList.filter(p => p.status !== 'closed' || p.po_number === sjModal.no_po)" :key="po.id" :value="po.po_number">{{ po.po_number }}</option>
          </select>
          <div style="display:flex;gap:8px;">
            <button class="btn" style="flex:1;background:var(--surface2);color:var(--text);" @click="sjModal.show=false">Batal</button>
            <button class="btn btn-primary" style="flex:1;" @click="submitSJModal" :disabled="sjModal.loading">
              {{ sjModal.loading ? 'Membuat...' : 'Download' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MOBILE NAV -->
    <nav class="mobile-nav">
      <div class="mobile-nav-inner">
        <div class="nav-tab" :class="{active: page==='dashboard'}" @click="goPage('dashboard')"><i class="ti ti-layout-dashboard"></i><span>Dashboard</span></div>
        <div class="nav-tab" :class="{active: page==='input'}" @click="goPage('input')" v-if="canManage"><i class="ti ti-circle-plus"></i><span>Input</span></div>
        <div class="nav-tab" :class="{active: page==='partlist'}" @click="goPage('partlist')"><i class="ti ti-box"></i><span>Part</span></div>
        <div class="nav-tab" :class="{active: page==='history'}" @click="goPage('history')"><i class="ti ti-history"></i><span>History</span></div>
      </div>
    </nav>

  </div>
</div>

<script type="module" src="js/main.js?v=6"></script>
</body>
</html>