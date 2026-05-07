import { NavLink, Outlet, useNavigate } from 'react-router-dom'
import { logout, getStoredUser } from '../api/auth'

export default function Layout() {
  const navigate = useNavigate()
  const user = getStoredUser()

  async function handleLogout() {
    await logout()
    navigate('/login', { replace: true })
  }

  const navCls = ({ isActive }) =>
    `flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium ${
      isActive ? 'bg-brand-600 text-white' : 'text-slate-600 hover:bg-slate-100'
    }`

  return (
    <div className="min-h-screen flex">
      <aside className="w-60 bg-white border-r border-slate-200 hidden md:flex md:flex-col">
        <div className="px-4 py-5 border-b border-slate-200">
          <div className="font-bold text-brand-700">Laporan Harian</div>
          <div className="text-xs text-slate-500">DPMPTSP Surabaya</div>
        </div>
        <nav className="p-3 space-y-1 flex-1">
          <NavLink to="/dashboard" className={navCls}>📊 Dashboard</NavLink>
          <NavLink to="/reports" className={navCls}>📄 Laporan</NavLink>
          <NavLink to="/categories" className={navCls}>🏷️ Kategori</NavLink>
          {user?.role === 'admin' && <NavLink to="/users" className={navCls}>👥 Pegawai</NavLink>}
        </nav>
        <div className="p-3 border-t border-slate-200">
          <div className="text-xs text-slate-500 mb-1">{user?.name}</div>
          <div className="text-xs text-slate-400 mb-2">{user?.role === 'admin' ? 'Administrator' : 'User'}</div>
          <button onClick={handleLogout} className="btn-ghost w-full text-xs">Logout</button>
        </div>
      </aside>
      <main className="flex-1 min-w-0">
        <header className="md:hidden bg-white border-b border-slate-200 p-3 flex items-center justify-between">
          <div className="font-bold text-brand-700">Laporan Harian</div>
          <button onClick={handleLogout} className="text-xs text-red-600">Logout</button>
        </header>
        <div className="p-4 md:p-6 max-w-7xl mx-auto">
          <Outlet />
        </div>
      </main>
    </div>
  )
}
