import { useEffect, useMemo, useState } from 'react'
import { useNavigate, useParams, Link } from 'react-router-dom'
import { api, apiBase } from '../api/client'
import { getToken, getStoredUser } from '../api/auth'

const empty = {
  title: 'LAPORAN HARIAN DPMPTSP',
  category_id: '',
  subtitle: '',
  report_date: new Date().toISOString().slice(0, 10),
  time_start: '08:00',
  time_end: '15:00',
  notes: '',
  violations_count: 0,
  status: 'draft',
}

function emptyItem(me) {
  return {
    category_id: '',
    time: '',
    location: '',
    notes: '',
    lokasi_id: me?.default_lokasi_id || '',
    loket_id: me?.default_loket_id || '',
    jenis_layanan_id: '',
    nib: '',
    applicant_name: '',
    gender: '',
    company: '',
    company_address: '',
    phone: '',
    email: '',
    purpose: '',
    complaint: '',
    solution: '',
  }
}

export default function ReportEditPage() {
  const me = getStoredUser()
  const { id } = useParams()
  const navigate = useNavigate()
  const isNew = !id
  const [report, setReport] = useState(empty)
  const [items, setItems] = useState([])
  const [categories, setCategories] = useState([])
  const [lokasis, setLokasis] = useState([])
  const [lokets, setLokets] = useState([])
  const [jenisLayanans, setJenisLayanans] = useState([])
  const [loading, setLoading] = useState(false)
  const [saving, setSaving] = useState(false)
  const [newItem, setNewItem] = useState(emptyItem(me))
  const [editingItemId, setEditingItemId] = useState(null)

  async function loadCats() {
    const { data } = await api.get('/categories', { params: { active_only: 1 } })
    setCategories(data)
  }
  async function loadMaster() {
    const [a, b, c] = await Promise.all([
      api.get('/lokasis', { params: { active_only: 1 } }),
      api.get('/lokets', { params: { active_only: 1 } }),
      api.get('/jenis-layanans', { params: { active_only: 1 } }),
    ])
    setLokasis(a.data); setLokets(b.data); setJenisLayanans(c.data)
  }

  async function loadReport() {
    if (isNew) return
    setLoading(true)
    try {
      const { data } = await api.get(`/reports/${id}`)
      setReport({
        ...data,
        category_id: data.category_id || '',
        report_date: (data.report_date || '').slice(0, 10) || empty.report_date,
        time_start: data.time_start?.slice(0, 5) || '',
        time_end: data.time_end?.slice(0, 5) || '',
        violations_count: data.violations_count ?? 0,
      })
      setItems(data.items || [])
    } finally { setLoading(false) }
  }

  useEffect(() => { loadCats(); loadMaster(); loadReport() }, [id])

  useEffect(() => {
    if (report.category_id && !newItem.category_id) {
      setNewItem(it => ({ ...it, category_id: report.category_id }))
    }
  }, [report.category_id])

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

  function cleanItem(it) {
    const out = { ...it }
    if (!out.time) delete out.time
    out.lokasi_id = out.lokasi_id || null
    out.loket_id = out.loket_id || null
    out.jenis_layanan_id = out.jenis_layanan_id || null
    Object.keys(out).forEach(k => { if (out[k] === '') out[k] = null })
    return out
  }

  async function addItem(e) {
    e.preventDefault()
    if (!id) { alert('Simpan laporan terlebih dahulu sebelum menambah item.'); return }
    const payload = cleanItem(newItem)
    const { data } = await api.post(`/reports/${id}/items`, payload)
    setItems([...items, data])
    setNewItem(emptyItem(me))
  }

  async function updateItem(item) {
    const payload = cleanItem({
      category_id: item.category_id,
      time: item.time || null,
      location: item.location,
      notes: item.notes,
      lokasi_id: item.lokasi_id || null,
      loket_id: item.loket_id || null,
      jenis_layanan_id: item.jenis_layanan_id || null,
      nib: item.nib,
      applicant_name: item.applicant_name,
      gender: item.gender,
      company: item.company,
      company_address: item.company_address,
      phone: item.phone,
      email: item.email,
      purpose: item.purpose,
      complaint: item.complaint,
      solution: item.solution,
    })
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

  const catById = useMemo(() => Object.fromEntries(categories.map(c => [c.id, c])), [categories])
  const reportCat = catById[report.category_id]
  const showViolations = !!reportCat?.is_violation

  function lokestForLokasi(lokasiId) {
    if (!lokasiId) return lokets
    return lokets.filter(l => !l.lokasi_id || l.lokasi_id === +lokasiId)
  }

  const userBidangId = me?.bidang_id || null
  const reportBidangId = report.bidang_id || userBidangId
  const jenisLayananOptions = reportBidangId
    ? jenisLayanans.filter(j => j.bidang_id === +reportBidangId)
    : jenisLayanans

  const newCat = catById[newItem.category_id]
  const isNewServ = !!newCat?.is_service

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
          <label className="label">Kategori Laporan</label>
          <select className="input" required value={report.category_id || ''} onChange={(e) => setReport({ ...report, category_id: e.target.value ? +e.target.value : '' })}>
            <option value="">Pilih kategori…</option>
            {categories.map(c => <option key={c.id} value={c.id}>{c.name}{c.is_service ? ' (pelayanan)' : ''}</option>)}
          </select>
        </div>
        <div>
          <label className="label">Tanggal</label>
          <input className="input" type="date" value={report.report_date} onChange={(e) => setReport({ ...report, report_date: e.target.value })} required />
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
        {showViolations && (
          <div>
            <label className="label">Jumlah Pelanggaran</label>
            <input className="input" type="number" min="0" value={report.violations_count ?? 0} onChange={(e) => setReport({ ...report, violations_count: Math.max(0, +e.target.value || 0) })} />
          </div>
        )}
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

          <form onSubmit={addItem} className="space-y-3 border border-dashed border-slate-200 rounded-lg p-3">
            <div className="grid grid-cols-1 md:grid-cols-12 gap-2">
              <div className="md:col-span-3">
                <label className="label">Jenis Kegiatan</label>
                <select className="input" required value={newItem.category_id} onChange={(e) => setNewItem({ ...newItem, category_id: e.target.value })}>
                  <option value="">Pilih…</option>
                  {categories.map((c) => <option key={c.id} value={c.id}>{c.name}{c.is_service ? ' (pelayanan)' : ''}</option>)}
                </select>
              </div>
              <div className="md:col-span-2">
                <label className="label">Waktu</label>
                <input type="time" className="input" value={newItem.time} onChange={(e) => setNewItem({ ...newItem, time: e.target.value })} />
              </div>
              {!isNewServ && (
                <>
                  <div className="md:col-span-4">
                    <label className="label">Lokasi (teks)</label>
                    <input className="input" value={newItem.location} onChange={(e) => setNewItem({ ...newItem, location: e.target.value })} placeholder="Alamat / titik lokasi" />
                  </div>
                  <div className="md:col-span-3">
                    <label className="label">Catatan</label>
                    <input className="input" value={newItem.notes} onChange={(e) => setNewItem({ ...newItem, notes: e.target.value })} />
                  </div>
                </>
              )}
              {isNewServ && (
                <>
                  <div className="md:col-span-3">
                    <label className="label">Lokasi</label>
                    <select className="input" value={newItem.lokasi_id || ''} onChange={(e) => setNewItem({ ...newItem, lokasi_id: e.target.value, loket_id: '' })}>
                      <option value="">— Pilih —</option>
                      {lokasis.map(l => <option key={l.id} value={l.id}>{l.name}</option>)}
                    </select>
                  </div>
                  <div className="md:col-span-2">
                    <label className="label">Loket</label>
                    <select className="input" value={newItem.loket_id || ''} onChange={(e) => setNewItem({ ...newItem, loket_id: e.target.value })}>
                      <option value="">— Pilih —</option>
                      {lokestForLokasi(newItem.lokasi_id).map(l => <option key={l.id} value={l.id}>{l.name}</option>)}
                    </select>
                  </div>
                  <div className="md:col-span-2">
                    <label className="label">Jenis Layanan</label>
                    <select className="input" value={newItem.jenis_layanan_id || ''} onChange={(e) => {
                      const jl = jenisLayananOptions.find(j => j.id === +e.target.value)
                      setNewItem({ ...newItem, jenis_layanan_id: e.target.value, purpose: jl?.name || newItem.purpose })
                    }}>
                      <option value="">— Pilih —</option>
                      {jenisLayananOptions.map(j => <option key={j.id} value={j.id}>{j.name}</option>)}
                    </select>
                  </div>
                </>
              )}
            </div>

            {isNewServ && (
              <div className="grid grid-cols-1 md:grid-cols-12 gap-2">
                <div className="md:col-span-3">
                  <label className="label">NIB / No Online / SK</label>
                  <input className="input" value={newItem.nib} onChange={(e) => setNewItem({ ...newItem, nib: e.target.value })} />
                </div>
                <div className="md:col-span-3">
                  <label className="label">Nama Pemohon</label>
                  <input className="input" value={newItem.applicant_name} onChange={(e) => setNewItem({ ...newItem, applicant_name: e.target.value })} />
                </div>
                <div className="md:col-span-2">
                  <label className="label">Jenis Kelamin</label>
                  <select className="input" value={newItem.gender} onChange={(e) => setNewItem({ ...newItem, gender: e.target.value })}>
                    <option value="">—</option>
                    <option value="L">Laki-laki</option>
                    <option value="P">Perempuan</option>
                  </select>
                </div>
                <div className="md:col-span-4">
                  <label className="label">Perusahaan</label>
                  <input className="input" value={newItem.company} onChange={(e) => setNewItem({ ...newItem, company: e.target.value })} />
                </div>
                <div className="md:col-span-12">
                  <label className="label">Alamat Perusahaan</label>
                  <input className="input" value={newItem.company_address} onChange={(e) => setNewItem({ ...newItem, company_address: e.target.value })} />
                </div>
                <div className="md:col-span-4">
                  <label className="label">No. Telp / WhatsApp</label>
                  <input className="input" value={newItem.phone} onChange={(e) => setNewItem({ ...newItem, phone: e.target.value })} />
                </div>
                <div className="md:col-span-4">
                  <label className="label">Email</label>
                  <input className="input" type="email" value={newItem.email} onChange={(e) => setNewItem({ ...newItem, email: e.target.value })} />
                </div>
                <div className="md:col-span-6">
                  <label className="label">Pertanyaan / Aduan</label>
                  <textarea className="input" rows={2} value={newItem.complaint} onChange={(e) => setNewItem({ ...newItem, complaint: e.target.value })} />
                </div>
                <div className="md:col-span-6">
                  <label className="label">Solusi</label>
                  <textarea className="input" rows={2} value={newItem.solution} onChange={(e) => setNewItem({ ...newItem, solution: e.target.value })} />
                </div>
              </div>
            )}

            <div className="flex justify-end">
              <button className="btn-primary">+ Tambah Item</button>
            </div>
          </form>

          <div className="space-y-3">
            {items.map((it, idx) => {
              const cat = catById[it.category_id] || it.category
              const isServ = !!cat?.is_service
              const editing = editingItemId === it.id
              return (
                <div key={it.id} className="border border-slate-200 rounded-lg p-3">
                  <div className="flex items-start justify-between gap-3">
                    <div className="flex-1 min-w-0">
                      {editing ? (
                        <div className="space-y-2">
                          <div className="grid grid-cols-1 md:grid-cols-12 gap-2">
                            <div className="md:col-span-3">
                              <label className="label">Kategori</label>
                              <select className="input" value={it.category_id} onChange={(e) => setItems(items.map(x => x.id === it.id ? { ...x, category_id: +e.target.value } : x))}>
                                {categories.map((c) => <option key={c.id} value={c.id}>{c.name}{c.is_service ? ' (pelayanan)' : ''}</option>)}
                              </select>
                            </div>
                            <div className="md:col-span-2">
                              <label className="label">Waktu</label>
                              <input type="time" className="input" value={it.time?.slice(0,5) || ''} onChange={(e) => setItems(items.map(x => x.id === it.id ? { ...x, time: e.target.value } : x))} />
                            </div>
                            {!isServ ? (
                              <>
                                <div className="md:col-span-4">
                                  <label className="label">Lokasi (teks)</label>
                                  <input className="input" value={it.location || ''} onChange={(e) => setItems(items.map(x => x.id === it.id ? { ...x, location: e.target.value } : x))} />
                                </div>
                                <div className="md:col-span-3">
                                  <label className="label">Catatan</label>
                                  <input className="input" value={it.notes || ''} onChange={(e) => setItems(items.map(x => x.id === it.id ? { ...x, notes: e.target.value } : x))} />
                                </div>
                              </>
                            ) : (
                              <>
                                <div className="md:col-span-3">
                                  <label className="label">Lokasi</label>
                                  <select className="input" value={it.lokasi_id || ''} onChange={(e) => setItems(items.map(x => x.id === it.id ? { ...x, lokasi_id: e.target.value ? +e.target.value : null, loket_id: null } : x))}>
                                    <option value="">— Pilih —</option>
                                    {lokasis.map(l => <option key={l.id} value={l.id}>{l.name}</option>)}
                                  </select>
                                </div>
                                <div className="md:col-span-2">
                                  <label className="label">Loket</label>
                                  <select className="input" value={it.loket_id || ''} onChange={(e) => setItems(items.map(x => x.id === it.id ? { ...x, loket_id: e.target.value ? +e.target.value : null } : x))}>
                                    <option value="">— Pilih —</option>
                                    {lokestForLokasi(it.lokasi_id).map(l => <option key={l.id} value={l.id}>{l.name}</option>)}
                                  </select>
                                </div>
                                <div className="md:col-span-2">
                                  <label className="label">Jenis Layanan</label>
                                  <select className="input" value={it.jenis_layanan_id || ''} onChange={(e) => {
                                    const jl = jenisLayananOptions.find(j => j.id === +e.target.value)
                                    setItems(items.map(x => x.id === it.id ? { ...x, jenis_layanan_id: e.target.value ? +e.target.value : null, purpose: jl?.name || x.purpose } : x))
                                  }}>
                                    <option value="">— Pilih —</option>
                                    {jenisLayananOptions.map(j => <option key={j.id} value={j.id}>{j.name}</option>)}
                                  </select>
                                </div>
                              </>
                            )}
                          </div>

                          {isServ && (
                            <div className="grid grid-cols-1 md:grid-cols-12 gap-2">
                              <div className="md:col-span-3">
                                <label className="label">NIB / No Online / SK</label>
                                <input className="input" value={it.nib || ''} onChange={(e) => setItems(items.map(x => x.id === it.id ? { ...x, nib: e.target.value } : x))} />
                              </div>
                              <div className="md:col-span-3">
                                <label className="label">Nama Pemohon</label>
                                <input className="input" value={it.applicant_name || ''} onChange={(e) => setItems(items.map(x => x.id === it.id ? { ...x, applicant_name: e.target.value } : x))} />
                              </div>
                              <div className="md:col-span-2">
                                <label className="label">Jenis Kelamin</label>
                                <select className="input" value={it.gender || ''} onChange={(e) => setItems(items.map(x => x.id === it.id ? { ...x, gender: e.target.value } : x))}>
                                  <option value="">—</option>
                                  <option value="L">Laki-laki</option>
                                  <option value="P">Perempuan</option>
                                </select>
                              </div>
                              <div className="md:col-span-4">
                                <label className="label">Perusahaan</label>
                                <input className="input" value={it.company || ''} onChange={(e) => setItems(items.map(x => x.id === it.id ? { ...x, company: e.target.value } : x))} />
                              </div>
                              <div className="md:col-span-12">
                                <label className="label">Alamat Perusahaan</label>
                                <input className="input" value={it.company_address || ''} onChange={(e) => setItems(items.map(x => x.id === it.id ? { ...x, company_address: e.target.value } : x))} />
                              </div>
                              <div className="md:col-span-4">
                                <label className="label">No. Telp / WhatsApp</label>
                                <input className="input" value={it.phone || ''} onChange={(e) => setItems(items.map(x => x.id === it.id ? { ...x, phone: e.target.value } : x))} />
                              </div>
                              <div className="md:col-span-4">
                                <label className="label">Email</label>
                                <input className="input" type="email" value={it.email || ''} onChange={(e) => setItems(items.map(x => x.id === it.id ? { ...x, email: e.target.value } : x))} />
                              </div>
                              <div className="md:col-span-6">
                                <label className="label">Pertanyaan / Aduan</label>
                                <textarea className="input" rows={2} value={it.complaint || ''} onChange={(e) => setItems(items.map(x => x.id === it.id ? { ...x, complaint: e.target.value } : x))} />
                              </div>
                              <div className="md:col-span-6">
                                <label className="label">Solusi</label>
                                <textarea className="input" rows={2} value={it.solution || ''} onChange={(e) => setItems(items.map(x => x.id === it.id ? { ...x, solution: e.target.value } : x))} />
                              </div>
                            </div>
                          )}
                        </div>
                      ) : isServ ? (
                        <div className="text-sm space-y-1">
                          <div>
                            <span className="font-semibold">#{idx + 1}. {cat?.name}</span>
                            <span className="ml-2 text-slate-500">{it.time ? it.time.slice(0,5) : ''}</span>
                            {(it.jenis_layanan?.name || it.purpose) && <span className="ml-2 pill bg-amber-100 text-amber-700">{it.jenis_layanan?.name || it.purpose}</span>}
                          </div>
                          <div className="text-xs text-slate-500">
                            {it.lokasi?.name || '-'}{it.loket?.name ? ' • ' + it.loket.name : ''}
                          </div>
                          <div className="mt-1">
                            <span className="font-medium">{it.applicant_name || '-'}</span>
                            {it.gender && <span className="text-slate-500"> ({it.gender})</span>}
                            {it.company && <span className="text-slate-500"> — {it.company}</span>}
                          </div>
                          {it.nib && <div className="text-xs text-slate-500">NIB: {it.nib}</div>}
                          {it.company_address && <div className="text-xs text-slate-500">📍 {it.company_address}</div>}
                          {(it.phone || it.email) && <div className="text-xs text-slate-500">{it.phone}{it.phone && it.email ? ' • ' : ''}{it.email}</div>}
                          {it.complaint && <div className="mt-1 text-slate-700"><b>Aduan:</b> {it.complaint}</div>}
                          {it.solution && <div className="text-slate-700"><b>Solusi:</b> {it.solution}</div>}
                        </div>
                      ) : (
                        <>
                          <div className="text-sm">
                            <span className="font-semibold">#{idx + 1}. {cat?.name}</span>
                            <span className="ml-2 text-slate-500">{it.time ? it.time.slice(0,5) : ''}</span>
                          </div>
                          <div className="text-sm text-slate-700 mt-1">📍 {it.location}</div>
                          {it.notes && <div className="text-sm text-slate-600 mt-1 whitespace-pre-line">{it.notes}</div>}
                        </>
                      )}
                    </div>
                    <div className="flex flex-col gap-1 text-xs">
                      {editing ? (
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

                  <div className="mt-3 flex flex-wrap gap-2 items-start">
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
              )
            })}
            {items.length === 0 && <div className="text-sm text-slate-500">Belum ada item. Tambahkan di atas.</div>}
          </div>
        </div>
      )}
    </div>
  )
}
