import React, { useState, useEffect, useCallback } from 'react';
import useApi from '@/hooks/useApi';
import { ShieldCheck, ChevronLeft, ChevronRight, Loader2, Filter, Eye, X } from 'lucide-react';

const formatRp = (val) => 'Rp ' + Number(val || 0).toLocaleString('id-ID');

const parseJson = (val) => {
    if (!val) return {};
    try { return typeof val === 'string' ? JSON.parse(val) : val; } catch { return {}; }
};

// Komponen detail aktivitas — ringkasan satu baris
const LogDetail = ({ action, log }) => {
    try {
        const nv = parseJson(log.new_values);
        const id = log.record_id || '';

        const map = {
            LOGIN_SUCCESS: () => `Login berhasil`,
            LOGIN_FAILED: () => `Login gagal: ${nv.failure_reason || '-'}`,
            LOGOUT: () => `Logout`,
            PASSWORD_CHANGED: () => `Ubah password`,
            PIN_CHANGED: () => `Ubah PIN transaksi`,
            PIN_RESET: () => `Reset PIN transaksi`,
            '2FA_ENABLED': () => `Aktifkan 2FA`,
            '2FA_DISABLED': () => `Nonaktifkan 2FA`,
            SESSION_TERMINATED: () => `Hentikan sesi #${id}`,
            APPROVE_LOAN: () => `Setujui Pinjaman #${id}`,
            REJECT_LOAN: () => `Tolak Pinjaman #${id}: ${nv.rejection_reason || '-'}`,
            DISBURSE_LOAN: () => `Cairkan Pinjaman #${id} — ${formatRp(nv.amount)}`,
            DELETE_LOAN: () => `Hapus Pinjaman #${id}`,
            LOAN_PRODUCT_CREATED: () => `Buat produk pinjaman: ${nv.product_name || '#' + id}`,
            LOAN_PRODUCT_UPDATED: () => `Update produk pinjaman #${id}`,
            LOAN_PRODUCT_DELETED: () => `Hapus produk pinjaman #${id}`,
            DEPOSIT_PRODUCT_CREATED: () => `Buat produk deposito: ${nv.product_name || '#' + id}`,
            DEPOSIT_PRODUCT_UPDATED: () => `Update produk deposito #${id}`,
            DEPOSIT_PRODUCT_DELETED: () => `Hapus produk deposito #${id}`,
            TELLER_DEPOSIT: () => `Setor Tunai — ${formatRp(nv.amount)} (Rek: ${nv.account_number || '-'})`,
            TELLER_WITHDRAWAL: () => `Tarik Tunai — ${formatRp(nv.amount)}`,
            TELLER_LOAN_PAYMENT: () => `Bayar Angsuran #${nv.installment_id || id} — ${formatRp(nv.amount_paid || nv.amount)}`,
            EXTERNAL_TRANSFER_EXECUTED: () => `Transfer Eksternal — ${formatRp(nv.amount)} ke ${nv.to_bank_code || '-'} ${nv.to_account_number || ''}`,
            EWALLET_TOPUP_EXECUTED: () => `Top-up ${nv.provider || 'E-Wallet'} — ${formatRp(nv.amount)} ke ${nv.phone_number || '-'}`,
            TRANSACTION_REVERSED: () => `Reversal Transaksi #${id}`,
            STAFF_CREATED: () => `Buat staf baru #${id}`,
            STAFF_UPDATED: () => `Update staf #${id}`,
            STAFF_STATUS_CHANGED: () => `Ubah status staf #${id} → ${nv.status || '-'}`,
            STAFF_PASSWORD_RESET: () => `Reset password staf #${id}`,
            STAFF_ASSIGNMENT_CHANGED: () => `Pindah tugas staf #${id} → Unit ${nv.unit_id || '-'}`,
            UNIT_CREATED: () => `Buat unit: ${nv.unit_name || '#' + id}`,
            UNIT_UPDATED: () => `Update unit #${id}`,
            UNIT_DELETED: () => `Hapus unit #${id}`,
            ANNOUNCEMENT_CREATED: () => `Buat pengumuman: ${nv.title || '#' + id}`,
            ANNOUNCEMENT_UPDATED: () => `Update pengumuman #${id}`,
            ANNOUNCEMENT_DELETED: () => `Hapus pengumuman #${id}`,
            LOYALTY_POINTS_REDEEMED: () => `Redeem ${nv.points_redeemed || 0} poin`,
            SYSTEM_CONFIG_UPDATED: () => `Update konfigurasi: ${(nv.updated_settings || []).join(', ')}`,
            WITHDRAWAL_REQUEST_PROCESSED: () => `Proses penarikan #${id} → ${nv.status || '-'}`,
            CARD_REQUEST_PROCESSED: () => `Proses kartu #${id} → ${nv.action || '-'}`,
        };

        return (map[action] ? map[action]() : `${log.table_name || ''}${id ? ' #' + id : ''}`);
    } catch {
        return action;
    }
};

