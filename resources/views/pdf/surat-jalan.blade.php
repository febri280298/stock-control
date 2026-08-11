<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  @page { margin: 25px 30px; }
  body { font-family: 'Calibri', sans-serif; font-size: 11px; color: #000; }
  table { border-collapse: collapse; width: 100%; }
  .header-table td { vertical-align: top; border: none; padding: 0; }
  .box { border: 1px solid #000; }
  .box td, .box th { border: 1px solid #000; padding: 3px 6px; }
  .title { font-size: 26px; font-weight: bold; text-align: center; text-decoration: underline; margin: 6px 0 2px; }
  .subno { text-align: center; font-weight: bold; font-size: 13px; margin-bottom: 10px; }
  .company { font-size: 15px; font-weight: bold; margin-bottom: 4px; }
  .meta-box { font-size: 10px; }
  .meta-box td { border: 1px solid #000; padding: 2px 5px; }
  .info-row td { padding: 3px 0; font-size: 11px; }
  .item-table { margin-top: 10px; }
  .item-table th { background: #eee; font-size: 10px; text-align: center; border: 1px solid #000; padding: 5px 3px; }
  .item-table td { border: 1px solid #000; padding: 5px 4px; font-size: 10px; text-align: center; height: 20px; }
  .item-table td.left { text-align: left; }
  .footer-table { margin-top: 30px; }
  .footer-table td { border: 1px solid #000; width: 25%; vertical-align: top; padding: 0; }
  .footer-table .lbl { text-align: center; font-weight: bold; font-size: 10px; border-bottom: 1px solid #000; padding: 4px; }
  .footer-table .sub { text-align: center; font-size: 9px; border-bottom: 1px solid #000; padding: 3px; }
  .footer-table .space { height: 55px; }
</style>
</head>
<body>

  <table class="header-table">
    <tr>
      <td style="width:55%;">
        <div class="company">PT. BONECOM TRICOM</div>
        <table class="meta-box box" style="width:200px;">
            <tr><td colspan="2" style="text-align:center; font-weight:bold;">SECURITY CUSTOMER</td></tr>
            <tr><td style="width:60px; height:35px;">DATE</td><td rowspan="2" style="height:55px;">&nbsp;</td></tr>
            <tr><td style="height:20px;">&nbsp;</td></tr>
            <tr><td style="height:35px;">TIME</td><td rowspan="2" style="height:55px;">&nbsp;</td></tr>
            <tr><td style="height:20px;">&nbsp;</td></tr>
    </table>
      </td>
      <td style="width:45%; text-align:right;">
        <table class="meta-box box" style="width:100%;">
          <tr><td style="width:60px;">NO</td><td>: FM-PCD-002</td></tr>
          <tr><td>REVISI</td><td>: 00 / 08-06-2020</td></tr>
        </table>
      </td>
    </tr>
  </table>

  <div class="title">SURAT JALAN</div>
  <div class="subno">NO : <span style="display:inline-block; border-bottom:1px solid #000; width:220px;">&nbsp;</span></div>

  <table class="info-row">
    <tr><td style="width:100px;">DELIVERY TO</td><td>: {{ $delivery_to ?: '-' }}</td></tr>
    <tr><td>DATE</td><td>: {{ $date }}</td></tr>
  </table>

  <table class="item-table">
    <thead>
      <tr>
        <th style="width:5%;">NO</th>
        <th style="width:30%;">PART NAME</th>
        <th style="width:18%;">PART NO</th>
        <th style="width:10%;">UNIQ</th>
        <th style="width:10%;">QTY</th>
        <th style="width:12%;">SATUAN</th>
        <th style="width:15%;">KET</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($items as $i => $item)
        <tr>
          <td>{{ $i + 1 }}</td>
          <td class="left">{{ $item->part->part_name ?? '-' }}</td>
          <td>{{ $item->part->part_number ?? '-' }}</td>
          <td>-</td>
          <td>{{ $item->qty }}</td>
          <td>PCS</td>
          <td>{{ $item->type === 'masuk' ? ($item->supplier ?? '-') : ($item->tujuan ?? '-') }}</td>
        </tr>
      @endforeach
      {{-- baris kosong biar rapi kalau item dikit --}}
      @for ($j = count($items); $j < 8; $j++)
        <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
      @endfor
    </tbody>
  </table>

  <table class="footer-table">
    <tr>
      <td><div class="lbl">CUSTOMER</div><div class="sub">RECEIVING</div><div class="space"></div></td>
      <td><div class="lbl">DELIVERY</div><div class="sub">PREPARED</div><div class="sub" style="border-bottom:none;">KEND. NO :</div><div class="space" style="height:35px;"></div></td>
      <td><div class="lbl">SUPPLIER</div><div class="sub">SECURITY</div><div class="space"></div></td>
      <td><div class="lbl">SUPPLIER</div><div class="sub">PREPARED</div><div class="space"></div></td>
    </tr>
  </table>

</body>
</html>