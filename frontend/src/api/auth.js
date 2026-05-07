import { api } from './client'

export function getStoredUser() {
  try { return JSON.parse(localStorage.getItem('user') || 'null') } catch { return null }
}

export function getToken() { return localStorage.getItem('token') }

export async function login(nip, password) {
  const { data } = await api.post('/login', { nip, password })
  localStorage.setItem('token', data.token)
  localStorage.setItem('user', JSON.stringify(data.user))
  return data.user
}

export async function logout() {
  try { await api.post('/logout') } catch {}
  localStorage.removeItem('token')
  localStorage.removeItem('user')
}

export async function fetchMe() {
  const { data } = await api.get('/me')
  localStorage.setItem('user', JSON.stringify(data))
  return data
}
