<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rekap {{ $bidang->name }} - {{ $period_label }}</title>
<style>
  @page { margin: 26px 26px 36px 26px; }
  * { box-sizing: border-box; }
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111827; margin: 0; }
  .muted { color: #6b7280; }
  .header { border-bottom: 1px solid #e5e7eb; padding-bottom: 10px; margin-bottom: 14px; text-align: center; }
  .header h1 { margin: 0; font-size: 20px; letter-spacing: 1px; }
  .header h2 { margin: 4px 0 0 0; font-size: 13px; color: #374151; font-weight: normal; }
  .section { margin-top: 18px; }
  .section h3 { font-size: 14px; font-weight: 700; margin: 0 0 8px 0; color: #0c4a6e; border-bottom: 1px solid #e0f2fe; padding-bottom: 4px; }
  .kv-row { display: table; width: 100%; padding: 3px 0; }
  .kv-key { display: table-cell; }
  .kv-val { display: table-cell; text-align: right; font-weight: 700; width: 80px; }
  .total-row { border-top: 2px solid #0c4a6e; margin-top: 6px; padding-top: 6px; font-size: 14px; }
  .total-row .kv-key { color: #0c4a6e; font-weight: 700; }
  .total-row .kv-val { color: #0c4a6e; }
  .footer-page { position: fixed; bottom: -22px; left: 0; right: 0; text-align: center; color: #2563eb; font-size: 10px; font-weight: 700; }
  .photo-grid { width: 100%; }
  .photo-grid img { width: 56px; height: 56px; object-fit: cover; margin: 1px; border-radius: 2px; border: 1px solid #e5e7eb; }
  .page-break { page-break-before: always; }
  table.t { width: 100%; border-collapse: collapse; margin-top: 6px; }
  table.t th { background: #1e293b; color: #fff; font-size: 11px; padding: 6px; text-align: left; }
  table.t td { border-bottom: 1px solid #e5e7eb; padding: 6px; vertical-align: top; font-size: 10.5px; }
  table.t tr.alt td { background: #f9fafb; }
  table.t th.center, table.t td.center { text-align: center; }
</style>
</head>
<body>

<div class="header">
  <h1>{{ strtoupper($bidang->name) }}</h1>
  <h2>Tanggal : {{ $period_label }}</h2>
</div>

@if($totals['items'] > 0)
  <div class="section">
    <h3>Rekap Per Loket</h3>
    @forelse($by_loket as $name => $count)
      <div class="kv-row">
        <div class="kv-key">{{ $name }}</div>
        <div class="kv-val">{{ $count }}</div>
      </div>
    @empty
      <div class="muted">Tidak ada data loket.</div>
    @endforelse
  </div>

  <div class="section">
    <h3>Rekap Per Jenis Layanan / Keperluan</h3>
    @forelse($by_jenis as $name => $count)
      <div class="kv-row">
        <div class="kv-key">{{ $name }}</div>
        <div class="kv-val">{{ $count }}</div>
      </div>
    @empty
      <div class="muted">Tidak ada data jenis layanan.</div>
    @endforelse
  </div>

  <div class="section">
    <div class="kv-row total-row">
      <div class="kv-key">Jumlah Total Pemohon / Aktivitas</div>
      <div class="kv-val">{{ $totals['items'] }}</div>
    </div>
    @if($totals['violations'] > 0)
      <div class="kv-row">
        <div class="kv-key" style="color:#dc2626;">Jumlah Pelanggaran</div>
        <div class="kv-val" style="color:#dc2626;">{{ $totals['violations'] }}</div>
      </div>
    @endif
  </div>

  <div class="section">
    <h3>Rincian</h3>
    <table class="t">
      <thead>
        <tr>
          <th class="center" style="width: 28px;">No</th>
          <th style="width: 70px;">Tanggal</th>
          <th>Kategori / Jenis Layanan</th>
          <th>Pemohon / Catatan</th>
          <th style="width: 80px;">Loket</th>
          <th style="width: 130px;">Foto</th>
        </tr>
      </thead>
      <tbody>
        @php $rowIdx = 0; @endphp
        @foreach($reports as $rep)
          @foreach($rep->items as $item)
            @php $rowIdx++; @endphp
            <tr class="{{ $rowIdx % 2 == 0 ? 'alt' : '' }}">
              <td class="center">{{ $rowIdx }}</td>
              <td>{{ \Illuminate\Support\Carbon::parse($rep->report_date)->format('d/m/Y') }}</td>
              <td>
                <div style="font-weight:600;">{{ $rep->category?->name ?? '-' }}</div>
                @if($item->jenisLayanan?->name)
                  <div class="muted">{{ $item->jenisLayanan->name }}</div>
                @elseif($item->purpose)
                  <div class="muted">{{ $item->purpose }}</div>
                @endif
              </td>
              <td>
                @if($item->applicant_name)
                  <div>{{ $item->applicant_name }}{{ $item->gender ? ' ('.$item->gender.')' : '' }}</div>
                  @if($item->company)<div class="muted">{{ $item->company }}</div>@endif
                  @if($item->complaint)<div class="muted">Aduan: {{ \Illuminate\Support\Str::limit($item->complaint, 80) }}</div>@endif
                @else
                  <div>{{ $item->location ?: '-' }}</div>
                  @if($item->notes)<div class="muted">{{ \Illuminate\Support\Str::limit($item->notes, 80) }}</div>@endif
                @endif
              </td>
              <td>{{ $item->loket?->name ?: '-' }}</td>
              <td>
                @if($item->photos && $item->photos->count() > 0)
                  <div class="photo-grid">
                    @foreach($item->photos as $photo)
                      @php $abs = storage_path('app/public/' . $photo->path); @endphp
                      @if(file_exists($abs))
                        <img src="{{ $abs }}">
                      @endif
                    @endforeach
                  </div>
                @else
                  <span class="muted">—</span>
                @endif
              </td>
            </tr>
          @endforeach
        @endforeach
      </tbody>
    </table>
  </div>
@else
  <div class="section">
    <div class="muted" style="text-align: center; padding: 40px 0;">Tidak ada aktivitas pada periode tersebut.</div>
  </div>
@endif

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
