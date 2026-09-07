import { createApp, ref, computed, onMounted, nextTick } from 'vue';

// Menyesuaikan sendiri dengan lokasi halaman ini, jadi tidak perlu diganti
// manual setiap berpindah antara lokal dan production.
const API_URL = location.origin + location.pathname.replace(/\/[^/]*$/, '') + '/api';

createApp({
  setup() {
    // Cek auth — kalau tidak ada token, redirect ke login
    const token = ref(localStorage.getItem('token') || null);
    const currentUser = ref(JSON.parse(localStorage.getItem('currentUser') || 'null'));
    const SESSION_TIMEOUT_MS = 30 * 60 * 1000; // 30 menit

    function clearSession() {
      localStorage.removeItem('token');
      localStorage.removeItem('currentUser');
      localStorage.removeItem('lastActivity');
    }

    const lastActivityStored = parseInt(localStorage.getItem('lastActivity') || '0', 10);
    if (lastActivityStored && (Date.now() - lastActivityStored > SESSION_TIMEOUT_MS)) {
      clearSession();
      window.location.href = 'login.php';
      return {};
    }

    if (!token.value || !currentUser.value) {
      window.location.href = 'login.html';
      return {};
    }

    localStorage.setItem('lastActivity', Date.now().toString());

    const isDark = ref(localStorage.getItem('theme') === 'dark');
    const page = ref('dashboard');
    const inputTab = ref('masuk'); // khusus mobile: toggle form Masuk/Keluar di halaman Input Transaksi
    const partsList = ref([]);
    const historyList = ref([]);
    const selectedIds = ref([]);
    const sjModal = ref({ show: false, delivery_to: '', date: '', no_surat_jalan: '', project: '', no_po: '', loading: false });
    const allSelected = computed(() => historyList.value.length > 0 && selectedIds.value.length === historyList.value.length);
    const importFileInput = ref(null);
    const loadingImport = ref(false);
    const importPriceFileInput = ref(null);
    const loadingImportPrice = ref(false);
    const importPOFileInput = ref(null);
    const loadingImportPO = ref(false);
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
    const keluar = ref({ pn: '', model: '', commodity: '', part_name: '', qty: '', date: today(), status_qc: '', keterangan: '', tujuan: '', kategori_keluar: 'po', po_id: '', po_item_id: '', keterangan_non_po: '' });
    const qcModal = ref({ show: false, id: null, part_number: '', maxQty: 0, qty_ok: 0, keterangan_reject: '', loading: false });
    const suggests = ref({ masuk: [], keluar: [] });
    const loadingMasuk = ref(false);
    const loadingKeluar = ref(false);
    const newPart = ref({ model: '', commodity: '', part_name: '', part_number: '', supplier: '', stock: 0, min_stock: 1 });
    const openPOList = ref([]);
    const poList = ref([]);
    const poDetail = ref(null);
    const loadingPO = ref(false);
    const newPO = ref({ po_number: '', po_date: today(), customer_id: '', target_delivery: '', items: [{ part_number: '', part_name: '', qty_order: '' }] });
    const poItemsForSelectedPO = computed(() => {
      const po = openPOList.value.find(p => p.id == keluar.value.po_id);
      return po ? po.items.filter(it => it.status !== 'closed') : [];
    });
    const keluarPoItemQuery = ref('');
    const poItemSuggests = ref([]);
    const selectedPOItem = computed(() => poItemsForSelectedPO.value.find(it => it.id == keluar.value.po_item_id) || null);
    function searchPOItemSuggest() {
      if (selectedPOItem.value && keluarPoItemQuery.value !== selectedPOItem.value.part_number) {
        keluar.value.po_item_id = '';
      }
      const q = (keluarPoItemQuery.value || '').toLowerCase();
      poItemSuggests.value = poItemsForSelectedPO.value.filter(it =>
        it.part_number.toLowerCase().includes(q) || it.part_name.toLowerCase().includes(q)
      );
    }
    function selectPOItem(it) {
      keluar.value.po_item_id = it.id;
      keluarPoItemQuery.value = it.part_number;
      poItemSuggests.value = [];
    }
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
    const isPcd = computed(() => currentUser.value?.role === 'pcd');
    const canManage = computed(() => isAdmin.value || isPcd.value);
    const isMarketing = computed(() => currentUser.value?.role === 'marketing');
    const canSeePrice = computed(() => isAdmin.value || isMarketing.value);
    const poBelumClose = computed(() => poList.value.filter(po => po.status !== 'closed'));
    const poBelumCloseCount = computed(() => poBelumClose.value.length);
    const poClosedCount = computed(() => poList.value.filter(po => po.status === 'closed').length);
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
    const pageTitle = computed(() => ({ dashboard: 'Dashboard', input: 'Input Transaksi', po: 'Input PO', porekap: 'Rekap PO', partlist: 'Daftar Part', history: 'History Transaksi', sjhistory: 'History Surat Jalan', auditlog: 'Audit Trail' })[page.value] || '');
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

    function logout(reason) {
      if (token.value) apiFetch('/auth/logout', { method: 'POST' }).catch(() => {});
      clearSession();
      if (idleCheckTimer) clearInterval(idleCheckTimer);
      ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach(evt => {
        window.removeEventListener(evt, markActivity);
      });
      window.location.href = 'login.php' + (reason === 'idle' ? '?expired=1' : '');
    }
    let idleCheckTimer = null;

    function markActivity() {
      localStorage.setItem('lastActivity', Date.now().toString());
    }

    function checkIdle() {
      const last = parseInt(localStorage.getItem('lastActivity') || '0', 10);
      if (last && (Date.now() - last > SESSION_TIMEOUT_MS)) {
        showToast('Sesi Anda berakhir karena 30 menit tidak ada aktivitas.', 'error');
        logout('idle');
      }   
    }

    ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach(evt => {
      window.addEventListener(evt, markActivity, { passive: true });
    });
    idleCheckTimer = setInterval(checkIdle, 30 * 1000);


    async function loadAll() { await loadParts(); await loadDashboard(); }

    async function loadParts() {
      try { partsList.value = await apiFetch('/parts'); }
      catch (err) { showToast('Gagal load parts: ' + err.message, 'error'); }
    }

    async function loadDashboard() {
      try {
        const hist = await apiFetch('/transactions');
        let bqc = 0, aqc = 0;
        hist.forEach(h => {
          if (h.type === 'masuk' && h.status_qc === 'Before Check QC') bqc += h.qty_ok;
          if (h.type === 'masuk' && h.status_qc === 'After Check QC') aqc += h.qty_ok;
        });
        statBeforeQC.value = bqc; statAfterQC.value = aqc;
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

    const auditFilter = ref({ action: '' });
    const activityLogs = ref([]);
    const loadingAuditLogs = ref(false);
    async function loadActivityLogs() {
      loadingAuditLogs.value = true;
      try {
        const params = new URLSearchParams();
        if (auditFilter.value.action) params.append('action', auditFilter.value.action);
        const res = await apiFetch('/activity-logs?' + params.toString());
        activityLogs.value = res.data || res; // jaga-jaga kalau backend return paginator (.data) atau array polos
      } catch (err) { showToast('Gagal load audit trail: ' + err.message, 'error'); }
      finally { loadingAuditLogs.value = false; }
    }

    const loadingExportAudit = ref(false);
    async function exportAuditExcel() {
      loadingExportAudit.value = true;
      try {
        const params = new URLSearchParams();
        if (auditFilter.value.action) params.append('action', auditFilter.value.action);
        const logs = await apiFetch('/activity-logs/export?' + params.toString());
        if (!logs.length) { showToast('Ga ada data untuk di-export!', 'error'); return; }

        const data = logs.map(l => ({
          'Waktu': formatDateTime(l.created_at),
          'User': l.user_name || '-',
          'Role': l.role || '-',
          'Aksi': l.action,
          'Deskripsi': l.description,
        }));
        const ws = XLSX.utils.json_to_sheet(data);
        ws['!cols'] = [{ wch: 20 }, { wch: 20 }, { wch: 14 }, { wch: 16 }, { wch: 60 }];
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Audit Trail');
        XLSX.writeFile(wb, `audit-trail-${new Date().toISOString().split('T')[0]}.xlsx`);
        showToast('Excel berhasil didownload!');
      } catch (err) { showToast('Gagal export: ' + err.message, 'error'); }
      finally { loadingExportAudit.value = false; }
    }
    function formatDateTime(d) {
      if (!d) return '-';
      return new Date(d).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    function goPage(p) {
      if (p === 'input' && !canManage.value) { showToast('Hanya admin yang bisa input transaksi!', 'error'); return; }
      if (p === 'po' && !isAdmin.value && !isMarketing.value) { showToast('Hanya admin/marketing yang bisa input PO!', 'error'); return; }
      if (p === 'auditlog' && !isAdmin.value) { showToast('Hanya admin yang bisa lihat audit trail!', 'error'); return; }
      if (p === 'sjhistory' && !isAdmin.value && !isPcd.value) { showToast('Hanya admin/pcd yang bisa lihat history surat jalan!', 'error'); return; }
      page.value = p;
      if (p === 'history') loadHistory();
      if (p === 'dashboard') nextTick(() => loadChart());
      if (p === 'porekap') loadPOList();
      if (p === 'auditlog') loadActivityLogs();
      if (p === 'sjhistory') loadSJHistory();
    }
    
    const poSuggests = ref({});
    function searchPOSuggest(idx) {
      const q = (newPO.value.items[idx].part_number || '').toLowerCase();
      if (!q) { poSuggests.value[idx] = []; return; }
      poSuggests.value[idx] = partsList.value.filter(p =>
        p.part_number.toLowerCase().includes(q) || p.part_name.toLowerCase().includes(q)
      ).slice(0, 10);
    }
    function selectPOPart(idx, part) {
      newPO.value.items[idx].part_number = part.part_number;
      newPO.value.items[idx].part_name = part.part_name;
      poSuggests.value[idx] = [];
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
      if (keluar.value.kategori_keluar === 'po') {
        if (!keluar.value.po_id || !keluar.value.po_item_id) { showToast('Pilih No. PO dan item part-nya dulu!', 'error'); return; }
        const item = poItemsForSelectedPO.value.find(it => it.id == keluar.value.po_item_id);
        const sisa = item ? (item.qty_order - item.qty_delivered) : 0;
        if (item && parseInt(keluar.value.qty) > sisa) { showToast('Qty keluar melebihi sisa PO (sisa: ' + sisa + ')!', 'error'); return; }
      } else if (!keluar.value.keterangan_non_po) {
        showToast('Keterangan wajib diisi untuk keluar non-PO!', 'error'); return;
      }
      loadingKeluar.value = true;
      try {
        await apiFetch('/transactions', { method: 'POST', body: JSON.stringify({ part_number: keluar.value.pn, type: 'keluar', qty: parseInt(keluar.value.qty), date: keluar.value.date, status_qc: keluar.value.status_qc, keterangan: keluar.value.keterangan || null, tujuan: keluar.value.tujuan || null, kategori_keluar: keluar.value.kategori_keluar, po_item_id: keluar.value.kategori_keluar === 'po' ? keluar.value.po_item_id : null, keterangan_non_po: keluar.value.kategori_keluar === 'non_po' ? keluar.value.keterangan_non_po : null }) });
        showToast('Transaksi keluar berhasil disimpan!');
        keluar.value = { pn: '', model: '', commodity: '', part_name: '', qty: '', date: today(), status_qc: '', keterangan: '', tujuan: '', kategori_keluar: 'po', po_id: '', po_item_id: '', keterangan_non_po: '' };
        keluarPoItemQuery.value = ''; poItemSuggests.value = [];
        await loadParts(); loadDashboard(); loadOpenPOList();
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

    const partsColspan = computed(() => 9 + (canSeePrice.value ? 3 : 0) + ((canManage.value || canSeePrice.value) ? 1 : 0));

    const priceModal = ref({ show: false, loading: false, part: null, form: { price: '', price_valid_from: '', price_valid_until: '', tarikan_sales: '' } });
    function openPriceModal(p) {
      priceModal.value = {
        show: true, loading: false, part: p,
        form: {
          price: p.price ?? '',
          price_valid_from: p.price_valid_from ?? '',
          price_valid_until: p.price_valid_until ?? '',
          tarikan_sales: p.tarikan_sales ?? '',
        },
      };
    }
    async function submitPriceUpdate() {
      const f = priceModal.value.form;
      if (!f.price || !f.price_valid_from || !f.price_valid_until) { showToast('Price dan periode wajib diisi!', 'error'); return; }
      priceModal.value.loading = true;
      try {
        await apiFetch('/parts/' + priceModal.value.part.id + '/price', {
          method: 'PUT',
          body: JSON.stringify({
            price: parseFloat(f.price),
            price_valid_from: f.price_valid_from,
            price_valid_until: f.price_valid_until,
            tarikan_sales: f.tarikan_sales !== '' ? parseFloat(f.tarikan_sales) : null,
          }),
        });
        showToast('Price berhasil diupdate!');
        priceModal.value.show = false;
        await loadParts();
      } catch (err) { showToast(err.message, 'error'); }
      finally { priceModal.value.loading = false; }
    }
    function formatRupiah(v) {
      const n = parseFloat(v);
      if (isNaN(n)) return '-';
      return 'Rp ' + n.toLocaleString('id-ID', { maximumFractionDigits: 0 });
    }
    function formatDate(d) {
      if (!d) return '-';
      return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    }

    function excelDateToISO(v) {
      if (v === null || v === undefined || v === '') return null;
      if (typeof v === 'number') {
        const d = new Date(Math.round((v - 25569) * 86400 * 1000));
        return isNaN(d.getTime()) ? null : d.toISOString().split('T')[0];
      }
      const s = String(v).trim();
      let m = s.match(/^(\d{4})-(\d{1,2})-(\d{1,2})/);
      if (m) return `${m[1]}-${m[2].padStart(2,'0')}-${m[3].padStart(2,'0')}`;
      m = s.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/);
      if (m) return `${m[3]}-${m[2].padStart(2,'0')}-${m[1].padStart(2,'0')}`;
      const d = new Date(s);
      return isNaN(d.getTime()) ? null : d.toISOString().split('T')[0];
    }

    function downloadPriceTemplate() {
      const wb = XLSX.utils.book_new();
      const data = [
        ['PART NUMBER', 'PRICE', 'BERLAKU DARI', 'BERLAKU SAMPAI', 'TARIKAN SALES'],
        ['71173-X7V30', 15000, '2026-08-07', '2026-11-09', 5000],
      ];
      const ws = XLSX.utils.aoa_to_sheet(data);
      ws['!cols'] = [{ wch: 20 }, { wch: 12 }, { wch: 14 }, { wch: 14 }, { wch: 14 }];
      XLSX.utils.book_append_sheet(wb, ws, 'Template Price');
      XLSX.writeFile(wb, 'template-update-harga-part.xlsx');
    }

    async function handleImportPriceExcel(e) {
      const file = e.target.files[0];
      if (!file) return;
      loadingImportPrice.value = true;
      try {
        const buf = await file.arrayBuffer();
        const wb = XLSX.read(buf, { type: 'array' });
        const ws = wb.Sheets[wb.SheetNames[0]];
        const rows = XLSX.utils.sheet_to_json(ws, { header: 1, raw: false, defval: '' });

        let headerRowIdx = -1;
        const col = {};
        for (let r = 0; r < rows.length; r++) {
          const cells = rows[r].map(c => String(c).trim().toUpperCase());
          const pnIdx = cells.indexOf('PART NUMBER');
          if (pnIdx !== -1) {
            headerRowIdx = r;
            col.pn = pnIdx;
            col.price = cells.indexOf('PRICE');
            col.from = cells.indexOf('BERLAKU DARI');
            col.until = cells.indexOf('BERLAKU SAMPAI');
            col.tarikan = cells.indexOf('TARIKAN SALES');
            break;
          }
        }
        if (headerRowIdx === -1) { showToast('Format Excel tidak dikenali. Pastikan ada kolom "PART NUMBER". Pakai tombol Template Price biar pas.', 'error'); return; }

        const items = [];
        const invalidRows = [];
        for (let r = headerRowIdx + 1; r < rows.length; r++) {
          const row = rows[r];
          const pn = row[col.pn] ? String(row[col.pn]).trim() : '';
          if (!pn) continue;

          const priceRaw = col.price !== -1 ? row[col.price] : '';
          const fromRaw = col.from !== -1 ? row[col.from] : '';
          const untilRaw = col.until !== -1 ? row[col.until] : '';
          const tarikanRaw = col.tarikan !== -1 ? row[col.tarikan] : '';

          const price = parseFloat(String(priceRaw).replace(/[^0-9.]/g, ''));
          const from = excelDateToISO(fromRaw);
          const until = excelDateToISO(untilRaw);
          const tarikan = tarikanRaw !== '' ? parseFloat(String(tarikanRaw).replace(/[^0-9.]/g, '')) : null;

          if (!price || isNaN(price) || !from || !until) { invalidRows.push(pn); continue; }
          items.push({ part_number: pn, price, price_valid_from: from, price_valid_until: until, tarikan_sales: (tarikan !== null && !isNaN(tarikan)) ? tarikan : null });
        }

        if (!items.length) { showToast('Tidak ada baris valid yang bisa diimport. Cek lagi kolom Price/Tanggal-nya.', 'error'); return; }

        const data = await apiFetch('/parts/import-price', { method: 'POST', body: JSON.stringify({ items }) });
        let msg = data.message;
        if (invalidRows.length) msg += ' | ' + invalidRows.length + ' baris dilewati (data tidak lengkap/format salah): ' + invalidRows.slice(0, 10).join(', ');
        showToast(msg);
        await loadParts();
      } catch (err) {
        showToast('Gagal import: ' + err.message, 'error');
      } finally {
        loadingImportPrice.value = false;
        e.target.value = '';
      }
    }

    function addPOItemRow() { newPO.value.items.push({ part_number: '', part_name: '', qty_order: '' }); }
    function removePOItemRow(idx) { newPO.value.items.splice(idx, 1); }

    async function handleImportPOItems(e) {
      const file = e.target.files[0];
      if (!file) return;
      loadingImportPO.value = true;
      try {
        const buf = await file.arrayBuffer();
        const wb = XLSX.read(buf, { type: 'array' });
        const ws = wb.Sheets[wb.SheetNames[0]];
        const rows = XLSX.utils.sheet_to_json(ws, { header: 1, raw: false, defval: '' });

        let headerRowIdx = -1, pnCol = -1, qtyCol = -1;
        for (let r = 0; r < rows.length; r++) {
          const cells = rows[r].map(c => String(c).trim().toUpperCase());
          const pi = cells.indexOf('PART NUMBER');
          const ni = cells.indexOf('N');
          if (pi !== -1 && ni !== -1) { headerRowIdx = r; pnCol = pi; qtyCol = ni; break; }
        }
        if (headerRowIdx === -1) { showToast('Format Excel tidak dikenali. Pastikan ada kolom "PART NUMBER" dan "N".', 'error'); return; }

        const imported = [];
        let notFound = 0;
        const partsMap = new Map(partsList.value.map(p => [p.part_number.toLowerCase(), p]));
        for (let r = headerRowIdx + 1; r < rows.length; r++) {
          const row = rows[r];
          const pn = row[pnCol] ? String(row[pnCol]).trim() : '';
          const qtyRaw = row[qtyCol];
          const qty = parseInt(qtyRaw);
          if (!pn || !qty || isNaN(qty)) continue;
          const match = partsMap.get(pn.toLowerCase());
          if (!match) notFound++;
          imported.push({ part_number: pn, part_name: match ? match.part_name : '', qty_order: qty });
        }

        if (!imported.length) { showToast('Tidak ada baris valid yang bisa diimport.', 'error'); return; }

        const isEmpty = newPO.value.items.length === 1 && !newPO.value.items[0].part_number;
        newPO.value.items = isEmpty ? imported : [...newPO.value.items, ...imported];

        let msg = imported.length + ' item berhasil diimport!';
        if (notFound) msg += ' (' + notFound + ' part number tidak ditemukan di Daftar Part, cek manual ya)';
        showToast(msg);
      } catch (err) {
        showToast('Gagal baca file: ' + err.message, 'error');
      } finally {
        loadingImportPO.value = false;
        e.target.value = '';
      }
    }


    async function submitPO() {
      if (!newPO.value.po_number || !newPO.value.po_date) { showToast('No. PO dan tanggal wajib diisi!', 'error'); return; }
      const items = newPO.value.items.filter(it => it.part_number && it.qty_order);
      if (items.length === 0) { showToast('Minimal 1 item part wajib diisi!', 'error'); return; }
      loadingPO.value = true;
      try {
        await apiFetch('/po', { method: 'POST', body: JSON.stringify({ po_number: newPO.value.po_number, po_date: newPO.value.po_date, customer_id: newPO.value.customer_id || null, target_delivery: newPO.value.target_delivery || null, items: items.map(it => ({ part_number: it.part_number, qty_order: parseInt(it.qty_order) })) }) });
        showToast('PO berhasil disimpan!');
        newPO.value = { po_number: '', po_date: today(), customer_id: '', target_delivery: '', items: [{ part_number: '', part_name: '', qty_order: '' }] };
        await loadOpenPOList();
      } catch (err) { showToast(err.message, 'error'); }
      finally { loadingPO.value = false; }
    }

    async function loadOpenPOList() {
      try { openPOList.value = await apiFetch('/po?status=open,partial'); } catch (err) { console.error(err); }
    }

    async function loadPOList() {
      loadingPO.value = true;
      try { poList.value = await apiFetch('/po'); } catch (err) { showToast(err.message, 'error'); }
      finally { loadingPO.value = false; }
    }

    async function viewPODetail(id) {
      try { poDetail.value = await apiFetch('/po/' + id); } catch (err) { showToast(err.message, 'error'); }
    }

    function poStatusBadge(status) {
      if (status === 'closed') return { bg: 'var(--bg-success, #e1f5ee)', color: '#0f6e56', label: 'Closed' };
      if (status === 'partial') return { bg: 'var(--bg-warning, #faeeda)', color: '#854f0b', label: 'Partial' };
      return { bg: 'var(--bg-danger, #fcebeb)', color: '#a32d2d', label: 'Open' };
    }

    function poApprovalBadge(stage) {
      const map = {
        delivery: { bg: 'var(--bg-danger, #fcebeb)', color: '#a32d2d', label: 'Belum Delivery' },
        mkt1:     { bg: 'var(--bg-warning, #faeeda)', color: '#854f0b', label: 'Menunggu MKT' },
        pcd:      { bg: 'var(--bg-warning, #faeeda)', color: '#854f0b', label: 'Menunggu PCD' },
        mkt2:     { bg: 'var(--bg-warning, #faeeda)', color: '#854f0b', label: 'Menunggu MKT (Final)' },
        completed:{ bg: 'var(--bg-success, #e1f5ee)', color: '#0f6e56', label: 'Selesai' },
      };
      return map[stage] || map.delivery;
    }

    function canApprovePO(po) {
      if (!po) return false;
      const stageRole = { mkt1: isMarketing.value, pcd: isPcd.value, mkt2: isMarketing.value };
      return isAdmin.value ? (po.approval_stage in stageRole) : !!stageRole[po.approval_stage];
    }

    const loadingApprovePO = ref(false);
    async function approvePO(id) {
      loadingApprovePO.value = true;
      try {
        const res = await apiFetch('/po/' + id + '/approve', { method: 'POST' });
        showToast(res.message);
        await viewPODetail(id);
        await loadPOList();
      } catch (err) {
        showToast('Gagal approve: ' + err.message, 'error');
      } finally {
        loadingApprovePO.value = false;
      }
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

    function toggleSelectAll(e) {
      selectedIds.value = e.target.checked ? historyList.value.map(h => h.id) : [];
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
          })
        });
        showToast('QC berhasil di-approve!');
        qcModal.value.show = false;
        await loadHistory(); loadParts(); loadDashboard();
      } catch (err) {
        showToast(err.message, 'error');
      } finally {
        qcModal.value.loading = false;
      }
    }

    const sjHistory = ref([]);
    const loadingSJHistory = ref(false);
    async function loadSJHistory() {
      loadingSJHistory.value = true;
      try { sjHistory.value = await apiFetch('/surat-jalan'); }
      catch (err) { showToast('Gagal load history surat jalan: ' + err.message, 'error'); }
      finally { loadingSJHistory.value = false; }
    }
    async function downloadSJAgain(id) {
      try {
        const res = await fetch(API_URL + '/surat-jalan/' + id + '/download', {
          headers: { 'Authorization': 'Bearer ' + token.value }
        });
        if (!res.ok) { const e = await res.json().catch(() => ({})); throw new Error(e.message || 'Gagal download PDF'); }
        const blob = await res.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = 'surat-jalan-' + id + '.pdf'; a.click();
        URL.revokeObjectURL(url);
      } catch (err) { showToast(err.message, 'error'); }
    }

    function openSJModal() {
      sjModal.value = { show: true, delivery_to: '', date: today(), no_surat_jalan: '', project: '', no_po: '', loading: false };
      if (!poList.value.length) loadPOList();
    }

    async function downloadSJ(ids, delivery_to, date, extra = {}) {
      try {
        const res = await fetch(API_URL + '/surat-jalan', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': 'Bearer ' + token.value },
          body: JSON.stringify({ ids, delivery_to, date, ...extra })
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
      await downloadSJ(selectedIds.value, sjModal.value.delivery_to, sjModal.value.date, {
        no_surat_jalan: sjModal.value.no_surat_jalan,
        project: sjModal.value.project,
        no_po: sjModal.value.no_po,
      });
      sjModal.value.loading = false;
      sjModal.value.show = false;
      selectedIds.value = [];
    }

    async function quickDownloadSJ(h) {
      await downloadSJ([h.id], '', h.date);
    }

    async function exportCSV() {
      try {
        const hist = await apiFetch('/transactions');
        if (!hist.length) { showToast('Data history kosong!', 'error'); return; }
        const data = hist.map(h => ({
          'Tanggal': h.date,
          'Tipe': h.type === 'masuk' ? 'Masuk' : 'Keluar',
          'Part Number': h.part_number,
          'Part Name': h.part_name,
          'Qty': h.qty_ok,
          'Status QC': h.status_qc || '-',
          'Keterangan': h.type === 'masuk' ? (h.supplier || '-') : (h.tujuan || '-')
        }));
        const ws = XLSX.utils.json_to_sheet(data);
        ws['!cols'] = [{ wch: 12 }, { wch: 10 }, { wch: 20 }, { wch: 40 }, { wch: 8 }, { wch: 14 }, { wch: 22 }];
        const range = XLSX.utils.decode_range(ws['!ref']);
        for (let c = range.s.c; c <= range.e.c; c++) {
          const addr = XLSX.utils.encode_cell({ r: 0, c });
          if (ws[addr]) ws[addr].s = { font: { bold: true, color: { rgb: 'FFFFFF' } }, fill: { fgColor: { rgb: '5F6FFF' } }, alignment: { horizontal: 'center' } };
        }
        ws['!autofilter'] = { ref: ws['!ref'] };
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'History Transaksi');
        XLSX.writeFile(wb, `history-transaksi-${new Date().toISOString().split('T')[0]}.xlsx`, { cellStyles: true });
        showToast('Excel berhasil didownload!');
      } catch (err) { showToast('Gagal export: ' + err.message, 'error'); }
    }

    const loadingExportPO = ref(false);
    async function exportPORekap() {
      loadingExportPO.value = true;
      try {
        const all = await apiFetch('/po');
        const done = all.filter(po => po.status === 'closed' && po.approval_stage === 'completed');
        if (!done.length) { showToast('Belum ada PO yang Closed & Selesai approval-nya.', 'error'); return; }
        const data = done.map(po => ({
          'No. PO': po.po_number,
          'Tanggal PO': po.po_date,
          'Customer ID': po.customer_id || '-',
          'Jumlah Item': po.item_count,
          'Approval MKT (1)': po.approval_mkt1_by ? (po.approval_mkt1_by + ' • ' + po.approval_mkt1_at) : '-',
          'Approval PCD': po.approval_pcd_by ? (po.approval_pcd_by + ' • ' + po.approval_pcd_at) : '-',
          'Approval MKT (Final)': po.approval_mkt2_by ? (po.approval_mkt2_by + ' • ' + po.approval_mkt2_at) : '-',
        }));
        const ws = XLSX.utils.json_to_sheet(data);
        ws['!cols'] = [{ wch: 18 }, { wch: 12 }, { wch: 16 }, { wch: 12 }, { wch: 28 }, { wch: 28 }, { wch: 28 }];
        const range = XLSX.utils.decode_range(ws['!ref']);
        for (let c = range.s.c; c <= range.e.c; c++) {
          const addr = XLSX.utils.encode_cell({ r: 0, c });
          if (ws[addr]) ws[addr].s = { font: { bold: true, color: { rgb: 'FFFFFF' } }, fill: { fgColor: { rgb: '5F6FFF' } }, alignment: { horizontal: 'center' } };
        }
        ws['!autofilter'] = { ref: ws['!ref'] };
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Rekap PO Selesai');
        XLSX.writeFile(wb, `rekap-po-selesai-${new Date().toISOString().split('T')[0]}.xlsx`, { cellStyles: true });
        showToast('Excel berhasil didownload!');
      } catch (err) {
        showToast('Gagal export: ' + err.message, 'error');
      } finally {
        loadingExportPO.value = false;
      }
    }


    function exportExcelStok() {
      try {
        if (!partsList.value.length) { showToast('Data part kosong!', 'error'); return; }
        const data = partsList.value.map(p => ({ 'Model': p.model || '-', 'Commodity': p.commodity, 'Part Name': p.part_name, 'Part Number': p.part_number, 'Supplier': p.supplier || '-', 'Sisa Stok': p.stock, 'Minimal Stok': p.min_stock, 'Reject': p.total_reject || 0, 'Status': p.stock === 0 ? 'HABIS' : p.stock <= p.min_stock ? 'KRITIS' : 'OK' }));
        const ws = XLSX.utils.json_to_sheet(data);
        ws['!cols'] = [{ wch:10 },{ wch:20 },{ wch:45 },{ wch:20 },{ wch:12 },{ wch:14 },{ wch:10 }];
        const wb = XLSX.utils.book_new(); XLSX.utils.book_append_sheet(wb, ws, 'Stok Part');
        XLSX.writeFile(wb, `rekap-stok-${new Date().toISOString().split('T')[0]}.xlsx`);
        showToast('Excel berhasil didownload!');
      } catch (err) { showToast('Gagal export: ' + err.message, 'error'); }
    }

    function shareWA(h) {
      const tipe = h.type === 'masuk' ? '📥 *BARANG MASUK*' : '📤 *BARANG KELUAR*';
      const ket = h.type === 'masuk' ? (h.supplier ? '🏭 Supplier : ' + h.supplier : '') : (h.tujuan ? '🎯 Tujuan   : ' + h.tujuan : '');
      const qc = h.status_qc ? '\n✅ QC      : ' + h.status_qc : '';
      const note = h.keterangan ? '\n📌 Ket     : ' + h.keterangan : '';
      const by = h.input_by ? '\n👤 By      : ' + h.input_by : '';
      const time = h.time ? ' ' + h.time : '';
      const msg = tipe + '\n' + '━━━━━━━━━━━━━━━━━━\n' + '🔢 PN      : ' + h.part_number + '\n' + '📝 Nama    : ' + h.part_name + '\n' + '📦 Qty     : ' + h.qty_ok + ' pcs\n' + '📅 Tgl     : ' + h.date + time + (ket ? '\n' + ket : '') + qc + note + by + '\n' + '━━━━━━━━━━━━━━━━━━\n' + '_Control Stock App_';
      window.open('https://wa.me/?text=' + encodeURIComponent(msg), '_blank');
    }

    onMounted(() => { masuk.value.date = today(); keluar.value.date = today(); loadAll(); loadOpenPOList(); loadPOList(); });

    return {
      token, currentUser, isDark, page, inputTab, partsList, historyList, toast,
      masuk, keluar, suggests, loadingMasuk, loadingKeluar,
      newPart, loadingNewPart, partSearch, partModelFilter, historyFilter, partsColspan,
      auditFilter, activityLogs, loadingAuditLogs, loadActivityLogs, formatDateTime,
      sjHistory, loadingSJHistory, loadSJHistory, downloadSJAgain,
      loadingExportAudit, exportAuditExcel,
      priceModal, openPriceModal, submitPriceUpdate, formatRupiah, formatDate,
      importPriceFileInput, loadingImportPrice, handleImportPriceExcel, downloadPriceTemplate,
      timeStr, dateStr, statBeforeQC, statAfterQC,
      isAdmin, isMarketing, isPcd, canManage, canSeePrice, statOk, statCrit, statEmpty, statTotalReject, kritisItems, habisItems,
      poBelumClose, poBelumCloseCount, poClosedCount,
      partModels, filteredParts, pageTitle, greeting,
      openPOList, poList, poDetail, loadingPO, newPO, poItemsForSelectedPO,
      keluarPoItemQuery, poItemSuggests, selectedPOItem, searchPOItemSuggest, selectPOItem,
      logout, toggleTheme, goPage, searchSuggest, selectPart, poSuggests, searchPOSuggest, selectPOPart,
      submitMasuk, submitKeluar, submitTambahPart,
      addPOItemRow, removePOItemRow, submitPO, loadOpenPOList, loadPOList, viewPODetail, poStatusBadge,
      poApprovalBadge, canApprovePO, approvePO, loadingApprovePO,
      deletePart, deleteHistory, exportCSV, exportExcelStok, loadHistory, shareWA,
      exportPORekap, loadingExportPO,
      importFileInput, loadingImport, handleImportFile,
      importPOFileInput, loadingImportPO, handleImportPOItems,
      selectedIds, sjModal, allSelected, toggleSelectAll, openSJModal, submitSJModal, quickDownloadSJ,
      qcModal, openQcModal, submitQcModal,
    };
  }
}).mount('#app');