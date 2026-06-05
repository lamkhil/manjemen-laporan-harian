import { useEffect, useMemo, useState } from 'react'
import { useParams } from 'react-router-dom'
import { api } from '../api/client'

const HARI = { 0:'Minggu',1:'Senin',2:'Selasa',3:'Rabu',4:'Kamis',5:'Jumat',6:'Sabtu' }
const BULAN = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']

function formatTanggal(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  if (isNaN(d.getTime())) return ''
  return `Hari ${HARI[d.getDay()]}, tanggal ${d.getDate()} ${BULAN[d.getMonth()]} ${d.getFullYear()}`
}

export default function ReportPrintPage() {
  const { id } = useParams()
  const [r, setR] = useState(null)

  useEffect(() => {
    api.get(`/reports/${id}`).then(({ data }) => setR(data))
  }, [id])

  const stats = useMemo(() => {
    if (!r) return null
    const items = r.items || []
    const isService = !!r.category?.is_service
    const violations = Number(r.violations_count || 0)
    const locations = new Set(items.map(i => i.location).filter(Boolean)).size
    const groups = items.reduce((acc, it) => { const k = it.category?.name || '-'; acc[k] = (acc[k] || 0) + 1; return acc }, {})
    const sorted = Object.entries(groups).sort((a,b) => b[1] - a[1])
    const top = sorted[0]?.[0] || '-'
    const max = sorted[0]?.[1] || 1
    const pkl = items.filter(i => i.category?.slug === 'penertiban-pedagang-liar').length
    const baliho = items.filter(i => i.category?.slug === 'baliho-banner-liar').length
    const pct = items.length ? Math.round(violations / items.length * 100) : 0

    const male = items.filter(i => i.gender === 'L').length
    const female = items.filter(i => i.gender === 'P').length
    const companies = new Set(items.map(i => i.company).filter(Boolean)).size
    const byPurposeMap = items.reduce((acc, it) => { const k = it.purpose || '-'; acc[k] = (acc[k] || 0) + 1; return acc }, {})
    const byPurpose = Object.entries(byPurposeMap).sort((a,b) => b[1] - a[1])
    const topPurpose = byPurpose[0]?.[0] || '-'
    const maxPurpose = byPurpose[0]?.[1] || 1

    return { items, isService, violations, locations, top, sorted, max, pkl, baliho, pct, male, female, companies, byPurpose, topPurpose, maxPurpose }
  }, [r])

  if (!r || !stats) return <div className="p-8 text-slate-500">Memuat…</div>

  const palette = ['#0ea5e9','#10b981','#f59e0b','#8b5cf6','#ec4899','#06b6d4','#ef4444','#84cc16']
  const jam = (r.time_start || r.time_end) ? ` Pukul ${(r.time_start || '').slice(0,5)} - ${(r.time_end || '').slice(0,5)}` : ''

  return (
    <div className="bg-slate-100 min-h-screen p-4 print:p-0 print:bg-white">
      <div className="no-print mb-3 max-w-5xl mx-auto flex gap-2 justify-end">
        <button onClick={() => window.print()} className="btn-primary">🖨️ Print / Save as PDF</button>
        <button onClick={() => window.close()} className="btn-ghost">Tutup</button>
      </div>

      <div className="bg-white max-w-5xl mx-auto shadow print:shadow-none p-8 print:p-6 text-slate-800" style={{ fontFamily: 'Arial, sans-serif' }}>

        <div className="flex items-center gap-4 border-b pb-3">
          <div className="w-16 h-16 rounded-full bg-brand-900 grid place-items-center shrink-0">
            <span className="text-amber-400 text-3xl font-bold">S</span>
          </div>
          <div className="flex-1 text-center">
            <h1 className="font-bold text-lg tracking-wide">{(r.title || 'LAPORAN HARIAN DPMPTSP').toUpperCase()}</h1>
            {r.category && <h2 className="font-bold text-base">KATEGORI: {r.category.name.toUpperCase()}</h2>}
            <div className="text-xs mt-1">{formatTanggal(r.report_date)}{jam}</div>
          </div>
        </div>

        {stats.isService ? (
          <>
            <div className="grid grid-cols-2 gap-3 mt-4">
              <Card label="Total Pemohon" value={stats.items.length} icon="🧑‍💼" tone="bg-sky-100 text-sky-700" />
              <Card label="Perusahaan Unik" value={stats.companies} icon="🏢" tone="bg-emerald-100 text-emerald-700" />
              <Card label="Laki-laki / Perempuan" value={`${stats.male} / ${stats.female}`} small icon="⚧" tone="bg-violet-100 text-violet-700" />
              <Card label="Keperluan Terbanyak" value={stats.topPurpose} small icon="📌" tone="bg-amber-100 text-amber-700" />
            </div>

            <div className="mt-5 border rounded-lg p-3">
              <div className="font-semibold mb-2">Distribusi Keperluan</div>
              {stats.byPurpose.length === 0 && <div className="text-sm text-slate-500">Belum ada data.</div>}
              {stats.byPurpose.slice(0, 8).map(([name, count], i) => (
                <div key={name} className="grid grid-cols-12 items-center gap-2 text-sm mb-1">
                  <div className="col-span-4 truncate">{name}</div>
                  <div className="col-span-7 bg-slate-100 h-3 rounded">
                    <div className="h-full rounded" style={{ width: `${Math.max(6, (count / stats.maxPurpose) * 100)}%`, background: palette[i % palette.length] }} />
                  </div>
                  <div className="col-span-1 text-right font-bold">{count}</div>
                </div>
              ))}
            </div>
          </>
        ) : (
          <>
            <div className="grid grid-cols-2 gap-3 mt-4">
              <Card label="Total Laporan" value={stats.items.length} icon="📄" tone="bg-blue-100 text-blue-700" />
              <Card label="Total Pelanggaran" value={stats.violations} icon="⚠️" tone="bg-red-100 text-red-700" />
              <Card label="Total Lokasi Diawasi" value={stats.locations} icon="📍" tone="bg-emerald-100 text-emerald-700" />
              <Card label="Aktivitas Terbanyak" value={stats.top} small icon="📊" tone="bg-amber-100 text-amber-700" />
              <Card label="PKL Ditertibkan" value={stats.pkl} icon="👤" tone="bg-violet-100 text-violet-700" />
              <Card label="Baliho/Banner Liar" value={stats.baliho} icon="🖼️" tone="bg-pink-100 text-pink-700" />
            </div>

            <div className="mt-5 border rounded-lg p-3">
              <div className="font-semibold mb-2">Distribusi Jenis Kegiatan</div>
              {stats.sorted.length === 0 && <div className="text-sm text-slate-500">Belum ada data.</div>}
              {stats.sorted.slice(0, 8).map(([name, count], i) => (
                <div key={name} className="grid grid-cols-12 items-center gap-2 text-sm mb-1">
                  <div className="col-span-4 truncate">{name}</div>
                  <div className="col-span-7 bg-slate-100 h-3 rounded">
                    <div className="h-full rounded" style={{ width: `${Math.max(6, (count / stats.max) * 100)}%`, background: palette[i % palette.length] }} />
                  </div>
                  <div className="col-span-1 text-right font-bold">{count}</div>
                </div>
              ))}
            </div>

            <div className="mt-4 border rounded-lg p-3">
              <div className="font-semibold mb-2">Temuan Pelanggaran</div>
              <div className="flex items-center gap-6">
                <div className="relative w-28 h-28 rounded-full" style={{ background: `conic-gradient(#ef4444 0 ${stats.pct}%, #e5e7eb ${stats.pct}% 100%)` }}>
                  <div className="absolute inset-3 bg-white rounded-full grid place-items-center font-bold">{stats.pct}%</div>
                </div>
                <div className="text-sm space-y-1">
                  <div>Total Pelanggaran: <span className="text-red-600 font-bold text-lg">{stats.violations}</span></div>
                  <div>Dari Total Laporan: <span className="font-bold text-lg">{stats.items.length}</span></div>
                  <div className="text-slate-500">PKL: {stats.pkl} · Baliho: {stats.baliho}</div>
                </div>
              </div>
            </div>
          </>
        )}

        <div style={{ pageBreakBefore: 'always' }} className="mt-8 print:mt-0">
          <h3 className="text-center font-bold text-base mb-3">{stats.isService ? 'RINCIAN PELAYANAN' : 'RINCIAN AKTIVITAS'}</h3>

          {stats.isService ? (
            <table className="w-full text-[10px] border-collapse">
              <thead>
                <tr className="bg-slate-900 text-white">
                  <th className="p-2 w-8 text-center">No</th>
                  <th className="p-2 w-12 text-left">Waktu</th>
                  <th className="p-2 w-24 text-left">NIB</th>
                  <th className="p-2 text-left">Nama Pemohon</th>
                  <th className="p-2 text-left">Perusahaan</th>
                  <th className="p-2 w-20 text-left">Loket</th>
                  <th className="p-2 w-24 text-left">Jenis Layanan</th>
                  <th className="p-2 text-left">Aduan</th>
                  <th className="p-2 text-left">Solusi</th>
                </tr>
              </thead>
              <tbody>
                {stats.items.map((it, idx) => (
                  <tr key={it.id} className={idx % 2 ? 'bg-slate-50' : ''} style={{ pageBreakInside: 'avoid' }}>
                    <td className="p-2 text-center align-top border-b">{idx + 1}</td>
                    <td className="p-2 align-top border-b">{(it.time || '').slice(0,5) || '-'}</td>
                    <td className="p-2 align-top border-b font-mono text-[9px]">{it.nib || '-'}</td>
                    <td className="p-2 align-top border-b">
                      <div className="font-semibold">{it.applicant_name || '-'}{it.gender ? ` (${it.gender})` : ''}</div>
                      {it.phone && <div className="text-slate-500 text-[9px]">{it.phone}</div>}
                    </td>
                    <td className="p-2 align-top border-b">
                      <div>{it.company || '-'}</div>
                      {it.company_address && <div className="text-slate-500 text-[9px]">{it.company_address}</div>}
                    </td>
                    <td className="p-2 align-top border-b">{it.loket?.name || '-'}</td>
                    <td className="p-2 align-top border-b">{it.jenis_layanan?.name || it.purpose || '-'}</td>
                    <td className="p-2 align-top border-b whitespace-pre-line">{it.complaint}</td>
                    <td className="p-2 align-top border-b whitespace-pre-line">{it.solution}</td>
                  </tr>
                ))}
                {stats.items.length === 0 && <tr><td colSpan={9} className="p-4 text-center text-slate-500">Belum ada data pelayanan.</td></tr>}
              </tbody>
            </table>
          ) : (
            <table className="w-full text-xs border-collapse">
              <thead>
                <tr className="bg-slate-900 text-white">
                  <th className="p-2 w-10 text-center">No</th>
                  <th className="p-2 text-left">Jenis Kegiatan</th>
                  <th className="p-2 w-16 text-left">Waktu</th>
                  <th className="p-2 text-left w-40">Lokasi</th>
                  <th className="p-2 text-left">Catatan</th>
                  <th className="p-2 text-left w-44">Foto</th>
                </tr>
              </thead>
              <tbody>
                {stats.items.map((it, idx) => (
                  <tr key={it.id} className={idx % 2 ? 'bg-slate-50' : ''} style={{ pageBreakInside: 'avoid' }}>
                    <td className="p-2 text-center align-top border-b">{idx + 1}</td>
                    <td className="p-2 align-top border-b">{it.category?.name}</td>
                    <td className="p-2 align-top border-b">{(it.time || '').slice(0,5) || '-'}</td>
                    <td className="p-2 align-top border-b">{it.location}</td>
                    <td className="p-2 align-top border-b whitespace-pre-line">{it.notes}</td>
                    <td className="p-2 align-top border-b">
                      <div className="flex flex-wrap gap-1">
                        {(it.photos || []).map(p => <img key={p.id} src={p.url} className="w-14 h-14 object-cover rounded" />)}
                      </div>
                    </td>
                  </tr>
                ))}
                {stats.items.length === 0 && <tr><td colSpan={6} className="p-4 text-center text-slate-500">Belum ada item.</td></tr>}
              </tbody>
            </table>
          )}
        </div>
      </div>
    </div>
  )
}

function Card({ label, value, icon, tone, small }) {
  return (
    <div className="border rounded-lg p-3 flex items-center justify-between">
      <div className="min-w-0">
        <div className="text-[11px] text-slate-500">{label}</div>
        <div className={`mt-1 font-bold ${small ? 'text-sm truncate' : 'text-2xl'}`}>{value}</div>
      </div>
      <div className={`w-9 h-9 rounded-full grid place-items-center text-sm ${tone}`}>{icon}</div>
    </div>
  )
}
