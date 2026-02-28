import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { ArrowLeft, Play, CheckCircle, XCircle, Loader } from 'lucide-react';
import useApi from '../hooks/useApi';
import Button from '../components/ui/Button';

export default function AdminBuildPage() {
    const { loading, callApi } = useApi();
    const [buildResult, setBuildResult] = useState(null);
    const [buildOutput, setBuildOutput] = useState('');

    const handleBuild = async () => {
        setBuildResult(null);
        setBuildOutput('Building...');

        try {
            const response = await callApi('/admin_trigger_build.php', 'POST');

            if (response && response.status === 'success') {
                setBuildResult('success');
                setBuildOutput(response.output || 'Build completed successfully!');
            } else {
                setBuildResult('error');
                setBuildOutput(response?.output || response?.message || 'Build failed');
            }
        } catch (error) {
            setBuildResult('error');
            setBuildOutput(error.message || 'Failed to trigger build');
        }
    };

    return (
        <div className="min-h-screen bg-gray-50 py-8">
            <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="mb-6">
                    <Link
                        to="/admin/dashboard"
                        className="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-4"
                    >
                        <ArrowLeft className="w-4 h-4 mr-1" />
                        Kembali ke Dashboard
                    </Link>
                    <h1 className="text-3xl font-bold text-gray-900">Build Frontend</h1>
                    <p className="mt-2 text-sm text-gray-600">
                        Trigger build frontend untuk deploy perubahan terbaru
                    </p>
                </div>

                {/* Build Card */}
                <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div className="flex items-start justify-between mb-6">
                        <div>
                            <h2 className="text-lg font-semibold text-gray-900">
                                Frontend Build
                            </h2>
                            <p className="mt-1 text-sm text-gray-600">
                                Klik tombol di bawah untuk memulai build process
                            </p>
                        </div>
                        <Button
                            onClick={handleBuild}
                            disabled={loading}
                            variant="primary"
                            className="flex items-center gap-2"
                        >
                            {loading ? (
                                <>
                                    <Loader className="w-4 h-4 animate-spin" />
                                    Building...
                                </>
                            ) : (
                                <>
                                    <Play className="w-4 h-4" />
                                    Start Build
                                </>
                            )}
                        </Button>
                    </div>

                    {/* Build Status */}
                    {buildResult && (
                        <div
                            className={`mb-4 p-4 rounded-lg border ${buildResult === 'success'
                                    ? 'bg-green-50 border-green-200'
                                    : 'bg-red-50 border-red-200'
                                }`}
                        >
                            <div className="flex items-center gap-2">
                                {buildResult === 'success' ? (
                                    <CheckCircle className="w-5 h-5 text-green-600" />
                                ) : (
                                    <XCircle className="w-5 h-5 text-red-600" />
                                )}
                                <span
                                    className={`font-semibold ${buildResult === 'success'
                                            ? 'text-green-900'
                                            : 'text-red-900'
                                        }`}
                                >
                                    {buildResult === 'success'
                                        ? 'Build Berhasil!'
                                        : 'Build Gagal'}
                                </span>
                            </div>
                        </div>
                    )}

                    {/* Build Output */}
                    {buildOutput && (
                        <div className="bg-gray-900 rounded-lg p-4 overflow-auto">
                            <pre className="text-xs text-green-400 font-mono whitespace-pre-wrap">
                                {buildOutput}
                            </pre>
                        </div>
                    )}

                    {/* Info */}
                    <div className="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <h3 className="text-sm font-semibold text-blue-900 mb-2">
                            ℹ️ Informasi
                        </h3>
                        <ul className="text-sm text-blue-800 space-y-1">
                            <li>• Build process akan compile frontend ke folder dist/</li>
                            <li>• Proses ini memakan waktu 10-30 detik</li>
                            <li>• Hanya Super Admin yang bisa trigger build</li>
                            <li>
                                • Setelah build selesai, perubahan akan langsung terlihat di
                                production
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    );
}
