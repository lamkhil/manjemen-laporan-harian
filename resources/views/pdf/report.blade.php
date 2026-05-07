<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>{{ $report->title }} - {{ $report->report_date->format('d M Y') }}</title>
<style>
  @page { margin: 28px 28px 36px 28px; }
  * { box-sizing: border-box; }
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #111827; margin: 0; }
  .muted { color: #6b7280; }
  .header { display: table; width: 100%; border-bottom: 1px solid #e5e7eb; padding-bottom: 10px; margin-bottom: 14px; }
  .header .logo { display: table-cell; width: 86px; vertical-align: middle; }
  .header .title { display: table-cell; vertical-align: middle; text-align: center; }
  .header .title h1 { margin: 0; font-size: 18px; letter-spacing: 1px; }
  .header .title h2 { margin: 2px 0 0 0; font-size: 14px; }
  .header .title .date { margin-top: 6px; font-size: 11px; color: #374151; }
  .grid { display: table; width: 100%; border-spacing: 8px; }
  .row { display: table-row; }
  .col { display: table-cell; width: 50%; vertical-align: top; }
  .card { border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px 12px; background: #fff; }
  .card .label { color: #6b7280; font-size: 10px; }
  .card .value { font-size: 22px; font-weight: 700; margin-top: 4px; }
  .card .value.sm { font-size: 14px; }
  .card .icon { float: right; width: 24px; height: 24px; border-radius: 50%; text-align: center; line-height: 24px; font-size: 12px; }
  .icon-blue { background: #dbeafe; color: #1d4ed8; }
  .icon-red  { background: #fee2e2; color: #b91c1c; }
  .icon-green{ background: #d1fae5; color: #065f46; }
  .icon-amber{ background: #fef3c7; color: #92400e; }
  .icon-violet{ background: #ede9fe; color: #5b21b6; }
  .icon-pink { background: #fce7f3; color: #9d174d; }
  .section { margin-top: 14px; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; }
  .section h3 { margin: 0 0 10px 0; font-size: 13px; }
  .bar-row { display: table; width: 100%; margin-bottom: 6px; }
  .bar-label { display: table-cell; width: 35%; font-size: 11px; color: #374151; padding-right: 8px; vertical-align: middle; }
  .bar-track { display: table-cell; vertical-align: middle; }
  .bar { height: 14px; border-radius: 3px; }
  .bar-count { display: table-cell; width: 30px; text-align: right; font-weight: 700; padding-left: 8px; vertical-align: middle; }
  .donut-wrap { display: table; width: 100%; }
  .donut-cell { display: table-cell; vertical-align: middle; width: 130px; }
  .donut-text { display: table-cell; vertical-align: middle; padding-left: 14px; }
  .pie { width: 110px; height: 110px; border-radius: 50%;
    background: conic-gradient(#ef4444 0 {{ $stats['violation_pct'] }}%, #e5e7eb {{ $stats['violation_pct'] }}% 100%);
    position: relative;
  }
  .pie::after { content: ''; position: absolute; top: 14px; left: 14px; right: 14px; bottom: 14px; background: #fff; border-radius: 50%; }
  .pie .pct { position: absolute; width: 100%; top: 38%; text-align: center; font-weight: 700; z-index: 2; }
  table.items { width: 100%; border-collapse: collapse; margin-top: 10px; page-break-inside: auto; }
  table.items th { background: #111827; color: #fff; font-size: 11px; padding: 8px; text-align: left; }
  table.items th.center, table.items td.center { text-align: center; }
  table.items td { border-bottom: 1px solid #e5e7eb; padding: 8px; vertical-align: top; font-size: 10.5px; }
  table.items tr.alt td { background: #f9fafb; }
  table.items tr { page-break-inside: avoid; }
  .photo-grid { width: 100%; }
  .photo-grid img { width: 56px; height: 56px; object-fit: cover; margin: 1px; border-radius: 2px; }
  .footer-page { position: fixed; bottom: -22px; left: 0; right: 0; text-align: center; color: #2563eb; font-size: 10px; font-weight: 700; }
  .page-break { page-break-before: always; }
  .pre { white-space: pre-wrap; }
  .h-section { font-size: 14px; font-weight: 700; text-align: center; margin: 6px 0 10px; letter-spacing: 0.5px; }
</style>
</head>
<body>

<div class="header">
  <div class="logo">
    <svg width="68" height="68" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
      <circle cx="50" cy="50" r="48" fill="#1e3a8a" stroke="#fbbf24" stroke-width="3"/>
      <text x="50" y="58" text-anchor="middle" font-size="42" fill="#fbbf24" font-family="serif" font-weight="bold">S</text>
    </svg>
  </div>
  <div class="title">
    <h1>{{ strtoupper($report->title ?: 'LAPORAN PENGAWASAN KEWILAYAHAN') }}</h1>
    @if($report->kecamatan)
      <h2>KECAMATAN {{ strtoupper($report->kecamatan) }}</h2>
    @elseif($report->subtitle)
      <h2>{{ strtoupper($report->subtitle) }}</h2>
    @endif
    <div class="date">{{ $tanggal_label }}, (Shift {{ $report->shift }}){{ $jam_label }}</div>
  </div>
</div>

<div class="grid">
  <div class="row">
    <div class="col"><div class="card"><span class="icon icon-blue">L</span><div class="label">Total Laporan</div><div class="value">{{ $stats['total_items'] }}</div></div></div>
    <div class="col"><div class="card"><span class="icon icon-red">!</span><div class="label">Total Pelanggaran</div><div class="value">{{ $stats['violations'] }}</div></div></div>
  </div>
  <div class="row">
    <div class="col"><div class="card"><span class="icon icon-green">P</span><div class="label">Total Lokasi Diawasi</div><div class="value">{{ $stats['locations'] }}</div></div></div>
    <div class="col"><div class="card"><span class="icon icon-amber">#</span><div class="label">Aktivitas Terbanyak</div><div class="value sm">{{ \Illuminate\Support\Str::limit($stats['top_activity'], 22) }}</div></div></div>
  </div>
  <div class="row">
    <div class="col"><div class="card"><span class="icon icon-violet">U</span><div class="label">PKL Ditertibkan</div><div class="value">{{ $stats['pkl'] }}</div></div></div>
    <div class="col"><div class="card"><span class="icon icon-pink">B</span><div class="label">Baliho/Banner Liar</div><div class="value">{{ $stats['baliho'] }}</div></div></div>
  </div>
</div>

<div class="section">
  <h3>Distribusi Jenis Kegiatan</h3>
  @php $palette = ['#3b82f6','#10b981','#f59e0b','#8b5cf6','#ec4899','#06b6d4','#ef4444','#84cc16']; @endphp
  @forelse($cat_bars as $i => $bar)
    <div class="bar-row">
      <div class="bar-label">{{ \Illuminate\Support\Str::limit($bar['name'], 24) }}</div>
      <div class="bar-track"><div class="bar" style="width: {{ $bar['pct'] }}%; background: {{ $palette[$i % count($palette)] }};"></div></div>
      <div class="bar-count">{{ $bar['count'] }}</div>
    </div>
  @empty
    <div class="muted">Belum ada data.</div>
  @endforelse
</div>

<div class="section">
  <h3>Temuan Pelanggaran</h3>
  <div class="donut-wrap">
    <div class="donut-cell">
      <div class="pie"><div class="pct">{{ $stats['violation_pct'] }}%</div></div>
    </div>
    <div class="donut-text">
      <table style="width: 100%;">
        <tr><td class="muted">Total Pelanggaran:</td><td style="color:#dc2626; font-weight:700; font-size:16px;">{{ $stats['violations'] }}</td><td class="muted" style="padding-left:24px;">PKL: {{ $stats['pkl'] }}</td></tr>
        <tr><td class="muted">Dari Total Laporan:</td><td style="font-weight:700; font-size:16px;">{{ $stats['total_items'] }}</td><td class="muted" style="padding-left:24px;">Baliho: {{ $stats['baliho'] }}</td></tr>
      </table>
    </div>
  </div>
</div>

<div class="page-break"></div>
<div class="h-section">RINCIAN AKTIVITAS</div>

<table class="items">
  <thead>
    <tr>
      <th class="center" style="width: 32px;">No</th>
      <th style="width: 120px;">Jenis Kegiatan</th>
      <th style="width: 60px;">Waktu</th>
      <th style="width: 130px;">Lokasi</th>
      <th>Catatan</th>
      <th style="width: 130px;">Foto</th>
    </tr>
  </thead>
  <tbody>
    @forelse($items as $idx => $item)
      <tr class="{{ $idx % 2 == 0 ? '' : 'alt' }}">
        <td class="center">{{ $idx + 1 }}</td>
        <td>{{ $item->category?->name }}</td>
        <td>{{ $item->time ? substr($item->time, 0, 5) : '-' }}</td>
        <td>{{ $item->location }}</td>
        <td class="pre">{{ $item->notes }}</td>
        <td>
          <div class="photo-grid">
            @foreach($item->photos as $photo)
              @php
                $abs = storage_path('app/public/' . $photo->path);
              @endphp
              @if(file_exists($abs))
                <img src="{{ $abs }}">
              @endif
            @endforeach
          </div>
        </td>
      </tr>
    @empty
      <tr><td colspan="6" class="center muted">Belum ada item laporan.</td></tr>
    @endforelse
  </tbody>
</table>

<script type="text/php">
if (isset($pdf)) {
  $font = $fontMetrics->getFont("DejaVu Sans", "bold");
  $size = 9;
  $w = $pdf->get_width();
  $h = $pdf->get_height();
  $text = "Halaman {PAGE_NUM} dari {PAGE_COUNT}";
  $tw = $fontMetrics->getTextWidth("Halaman 99 dari 99", $font, $size);
  $x = ($w - $tw) / 2;
  $y = $h - 24;
  $pdf->page_text($x, $y, $text, $font, $size, [0.15, 0.39, 0.92]);
}
</script>

</body>
</html>
