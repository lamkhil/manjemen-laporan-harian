import { useEffect, useState } from 'react'
import { api } from '../api/client'
import { getStoredUser } from '../api/auth'

const empty = { name: '', bidang_id: '', description: '', is_active: true, sort_order: 0 }

export default function JenisLayananPage() {
  const user = getStoredUser()
  const isAdmin = user?.role === 'admin'
  const [items, setItems] = useState([])
  const [bidangs, setBidangs] = useState([])
  const [loading, setLoading] = useState(false)
  const [editing, setEditing] = useState(null)
  const [form, setForm] = useState(empty)
  const [error, setError] = useState('')
  const [filterBidang, setFilterBidang] = useState('')

  async function load() {
    setLoading(true)
    try {
      const params = filterBidang ? { bidang_id: filterBidang } : {}
      const [a, b] = await Promise.all([
        api.get('/jenis-layanans', { params }),
        api.get('/bidangs'),
      ])
      setItems(a.data)
      setBidangs(b.data)
    } finally { setLoading(false) }
  }
  useEffect(() => { load() }, [filterBidang])

  function startNew() { setEditing('new'); setForm({ ...empty, bidang_id: filterBidang || '' }); setError('') }
  function startEdit(it) { setEditing(it.id); setForm({ ...empty, ...it, bidang_id: it.bidang_id || '' }); setError('') }
  function cancel() { setEditing(null); setForm(empty); setError('') }

  async function save(e) {
    e.preventDefault()
    setError('')
    try {
      if (editing === 'new') await api.post('/jenis-layanans', form)
      else await api.patch(`/jenis-layanans/${editing}`, form)
      cancel(); load()
    } catch (err) { setError(err?.response?.data?.message || 'Gagal menyimpan.') }
  }

  async function remove(id) {
    if (!confirm('Hapus jenis layanan ini?')) return
    try { await api.delete(`/jenis-layanans/${id}`); load() }
    catch (err) { alert(err?.response?.data?.message || 'Gagal hapus.') }
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between gap-2">
        <div>
          <h1 className="text-xl font-bold">Jenis Layanan</h1>
          <p className="text-sm text-slate-500">Master jenis layanan per bidang.</p>
        </div>
        <div className="flex gap-2">
          <select className="input" value={filterBidang} onChange={(e) => setFilterBidang(e.target.value)}>
            <option value="">Semua bidang</option>
            {bidangs.map(b => <option key={b.id} value={b.id}>{b.name}</option>)}
          </select>
          {isAdmin && <button className="btn-primary" onClick={startNew}>+ Jenis Layanan</button>}
        </div>
      </div>

      {editing && (
        <form className="card p-4 space-y-3" onSubmit={save}>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label className="label">Bidang</label>
              <select className="input" required value={form.bidang_id} onChange={(e) => setForm({ ...form, bidang_id: e.target.value })}>
                <option value="">Pilih bidang…</option>
                {bidangs.map(b => <option key={b.id} value={b.id}>{b.name}</option>)}
              </select>
            </div>
            <div>
              <label className="label">Nama</label>
              <input className="input" required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
            </div>
            <div className="md:col-span-2">
              <label className="label">Deskripsi</label>
              <input className="input" value={form.description || ''} onChange={(e) => setForm({ ...form, description: e.target.value })} />
            </div>
            <div>
              <label className="label">Urutan</label>
              <input className="input" type="number" value={form.sort_order} onChange={(e) => setForm({ ...form, sort_order: +e.target.value })} />
            </div>
            <label className="inline-flex items-center gap-2 text-sm">
              <input type="checkbox" checked={!!form.is_active} onChange={(e) => setForm({ ...form, is_active: e.target.checked })} /> Aktif
            </label>
          </div>
          {error && <div className="text-xs text-red-600">{error}</div>}
          <div className="flex gap-2">
            <button className="btn-primary" type="submit">Simpan</button>
            <button className="btn-ghost" type="button" onClick={cancel}>Batal</button>
          </div>
        </form>
      )}

      <div className="card overflow-hidden">
        <div className="overflow-x-auto">
        <table className="w-full text-sm min-w-[640px]">
          <thead className="bg-slate-50 text-slate-600">
            <tr>
              <th className="text-left p-3">Bidang</th>
              <th className="text-left p-3">Nama</th>
              <th className="text-left p-3">Deskripsi</th>
              <th className="p-3">Aktif</th>
              <th className="p-3"></th>
            </tr>
          </thead>
          <tbody>
            {loading && <tr><td className="p-3 text-slate-500" colSpan={5}>Memuat…</td></tr>}
            {!loading && items.length === 0 && <tr><td className="p-3 text-slate-500" colSpan={5}>Tidak ada data.</td></tr>}
            {items.map((it) => (
              <tr key={it.id} className="border-t border-slate-100">
                <td className="p-3 text-slate-600">{it.bidang?.name || '-'}</td>
                <td className="p-3 font-medium">{it.name}</td>
                <td className="p-3 text-slate-600">{it.description || '-'}</td>
                <td className="p-3 text-center">{it.is_active ? <span className="pill bg-emerald-100 text-emerald-700">aktif</span> : <span className="pill bg-slate-100">nonaktif</span>}</td>
                <td className="p-3 text-right whitespace-nowrap">
                  {isAdmin && <>
                    <button className="text-brand-600 mr-3" onClick={() => startEdit(it)}>Edit</button>
                    <button className="text-red-600" onClick={() => remove(it.id)}>Hapus</button>
                  </>}
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
