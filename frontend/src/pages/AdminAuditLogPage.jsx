import React, { useState, useEffect, useCallback } from 'react';
import useApi from '../hooks/useApi';
import { ShieldCheck, ChevronLeft, ChevronRight, Loader2, Filter } from 'lucide-react';

// REVISI: Komponen diperbarui untuk menangani lebih banyak jenis aksi
const LogDetail = ({ action, details, transactionCode }) => {
    if (transactionCode) {
        return `Kode Transaksi: ${transactionCode}`;
    }

    try {
        const data = JSON.parse(details);
        switch (action) {
            case 'APPROVE_LOAN':
                return `Menyetujui Pinjaman ID: ${data.loan_id}`;
            case 'REJECT_LOAN':
                return `Menolak Pinjaman ID: ${data.loan_id}`;
            case 'APPROVE_TOPUP':
                return `Menyetujui Top-Up ID: ${data.topup_request_id}`;
            case 'CUSTOMER_PIN_RESET':
                 return `Reset PIN untuk Customer ID: ${data.reset_pin_for_customer_id}`;
            case 'TRANSACTION_REVERSAL':
                return `Membatalkan Transaksi ID: ${data.reversed_transaction_id}`;
            case 'DISBURSE_LOAN':
                return `Mencairkan Pinjaman ID: ${data.loan_id}`;
            case 'DISBURSE_WITHDRAWAL':
                return `Mencairkan Penarikan ID: ${data.withdrawal_request_id}`;
            // --- PENAMBAHAN KASUS BARU ---
            case 'UPDATE_SYSTEM_CONFIG':
                return `Memperbarui konfigurasi: ${data.updated_keys.join(', ')}`;
            case 'CREATE_STAFF':
                return `Membuat staf baru: ${data.email} (ID: ${data.new_staff_id})`;
            case 'UPDATE_STAFF_STATUS':
                return `Ubah status staf ID ${data.target_staff_id} menjadi ${data.new_status}`;
            case 'UPDATE_STAFF_ASSIGNMENT':
                return `Pindah tugas staf ID ${data.target_staff_id} ke unit ID ${data.new_unit_id}`;
            case 'EDIT_CUSTOMER_PROFILE':
                return `Edit profil nasabah ID: ${data.edited_customer_id}`;
            case 'APPROVE_CARD_REQUEST':
                return `Menyetujui kartu ID: ${data.approved_card_id}`;
            case 'PROCESS_ACCOUNT_CLOSURE':
                return `${data.action === 'APPROVE' ? 'Menyetujui' : 'Menolak'} penutupan akun untuk nasabah ID: ${data.customer_id}`;
            // --- AKHIR PENAMBAHAN ---
            default:
                return <span className="text-gray-500 text-xs">{details}</span>;
        }
    } catch (e) {
        return <span className="text-gray-500 text-xs">{details || 'N/A'}</span>;
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
        'APPROVE_TOPUP', 'DISBURSE_WITHDRAWAL', 'APPROVE_LOAN', 'REJECT_LOAN', 'DISBURSE_LOAN',
        'CUSTOMER_PIN_RESET', 'TRANSACTION_REVERSAL', 'UPDATE_SYSTEM_CONFIG', 'CREATE_STAFF',
        'UPDATE_STAFF_STATUS', 'UPDATE_STAFF_ASSIGNMENT', 'EDIT_CUSTOMER_PROFILE',
        'APPROVE_CARD_REQUEST', 'PROCESS_ACCOUNT_CLOSURE'
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
                    <ShieldCheck size={32} className="text-taskora-green-700"/>
                    <h1 className="text-2xl md:text-3xl font-bold text-gray-800">Log Audit Sistem</h1>
                </div>
                <div className="flex items-center gap-2">
                    <Filter size={18} className="text-gray-500"/>
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
                                <tr><td colSpan="5" className="p-8 text-center"><Loader2 className="animate-spin inline-block mr-2"/> Memuat log...</td></tr>
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
                                    <td className="p-4 text-sm text-gray-700 font-mono"><LogDetail action={log.action} details={log.details} transactionCode={log.transaction_code} /></td>
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
                            <ChevronLeft size={16}/> Sebelumnya
                        </button>
                        <span className="text-sm text-gray-700">
                            Halaman {pagination.current_page || 0} dari {pagination.total_pages || 0}
                        </span>
                        <button onClick={() => setPage(page + 1)} disabled={page >= pagination.total_pages || loading} className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50">
                            Berikutnya <ChevronRight size={16}/>
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
};

export default AdminAuditLogPage;