// Modal detail lengkap
const DetailModal = ({ log, onClose }) => {
    if (!log) return null;
    const ov = parseJson(log.old_values);
    const nv = parseJson(log.new_values);
    const hasOld = Object.keys(ov).length > 0;
    const hasNew = Object.keys(nv).length > 0;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col">
                <div className="flex justify-between items-center p-5 border-b">
                    <div>
                        <h2 className="text-lg font-bold text-gray-800">Detail Log Audit</h2>
                        <p className="text-xs text-gray-500 mt-0.5">#{log.id} · {new Date(log.created_at).toLocaleString('id-ID')}</p>
                    </div>
                    <button onClick={onClose} className="p-2 hover:bg-gray-100 rounded-full"><X size={20} /></button>
                </div>
                <div className="overflow-y-auto p-5 space-y-4">
                    {/* Info dasar */}
                    <div className="grid grid-cols-2 gap-3 text-sm">
                        <div className="bg-gray-50 rounded-lg p-3">
                            <p className="text-xs text-gray-500 mb-1">Pelaku</p>
                            <p className="font-semibold">{log.full_name || 'System'}</p>
                        </div>
                        <div className="bg-gray-50 rounded-lg p-3">
                            <p className="text-xs text-gray-500 mb-1">Aksi</p>
                            <p className="font-mono font-semibold text-blue-700">{log.action}</p>
                        </div>
                        <div className="bg-gray-50 rounded-lg p-3">
                            <p className="text-xs text-gray-500 mb-1">Tabel / Record</p>
                            <p className="font-semibold">{log.table_name}{log.record_id ? ' #' + log.record_id : ''}</p>
                        </div>
                        <div className="bg-gray-50 rounded-lg p-3">
                            <p className="text-xs text-gray-500 mb-1">Alamat IP</p>
                            <p className="font-semibold">{log.ip_address || '-'}</p>
                        </div>
                    </div>

                    {/* Ringkasan */}
                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm">
                        <p className="text-xs text-blue-600 font-semibold mb-1">Ringkasan Aktivitas</p>
                        <p className="text-blue-900"><LogDetail action={log.action} log={log} /></p>
                    </div>

                    {/* Perubahan data */}
                    {(hasOld || hasNew) && (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                            {hasOld && (
                                <div>
                                    <p className="text-xs font-semibold text-red-600 mb-1">Data Sebelum</p>
                                    <pre className="bg-red-50 border border-red-200 rounded-lg p-3 text-xs overflow-auto max-h-48 text-gray-700 whitespace-pre-wrap">
                                        {JSON.stringify(ov, null, 2)}
                                    </pre>
                                </div>
                            )}
                            {hasNew && (
                                <div>
                                    <p className="text-xs font-semibold text-green-600 mb-1">Data Sesudah / Detail</p>
                                    <pre className="bg-green-50 border border-green-200 rounded-lg p-3 text-xs overflow-auto max-h-48 text-gray-700 whitespace-pre-wrap">
                                        {JSON.stringify(nv, null, 2)}
                                    </pre>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

const actionStyle = (action) => {
    if (/APPROVE|DISBURSE|CREATE|SUCCESS|ENABLED/.test(action)) return 'bg-green-100 text-green-800';
    if (/REJECT|DELETE|FAILED|REVERSAL/.test(action)) return 'bg-red-100 text-red-800';
    if (/RESET|UPDATE|CHANGED|PROCESSED/.test(action)) return 'bg-yellow-100 text-yellow-800';
    if (/LOGIN|LOGOUT/.test(action)) return 'bg-blue-100 text-blue-800';
    return 'bg-gray-100 text-gray-800';
};

const ACTION_LIST = [
    'LOGIN_SUCCESS', 'LOGIN_FAILED', 'LOGOUT',
    'PASSWORD_CHANGED', 'PIN_CHANGED', 'PIN_RESET', '2FA_ENABLED', '2FA_DISABLED',
    'APPROVE_LOAN', 'REJECT_LOAN', 'DISBURSE_LOAN', 'DELETE_LOAN',
    'LOAN_PRODUCT_CREATED', 'LOAN_PRODUCT_UPDATED', 'LOAN_PRODUCT_DELETED',
    'DEPOSIT_PRODUCT_CREATED', 'DEPOSIT_PRODUCT_UPDATED', 'DEPOSIT_PRODUCT_DELETED',
    'TELLER_DEPOSIT', 'TELLER_WITHDRAWAL', 'TELLER_LOAN_PAYMENT',
    'EXTERNAL_TRANSFER_EXECUTED', 'EWALLET_TOPUP_EXECUTED', 'TRANSACTION_REVERSED',
    'STAFF_CREATED', 'STAFF_UPDATED', 'STAFF_STATUS_CHANGED', 'STAFF_PASSWORD_RESET', 'STAFF_ASSIGNMENT_CHANGED',
    'UNIT_CREATED', 'UNIT_UPDATED', 'UNIT_DELETED',
    'ANNOUNCEMENT_CREATED', 'ANNOUNCEMENT_UPDATED', 'ANNOUNCEMENT_DELETED',
    'WITHDRAWAL_REQUEST_PROCESSED', 'CARD_REQUEST_PROCESSED',
    'SYSTEM_CONFIG_UPDATED', 'LOYALTY_POINTS_REDEEMED',
].sort();

const AdminAuditLogPage = () => {
    const { loading, error, callApi } = useApi();
    const [logs, setLogs] = useState([]);
    const [pagination, setPagination] = useState({});
    const [page, setPage] = useState(1);
    const [actionFilter, setActionFilter] = useState('');
    const [selectedLog, setSelectedLog] = useState(null);

    const fetchLogs = useCallback(async (currentPage, filter) => {
        const result = await callApi(`/admin/audit-log/data?page=${currentPage}&action=${filter}`);
        if (result && result.status === 'success') {
            setLogs(result.data);
            setPagination(result.pagination);
        }
    }, [callApi]);

    useEffect(() => { fetchLogs(page, actionFilter); }, [page, actionFilter, fetchLogs]);

    return (
        <>
            <div>
                <div className="flex flex-col md:flex-row justify-between md:items-center gap-4 mb-6">
                    <div className="flex items-center gap-3">
                        <ShieldCheck size={32} className="text-blue-600" />
                        <h1 className="text-2xl md:text-3xl font-bold text-gray-800">Log Audit Sistem</h1>
                    </div>
                    <div className="flex items-center gap-2">
                        <Filter size={18} className="text-gray-500" />
                        <select
                            value={actionFilter}
                            onChange={(e) => { setActionFilter(e.target.value); setPage(1); }}
                            className="p-2 border rounded-lg text-sm bg-white"
                        >
                            <option value="">Semua Aksi</option>
                            {ACTION_LIST.map(a => <option key={a} value={a}>{a}</option>)}
                        </select>
                    </div>
                </div>

                <div className="bg-white rounded-lg shadow-md overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="p-4 text-left text-sm font-semibold text-gray-600 whitespace-nowrap">Tanggal & Waktu</th>
                                    <th className="p-4 text-left text-sm font-semibold text-gray-600">Staf Pelaku</th>
                                    <th className="p-4 text-left text-sm font-semibold text-gray-600">Aksi</th>
                                    <th className="p-4 text-left text-sm font-semibold text-gray-600">Detail Aktivitas</th>
                                    <th className="p-4 text-left text-sm font-semibold text-gray-600">IP</th>
                                    <th className="p-4 text-center text-sm font-semibold text-gray-600">Detail</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {loading && logs.length === 0 ? (
                                    <tr><td colSpan="6" className="p-8 text-center"><Loader2 className="animate-spin inline-block mr-2" />Memuat log...</td></tr>
                                ) : error ? (
                                    <tr><td colSpan="6" className="p-8 text-center text-red-500">{error}</td></tr>
                                ) : logs.length > 0 ? logs.map(log => (
                                    <tr key={log.id} className="hover:bg-gray-50">
                                        <td className="p-4 text-sm text-gray-600 whitespace-nowrap">
                                            {new Date(log.created_at).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'medium' })}
                                        </td>
                                        <td className="p-4 font-medium text-gray-800 whitespace-nowrap">{log.full_name || 'System'}</td>
                                        <td className="p-4 text-sm">
                                            <span className={`font-mono px-2 py-1 rounded text-xs font-semibold ${actionStyle(log.action)}`}>
                                                {log.action}
                                            </span>
                                        </td>
                                        <td className="p-4 text-sm text-gray-700 max-w-xs">
                                            <LogDetail action={log.action} log={log} />
                                        </td>
                                        <td className="p-4 text-sm text-gray-500 whitespace-nowrap">{log.ip_address || '-'}</td>
                                        <td className="p-4 text-center">
                                            <button
                                                onClick={() => setSelectedLog(log)}
                                                className="p-1.5 text-blue-600 hover:bg-blue-50 rounded-full"
                                                title="Lihat detail lengkap"
                                            >
                                                <Eye size={16} />
                                            </button>
                                        </td>
                                    </tr>
                                )) : (
                                    <tr><td colSpan="6" className="p-8 text-center text-gray-500">Tidak ada log yang cocok.</td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    {pagination?.total_pages > 1 && (
                        <div className="flex justify-between items-center px-6 py-3 border-t">
                            <button onClick={() => setPage(p => p - 1)} disabled={page <= 1 || loading} className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border rounded-lg hover:bg-gray-100 disabled:opacity-50">
                                <ChevronLeft size={16} /> Sebelumnya
                            </button>
                            <span className="text-sm text-gray-700">Halaman {pagination.current_page} dari {pagination.total_pages}</span>
                            <button onClick={() => setPage(p => p + 1)} disabled={page >= pagination.total_pages || loading} className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border rounded-lg hover:bg-gray-100 disabled:opacity-50">
                                Berikutnya <ChevronRight size={16} />
                            </button>
                        </div>
                    )}
                </div>
            </div>

            {selectedLog && <DetailModal log={selectedLog} onClose={() => setSelectedLog(null)} />}
        </>
    );
};

export default AdminAuditLogPage;
