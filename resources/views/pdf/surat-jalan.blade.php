{{--
  Surat Jalan — reproduksi form resmi FM-PCD-002 rev.00 (08-06-2020).
  Semua posisi dalam pt, diambil dari geometri asli surat-jalan-template.pdf
  (Letter 612x792). Jangan ubah angka koordinat tanpa mengukur ulang PDF-nya.
--}}
@php
    $rowH   = 20;      // tinggi baris item
    $bodyH  = 448.4;   // tinggi area isi tabel (475.5 - 27.1 header)
    $fill   = max(0, $bodyH - (count($items) * $rowH));
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  @page { margin: 0; }
  body {
    margin: 0;
    font-family: Helvetica, Arial, sans-serif;
    font-size: 8pt;
    color: #000;
  }
  .abs { position: absolute; }
  table { border-collapse: collapse; }
  .bx td, .bx th { border: 0.8pt solid #000; }

  /* ---------- kop ---------- */
  .company  { left: 40.9pt; top: 24pt;   font-size: 10.5pt; font-weight: bold; }
  .seccust  { left: 40.9pt; top: 39pt;   width: 124.1pt; font-size: 7pt; }
  .seccust td { padding: 1pt 3pt; }
  .norev    { left: 389.6pt; top: 23.5pt; width: 141.2pt; font-size: 7.5pt; }
  .norev td { padding: 1pt 4pt; }

  .title    { left: 205.6pt; top: 72pt;  width: 183.1pt; text-align: center;
              font-size: 21pt; font-weight: bold; text-decoration: underline; }
  .nobox    { left: 208.7pt; top: 108.8pt; width: 169.3pt; height: 22.9pt;
              border: 0.8pt solid #000; font-size: 9pt; font-weight: bold; }
  .nobox span { display: block; padding: 6pt 0 0 8pt; }

  /* ---------- baris info ---------- */
  .info { font-size: 8.5pt; }
  .info td { padding: 1pt 0; }
  .info .lbl { width: 66pt; }
  .info .sep { width: 10pt; }
  .info-l { left: 76pt;  top: 160pt; }
  .info-r { left: 361pt; top: 160pt; }

  /* ---------- tabel item ---------- */
  /* Sengaja auto-layout: table-layout:fixed diabaikan dompdf (kolom jadi
     rata semua). Lebar dijaga lewat <th> + PdfText::fit() yang memastikan
     tidak ada kata melebihi lebar kolomnya. */
  .items { left: 28.2pt; top: 188.3pt; width: 516pt; }
  .items th {
    border: 0.8pt solid #000;
    height: 27.1pt;
    font-size: 8pt;
    font-weight: normal;
    text-align: center;
    vertical-align: middle;
  }
  .items td {
    border-left: 0.8pt solid #000;
    border-right: 0.8pt solid #000;
    border-top: none;
    border-bottom: none;
    height: {{ $rowH }}pt;
    font-size: 8.5pt;
    padding: 0 3pt;
    vertical-align: middle;
    word-wrap: break-word;
    overflow-wrap: break-word;
  }
  .items td.c { text-align: center; }
  .items tr.fill td { border-bottom: 0.8pt solid #000; height: {{ $fill }}pt; }

  /* ---------- blok tanda tangan ---------- */
  .sign { top: 676pt; width: 83pt; font-size: 7pt; }
  .sign .cap { text-align: center; padding-bottom: 1pt; border-bottom: 0.8pt solid #000; }
  .sign table { width: 83pt; margin-top: 3pt; }
  .sign td { border: 0.8pt solid #000; padding: 1pt 3pt; }
  .sign td.h { text-align: center; height: 12pt; }
  .sign td.kend  { height: 12pt; }
  .sign td.blank { height: 37pt; }
  .sign td.foot  { height: 12pt; }
</style>
</head>
<body>

  {{-- ===== KOP KIRI ===== --}}
  <div class="abs company">PT. BONECOM TRICOM</div>

  <table class="abs seccust bx">
    <tr><td colspan="2" style="text-align:center; height:13pt;">SECURITY CUSTOMER</td></tr>
    <tr><td style="width:43pt; height:20pt;">DATE</td><td rowspan="2" style="width:81pt;">&nbsp;</td></tr>
    <tr><td style="height:20pt;">&nbsp;</td></tr>
    <tr><td style="height:20pt;">TIME</td><td rowspan="2">&nbsp;</td></tr>
    <tr><td style="height:21.6pt;">&nbsp;</td></tr>
  </table>

  {{-- ===== KOP KANAN ===== --}}
  <table class="abs norev bx">
    <tr><td style="width:61pt;">NO</td><td style="width:80pt;">: FM - PCD -002</td></tr>
    <tr><td>REVISI</td><td>: 00 / 08-06-2020</td></tr>
  </table>

  {{-- ===== JUDUL ===== --}}
  <div class="abs title">SURAT JALAN</div>
  <div class="abs nobox"><span>NO : {{ $no ?? '' }}</span></div>

  {{-- ===== INFO ===== --}}
  <table class="abs info info-l">
    <tr><td class="lbl">DELIVERY</td><td class="sep">:</td><td>{{ $delivery_to ?: '' }}</td></tr>
    <tr><td class="lbl">DATE</td><td class="sep">:</td><td>{{ $date }}</td></tr>
  </table>

  <table class="abs info info-r">
    <tr><td class="lbl">PROJECT</td><td class="sep">:</td><td>{{ $project ?? '' }}</td></tr>
    <tr><td class="lbl">NO. PO</td><td class="sep">:</td><td>{{ $no_po ?? '' }}</td></tr>
  </table>

  {{-- ===== TABEL ITEM ===== --}}
  <table class="abs items">
    <thead>
      <tr>
        <th style="width:30pt;">NO</th>
        <th style="width:158.9pt;">PART NAME</th>
        <th style="width:134.2pt;">PART NO</th>
        <th style="width:48pt;">UNIQ</th>
        <th style="width:48pt;">QTY</th>
        <th style="width:48pt;">SATUAN</th>
        <th style="width:48.1pt;">KET</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($items as $i => $item)
        @php
            // Lebar pakai = lebar kolom dikurangi padding kiri-kanan (3pt + 3pt).
            [$nama, $szNama] = \App\Support\PdfText::fit($item->part->part_name   ?? '-', 152.9);
            [$pnum, $szPnum] = \App\Support\PdfText::fit($item->part->part_number ?? '-', 128.2);
            $ketRaw = $item->type === 'masuk' ? $item->supplier : $item->tujuan;
            [$ket,  $szKet]  = \App\Support\PdfText::fit($ketRaw ?: '-', 42.1);
        @endphp
        <tr>
          <td class="c">{{ $i + 1 }}</td>
          <td style="font-size:{{ $szNama }}pt; line-height:9pt;">{{ $nama }}</td>
          <td style="font-size:{{ $szPnum }}pt; line-height:9pt;">{{ $pnum }}</td>
          <td class="c"></td>
          <td class="c">{{ $item->qty }}</td>
          <td class="c">PCS</td>
          <td style="font-size:{{ $szKet }}pt; line-height:9pt;">{{ $ket }}</td>
        </tr>
      @endforeach
      <tr class="fill">
        <td></td><td></td><td></td><td></td><td></td><td></td><td></td>
      </tr>
    </tbody>
  </table>

  {{-- ===== TANDA TANGAN ===== --}}
  <div class="abs sign" style="left:40.9pt;">
    <div class="cap">CUSTOMER</div>
    <table><tr><td class="h">RECEIVING</td></tr><tr><td class="blank"></td></tr><tr><td class="foot"></td></tr></table>
  </div>

  <div class="abs sign" style="left:173.2pt;">
    <div class="cap">DELIVERY</div>
    <table><tr><td class="h">PREPARED</td></tr><tr><td class="kend">KEND. NO :</td></tr><tr><td class="blank" style="height:23pt;"></td></tr><tr><td class="foot"></td></tr></table>
  </div>

  <div class="abs sign" style="left:297.2pt;">
    <div class="cap">SUPPLIER</div>
    <table><tr><td class="h">SECURITY</td></tr><tr><td class="blank"></td></tr><tr><td class="foot"></td></tr></table>
  </div>

  <div class="abs sign" style="left:437.6pt;">
    <div class="cap">SUPPLIER</div>
    <table><tr><td class="h">PREPARED</td></tr><tr><td class="blank"></td></tr><tr><td class="foot"></td></tr></table>
  </div>

</body>
</html>
