import { useEffect, useRef, useState } from 'react'
import { api, apiBase } from '../api/client'
import { getStoredUser } from '../api/auth'

const empty = { name: '', nip: '', email: '', password: '', role: 'user', unit_kerja: '', jabatan: '', bidang_id: '', default_lokasi_id: '', default_loket_id: '' }

export default function UsersPage() {
  const me = getStoredUser()
  const isAdmin = me?.role === 'admin'
  const [items, setItems] = useState([])
  const [loading, setLoading] = useState(false)
  const [editing, setEditing] = useState(null)
  const [form, setForm] = useState(empty)
  const [error, setError] = useState('')
  const [search, setSearch] = useState('')
  const [importing, setImporting] = useState(false)
  const [importResult, setImportResult] = useState(null)
  const [lokasis, setLokasis] = useState([])
  const [lokets, setLokets] = useState([])
  const [bidangs, setBidangs] = useState([])
  const fileRef = useRef(null)

  async function load() {
    setLoading(true)
    try {
      const { data } = await api.get('/users', { params: { search, per_page: 100 } })
      setItems(data.data || [])
    } finally { setLoading(false) }
  }

  async function loadMaster() {
    const [a, b, c] = await Promise.all([
      api.get('/lokasis', { params: { active_only: 1 } }),
      api.get('/lokets', { params: { active_only: 1 } }),
      api.get('/bidangs', { params: { active_only: 1 } }),
    ])
    setLokasis(a.data); setLokets(b.data); setBidangs(c.data)
  }

  useEffect(() => { if (isAdmin) { load(); loadMaster() } }, [search])

  if (!isAdmin) {
    return <div className="card p-6 text-sm text-slate-500">Hanya admin yang dapat mengelola pengguna.</div>
  }

  function startNew() { setEditing('new'); setForm(empty); setError('') }
  function startEdit(u) {
    setEditing(u.id)
    setForm({
      ...empty,
      ...u,
      password: '',
      bidang_id: u.bidang_id || '',
      default_lokasi_id: u.default_lokasi_id || '',
      default_loket_id: u.default_loket_id || '',
    })
    setError('')
  }
  function cancel() { setEditing(null); setForm(empty); setError('') }

  async function save(e) {
    e.preventDefault()
    setError('')
    try {
      const payload = { ...form }
      if (!payload.password) delete payload.password
      if (!payload.email) payload.email = null
      payload.bidang_id = payload.bidang_id || null
      payload.default_lokasi_id = payload.default_lokasi_id || null
      payload.default_loket_id = payload.default_loket_id || null
      if (editing === 'new') await api.post('/users', payload)
      else await api.patch(`/users/${editing}`, payload)
      cancel()
      load()
    } catch (err) {
      const errs = err?.response?.data?.errors
      if (errs) setError(Object.values(errs).flat().join(', '))
      else setError(err?.response?.data?.message || 'Gagal menyimpan.')
    }
  }

  const filteredLokets = form.default_lokasi_id
    ? lokets.filter(l => !l.lokasi_id || l.lokasi_id === +form.default_lokasi_id)
    : lokets

  async function remove(id) {
    if (!confirm('Hapus pengguna ini?')) return
    try { await api.delete(`/users/${id}`); load() }
    catch (err) { alert(err?.response?.data?.message || 'Gagal hapus.') }
  }

  function downloadExample() {
    window.open(`${apiBase}/users/example-csv`, '_blank')
  }

  async function uploadCsv(file) {
    setImporting(true)
    setImportResult(null)
    try {
      const fd = new FormData()
      fd.append('file', file)
      const { data } = await api.post('/users/import', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
      setImportResult(data)
      load()
    } catch (err) {
      setImportResult({ error: err?.response?.data?.message || 'Gagal import.' })
    } finally {
      setImporting(false)
      if (fileRef.current) fileRef.current.value = ''
    }
  }

  return (
    <div className="space-y-4">
      <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
        <div>
          <h1 className="text-xl font-bold">Manajemen Pegawai</h1>
          <p className="text-sm text-slate-500">Kelola akun pegawai pengawas wilayah.</p>
        </div>
        <div className="flex gap-2 flex-wrap">
          <button className="btn-ghost" onClick={downloadExample}>📥 Contoh CSV</button>
          <label className="btn-ghost cursor-pointer">
            {importing ? 'Mengimport…' : '📤 Import CSV'}
            <input
              ref={fileRef}
              type="file"
              accept=".csv,text/csv"
              className="hidden"
              onChange={(e) => e.target.files[0] && uploadCsv(e.target.files[0])}
              disabled={importing}
            />
          </label>
          <button className="btn-primary" onClick={startNew}>+ Pegawai</button>
        </div>
      </div>

      {importResult && (
        <div className={`card p-3 text-sm ${importResult.error ? 'text-red-700' : 'text-emerald-700'}`}>
          {importResult.error ? (
            <>Error: {importResult.error}</>
          ) : (
            <>
              <div>✓ Import selesai: <b>{importResult.created}</b> dibuat, <b>{importResult.updated}</b> diperbarui (total {importResult.total_processed}).</div>
              {importResult.errors?.length > 0 && (
                <details className="mt-2">
                  <summary className="cursor-pointer text-amber-700">{importResult.errors.length} baris gagal — klik untuk lihat</summary>
                  <ul className="list-disc pl-5 mt-1 text-xs text-amber-700">
                    {importResult.errors.map((e, i) => <li key={i}>{e}</li>)}
                  </ul>
                </details>
              )}
            </>
          )}
        </div>
      )}

      <div className="card p-3 flex gap-2 items-end">
        <div className="flex-1">
          <label className="label">Cari</label>
          <input className="input" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="NIP, nama, atau email…" />
        </div>
      </div>

      {editing && (
        <form className="card p-4 space-y-3" onSubmit={save}>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label className="label">NIP / NIK</label>
              <input className="input" required value={form.nip} onChange={(e) => setForm({ ...form, nip: e.target.value })} />
            </div>
            <div>
              <label className="label">Nama</label>
              <input className="input" required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
            </div>
            <div>
              <label className="label">Email (opsional)</label>
              <input className="input" type="email" value={form.email || ''} onChange={(e) => setForm({ ...form, email: e.target.value })} />
            </div>
            <div>
              <label className="label">Password {editing !== 'new' && <span className="text-slate-400">(kosongkan untuk tidak ubah)</span>}</label>
              <input className="input" type="password" required={editing === 'new'} value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} />
            </div>
            <div>
              <label className="label">Role</label>
              <select className="input" value={form.role} onChange={(e) => setForm({ ...form, role: e.target.value })}>
                <option value="user">User</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <div>
              <label className="label">Unit Kerja</label>
              <input className="input" value={form.unit_kerja || ''} onChange={(e) => setForm({ ...form, unit_kerja: e.target.value })} />
            </div>
            <div className="md:col-span-2">
              <label className="label">Jabatan</label>
              <input className="input" value={form.jabatan || ''} onChange={(e) => setForm({ ...form, jabatan: e.target.value })} />
            </div>
            <div>
              <label className="label">Bidang</label>
              <select className="input" value={form.bidang_id || ''} onChange={(e) => setForm({ ...form, bidang_id: e.target.value })}>
                <option value="">— Tidak ada —</option>
                {bidangs.map(b => <option key={b.id} value={b.id}>{b.name}</option>)}
              </select>
            </div>
            <div>
              <label className="label">Default Lokasi</label>
              <select className="input" value={form.default_lokasi_id || ''} onChange={(e) => setForm({ ...form, default_lokasi_id: e.target.value, default_loket_id: '' })}>
                <option value="">— Tidak ada —</option>
                {lokasis.map(l => <option key={l.id} value={l.id}>{l.name}</option>)}
              </select>
            </div>
            <div>
              <label className="label">Default Loket</label>
              <select className="input" value={form.default_loket_id || ''} onChange={(e) => setForm({ ...form, default_loket_id: e.target.value })}>
                <option value="">— Tidak ada —</option>
                {filteredLokets.map(l => <option key={l.id} value={l.id}>{l.name}</option>)}
              </select>
            </div>
          </div>
          {error && <div className="text-xs text-red-600">{error}</div>}
          <div className="flex gap-2">
            <button className="btn-primary" type="submit">Simpan</button>
            <button className="btn-ghost" type="button" onClick={cancel}>Batal</button>
          </div>
        </form>
      )}

      <div className="card overflow-x-auto">
        <table className="w-full text-sm min-w-[860px]">
          <thead className="bg-slate-50 text-slate-600">
            <tr>
              <th className="text-left p-3">NIP/NIK</th>
              <th className="text-left p-3">Nama</th>
              <th className="text-left p-3">Email</th>
              <th className="text-left p-3">Role</th>
              <th className="text-left p-3">Unit/Jabatan</th>
              <th className="text-left p-3">Lokasi/Loket</th>
              <th className="p-3"></th>
            </tr>
          </thead>
          <tbody>
            {loading && <tr><td colSpan={7} className="p-3 text-slate-500">Memuat…</td></tr>}
            {!loading && items.length === 0 && <tr><td colSpan={7} className="p-3 text-slate-500">Tidak ada data.</td></tr>}
            {items.map((u) => (
              <tr key={u.id} className="border-t border-slate-100">
                <td className="p-3 font-mono text-xs">{u.nip || '-'}</td>
                <td className="p-3 font-medium">{u.name}</td>
                <td className="p-3 text-slate-600">{u.email || '-'}</td>
                <td className="p-3">{u.role === 'admin' ? <span className="pill bg-violet-100 text-violet-700">admin</span> : <span className="pill bg-slate-100">user</span>}</td>
                <td className="p-3 text-slate-600">
                  <div>{u.unit_kerja}</div>
                  <div className="text-xs text-slate-400">{u.jabatan}</div>
                </td>
                <td className="p-3 text-slate-600 text-xs">
                  <div>{u.default_lokasi?.name || '-'}</div>
                  <div className="text-slate-400">{u.default_loket?.name || '-'}</div>
                </td>
                <td className="p-3 text-right whitespace-nowrap">
                  <button className="text-brand-600 mr-3" onClick={() => startEdit(u)}>Edit</button>
                  {u.id !== me.id && <button className="text-red-600" onClick={() => remove(u.id)}>Hapus</button>}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
