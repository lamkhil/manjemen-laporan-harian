import { useEffect, useState } from 'react'
import { useNavigate, useParams, Link } from 'react-router-dom'
import { api, apiBase } from '../api/client'
import { getToken } from '../api/auth'

const empty = {
  title: 'LAPORAN PENGAWASAN KEWILAYAHAN',
  kecamatan: '',
  subtitle: '',
  report_date: new Date().toISOString().slice(0, 10),
  shift: 'Pagi',
  time_start: '06:00',
  time_end: '14:00',
  notes: '',
  status: 'draft',
}

export default function ReportEditPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const isNew = !id
  const [report, setReport] = useState(empty)
  const [items, setItems] = useState([])
  const [categories, setCategories] = useState([])
  const [loading, setLoading] = useState(false)
  const [saving, setSaving] = useState(false)
  const [newItem, setNewItem] = useState({ category_id: '', time: '', location: '', notes: '' })
  const [editingItemId, setEditingItemId] = useState(null)

  async function loadCats() {
    const { data } = await api.get('/categories', { params: { active_only: 1 } })
    setCategories(data)
  }

  async function loadReport() {
    if (isNew) return
    setLoading(true)
    try {
      const { data } = await api.get(`/reports/${id}`)
      setReport({
        ...data,
        report_date: data.report_date?.slice(0, 10) || empty.report_date,
        time_start: data.time_start?.slice(0, 5) || '',
        time_end: data.time_end?.slice(0, 5) || '',
      })
      setItems(data.items || [])
    } finally { setLoading(false) }
  }

  useEffect(() => { loadCats(); loadReport() }, [id])

  async function saveReport(e) {
    e?.preventDefault()
    setSaving(true)
    try {
      const payload = { ...report }
      if (!payload.time_start) delete payload.time_start
      if (!payload.time_end) delete payload.time_end
      if (isNew) {
        const { data } = await api.post('/reports', payload)
        navigate(`/reports/${data.id}`, { replace: true })
      } else {
        await api.patch(`/reports/${id}`, payload)
        await loadReport()
      }
    } finally { setSaving(false) }
  }

  async function addItem(e) {
    e.preventDefault()
    if (!id) { alert('Simpan laporan terlebih dahulu sebelum menambah item.'); return }
    const payload = { ...newItem }
    if (!payload.time) delete payload.time
    const { data } = await api.post(`/reports/${id}/items`, payload)
    setItems([...items, data])
    setNewItem({ category_id: '', time: '', location: '', notes: '' })
  }

  async function updateItem(item) {
    const payload = {
      category_id: item.category_id,
      time: item.time || null,
      location: item.location,
      notes: item.notes,
    }
    if (!payload.time) delete payload.time
    const { data } = await api.patch(`/reports/${id}/items/${item.id}`, payload)
    setItems(items.map((it) => it.id === item.id ? data : it))
    setEditingItemId(null)
  }

  async function removeItem(itemId) {
    if (!confirm('Hapus item ini?')) return
    await api.delete(`/reports/${id}/items/${itemId}`)
    setItems(items.filter((it) => it.id !== itemId))
  }

  async function uploadPhoto(itemId, file) {
    const fd = new FormData()
    fd.append('photo', file)
    const { data } = await api.post(`/reports/${id}/items/${itemId}/photos`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    setItems(items.map((it) => it.id === itemId ? { ...it, photos: [...(it.photos || []), data] } : it))
  }

  async function removePhoto(itemId, photoId) {
    if (!confirm('Hapus foto?')) return
    await api.delete(`/reports/${id}/items/${itemId}/photos/${photoId}`)
    setItems(items.map((it) => it.id === itemId ? { ...it, photos: it.photos.filter(p => p.id !== photoId) } : it))
  }

  function downloadPdf() {
    fetch(`${apiBase}/reports/${id}/pdf`, { headers: { Authorization: `Bearer ${getToken()}` } })
      .then(r => r.blob()).then(b => {
        const a = document.createElement('a')
        a.href = URL.createObjectURL(b)
        a.download = `laporan-${report.report_date}-${id}.pdf`
        a.click()
      })
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center gap-3">
        <Link to="/reports" className="text-brand-600">← Kembali</Link>
        <h1 className="text-xl font-bold">{isNew ? 'Buat Laporan' : `Edit Laporan #${id}`}</h1>
        {!isNew && <>
          <Link to={`/print/${id}`} target="_blank" className="btn-ghost ml-auto">Print HTML</Link>
          <button onClick={downloadPdf} className="btn-ghost">Download PDF</button>
        </>}
      </div>

      {loading && <div className="text-sm text-slate-500">Memuat…</div>}

      <form onSubmit={saveReport} className="card p-4 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div className="md:col-span-3">
          <label className="label">Judul</label>
          <input className="input" value={report.title} onChange={(e) => setReport({ ...report, title: e.target.value })} />
        </div>
        <div>
          <label className="label">Kecamatan</label>
          <input className="input" value={report.kecamatan || ''} onChange={(e) => setReport({ ...report, kecamatan: e.target.value })} placeholder="cth. Sukolilo" />
        </div>
        <div>
          <label className="label">Tanggal</label>
          <input className="input" type="date" value={report.report_date} onChange={(e) => setReport({ ...report, report_date: e.target.value })} required />
        </div>
        <div>
          <label className="label">Shift</label>
          <select className="input" value={report.shift} onChange={(e) => setReport({ ...report, shift: e.target.value })}>
            <option>Pagi</option><option>Siang</option><option>Malam</option>
          </select>
        </div>
        <div>
          <label className="label">Mulai</label>
          <input className="input" type="time" value={report.time_start || ''} onChange={(e) => setReport({ ...report, time_start: e.target.value })} />
        </div>
        <div>
          <label className="label">Selesai</label>
          <input className="input" type="time" value={report.time_end || ''} onChange={(e) => setReport({ ...report, time_end: e.target.value })} />
        </div>
        <div>
          <label className="label">Status</label>
          <select className="input" value={report.status} onChange={(e) => setReport({ ...report, status: e.target.value })}>
            <option value="draft">Draft</option>
            <option value="final">Final</option>
          </select>
        </div>
        <div className="md:col-span-3">
          <label className="label">Catatan Umum</label>
          <textarea className="input" rows={2} value={report.notes || ''} onChange={(e) => setReport({ ...report, notes: e.target.value })} />
        </div>
        <div className="md:col-span-3">
          <button className="btn-primary" disabled={saving}>{saving ? 'Menyimpan…' : (isNew ? 'Simpan & Lanjut Tambah Item' : 'Simpan')}</button>
        </div>
      </form>

      {!isNew && (
        <div className="card p-4 space-y-4">
          <div className="font-semibold">Rincian Aktivitas ({items.length})</div>

          <form onSubmit={addItem} className="grid grid-cols-1 md:grid-cols-12 gap-2 items-end border border-dashed border-slate-200 rounded-lg p-3">
            <div className="md:col-span-3">
              <label className="label">Jenis Kegiatan</label>
              <select className="input" required value={newItem.category_id} onChange={(e) => setNewItem({ ...newItem, category_id: e.target.value })}>
                <option value="">Pilih…</option>
                {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
              </select>
            </div>
            <div className="md:col-span-2">
              <label className="label">Waktu</label>
              <input type="time" className="input" value={newItem.time} onChange={(e) => setNewItem({ ...newItem, time: e.target.value })} />
            </div>
            <div className="md:col-span-3">
              <label className="label">Lokasi</label>
              <input className="input" required value={newItem.location} onChange={(e) => setNewItem({ ...newItem, location: e.target.value })} />
            </div>
            <div className="md:col-span-3">
              <label className="label">Catatan</label>
              <input className="input" value={newItem.notes} onChange={(e) => setNewItem({ ...newItem, notes: e.target.value })} />
            </div>
            <div className="md:col-span-1"><button className="btn-primary w-full">+</button></div>
          </form>

          <div className="space-y-3">
            {items.map((it, idx) => (
              <div key={it.id} className="border border-slate-200 rounded-lg p-3">
                <div className="flex items-start justify-between gap-3">
                  <div className="flex-1 min-w-0">
                    {editingItemId === it.id ? (
                      <div className="grid grid-cols-1 md:grid-cols-4 gap-2">
                        <select className="input" value={it.category_id} onChange={(e) => setItems(items.map(x => x.id === it.id ? { ...x, category_id: +e.target.value } : x))}>
                          {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                        </select>
                        <input type="time" className="input" value={it.time?.slice(0,5) || ''} onChange={(e) => setItems(items.map(x => x.id === it.id ? { ...x, time: e.target.value } : x))} />
                        <input className="input md:col-span-2" value={it.location} onChange={(e) => setItems(items.map(x => x.id === it.id ? { ...x, location: e.target.value } : x))} />
                        <textarea className="input md:col-span-4" rows={3} value={it.notes || ''} onChange={(e) => setItems(items.map(x => x.id === it.id ? { ...x, notes: e.target.value } : x))} />
                      </div>
                    ) : (
                      <>
                        <div className="text-sm">
                          <span className="font-semibold">#{idx + 1}. {it.category?.name}</span>
                          <span className="ml-2 text-slate-500">{it.time ? it.time.slice(0,5) : ''}</span>
                        </div>
                        <div className="text-sm text-slate-700 mt-1">📍 {it.location}</div>
                        {it.notes && <div className="text-sm text-slate-600 mt-1 whitespace-pre-line">{it.notes}</div>}
                      </>
                    )}
                  </div>
                  <div className="flex flex-col gap-1 text-xs">
                    {editingItemId === it.id ? (
                      <>
                        <button className="text-emerald-700" onClick={() => updateItem(it)}>Simpan</button>
                        <button className="text-slate-600" onClick={() => { setEditingItemId(null); loadReport() }}>Batal</button>
                      </>
                    ) : (
                      <>
                        <button className="text-brand-600" onClick={() => setEditingItemId(it.id)}>Edit</button>
                        <button className="text-red-600" onClick={() => removeItem(it.id)}>Hapus</button>
                      </>
                    )}
                  </div>
                </div>

                <div className="mt-3 flex flex-wrap gap-2">
                  {(it.photos || []).map((p) => (
                    <div key={p.id} className="relative group">
                      <img src={p.url} alt="" className="w-20 h-20 object-cover rounded-md border border-slate-200" />
                      <button onClick={() => removePhoto(it.id, p.id)} className="absolute -top-1 -right-1 bg-red-600 text-white text-xs w-5 h-5 rounded-full opacity-0 group-hover:opacity-100">×</button>
                    </div>
                  ))}
                  <label className="w-20 h-20 grid place-items-center border-2 border-dashed border-slate-300 rounded-md text-xs text-slate-500 cursor-pointer hover:bg-slate-50">
                    + Foto
                    <input type="file" accept="image/*" className="hidden" onChange={(e) => e.target.files[0] && uploadPhoto(it.id, e.target.files[0])} />
                  </label>
                </div>
              </div>
            ))}
            {items.length === 0 && <div className="text-sm text-slate-500">Belum ada item. Tambahkan di atas.</div>}
          </div>
        </div>
      )}
    </div>
  )
}
