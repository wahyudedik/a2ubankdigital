import { useState, useEffect } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import useApi from '../hooks/useApi';
import Input from '../components/ui/Input';
import Button from '../components/ui/Button';

export default function LoginPage() {
  const navigate = useNavigate();
  const { loading, error: apiError, callApi } = useApi();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [localError, setLocalError] = useState(''); 

  // State untuk memicu navigasi setelah data tersimpan aman
  const [loginSuccess, setLoginSuccess] = useState(null);

  // Bersihkan session lama saat halaman dimuat
  useEffect(() => {
    localStorage.clear();
  }, []);

  // Effect untuk menangani pengalihan halaman
  useEffect(() => {
    if (loginSuccess) {
      const roleId = Number(loginSuccess.roleId);

      // Beri sedikit delay (100ms) untuk memastikan localStorage sudah tertulis sempurna
      // sebelum router melakukan pengecekan di halaman tujuan
      const timer = setTimeout(() => {
        console.log('Redirecting user with role:', roleId);

        if (roleId === 9) {
          // Nasabah -> /dashboard (sesuai route di App.jsx)
          navigate('/dashboard', { replace: true });
        } else {
          // Staf/Admin -> /admin/dashboard
          navigate('/admin/dashboard', { replace: true });
        }
      }, 100);

      return () => clearTimeout(timer);
    }
  }, [loginSuccess, navigate]);

  const handleLogin = async (e) => {
    e.preventDefault();
    setLocalError('');

    try {
      // 1. Panggil API
      const response = await callApi('/auth_login.php', 'POST', {
        email,
        password
      });

      // 2. Validasi & Simpan
      if (response && response.status === 'success' && response.token) {
        const { token, user } = response;

        // --- PERBAIKAN KRUSIAL DI SINI ---
        // Kita simpan dengan nama 'authToken' dan 'authUser' agar sesuai 
        // dengan pengecekan di App.jsx / useApi.js
        localStorage.setItem('authToken', token);
        localStorage.setItem('authUser', JSON.stringify(user));

        // 3. Trigger state success agar useEffect berjalan
        setLoginSuccess(user);
      } else {
        setLocalError(response?.message || 'Login gagal. Periksa email dan password Anda.');
      }

    } catch (err) {
      console.error("Login Error:", err);
      setLocalError('Terjadi kesalahan koneksi. Silakan coba lagi.');
    }
  };

  const displayError = apiError || localError;

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans">
      <div className="sm:mx-auto sm:w-full sm:max-w-md">
        <img
          className="mx-auto h-16 w-auto"
          src="/a2u-logo.png"
          alt="A2U Bank Digital"
          onError={(e) => {
            e.target.onerror = null;
            e.target.src = "https://via.placeholder.com/150x60?text=A2U+Bank";
          }}
        />
        <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Masuk ke Akun Anda
        </h2>
        <p className="mt-2 text-center text-sm text-gray-600">
          Atau{' '}
          <Link to="/register" className="font-medium text-bpn-blue hover:text-bpn-blue-dark">
            daftar rekening baru
          </Link>
        </p>
      </div>

      <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div className="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10 border border-gray-100">

          {displayError && (
            <div className="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-md">
              <div className="flex">
                <div className="flex-shrink-0">
                  <svg className="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                  </svg>
                </div>
                <div className="ml-3">
                  <p className="text-sm text-red-700 font-medium">{displayError}</p>
                </div>
              </div>
            </div>
          )}

          <form className="space-y-6" onSubmit={handleLogin}>
            <Input
              label="Email"
              type="email"
              id="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              placeholder="nama@email.com"
            />

            <div>
              <Input
                label="Password"
                type="password"
                id="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
                placeholder="••••••••"
              />
              <div className="flex items-center justify-end mt-1">
                <Link
                  to="/forgot-password"
                  className="text-sm font-medium text-bpn-blue hover:text-bpn-blue-dark"
                >
                  Lupa password?
                </Link>
              </div>
            </div>

            <div>
              <Button
                type="submit"
                variant="primary"
                fullWidth
                isLoading={loading}
                className="bg-bpn-blue hover:bg-bpn-blue-dark focus:ring-bpn-blue"
              >
                {loading ? 'Memproses...' : 'Masuk'}
              </Button>
            </div>
          </form>

          <div className="mt-6">
            <div className="relative">
              <div className="absolute inset-0 flex items-center">
                <div className="w-full border-t border-gray-300" />
              </div>
              <div className="relative flex justify-center text-sm">
                <span className="px-2 bg-white text-gray-500">
                  A2U Bank Digital
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}