import React, { useState, useEffect, useCallback } from 'react';
import useApi from '@/hooks/useApi';
import { ShieldCheck, ChevronLeft, ChevronRight, Loader2, Filter } from 'lucide-react';

// REVISI: Komponen diperbarui untuk menangani lebih banyak jenis aksi
const LogDetail = ({ action, log }) => {
    try {
        const newValues = log.new_values ? (typeof log.new_values === 'string' ? JSON.parse(log.new_values) : log.new_values) : {};
        const tableName = log.table_name || '';
        const recordId = log.record_id || '';

        const base = `${tableName}${recordId ? ' #' + recordId : ''}`;

        switch (action) {
            case 'LOGIN_SUCCESS': return `Login berhasil`;
            case 'LOGIN_FAILED': return `Login gagal: ${newValues.failure_reason || 'Unknown'}`;
            case 'LOGOUT': return `Logout`;
            case 'APPROVE_LOAN': return `Menyetujui Pinjaman #${recordId}`;
            case 'REJECT_LOAN': return `Menolak Pinjaman #${recordId}`;
            case 'DISBURSE_LOAN': return `Mencairkan Pinjaman #${recordId} - Rp ${Number(newValues.amount || 0).toLocaleString('id-ID')}`;
            case 'TELLER_DEPOSIT': return `Setor Tunai - Rp ${Number(newValues.amount || 0).toLocaleString('id-ID')}`;
            case 'TELLER_WITHDRAWAL': return `Tarik Tunai - Rp ${Number(newValues.amount || 0).toLocaleString('id-ID')}`;
            case 'TELLER_LOAN_PAYMENT': return `Bayar Angsuran - Rp ${Number(newValues.amount || 0).toLocaleString('id-ID')}`;
            case 'LOAN_PRODUCT_CREATED': return `Buat produk pinjaman: ${newValues.product_name || base}`;
            case 'LOAN_PRODUCT_UPDATED': return `Update produk pinjaman #${recordId}`;
            case 'LOAN_PRODUCT_DELETED': return `Hapus produk pinjaman #${recordId}`;
            case 'DEPOSIT_PRODUCT_CREATED': return `Buat produk deposito: ${newValues.product_name || base}`;
            case 'DEPOSIT_PRODUCT_UPDATED': return `Update produk deposito #${recordId}`;
            case 'STAFF_CREATED': return `Buat staf baru #${recordId}`;
            case 'STAFF_UPDATED': return `Update staf #${recordId}`;
            case 'STAFF_STATUS_CHANGED': return `Ubah status staf #${recordId} → ${newValues.status || ''}`;
            case 'STAFF_PASSWORD_RESET': return `Reset password staf #${recordId}`;
            case 'STAFF_ASSIGNMENT_CHANGED': return `Pindah tugas staf #${recordId}`;
            case 'UNIT_CREATED': return `Buat unit: ${newValues.unit_name || base}`;
            case 'UNIT_UPDATED': return `Update unit #${recordId}`;
            case 'UNIT_DELETED': return `Hapus unit #${recordId}`;
            case 'ANNOUNCEMENT_CREATED': return `Buat pengumuman: ${newValues.title || base}`;
            case 'ANNOUNCEMENT_UPDATED': return `Update pengumuman #${recordId}`;
            case 'ANNOUNCEMENT_DELETED': return `Hapus pengumuman #${recordId}`;
            case 'PASSWORD_CHANGED': return `Ubah password`;
            case 'PIN_CHANGED': return `Ubah PIN`;
            case '2FA_ENABLED': return `Aktifkan 2FA`;
            case '2FA_DISABLED': return `Nonaktifkan 2FA`;
            case 'TRANSACTION_REVERSED': return `Reversal transaksi #${recordId}`;
            case 'EXTERNAL_TRANSFER_EXECUTED': return `Transfer eksternal - Rp ${Number(newValues.amount || 0).toLocaleString('id-ID')}`;
            case 'EWALLET_TOPUP_EXECUTED': return `Top-up e-wallet ${newValues.provider || ''}`;
            case 'LOYALTY_POINTS_REDEEMED': return `Redeem ${newValues.points_redeemed || 0} poin`;
            default: return base || action;
        }
    } catch (e) {
        return action;
    }
};

