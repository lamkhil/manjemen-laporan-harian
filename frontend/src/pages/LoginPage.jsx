import { useState } from 'react'
import { useNavigate, Navigate } from 'react-router-dom'
import { login, getToken } from '../api/auth'

export default function LoginPage() {
  const navigate = useNavigate()
  const [nip, setNip] = useState('')
  const [password, setPassword] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  if (getToken()) return <Navigate to="/dashboard" replace />

  async function onSubmit(e) {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      await login(nip.trim().replace(/\s+/g, ''), password)
      navigate('/dashboard', { replace: true })
    } catch (err) {
      let msg = err?.response?.data?.message
      if (!msg) {
        if (err?.response) msg = `HTTP ${err.response.status} dari server.`
        else if (err?.request) msg = 'Tidak bisa menghubungi server. Cek koneksi internet & DNS.'
        else msg = err?.message || 'Gagal login.'
      }
      setError(msg)
      console.error('Login error:', err)
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen grid place-items-center bg-gradient-to-br from-brand-50 to-slate-100 p-4">
      <div className="w-full max-w-sm card p-6">
        <div className="text-center mb-6">
          <div className="mx-auto w-14 h-14 rounded-full bg-brand-900 grid place-items-center mb-3">
            <span className="text-amber-400 text-2xl font-bold">S</span>
          </div>
          <h1 className="text-lg font-bold text-slate-800">Laporan Harian</h1>
          <p className="text-xs text-slate-500">Pengawasan Kewilayahan DPMPTSP</p>
        </div>
        <form onSubmit={onSubmit} className="space-y-3">
          <div>
            <label className="label">NIP / NIK</label>
            <input
              className="input"
              type="text"
              inputMode="numeric"
              autoComplete="username"
              pattern="[0-9 ]*"
              value={nip}
              onChange={(e) => setNip(e.target.value)}
              placeholder="cth. 198001012005011001"
              required
              autoFocus
            />
          </div>
          <div>
            <label className="label">Password</label>
            <input
              className="input"
              type="password"
              autoComplete="current-password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
            />
          </div>
          {error && <div className="text-xs text-red-600 bg-red-50 border border-red-100 rounded-md p-2">{error}</div>}
          <button className="btn-primary w-full" disabled={loading}>{loading ? 'Memproses...' : 'Masuk'}</button>
        </form>
        <div className="mt-4 text-[11px] text-slate-400 text-center">
          Demo admin: <code>198001012005011001</code> / <code>admin123</code>
        </div>
      </div>
    </div>
  )
}
