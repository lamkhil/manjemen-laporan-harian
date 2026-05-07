import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api, apiBase } from '../api/client'
import { getStoredUser, getToken } from '../api/auth'

export default function ReportsPage() {
  const user = getStoredUser()
  const today = new Date().toISOString().slice(0, 10)
  const [date, setDate] = useState(today)
  const [shift, setShift] = useState('')
  const [data, setData] = useState({ data: [], total: 0 })
  const [loading, setLoading] = useState(false)

  async function load() {
    setLoading(true)
    try {
      const params = { per_page: 50 }
      if (date) params.date = date
      if (shift) params.shift = shift
      if (user?.role === 'admin') params.all = 1
      const { data } = await api.get('/reports', { params })
      setData(data)
    } finally { setLoading(false) }
  }

  useEffect(() => { load() }, [date, shift])

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

  return (
    <div className="space-y-4">
      <div className="flex flex-col md:flex-row md:items-end gap-3 md:justify-between">
        <div>
          <h1 className="text-xl font-bold">Laporan</h1>
          <p className="text-sm text-slate-500">Daftar laporan harian — filter berdasarkan tanggal & shift.</p>
        </div>
        <Link to="/reports/new" className="btn-primary">+ Buat Laporan</Link>
      </div>

      <div className="card p-3 flex flex-wrap gap-3 items-end">
        <div>
          <label className="label">Tanggal</label>
          <input type="date" className="input" value={date} onChange={(e) => setDate(e.target.value)} />
        </div>
        <div>
          <label className="label">Shift</label>
          <select className="input" value={shift} onChange={(e) => setShift(e.target.value)}>
            <option value="">Semua</option>
            <option>Pagi</option>
            <option>Siang</option>
            <option>Malam</option>
          </select>
        </div>
        <button className="btn-ghost" onClick={() => { setDate(''); setShift('') }}>Reset</button>
      </div>

      <div className="card overflow-hidden">
        <table className="w-full text-sm">
          <thead className="bg-slate-50 text-slate-600">
            <tr>
              <th className="p-3 text-left">Tanggal</th>
              <th className="p-3 text-left">Judul / Kecamatan</th>
              <th className="p-3 text-left">Shift</th>
              <th className="p-3 text-left">Pembuat</th>
              <th className="p-3 text-center">Item</th>
              <th className="p-3 text-center">Status</th>
              <th className="p-3"></th>
            </tr>
          </thead>
          <tbody>
            {loading && <tr><td colSpan={7} className="p-4 text-slate-500">Memuat…</td></tr>}
            {!loading && data.data.length === 0 && <tr><td colSpan={7} className="p-4 text-slate-500">Tidak ada laporan.</td></tr>}
            {data.data.map((r) => (
              <tr key={r.id} className="border-t border-slate-100">
                <td className="p-3 whitespace-nowrap">{r.report_date}</td>
                <td className="p-3">
                  <div className="font-medium">{r.title}</div>
                  <div className="text-xs text-slate-500">{r.kecamatan || r.subtitle || '-'}</div>
                </td>
                <td className="p-3"><span className="pill bg-slate-100">{r.shift}</span></td>
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
  )
}