const AdminAuditLogPage = () => {
    const { loading, error, callApi } = useApi();
    const [logs, setLogs] = useState([]);
    const [pagination, setPagination] = useState({});
    const [page, setPage] = useState(1);
    const [actionFilter, setActionFilter] = useState('');

    const fetchLogs = useCallback(async (currentPage, filter) => {
        const result = await callApi(`admin_get_audit_log.php?page=${currentPage}&action=${filter}`);
        if (result && result.status === 'success') {
            setLogs(result.data);
            setPagination(result.pagination);
        }
    }, [callApi]);

    useEffect(() => {
        fetchLogs(page, actionFilter);
    }, [page, actionFilter, fetchLogs]);

    // REVISI: Menambahkan daftar aksi yang baru
    const uniqueActions = [
        'LOGIN_SUCCESS', 'LOGIN_FAILED', 'LOGOUT',
        'APPROVE_LOAN', 'REJECT_LOAN', 'DISBURSE_LOAN',
        'TELLER_DEPOSIT', 'TELLER_WITHDRAWAL', 'TELLER_LOAN_PAYMENT',
        'LOAN_PRODUCT_CREATED', 'LOAN_PRODUCT_UPDATED', 'LOAN_PRODUCT_DELETED',
        'DEPOSIT_PRODUCT_CREATED', 'DEPOSIT_PRODUCT_UPDATED',
        'STAFF_CREATED', 'STAFF_UPDATED', 'STAFF_STATUS_CHANGED', 'STAFF_PASSWORD_RESET', 'STAFF_ASSIGNMENT_CHANGED',
        'UNIT_CREATED', 'UNIT_UPDATED', 'UNIT_DELETED',
        'ANNOUNCEMENT_CREATED', 'ANNOUNCEMENT_UPDATED', 'ANNOUNCEMENT_DELETED',
        'PASSWORD_CHANGED', 'PIN_CHANGED', '2FA_ENABLED', '2FA_DISABLED',
        'TRANSACTION_REVERSED', 'EXTERNAL_TRANSFER_EXECUTED', 'EWALLET_TOPUP_EXECUTED', 'LOYALTY_POINTS_REDEEMED',
    ].sort();

    const actionStyle = (action) => {
        if (action.includes('APPROVE') || action.includes('DISBURSE') || action.includes('CREATE')) return 'bg-green-100 text-green-800';
        if (action.includes('REJECT') || action.includes('REVERSAL')) return 'bg-red-100 text-red-800';
        if (action.includes('RESET') || action.includes('UPDATE') || action.includes('EDIT')) return 'bg-yellow-100 text-yellow-800';
        return 'bg-gray-100 text-gray-800';
    };

    return (
        <div>
            <div className="flex flex-col md:flex-row justify-between md:items-center gap-4 mb-6">
                <div className="flex items-center gap-3">
                    <ShieldCheck size={32} className="text-taskora-green-700" />
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
                        {uniqueActions.map(action => <option key={action} value={action}>{action}</option>)}
                    </select>
                </div>
            </div>

            <div className="bg-white rounded-lg shadow-md overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="p-4 text-left text-sm font-semibold text-gray-600">Tanggal & Waktu</th>
                                <th className="p-4 text-left text-sm font-semibold text-gray-600">Staf Pelaku</th>
                                <th className="p-4 text-left text-sm font-semibold text-gray-600">Aksi</th>
                                <th className="p-4 text-left text-sm font-semibold text-gray-600">Detail Aktivitas</th>
                                <th className="p-4 text-left text-sm font-semibold text-gray-600">Alamat IP</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {loading && logs.length === 0 ? (
                                <tr><td colSpan="5" className="p-8 text-center"><Loader2 className="animate-spin inline-block mr-2" /> Memuat log...</td></tr>
                            ) : error ? (
                                <tr><td colSpan="5" className="p-8 text-center text-red-500">{error}</td></tr>
                            ) : logs.length > 0 ? logs.map(log => (
                                <tr key={log.id}>
                                    <td className="p-4 text-sm text-gray-600 whitespace-nowrap">{new Date(log.created_at).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'medium' })}</td>
                                    <td className="p-4 font-medium text-gray-800">{log.full_name}</td>
                                    <td className="p-4 text-sm">
                                        <span className={`font-mono px-2 py-1 rounded text-xs font-semibold ${actionStyle(log.action)}`}>
                                            {log.action}
                                        </span>
                                    </td>
                                    <td className="p-4 text-sm text-gray-700"><LogDetail action={log.action} log={log} /></td>
                                    <td className="p-4 text-sm text-gray-600">{log.ip_address}</td>
                                </tr>
                            )) : (
                                <tr><td colSpan="5" className="p-8 text-center text-gray-500">Tidak ada log audit yang cocok dengan filter.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
                {pagination && pagination.total_pages > 1 && (
                    <div className="flex justify-between items-center px-6 py-3 border-t">
                        <button onClick={() => setPage(page - 1)} disabled={page <= 1 || loading} className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50">
                            <ChevronLeft size={16} /> Sebelumnya
                        </button>
                        <span className="text-sm text-gray-700">
                            Halaman {pagination.current_page || 0} dari {pagination.total_pages || 0}
                        </span>
                        <button onClick={() => setPage(page + 1)} disabled={page >= pagination.total_pages || loading} className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50">
                            Berikutnya <ChevronRight size={16} />
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
};

export default AdminAuditLogPage;
