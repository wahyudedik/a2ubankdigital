import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import Input from '@/components/ui/Input';
import Button from '@/components/ui/Button';

export default function LoginPage() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleLogin = (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    router.post('/login', { email, password }, {
      onError: (errors) => setError(errors.email || 'Login gagal.'),
      onFinish: () => setLoading(false),
    });
  };

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans">
      <div className="sm:mx-auto sm:w-full sm:max-w-md">
        <img className="mx-auto h-16 w-auto" src="/a2u-logo.png" alt="A2U Bank Digital"
          onError={(e) => { e.target.onerror = null; e.target.src = "https://via.placeholder.com/150x60?text=A2U+Bank"; }} />
        <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">Masuk ke Akun Anda</h2>
        <p className="mt-2 text-center text-sm text-gray-600">
          Atau{' '}
          <Link href="/register" className="font-medium text-bpn-blue hover:text-bpn-blue-dark">daftar rekening baru</Link>
        </p>
      </div>

      <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div className="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10 border border-gray-100">
          {error && (
            <div className="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-md">
              <div className="flex">
                <div className="flex-shrink-0">
                  <svg className="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                  </svg>
                </div>
                <div className="ml-3"><p className="text-sm text-red-700 font-medium">{error}</p></div>
              </div>
            </div>
          )}

          <form className="space-y-6" onSubmit={handleLogin}>
            <Input label="Email" type="email" id="email" value={email} onChange={(e) => setEmail(e.target.value)} required placeholder="nama@email.com" />
            <div>
              <Input label="Password" type="password" id="password" value={password} onChange={(e) => setPassword(e.target.value)} required placeholder="••••••••" />
              <div className="flex items-center justify-end mt-1">
                <Link href="/forgot-password" className="text-sm font-medium text-bpn-blue hover:text-bpn-blue-dark">Lupa password?</Link>
              </div>
            </div>
            <div>
              <Button type="submit" variant="primary" fullWidth isLoading={loading} className="bg-bpn-blue hover:bg-bpn-blue-dark focus:ring-bpn-blue">
                {loading ? 'Memproses...' : 'Masuk'}
              </Button>
            </div>
          </form>

          <div className="mt-6">
            <div className="relative">
              <div className="absolute inset-0 flex items-center"><div className="w-full border-t border-gray-300" /></div>
              <div className="relative flex justify-center text-sm"><span className="px-2 bg-white text-gray-500">A2U Bank Digital</span></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
