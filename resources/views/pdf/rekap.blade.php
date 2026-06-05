<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rekap Laporan {{ $period_label }}</title>
<style>
  @page { margin: 26px 26px 36px 26px; }
  * { box-sizing: border-box; }
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #111827; margin: 0; }
  .muted { color: #6b7280; }
  .header { border-bottom: 1px solid #e5e7eb; padding-bottom: 10px; margin-bottom: 14px; text-align: center; }
  .header h1 { margin: 0; font-size: 18px; letter-spacing: 1px; }
  .header h2 { margin: 4px 0 0 0; font-size: 13px; color: #374151; }
  .grid { display: table; width: 100%; border-spacing: 8px; margin-bottom: 8px; }
  .row { display: table-row; }
  .col { display: table-cell; width: 25%; vertical-align: top; }
  .card { border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px 10px; background: #fff; text-align: center; }
  .card .label { color: #6b7280; font-size: 9.5px; }
  .card .value { font-size: 18px; font-weight: 700; margin-top: 2px; }
  table.t { width: 100%; border-collapse: collapse; margin-top: 6px; }
  table.t th { background: #1e293b; color: #fff; font-size: 10.5px; padding: 6px; text-align: left; }
  table.t td { border-bottom: 1px solid #e5e7eb; padding: 6px; vertical-align: top; font-size: 10.5px; }
  table.t tr.alt td { background: #f9fafb; }
  table.t th.center, table.t td.center { text-align: center; }
  table.t th.right, table.t td.right { text-align: right; }
  .bidang-title { background: #0ea5e9; color: #fff; padding: 6px 10px; font-weight: 700; border-radius: 4px 4px 0 0; margin-top: 18px; }
  .footer-page { position: fixed; bottom: -22px; left: 0; right: 0; text-align: center; color: #2563eb; font-size: 10px; font-weight: 700; }
  .small-pill { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 9px; background: #e2e8f0; color: #475569; }
  .pill-service { background: #e0f2fe; color: #075985; }
  .photo-grid { width: 100%; }
  .photo-grid img { width: 56px; height: 56px; object-fit: cover; margin: 1px; border-radius: 2px; border: 1px solid #e5e7eb; }
  .page-break { page-break-before: always; }
</style>
</head>
<body>

<div class="header">
  <h1>REKAP LAPORAN HARIAN DPMPTSP</h1>
  <h2>{{ $period_label }}</h2>
</div>

<div class="grid">
  <div class="row">
    <div class="col"><div class="card"><div class="label">Total Laporan</div><div class="value">{{ $totals['reports'] }}</div></div></div>
    <div class="col"><div class="card"><div class="label">Total Item / Entri</div><div class="value">{{ $totals['items'] }}</div></div></div>
    <div class="col"><div class="card"><div class="label">Total Pelanggaran</div><div class="value" style="color:#dc2626;">{{ $totals['violations'] }}</div></div></div>
    <div class="col"><div class="card"><div class="label">Laporan Pelayanan</div><div class="value" style="color:#0284c7;">{{ $totals['service'] }}</div></div></div>
  </div>
</div>

@forelse($by_bidang as $bidangName => $list)
  <div class="bidang-title">{{ $bidangName }} <span style="font-weight:400; opacity:.85;">({{ $list->count() }} laporan)</span></div>
  <table class="t">
    <thead>
      <tr>
        <th class="center" style="width: 26px;">No</th>
        <th style="width: 76px;">Tanggal</th>
        <th>Judul / Kategori</th>
        <th>Pembuat</th>
        <th class="center" style="width: 40px;">Item</th>
        <th class="center" style="width: 60px;">Pelanggaran</th>
        <th class="center" style="width: 44px;">Status</th>
      </tr>
    </thead>
    <tbody>
      @foreach($list as $idx => $r)
        <tr class="{{ $idx % 2 == 0 ? '' : 'alt' }}">
          <td class="center">{{ $idx + 1 }}</td>
          <td>{{ \Illuminate\Support\Carbon::parse($r->report_date)->format('d/m/Y') }}</td>
          <td>
            <div style="font-weight:600;">{{ $r->title }}</div>
            <div class="muted">
              <span class="small-pill {{ $r->category?->is_service ? 'pill-service' : '' }}">{{ $r->category?->name ?? '-' }}</span>
            </div>
          </td>
          <td>{{ $r->user?->name }}</td>
          <td class="center">{{ $r->items->count() }}</td>
          <td class="center" style="color:#dc2626;">{{ $r->violations_count }}</td>
          <td class="center">
            @if($r->status === 'final')
              <span class="small-pill" style="background:#dcfce7;color:#166534;">final</span>
            @else
              <span class="small-pill" style="background:#fef3c7;color:#92400e;">draft</span>
            @endif
          </td>
        </tr>
        @if($r->category?->is_service && $r->items->count())
          @php
            $byLoket = $r->items->groupBy(fn ($i) => $i->loket?->name ?: '-')->map->count()->sortDesc();
            $byJL = $r->items->groupBy(fn ($i) => $i->jenisLayanan?->name ?: ($i->purpose ?: '-'))->map->count()->sortDesc();
          @endphp
          <tr class="{{ $idx % 2 == 0 ? '' : 'alt' }}">
            <td></td>
            <td colspan="6" style="padding-top:0;">
              <div class="muted" style="margin-bottom:2px;">Rekap Loket:</div>
              <div>
                @foreach($byLoket as $name => $count)
                  <span class="small-pill" style="margin-right:4px;">{{ $name }}: <b>{{ $count }}</b></span>
                @endforeach
              </div>
              <div class="muted" style="margin: 4px 0 2px;">Rekap Jenis Layanan / Keperluan:</div>
              <div>
                @foreach($byJL as $name => $count)
                  <span class="small-pill" style="margin-right:4px; background:#e0f2fe; color:#075985;">{{ $name }}: <b>{{ $count }}</b></span>
                @endforeach
              </div>
            </td>
          </tr>
        @endif
        @php
          $reportPhotoItems = $r->items->filter(fn ($i) => $i->photos && $i->photos->count() > 0);
        @endphp
        @if($reportPhotoItems->count() > 0)
          <tr class="{{ $idx % 2 == 0 ? '' : 'alt' }}">
            <td></td>
            <td colspan="6" style="padding-top: 2px;">
              <div class="muted" style="margin-bottom:3px;">Foto Aktivitas:</div>
              @foreach($reportPhotoItems as $item)
                @php
                  $label = $item->applicant_name ?: ($item->location ?: 'Aktivitas');
                @endphp
                <div style="margin-top: 4px;">
                  <div style="font-size: 10px;">
                    <b>{{ \Illuminate\Support\Str::limit($label, 60) }}</b>{{ $item->loket?->name ? ' • '.$item->loket->name : '' }}
                  </div>
                  <div class="photo-grid">
                    @foreach($item->photos as $photo)
                      @php $abs = storage_path('app/public/' . $photo->path); @endphp
                      @if(file_exists($abs))
                        <img src="{{ $abs }}">
                      @endif
                    @endforeach
                  </div>
                </div>
              @endforeach
            </td>
          </tr>
        @endif
      @endforeach
    </tbody>
  </table>
@empty
  <div class="muted" style="text-align:center; margin-top: 30px;">Tidak ada laporan pada periode tersebut.</div>
@endforelse

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
