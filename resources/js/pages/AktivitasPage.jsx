import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api, apiBase } from '../api/client'
import { getStoredUser, getToken } from '../api/auth'

export default function AktivitasPage() {
  const user = getStoredUser()
  const today = new Date().toISOString().slice(0, 10)
  const [date, setDate] = useState(today)
  const [search, setSearch] = useState('')
  const [categoryId, setCategoryId] = useState('')
  const [categories, setCategories] = useState([])
  const [bidangs, setBidangs] = useState([])
  const [data, setData] = useState({ data: [], total: 0 })
  const [loading, setLoading] = useState(false)

  async function loadCats() {
    const [a, b] = await Promise.all([
      api.get('/categories', { params: { active_only: 1 } }),
      user?.role === 'admin' ? api.get('/bidangs', { params: { active_only: 1 } }) : Promise.resolve({ data: [] }),
    ])
    setCategories(a.data)
    setBidangs(b.data)
  }

  async function load() {
    setLoading(true)
    try {
      const params = { per_page: 50 }
      if (date) params.date = date
      if (search) params.search = search
      if (categoryId) params.category_id = categoryId
      if (user?.role === 'admin') params.all = 1
      const { data } = await api.get('/aktivitas', { params })
      setData(data)
    } finally { setLoading(false) }
  }

  useEffect(() => { loadCats() }, [])
  useEffect(() => { load() }, [date, search, categoryId])

  async function remove(id) {
    if (!confirm('Hapus aktivitas ini?')) return
    await api.delete(`/aktivitas/${id}`)
    load()
  }

  function downloadRekap() {
    const target = date || today
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

  function downloadRekapBidang(bidangId, bidangName) {
    const target = date || today
    const url = `${apiBase}/reports-rekap/bidang/${bidangId}/pdf?from=${target}&to=${target}`
    fetch(url, { headers: { Authorization: `Bearer ${getToken()}` } })
      .then(r => r.blob())
      .then(b => {
        const a = document.createElement('a')
        a.href = URL.createObjectURL(b)
        a.download = `rekap-${bidangName.toLowerCase().replace(/\s+/g, '-')}-${target}.pdf`
        a.click()
      })
  }

  // Group by date for cleaner display
  const groups = data.data.reduce((acc, it) => {
    const d = (it.report?.report_date || '').slice(0, 10)
    if (!acc[d]) acc[d] = []
    acc[d].push(it)
    return acc
  }, {})
  const sortedDates = Object.keys(groups).sort().reverse()

  return (
    <div className="space-y-4">
      <div className="flex flex-col md:flex-row md:items-end gap-3 md:justify-between">
        <div>
          <h1 className="text-xl font-bold">Aktivitas</h1>
          <p className="text-sm text-slate-500">Catat aktivitas/pelayanan harian. Setiap entri otomatis masuk ke laporan tanggalnya.</p>
        </div>
        <div className="flex gap-2 flex-wrap items-center">
          {user?.role === 'admin' && (
            <>
              <button onClick={downloadRekap} className="btn-ghost">📋 Rekap PDF (semua)</button>
              <select
                className="input"
                style={{ maxWidth: 220 }}
                value=""
                onChange={(e) => {
                  if (e.target.value) {
                    const b = bidangs.find(x => x.id === +e.target.value)
                    if (b) downloadRekapBidang(b.id, b.name)
                    e.target.value = ''
                  }
                }}
              >
                <option value="">📄 Rekap per Bidang…</option>
                {bidangs.map(b => <option key={b.id} value={b.id}>{b.name}</option>)}
              </select>
            </>
          )}
          <Link to="/aktivitas/new" className="btn-primary">+ Aktivitas Baru</Link>
        </div>
      </div>

      <div className="card p-3 grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
        <div>
          <label className="label">Tanggal</label>
          <input type="date" className="input" value={date} onChange={(e) => setDate(e.target.value)} />
        </div>
        <div>
          <label className="label">Kategori</label>
          <select className="input" value={categoryId} onChange={(e) => setCategoryId(e.target.value)}>
            <option value="">Semua</option>
            {categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
          </select>
        </div>
        <div className="md:col-span-2">
          <label className="label">Cari (nama / NIB / perusahaan / aduan)</label>
          <input className="input" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="…" />
        </div>
      </div>

      {loading && <div className="text-sm text-slate-500">Memuat…</div>}
      {!loading && data.data.length === 0 && <div className="card p-6 text-center text-slate-500">Belum ada aktivitas pada filter ini.</div>}

      {sortedDates.map(d => (
        <div key={d} className="card overflow-hidden">
          <div className="bg-slate-50 px-3 py-2 flex items-center justify-between">
            <div className="font-semibold text-sm">{d} <span className="text-slate-400 font-normal">— {groups[d].length} entri</span></div>
          </div>
          <div className="overflow-x-auto">
          <table className="w-full text-sm min-w-[900px]">
            <thead className="bg-slate-50 text-slate-600 text-xs">
              <tr>
                <th className="p-2 text-left w-16">Waktu</th>
                <th className="p-2 text-left">Kategori / Jenis Layanan</th>
                <th className="p-2 text-left">Pemohon / Lokasi</th>
                <th className="p-2 text-left">Detail</th>
                <th className="p-2 text-left w-40">Foto</th>
                <th className="p-2 text-left">Pembuat</th>
                <th className="p-2"></th>
              </tr>
            </thead>
            <tbody>
              {groups[d].map((it) => {
                const isServ = !!it.category?.is_service
                return (
                  <tr key={it.id} className="border-t border-slate-100 align-top">
                    <td className="p-2 text-slate-600 whitespace-nowrap">{(it.time || '').slice(0,5) || '-'}</td>
                    <td className="p-2">
                      <div className="font-medium text-xs">
                        <span className="pill" style={{ background: (it.category?.color || '#e2e8f0') + '22', color: it.category?.color || '#475569' }}>
                          {it.category?.name}
                        </span>
                      </div>
                      {isServ && it.jenis_layanan?.name && <div className="text-xs text-slate-500 mt-1">{it.jenis_layanan.name}</div>}
                      {!isServ && it.location && <div className="text-xs text-slate-500 mt-1">📍 {it.location}</div>}
                    </td>
                    <td className="p-2">
                      {isServ ? (
                        <>
                          <div className="font-medium">{it.applicant_name || '-'}{it.gender ? ` (${it.gender})` : ''}</div>
                          {it.company && <div className="text-xs text-slate-500">{it.company}</div>}
                          {it.nib && <div className="text-xs text-slate-400 font-mono">{it.nib}</div>}
                        </>
                      ) : (
                        <div className="text-xs text-slate-600 whitespace-pre-line">{it.notes || '-'}</div>
                      )}
                      <div className="text-xs text-slate-400 mt-1">{it.lokasi?.name}{it.loket?.name ? ' · ' + it.loket.name : ''}</div>
                    </td>
                    <td className="p-2 text-xs">
                      {it.complaint && <div><b>Aduan:</b> {it.complaint}</div>}
                      {it.solution && <div><b>Solusi:</b> {it.solution}</div>}
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
                    <td className="p-2 text-xs text-slate-500">{it.report?.user?.name}</td>
                    <td className="p-2 text-right whitespace-nowrap text-xs">
                      <Link to={`/aktivitas/${it.id}`} className="text-brand-600 mr-3">Edit</Link>
                      <button onClick={() => remove(it.id)} className="text-red-600">Hapus</button>
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
          </div>
        </div>
      ))}
    </div>
  )
}
