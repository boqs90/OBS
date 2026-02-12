// src/utils/auth.js
export function isLoggedIn() {
  return !!(localStorage.getItem('authToken') || sessionStorage.getItem('authToken'));
}

export function getToken() {
  return localStorage.getItem('authToken') || sessionStorage.getItem('authToken');
}

export function getUser() {
  const userString = localStorage.getItem('user') || sessionStorage.getItem('user');
  return userString ? JSON.parse(userString) : null;
}

export function logout() {
  localStorage.removeItem('authToken');
  localStorage.removeItem('user');
  sessionStorage.removeItem('authToken');
  sessionStorage.removeItem('user');
}
