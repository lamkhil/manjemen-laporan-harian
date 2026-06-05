import { useEffect, useMemo, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { api } from '../api/client'
import { getStoredUser } from '../api/auth'

function emptyForm(me) {
  return {
    report_date: new Date().toISOString().slice(0, 10),
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
    violations_count: 0,
  }
}

export default function AktivitasEditPage() {
  const me = getStoredUser()
  const { id } = useParams()
  const navigate = useNavigate()
  const isNew = !id
  const [form, setForm] = useState(emptyForm(me))
  const [item, setItem] = useState(null)
  const [categories, setCategories] = useState([])
  const [lokasis, setLokasis] = useState([])
  const [lokets, setLokets] = useState([])
  const [jenisLayanans, setJenisLayanans] = useState([])
  const [saving, setSaving] = useState(false)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  async function loadMaster() {
    const [a, b, c, d] = await Promise.all([
      api.get('/categories', { params: { active_only: 1 } }),
      api.get('/lokasis', { params: { active_only: 1 } }),
      api.get('/lokets', { params: { active_only: 1 } }),
      api.get('/jenis-layanans', { params: { active_only: 1 } }),
    ])
    setCategories(a.data); setLokasis(b.data); setLokets(c.data); setJenisLayanans(d.data)
  }

  async function loadItem() {
    if (isNew) return
    setLoading(true)
    try {
      const { data } = await api.get(`/aktivitas/${id}`)
      setItem(data)
      setForm({
        report_date: (data.report?.report_date || '').slice(0, 10),
        category_id: data.category_id || '',
        time: (data.time || '').slice(0, 5),
        location: data.location || '',
        notes: data.notes || '',
        lokasi_id: data.lokasi_id || '',
        loket_id: data.loket_id || '',
        jenis_layanan_id: data.jenis_layanan_id || '',
        nib: data.nib || '',
        applicant_name: data.applicant_name || '',
        gender: data.gender || '',
        company: data.company || '',
        company_address: data.company_address || '',
        phone: data.phone || '',
        email: data.email || '',
        purpose: data.purpose || '',
        complaint: data.complaint || '',
        solution: data.solution || '',
        violations_count: data.report?.violations_count ?? 0,
      })
    } finally { setLoading(false) }
  }

  useEffect(() => { loadMaster() }, [])
  useEffect(() => { loadItem() }, [id])

  const catById = useMemo(() => Object.fromEntries(categories.map(c => [c.id, c])), [categories])
  const selectedCat = catById[form.category_id]
  const isService = !!selectedCat?.is_service
  const isViolation = !!selectedCat?.is_violation

  const userBidangId = me?.bidang_id || null
  const jenisLayananOptions = userBidangId
    ? jenisLayanans.filter(j => j.bidang_id === +userBidangId)
    : jenisLayanans

  function lokestForLokasi(lokasiId) {
    if (!lokasiId) return lokets
    return lokets.filter(l => l.lokasi_id === +lokasiId)
  }

  const availableLokets = lokestForLokasi(form.lokasi_id)
  const lokasiHasLokets = !!form.lokasi_id && availableLokets.length > 0

  async function save(e) {
    e?.preventDefault()
    setError('')
    setSaving(true)
    try {
      const payload = { ...form }
      Object.keys(payload).forEach(k => { if (payload[k] === '') payload[k] = null })
      if (!payload.time) delete payload.time
      if (isNew) {
        const { data } = await api.post('/aktivitas', payload)
        navigate(`/aktivitas/${data.id}`, { replace: true })
      } else {
        await api.patch(`/aktivitas/${id}`, payload)
        await loadItem()
      }
    } catch (err) {
      const errs = err?.response?.data?.errors
      if (errs) setError(Object.values(errs).flat().join(', '))
      else setError(err?.response?.data?.message || 'Gagal menyimpan.')
    } finally { setSaving(false) }
  }

  async function uploadPhoto(file) {
    if (isNew) { alert('Simpan dulu sebelum unggah foto.'); return }
    const fd = new FormData()
    fd.append('photo', file)
    await api.post(`/aktivitas/${id}/photos`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    loadItem()
  }

  async function removePhoto(photoId) {
    if (!confirm('Hapus foto?')) return
    await api.delete(`/aktivitas/${id}/photos/${photoId}`)
    loadItem()
  }

return (
    <div className="space-y-4">
      <div className="flex items-center gap-3">
        <Link to="/aktivitas" className="text-brand-600">← Kembali</Link>
        <h1 className="text-xl font-bold">{isNew ? 'Aktivitas Baru' : `Edit Aktivitas #${id}`}</h1>
      </div>

      {loading && <div className="text-sm text-slate-500">Memuat…</div>}

      <form onSubmit={save} className="card p-4 space-y-4">
        <div className="grid grid-cols-1 md:grid-cols-12 gap-3">
          <div className="md:col-span-3">
            <label className="label">Tanggal</label>
            <input type="date" className="input" required value={form.report_date} onChange={(e) => setForm({ ...form, report_date: e.target.value })} />
          </div>
          <div className="md:col-span-2">
            <label className="label">Waktu</label>
            <input type="time" className="input" value={form.time} onChange={(e) => setForm({ ...form, time: e.target.value })} />
          </div>
          <div className="md:col-span-4">
            <label className="label">Kategori</label>
            <select className="input" required value={form.category_id} onChange={(e) => setForm({ ...form, category_id: e.target.value ? +e.target.value : '' })}>
              <option value="">Pilih kategori…</option>
              {categories.map(c => <option key={c.id} value={c.id}>{c.name}{c.is_service ? ' (pelayanan)' : ''}</option>)}
            </select>
          </div>
          {isViolation && (
            <div className="md:col-span-3">
              <label className="label">Jumlah Pelanggaran</label>
              <input type="number" min="0" className="input" value={form.violations_count} onChange={(e) => setForm({ ...form, violations_count: Math.max(0, +e.target.value || 0) })} />
            </div>
          )}
        </div>

        {isService ? (
          <>
            <div className="grid grid-cols-1 md:grid-cols-12 gap-3">
              <div className="md:col-span-3">
                <label className="label">Lokasi</label>
                <select className="input" value={form.lokasi_id || ''} onChange={(e) => setForm({ ...form, lokasi_id: e.target.value, loket_id: '', location: '' })}>
                  <option value="">— Pilih —</option>
                  {lokasis.map(l => <option key={l.id} value={l.id}>{l.name}</option>)}
                </select>
              </div>
              {lokasiHasLokets ? (
                <div className="md:col-span-3">
                  <label className="label">Loket</label>
                  <select className="input" value={form.loket_id || ''} onChange={(e) => setForm({ ...form, loket_id: e.target.value })}>
                    <option value="">— Pilih —</option>
                    {availableLokets.map(l => <option key={l.id} value={l.id}>{l.name}</option>)}
                  </select>
                </div>
              ) : (
                <div className="md:col-span-3">
                  <label className="label">Detail Lokasi</label>
                  <input className="input" value={form.location} onChange={(e) => setForm({ ...form, location: e.target.value })} placeholder="cth. Meeting Hall Siola R.203" />
                </div>
              )}
              <div className="md:col-span-3">
                <label className="label">Jenis Layanan</label>
                <select className="input" value={form.jenis_layanan_id || ''} onChange={(e) => {
                  const jl = jenisLayananOptions.find(j => j.id === +e.target.value)
                  setForm({ ...form, jenis_layanan_id: e.target.value, purpose: jl?.name || form.purpose })
                }}>
                  <option value="">— Pilih —</option>
                  {jenisLayananOptions.map(j => <option key={j.id} value={j.id}>{j.name}</option>)}
                </select>
              </div>
              <div className="md:col-span-3">
                <label className="label">Keperluan (label)</label>
                <input className="input" value={form.purpose} onChange={(e) => setForm({ ...form, purpose: e.target.value })} placeholder="cth. PERIZINAN" />
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-12 gap-3">
              <div className="md:col-span-3">
                <label className="label">NIB / No Online / SK</label>
                <input className="input" value={form.nib} onChange={(e) => setForm({ ...form, nib: e.target.value })} />
              </div>
              <div className="md:col-span-3">
                <label className="label">Nama Pemohon</label>
                <input className="input" value={form.applicant_name} onChange={(e) => setForm({ ...form, applicant_name: e.target.value })} />
              </div>
              <div className="md:col-span-2">
                <label className="label">Jenis Kelamin</label>
                <select className="input" value={form.gender} onChange={(e) => setForm({ ...form, gender: e.target.value })}>
                  <option value="">—</option>
                  <option value="L">Laki-laki</option>
                  <option value="P">Perempuan</option>
                </select>
              </div>
              <div className="md:col-span-4">
                <label className="label">Perusahaan</label>
                <input className="input" value={form.company} onChange={(e) => setForm({ ...form, company: e.target.value })} />
              </div>
              <div className="md:col-span-12">
                <label className="label">Alamat Perusahaan</label>
                <input className="input" value={form.company_address} onChange={(e) => setForm({ ...form, company_address: e.target.value })} />
              </div>
              <div className="md:col-span-4">
                <label className="label">No. Telp / WhatsApp</label>
                <input className="input" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} />
              </div>
              <div className="md:col-span-4">
                <label className="label">Email</label>
                <input className="input" type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
              </div>
              <div className="md:col-span-6">
                <label className="label">Pertanyaan / Aduan</label>
                <textarea className="input" rows={2} value={form.complaint} onChange={(e) => setForm({ ...form, complaint: e.target.value })} />
              </div>
              <div className="md:col-span-6">
                <label className="label">Solusi</label>
                <textarea className="input" rows={2} value={form.solution} onChange={(e) => setForm({ ...form, solution: e.target.value })} />
              </div>
            </div>
          </>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-12 gap-3">
            <div className="md:col-span-6">
              <label className="label">Lokasi (teks)</label>
              <input className="input" value={form.location} onChange={(e) => setForm({ ...form, location: e.target.value })} placeholder="cth. alamat / titik lokasi" />
            </div>
            <div className="md:col-span-6">
              <label className="label">Catatan</label>
              <input className="input" value={form.notes} onChange={(e) => setForm({ ...form, notes: e.target.value })} />
            </div>
          </div>
        )}

        {error && <div className="text-xs text-red-600">{error}</div>}
        <div className="flex gap-2">
          <button className="btn-primary" disabled={saving}>{saving ? 'Menyimpan…' : 'Simpan'}</button>
          <Link to="/aktivitas" className="btn-ghost">Batal</Link>
        </div>
      </form>

      {!isNew && item && (
        <div className="card p-4 space-y-4">
          <div className="font-semibold">Lampiran</div>
          <div>
            <div className="text-sm text-slate-600 mb-2">Foto Kegiatan</div>
            <div className="flex flex-wrap gap-2">
              {(item.photos || []).map((p) => (
                <div key={p.id} className="relative group">
                  <img src={p.url} alt="" className="w-24 h-24 object-cover rounded-md border border-slate-200" />
                  <button onClick={() => removePhoto(p.id)} className="absolute -top-1 -right-1 bg-red-600 text-white text-xs w-5 h-5 rounded-full opacity-0 group-hover:opacity-100">×</button>
                </div>
              ))}
              <label className="w-24 h-24 grid place-items-center border-2 border-dashed border-slate-300 rounded-md text-xs text-slate-500 cursor-pointer hover:bg-slate-50">
                + Foto
                <input type="file" accept="image/*" className="hidden" onChange={(e) => e.target.files[0] && uploadPhoto(e.target.files[0])} />
              </label>
            </div>
          </div>

        </div>
      )}
    </div>
  )
}
