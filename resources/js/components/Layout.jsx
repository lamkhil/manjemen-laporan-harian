import { useState, useEffect } from 'react'
import { NavLink, Outlet, useNavigate, useLocation } from 'react-router-dom'
import { logout, getStoredUser } from '../api/auth'

export default function Layout() {
  const navigate = useNavigate()
  const location = useLocation()
  const user = getStoredUser()
  const [drawerOpen, setDrawerOpen] = useState(false)

  useEffect(() => {
    setDrawerOpen(false)
  }, [location.pathname])

  useEffect(() => {
    if (drawerOpen) {
      document.body.style.overflow = 'hidden'
    } else {
      document.body.style.overflow = ''
    }
    return () => { document.body.style.overflow = '' }
  }, [drawerOpen])

  async function handleLogout() {
    await logout()
    navigate('/login', { replace: true })
  }

  const navCls = ({ isActive }) =>
    `flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium ${
      isActive ? 'bg-brand-600 text-white' : 'text-slate-600 hover:bg-slate-100'
    }`

  const navLinks = (
    <>
      <NavLink to="/dashboard" className={navCls}>📊 Dashboard</NavLink>
      <NavLink to="/aktivitas" className={navCls}>✏️ Aktivitas</NavLink>
      <NavLink to="/categories" className={navCls}>🏷️ Kategori</NavLink>
      {user?.role === 'admin' && <>
        <NavLink to="/bidangs" className={navCls}>🏛️ Bidang</NavLink>
        <NavLink to="/jenis-layanans" className={navCls}>🛎️ Jenis Layanan</NavLink>
        <NavLink to="/lokasis" className={navCls}>📍 Lokasi</NavLink>
        <NavLink to="/lokets" className={navCls}>🪟 Loket</NavLink>
        <NavLink to="/users" className={navCls}>👥 Pegawai</NavLink>
      </>}
    </>
  )

  return (
    <div className="min-h-screen flex">
      {/* Desktop sidebar */}
      <aside className="w-60 bg-white border-r border-slate-200 hidden md:flex md:flex-col">
        <div className="px-4 py-5 border-b border-slate-200">
          <div className="font-bold text-brand-700">Laporan Harian</div>
          <div className="text-xs text-slate-500">DPMPTSP Surabaya</div>
        </div>
        <nav className="p-3 space-y-1 flex-1">
          {navLinks}
        </nav>
        <div className="p-3 border-t border-slate-200">
          <div className="text-xs text-slate-500 mb-1">{user?.name}</div>
          <div className="text-xs text-slate-400 mb-2">{user?.role === 'admin' ? 'Administrator' : 'User'}</div>
          <button onClick={handleLogout} className="btn-ghost w-full text-xs">Logout</button>
        </div>
      </aside>

      {/* Mobile drawer overlay */}
      {drawerOpen && (
        <div
          className="fixed inset-0 bg-black/40 z-40 md:hidden"
          onClick={() => setDrawerOpen(false)}
          aria-hidden="true"
        />
      )}

      {/* Mobile drawer */}
      <aside
        className={`fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-slate-200 flex flex-col transform transition-transform duration-200 ease-in-out md:hidden ${
          drawerOpen ? 'translate-x-0' : '-translate-x-full'
        }`}
      >
        <div className="px-4 py-5 border-b border-slate-200 flex items-center justify-between">
          <div>
            <div className="font-bold text-brand-700">Laporan Harian</div>
            <div className="text-xs text-slate-500">DPMPTSP Surabaya</div>
          </div>
          <button
            onClick={() => setDrawerOpen(false)}
            className="p-1 rounded text-slate-500 hover:bg-slate-100"
            aria-label="Tutup menu"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
        <nav className="p-3 space-y-1 flex-1 overflow-y-auto">
          {navLinks}
        </nav>
        <div className="p-3 border-t border-slate-200">
          <div className="text-xs text-slate-500 mb-1">{user?.name}</div>
          <div className="text-xs text-slate-400 mb-2">{user?.role === 'admin' ? 'Administrator' : 'User'}</div>
          <button onClick={handleLogout} className="btn-ghost w-full text-xs">Logout</button>
        </div>
      </aside>

      <main className="flex-1 min-w-0">
        <header className="md:hidden bg-white border-b border-slate-200 p-3 flex items-center justify-between">
          <button
            onClick={() => setDrawerOpen(true)}
            className="p-2 -ml-2 rounded text-slate-700 hover:bg-slate-100"
            aria-label="Buka menu"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <line x1="3" y1="6" x2="21" y2="6"></line>
              <line x1="3" y1="12" x2="21" y2="12"></line>
              <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
          </button>
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
