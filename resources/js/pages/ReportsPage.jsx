import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api, apiBase } from '../api/client'
import { getStoredUser, getToken } from '../api/auth'

export default function ReportsPage() {
  const user = getStoredUser()
  const today = new Date().toISOString().slice(0, 10)
  const [date, setDate] = useState(today)
  const [data, setData] = useState({ data: [], total: 0 })
  const [loading, setLoading] = useState(false)

  async function load() {
    setLoading(true)
    try {
      const params = { per_page: 50 }
      if (date) params.date = date
      if (user?.role === 'admin') params.all = 1
      const { data } = await api.get('/reports', { params })
      setData(data)
    } finally { setLoading(false) }
  }

  useEffect(() => { load() }, [date])

  function downloadPdf(report) {
    const url = `${apiBase}/reports/${report.id}/pdf?token=${encodeURIComponent(getToken())}`
    fetch(url, { headers: { Authorization: `Bearer ${getToken()}` } })
      .then(r => r.blob())
      .then(b => {
        const a = document.createElement('a')
        a.href = URL.createObjectURL(b)
        a.download = `laporan-${report.report_date}-${report.id}.pdf`
        a.click()
      })
  }

  async function remove(id) {
    if (!confirm('Hapus laporan ini beserta semua item & foto?')) return
    await api.delete(`/reports/${id}`)
    load()
  }

  function downloadRekap() {
    const target = date || new Date().toISOString().slice(0, 10)
    const url = `${apiBase}/reports-rekap/pdf?from=${target}&to=${target}`
    fetch(url, { headers: { Authorization: `Bearer ${getToken()}` } })
      .then(r => r.blob())
      .then(b => {
        const a = document.createElement('a')
        a.href = URL.createObjectURL(b)
        a.download = `rekap-${target}.pdf`
        a.click()
      })
  }

  return (
    <div className="space-y-4">
      <div className="flex flex-col md:flex-row md:items-end gap-3 md:justify-between">
        <div>
          <h1 className="text-xl font-bold">Laporan</h1>
          <p className="text-sm text-slate-500">Daftar laporan harian — filter berdasarkan tanggal.</p>
        </div>
        <div className="flex gap-2">
          {user?.role === 'admin' && (
            <button onClick={downloadRekap} className="btn-ghost">📋 Rekap PDF</button>
          )}
          <Link to="/reports/new" className="btn-primary">+ Buat Laporan</Link>
        </div>
      </div>

      <div className="card p-3 flex flex-wrap gap-3 items-end">
        <div>
          <label className="label">Tanggal</label>
          <input type="date" className="input" value={date} onChange={(e) => setDate(e.target.value)} />
        </div>
        <button className="btn-ghost" onClick={() => setDate('')}>Reset</button>
      </div>

      <div className="card overflow-hidden">
        <div className="overflow-x-auto">
        <table className="w-full text-sm min-w-[760px]">
          <thead className="bg-slate-50 text-slate-600">
            <tr>
              <th className="p-3 text-left">Tanggal</th>
              <th className="p-3 text-left">Judul / Kategori</th>
              <th className="p-3 text-left">Pembuat</th>
              <th className="p-3 text-center">Item</th>
              <th className="p-3 text-center">Status</th>
              <th className="p-3"></th>
            </tr>
          </thead>
          <tbody>
            {loading && <tr><td colSpan={6} className="p-4 text-slate-500">Memuat…</td></tr>}
            {!loading && data.data.length === 0 && <tr><td colSpan={6} className="p-4 text-slate-500">Tidak ada laporan.</td></tr>}
            {data.data.map((r) => (
              <tr key={r.id} className="border-t border-slate-100">
                <td className="p-3 whitespace-nowrap">{(r.report_date || '').slice(0, 10)}</td>
                <td className="p-3">
                  <div className="font-medium">{r.title}</div>
                  <div className="text-xs">
                    {r.category ? (
                      <span className="pill" style={{ background: (r.category.color || '#e2e8f0') + '22', color: r.category.color || '#475569' }}>
                        {r.category.name}{r.category.is_service ? ' • pelayanan' : ''}
                      </span>
                    ) : <span className="text-slate-400">- tanpa kategori -</span>}
                  </div>
                </td>
                <td className="p-3 text-slate-600">{r.user?.name}</td>
                <td className="p-3 text-center">{r.items_count}</td>
                <td className="p-3 text-center">
                  {r.status === 'final'
                    ? <span className="pill bg-emerald-100 text-emerald-700">final</span>
                    : <span className="pill bg-amber-100 text-amber-700">draft</span>}
                </td>
                <td className="p-3 text-right whitespace-nowrap">
                  <Link to={`/reports/${r.id}`} className="text-brand-600 mr-3">Edit</Link>
                  <Link to={`/print/${r.id}`} target="_blank" className="text-slate-700 mr-3">Print</Link>
                  <button onClick={() => downloadPdf(r)} className="text-emerald-700 mr-3">PDF</button>
                  <button onClick={() => remove(r.id)} className="text-red-600">Hapus</button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        </div>
      </div>
    </div>
  )
}
