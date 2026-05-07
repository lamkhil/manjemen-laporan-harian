import { useEffect, useState } from 'react'
import { api } from '../api/client'
import { getStoredUser } from '../api/auth'

const empty = { name: '', color: '#3b82f6', is_violation: false, is_active: true, sort_order: 0, description: '' }

export default function CategoriesPage() {
  const user = getStoredUser()
  const isAdmin = user?.role === 'admin'
  const [items, setItems] = useState([])
  const [loading, setLoading] = useState(false)
  const [editing, setEditing] = useState(null)
  const [form, setForm] = useState(empty)
  const [error, setError] = useState('')

  async function load() {
    setLoading(true)
    try { const { data } = await api.get('/categories'); setItems(data) }
    finally { setLoading(false) }
  }
  useEffect(() => { load() }, [])

  function startNew() { setEditing('new'); setForm(empty); setError('') }
  function startEdit(cat) { setEditing(cat.id); setForm({ ...empty, ...cat }); setError('') }
  function cancel() { setEditing(null); setForm(empty); setError('') }

  async function save(e) {
    e.preventDefault()
    setError('')
    try {
      if (editing === 'new') await api.post('/categories', form)
      else await api.patch(`/categories/${editing}`, form)
      cancel()
      load()
    } catch (err) {
      setError(err?.response?.data?.message || 'Gagal menyimpan.')
    }
  }

  async function remove(id) {
    if (!confirm('Hapus kategori ini?')) return
    try { await api.delete(`/categories/${id}`); load() }
    catch (err) { alert(err?.response?.data?.message || 'Gagal hapus.') }
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-bold">Kategori Laporan</h1>
          <p className="text-sm text-slate-500">Daftar jenis kegiatan / pelanggaran.</p>
        </div>
        {isAdmin && <button className="btn-primary" onClick={startNew}>+ Kategori</button>}
      </div>

      {editing && (
        <form className="card p-4 space-y-3" onSubmit={save}>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label className="label">Nama</label>
              <input className="input" required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
            </div>
            <div>
              <label className="label">Warna</label>
              <input className="input" type="color" value={form.color} onChange={(e) => setForm({ ...form, color: e.target.value })} />
            </div>
            <div className="md:col-span-2">
              <label className="label">Deskripsi</label>
              <textarea className="input" rows={2} value={form.description || ''} onChange={(e) => setForm({ ...form, description: e.target.value })} />
            </div>
            <label className="inline-flex items-center gap-2 text-sm">
              <input type="checkbox" checked={!!form.is_violation} onChange={(e) => setForm({ ...form, is_violation: e.target.checked })} /> Termasuk pelanggaran
            </label>
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
        <table className="w-full text-sm">
          <thead className="bg-slate-50 text-slate-600">
            <tr><th className="text-left p-3">Nama</th><th className="p-3">Warna</th><th className="p-3">Pelanggaran</th><th className="p-3">Aktif</th><th className="p-3"></th></tr>
          </thead>
          <tbody>
            {loading && <tr><td className="p-3 text-slate-500" colSpan={5}>Memuat…</td></tr>}
            {!loading && items.length === 0 && <tr><td className="p-3 text-slate-500" colSpan={5}>Tidak ada data.</td></tr>}
            {items.map((c) => (
              <tr key={c.id} className="border-t border-slate-100">
                <td className="p-3 font-medium">{c.name}</td>
                <td className="p-3"><span className="inline-block w-5 h-5 rounded" style={{ background: c.color }} /></td>
                <td className="p-3 text-center">{c.is_violation ? <span className="pill bg-red-100 text-red-700">YA</span> : '-'}</td>
                <td className="p-3 text-center">{c.is_active ? <span className="pill bg-emerald-100 text-emerald-700">aktif</span> : <span className="pill bg-slate-100">nonaktif</span>}</td>
                <td className="p-3 text-right">
                  {isAdmin && <>
                    <button className="text-brand-600 mr-3" onClick={() => startEdit(c)}>Edit</button>
                    <button className="text-red-600" onClick={() => remove(c.id)}>Hapus</button>
                  </>}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
