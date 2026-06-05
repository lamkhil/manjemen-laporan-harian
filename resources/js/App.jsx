import { Routes, Route, Navigate } from 'react-router-dom'
import LoginPage from './pages/LoginPage'
import DashboardPage from './pages/DashboardPage'
import CategoriesPage from './pages/CategoriesPage'
import BidangPage from './pages/BidangPage'
import JenisLayananPage from './pages/JenisLayananPage'
import LokasiPage from './pages/LokasiPage'
import LoketPage from './pages/LoketPage'
import UsersPage from './pages/UsersPage'
import AktivitasPage from './pages/AktivitasPage'
import AktivitasEditPage from './pages/AktivitasEditPage'
import ReportPrintPage from './pages/ReportPrintPage'
import Layout from './components/Layout'
import { getToken } from './api/auth'

function Protected({ children }) {
  if (!getToken()) return <Navigate to="/login" replace />
  return children
}

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route path="/print/:id" element={<Protected><ReportPrintPage /></Protected>} />
      <Route element={<Protected><Layout /></Protected>}>
        <Route path="/" element={<Navigate to="/aktivitas" replace />} />
        <Route path="/dashboard" element={<DashboardPage />} />
        <Route path="/categories" element={<CategoriesPage />} />
        <Route path="/bidangs" element={<BidangPage />} />
        <Route path="/jenis-layanans" element={<JenisLayananPage />} />
        <Route path="/lokasis" element={<LokasiPage />} />
        <Route path="/lokets" element={<LoketPage />} />
        <Route path="/users" element={<UsersPage />} />
        <Route path="/aktivitas" element={<AktivitasPage />} />
        <Route path="/aktivitas/new" element={<AktivitasEditPage />} />
        <Route path="/aktivitas/:id" element={<AktivitasEditPage />} />
        <Route path="/reports" element={<Navigate to="/aktivitas" replace />} />
        <Route path="/reports/new" element={<Navigate to="/aktivitas/new" replace />} />
      </Route>
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  )
}
