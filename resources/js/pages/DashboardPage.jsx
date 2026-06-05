import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api } from '../api/client'
import { getStoredUser } from '../api/auth'

function StatCard({ label, value, icon, tone = 'blue' }) {
  const tones = {
    blue: 'bg-blue-100 text-blue-700',
    red: 'bg-red-100 text-red-700',
    green: 'bg-emerald-100 text-emerald-700',
    amber: 'bg-amber-100 text-amber-700',
    violet: 'bg-violet-100 text-violet-700',
    pink: 'bg-pink-100 text-pink-700',
  }
  return (
    <div className="card p-4 flex items-center justify-between">
      <div>
        <div className="text-xs text-slate-500">{label}</div>
        <div className="text-2xl font-bold mt-1">{value}</div>
      </div>
      <div className={`w-10 h-10 rounded-full grid place-items-center ${tones[tone]}`}>{icon}</div>
    </div>
  )
}

export default function DashboardPage() {
  const user = getStoredUser()
  const today = new Date().toISOString().slice(0, 10)
  const [from, setFrom] = useState(today)
  const [to, setTo] = useState(today)
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(false)
  const [activities, setActivities] = useState([])
  const [activitiesLoading, setActivitiesLoading] = useState(false)

  async function load() {
    setLoading(true)
    try {
      const { data } = await api.get('/stats/summary', { params: { from, to } })
      setData(data)
    } finally { setLoading(false) }
  }

  async function loadActivities() {
    setActivitiesLoading(true)
    try {
      const params = { per_page: 30, from, to }
      if (user?.role === 'admin') params.all = 1
      const { data } = await api.get('/aktivitas', { params })
      setActivities(data?.data || [])
    } finally { setActivitiesLoading(false) }
  }

  useEffect(() => { load(); loadActivities() }, [from, to])

  const violations = data?.total_violations || 0
  const totalItems = data?.total_items || 0
  const pct = totalItems ? Math.round((violations / totalItems) * 100) : 0
  const top = data?.by_category?.[0]?.category?.name || '-'
  const max = Math.max(1, ...(data?.by_category?.map(b => b.total) || [1]))

  return (
    <div className="space-y-5">
      <div className="flex flex-col md:flex-row md:items-end gap-3 md:justify-between">
        <div>
          <h1 className="text-xl font-bold">Dashboard</h1>
          <p className="text-sm text-slate-500">Ringkasan laporan harian DPMPTSP.</p>
        </div>
        <div className="flex gap-2 items-end">
          <div>
            <label className="label">Dari</label>
            <input type="date" className="input" value={from} onChange={(e) => setFrom(e.target.value)} />
          </div>
          <div>
            <label className="label">Sampai</label>
            <input type="date" className="input" value={to} onChange={(e) => setTo(e.target.value)} />
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <StatCard label="Total Laporan" value={data?.total_reports || 0} icon="📄" tone="blue" />
        <StatCard label="Total Item Aktivitas" value={totalItems} icon="📋" tone="violet" />
        <StatCard label="Total Pelanggaran" value={violations} icon="⚠️" tone="red" />
        <StatCard label="Persentase Pelanggaran" value={`${pct}%`} icon="🎯" tone="amber" />
        <StatCard label="Aktivitas Terbanyak" value={top.length > 18 ? top.slice(0, 18) + '…' : top} icon="📊" tone="green" />
        <StatCard label="Periode" value={from === to ? from : `${from} → ${to}`} icon="📅" tone="pink" />
      </div>

      <div className="card p-4">
        <div className="font-semibold mb-3">Distribusi Jenis Kegiatan</div>
        {loading && <div className="text-sm text-slate-500">Memuat…</div>}
        {!loading && (data?.by_category?.length ? (
          <div className="space-y-2">
            {data.by_category.map((row) => (
              <div key={row.category_id} className="grid grid-cols-12 items-center gap-2 text-sm">
                <div className="col-span-4 truncate text-slate-700">{row.category?.name || '-'}</div>
                <div className="col-span-7 bg-slate-100 rounded-md h-3 overflow-hidden">
                  <div className="h-full rounded-md" style={{ width: `${(row.total / max) * 100}%`, background: row.category?.color || '#3b82f6' }} />
                </div>
                <div className="col-span-1 text-right font-semibold">{row.total}</div>
              </div>
            ))}
          </div>
        ) : <div className="text-sm text-slate-500">Belum ada data pada periode ini.</div>)}
      </div>

      <div className="card overflow-hidden">
        <div className="px-3 py-2 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
          <div className="font-semibold text-sm">Aktivitas Terbaru</div>
          <Link to="/aktivitas" className="text-xs text-brand-600">Lihat semua →</Link>
        </div>
        {activitiesLoading && <div className="p-4 text-sm text-slate-500">Memuat…</div>}
        {!activitiesLoading && activities.length === 0 && (
          <div className="p-6 text-center text-sm text-slate-500">Belum ada aktivitas pada periode ini.</div>
        )}
        {!activitiesLoading && activities.length > 0 && (
          <div className="overflow-x-auto">
            <table className="w-full text-sm min-w-[760px]">
              <thead className="bg-slate-50 text-slate-600 text-xs">
                <tr>
                  <th className="p-2 text-left w-20">Tanggal</th>
                  <th className="p-2 text-left w-14">Waktu</th>
                  <th className="p-2 text-left">Kategori / Pemohon</th>
                  <th className="p-2 text-left">Detail</th>
                  <th className="p-2 text-left w-40">Foto</th>
                </tr>
              </thead>
              <tbody>
                {activities.map((it) => {
                  const isServ = !!it.category?.is_service
                  const d = (it.report?.report_date || '').slice(0, 10)
                  return (
                    <tr key={it.id} className="border-t border-slate-100 align-top">
                      <td className="p-2 text-slate-600 whitespace-nowrap">{d}</td>
                      <td className="p-2 text-slate-600 whitespace-nowrap">{(it.time || '').slice(0, 5) || '-'}</td>
                      <td className="p-2">
                        <span className="pill" style={{ background: (it.category?.color || '#e2e8f0') + '22', color: it.category?.color || '#475569' }}>
                          {it.category?.name}
                        </span>
                        {isServ
                          ? <div className="text-xs mt-1 font-medium">{it.applicant_name || '-'}{it.gender ? ` (${it.gender})` : ''}</div>
                          : it.location && <div className="text-xs text-slate-500 mt-1">📍 {it.location}</div>}
                        {it.company && <div className="text-xs text-slate-500">{it.company}</div>}
                      </td>
                      <td className="p-2 text-xs text-slate-600">
                        {isServ
                          ? (it.jenis_layanan?.name || it.purpose || '-')
                          : (it.notes ? <div className="whitespace-pre-line line-clamp-3">{it.notes}</div> : '-')}
                        {it.complaint && <div className="text-slate-500 mt-1"><b>Aduan:</b> {it.complaint}</div>}
                        <div className="text-slate-400 mt-1">{it.report?.user?.name}{it.loket?.name ? ' · ' + it.loket.name : ''}</div>
                      </td>
                      <td className="p-2">
                        {(it.photos?.length > 0) ? (
                          <div className="flex flex-wrap gap-1">
                            {it.photos.slice(0, 4).map(p => (
                              <a key={p.id} href={p.url} target="_blank" rel="noreferrer">
                                <img src={p.url} alt="" className="w-12 h-12 object-cover rounded border border-slate-200" />
                              </a>
                            ))}
                            {it.photos.length > 4 && <div className="text-xs text-slate-500 self-center">+{it.photos.length - 4}</div>}
                          </div>
                        ) : <span className="text-xs text-slate-400">—</span>}
                      </td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  )
}
