import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import useApi from '/src/hooks/useApi.js';
import { useModal } from '/src/contexts/ModalContext.jsx';
import { AppConfig } from '/src/config/index.js';
import { ArrowLeft, Edit, Landmark, PiggyBank, User, FileText, ChevronDown, Check, Clock, Camera, ShieldAlert, Scissors } from 'lucide-react';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
};
const formatTenor = (tenor, unit) => {
    if (!tenor || !unit) return '-';
    const unitText = {
        'HARI': 'Hari',
        'MINGGU': 'Minggu',
        'BULAN': 'Bulan'
    };
    return `${tenor} ${unitText[unit] || unit}`;
};


const InfoCard = ({ title, icon, children }) => (
    <div className="bg-white rounded-lg shadow-md overflow-hidden">
        <div className="px-4 py-4 sm:px-6 border-b bg-gray-50">
            <h3 className="text-lg leading-6 font-medium text-gray-900 flex items-center">
                {icon}
                <span className="ml-3">{title}</span>
            </h3>
        </div>
        <div className="px-4 py-4 sm:p-6">
            {children}
        </div>
    </div>
);

const DetailItem = ({ label, value }) => (
    <div className="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt className="text-sm font-medium text-gray-500">{label}</dt>
        <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{value || '-'}</dd>
    </div>
);

const KycImage = ({ label, path }) => {
    if (!path) return null;
    const fullUrl = `${AppConfig.api.baseUrl.replace('/app', '')}${path}`;
    return (
        <div>
            <p className="text-sm font-semibold text-gray-600">{label}</p>
            <a href={fullUrl} target="_blank" rel="noopener noreferrer">
                <img 
                    src={fullUrl} 
                    alt={label} 
                    className="mt-2 rounded-lg border w-full object-cover cursor-pointer hover:opacity-80 transition-opacity"
                    onError={(e) => { e.target.onerror = null; e.target.src="https://placehold.co/400x250/EEE/31343C?text=Gagal+Muat"; }}
                />
            </a>
        </div>
    );
};

const StatusActionCard = ({ customer, onStatusChange }) => {
    const { loading, callApi } = useApi();
    const modal = useModal();

    const handleChangeStatus = async (newStatus, actionText, confirmationTitle, confirmationMessage) => {
        const confirmed = await modal.showConfirmation({
            title: confirmationTitle,
            message: confirmationMessage,
            confirmText: `Ya, ${actionText}`
        });

        if (confirmed) {
            const result = await callApi('admin_update_customer_status.php', 'POST', {
                customer_id: customer.id,
                new_status: newStatus
            });
            if (result && result.status === 'success') {
                modal.showAlert({ title: 'Berhasil', message: result.message, type: 'success' });
                onStatusChange();
            } else {
                modal.showAlert({ title: 'Gagal', message: result.message || 'Terjadi kesalahan', type: 'warning' });
            }
        }
    };
    
    const renderActionButton = () => {
        switch (customer.status) {
            case 'DORMANT':
                return (
                    <button 
                        onClick={() => handleChangeStatus(
                            'ACTIVE', 
                            'Aktifkan Akun', 
                            'Konfirmasi Aktivasi Akun',
                            `Anda yakin ingin mengaktifkan kembali akun DORMANT untuk nasabah ${customer.full_name}?`
                        )} 
                        disabled={loading} 
                        className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 disabled:opacity-50"
                    >
                        {loading ? 'Memproses...' : 'Aktifkan (Dormant)'}
                    </button>
                );
            case 'BLOCKED':
                return (
                     <button 
                        onClick={() => handleChangeStatus(
                            'ACTIVE', 
                            'Buka Blokir',
                            'Konfirmasi Buka Blokir',
                            `Anda yakin ingin membuka blokir akun untuk nasabah ${customer.full_name}?`
                        )} 
                        disabled={loading} 
                        className="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700 disabled:opacity-50"
                    >
                        {loading ? 'Memproses...' : 'Buka Blokir'}
                    </button>
                );
            case 'ACTIVE':
                return (
                    <button 
                        onClick={() => handleChangeStatus(
                            'BLOCKED', 
                            'Blokir Akun',
                            'Konfirmasi Blokir Akun',
                            `Anda yakin ingin memblokir akun untuk nasabah ${customer.full_name}?`
                        )} 
                        disabled={loading} 
                        className="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-semibold hover:bg-red-700 disabled:opacity-50"
                    >
                        {loading ? 'Memproses...' : 'Blokir Akun'}
                    </button>
                );
            default:
                return null;
        }
    };

    const statusMap = {
        'ACTIVE': { text: 'Aktif', color: 'bg-green-100 text-green-800' },
        'DORMANT': { text: 'Dormant', color: 'bg-yellow-100 text-yellow-800' },
        'BLOCKED': { text: 'Diblokir', color: 'bg-red-100 text-red-800' },
        'CLOSED': { text: 'Ditutup', color: 'bg-gray-100 text-gray-800' }
    };

    const currentStatus = statusMap[customer.status] || statusMap['CLOSED'];

    return (
        <InfoCard title="Status Akun & Tindakan" icon={<ShieldAlert size={20} />}>
            <div className="flex justify-between items-center">
                <div>
                    <p className="text-sm text-gray-500">Status Saat Ini</p>
                    <span className={`mt-1 inline-block px-3 py-1 text-sm font-semibold rounded-full ${currentStatus.color}`}>
                        {currentStatus.text}
                    </span>
                </div>
                <div>
                    {renderActionButton()}
                </div>
            </div>
        </InfoCard>
    );
};

function CustomerDetailPage() {
    const { customerId } = useParams();
    const { loading, error, callApi } = useApi();
    const [customer, setCustomer] = useState(null);
    const [expandedLoanId, setExpandedLoanId] = useState(null);
    const modal = useModal();

    const fetchCustomerDetail = async () => {
        const result = await callApi(`admin_get_customer_detail.php?id=${customerId}`);
        if (result && result.status === 'success') {
            setCustomer(result.data);
        }
    };

    useEffect(() => {
        fetchCustomerDetail();
    }, [callApi, customerId]);

    const toggleLoanDetails = (loanId) => {
        setExpandedLoanId(prevId => (prevId === loanId ? null : loanId));
    };

    const handleForcePay = async (installment, customerName) => {
        const totalDue = parseFloat(installment.amount_due) + parseFloat(installment.penalty_amount || 0);
        const confirmed = await modal.showConfirmation({
            title: "Konfirmasi Potong Saldo",
            message: `Anda akan memotong saldo sebesar ${formatCurrency(totalDue)} dari rekening ${customerName} untuk membayar angsuran #${installment.installment_number}. Lanjutkan?`,
            confirmText: "Ya, Potong Saldo"
        });

        if (confirmed) {
            const result = await callApi('admin_force_pay_installment.php', 'POST', { installment_id: installment.id });
            if (result && result.status === 'success') {
                modal.showAlert({ title: "Berhasil", message: result.message, type: 'success' });
                fetchCustomerDetail(); // Refresh data
            } else {
                modal.showAlert({ title: "Gagal", message: result.message || 'Terjadi kesalahan', type: 'warning' });
            }
        }
    };

    if (loading && !customer) return <div className="text-center p-8">Memuat detail nasabah...</div>;
    if (error) return <div className="text-center p-8 text-red-500">{error}</div>;
    if (!customer) return <div className="text-center p-8">Data nasabah tidak ditemukan.</div>;
    
    const savingsAccounts = customer.accounts?.filter(acc => acc.account_type === 'TABUNGAN');
    const depositAccounts = customer.accounts?.filter(acc => acc.account_type === 'DEPOSITO');
    const savingsBalance = savingsAccounts && savingsAccounts.length > 0 ? parseFloat(savingsAccounts[0].balance) : 0;

    return (
        <div>
            <div className="flex justify-between items-center mb-6">
                 <Link to="/admin/customers" className="flex items-center gap-2 text-gray-600 hover:text-gray-900">
                    <ArrowLeft size={20} />
                    <h1 className="text-2xl md:text-3xl font-bold text-gray-800">Detail Nasabah</h1>
                </Link>
                <Link to={`/admin/customers/edit/${customerId}`} className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <Edit size={18} />
                    <span>Edit Nasabah</span>
                </Link>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-1 space-y-6">
                    <InfoCard title="Profil Utama" icon={<User size={20} />}>
                        <div className="text-center">
                            <div className="w-24 h-24 rounded-full bg-bpn-blue-100 text-bpn-blue-700 flex items-center justify-center font-bold text-4xl mb-4 mx-auto">
                                {customer.full_name ? customer.full_name.charAt(0) : '?'}
                            </div>
                            <h2 className="text-xl font-bold text-gray-800">{customer.full_name}</h2>
                            <p className="text-sm text-gray-500">{customer.bank_id}</p>
                        </div>
                    </InfoCard>
                    
                    <StatusActionCard customer={customer} onStatusChange={fetchCustomerDetail} />

                    <InfoCard title="Data Pribadi & Kontak" icon={<FileText size={20} />}>
                        <dl className="divide-y divide-gray-200">
                            <DetailItem label="Cabang Terdaftar" value={customer.branch_name} />
                            <DetailItem label="Unit Terdaftar" value={customer.unit_name} />
                            <DetailItem label="Email" value={customer.email} />
                            <DetailItem label="Telepon" value={customer.phone_number} />
                            <DetailItem label="NIK" value={customer.nik} />
                            <DetailItem label="Nama Ibu Kandung" value={customer.mother_maiden_name} />
                            <DetailItem label="TTL" value={`${customer.pob || ''}, ${formatDate(customer.dob)}`} />
                            <DetailItem label="Gender" value={customer.gender === 'L' ? 'Laki-laki' : 'Perempuan'} />
                            <DetailItem label="Alamat KTP" value={customer.address_ktp} />
                        </dl>
                    </InfoCard>

                    <InfoCard title="Dokumen KYC" icon={<Camera size={20}/>}>
                        <div className="space-y-4">
                            <KycImage label="Foto KTP" path={customer.ktp_image_path} />
                            <KycImage label="Foto Selfie" path={customer.selfie_image_path} />
                        </div>
                    </InfoCard>
                </div>
                
                <div className="lg:col-span-2 space-y-6">
                    <InfoCard title="Informasi Finansial" icon={<Landmark size={20} />}>
                        <h4 className="text-md font-semibold text-gray-800 mb-2">Rekening Tabungan</h4>
                        <ul className="divide-y mb-6">
                            {savingsAccounts?.map(acc => (
                                <li key={acc.id} className="py-2 flex justify-between items-center">
                                    <div>
                                        <p className="font-semibold">{acc.account_number}</p>
                                        <p className="text-sm text-gray-600">{formatCurrency(acc.balance)}</p>
                                    </div>
                                    <span className="text-xs font-medium bg-blue-100 text-blue-800 px-2 py-1 rounded-full">{acc.status}</span>
                                </li>
                            ))}
                        </ul>

                        <h4 className="text-md font-semibold text-gray-800 mb-2 border-t pt-4">Rekening Deposito</h4>
                        <div className="space-y-3 mb-6">
                            {depositAccounts?.length > 0 ? depositAccounts.map(depo => (
                                <div key={depo.id} className="p-3 border rounded-lg bg-gray-50">
                                    <div className="flex justify-between items-center font-semibold">
                                        <p>{depo.deposit_product_name}</p>
                                        <span className="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">{depo.status}</span>
                                    </div>
                                    <div className="mt-2 grid grid-cols-2 gap-2 text-sm">
                                        <div><span className="text-gray-500">Pokok:</span> {formatCurrency(depo.balance)}</div>
                                        <div><span className="text-gray-500">Keuntungan:</span> {formatCurrency(depo.interest_earned || 0)}</div>
                                        <div><span className="text-gray-500">Jatuh Tempo:</span> {formatDate(depo.maturity_date)}</div>
                                    </div>
                                </div>
                            )) : <p className="text-sm text-gray-500 text-center py-2">Tidak ada deposito.</p>}
                        </div>

                         <h4 className="text-md font-semibold text-gray-800 mb-2 border-t pt-4">Riwayat Pinjaman</h4>
                        <div className="space-y-2">
                            {customer.loans?.length > 0 ? customer.loans.map(loan => (
                                <div key={loan.id} className="border rounded-lg">
                                    <button onClick={() => toggleLoanDetails(loan.id)} className="w-full flex justify-between items-center p-3 text-left hover:bg-gray-50">
                                        <div>
                                            <p className="font-semibold">{loan.product_name}</p>
                                            <p className="text-sm text-gray-600">{formatCurrency(loan.loan_amount)} / {formatTenor(loan.tenor, loan.tenor_unit)}</p>
                                        </div>
                                        <div className="flex items-center gap-4">
                                            <span className="text-sm font-medium">{loan.status}</span>
                                            <ChevronDown className={`transform transition-transform ${expandedLoanId === loan.id ? 'rotate-180' : ''}`} size={20} />
                                        </div>
                                    </button>
                                    {expandedLoanId === loan.id && (
                                        <div className="p-3 border-t bg-gray-50">
                                            <h5 className="text-xs font-bold text-gray-500 mb-2">RINCIAN ANGSURAN</h5>
                                            <ul className="divide-y">
                                                {loan.installments.map(inst => {
                                                    const isOverdue = inst.status === 'OVERDUE';
                                                    const totalDue = parseFloat(inst.amount_due) + parseFloat(inst.penalty_amount || 0);
                                                    const canAfford = savingsBalance >= totalDue;
                                                    const canForcePay = isOverdue && canAfford;
                                                    
                                                    let buttonTitle = "Potong Saldo";
                                                    if (!isOverdue) buttonTitle = "Angsuran belum jatuh tempo";
                                                    else if (!canAfford) buttonTitle = "Potong Saldo (Saldo nasabah tidak cukup)";
                                                    
                                                    return(
                                                    <li key={inst.id} className="py-2 flex justify-between items-center text-xs">
                                                        <div>
                                                            <p>Angsuran #{inst.installment_number} - {formatCurrency(inst.amount_due)}</p>
                                                             {parseFloat(inst.penalty_amount || 0) > 0 && <p className="text-red-600">Denda: {formatCurrency(inst.penalty_amount)}</p>}
                                                            <p className="text-gray-500">Jatuh Tempo: {formatDate(inst.due_date)}</p>
                                                        </div>
                                                        <div className="flex items-center gap-2">
                                                            {inst.status === 'PAID' ? (
                                                                <span className="flex items-center gap-1 text-green-600"><Check size={14}/> Lunas</span>
                                                            ) : (
                                                                <>
                                                                    <span className={`flex items-center gap-1 font-semibold ${isOverdue ? 'text-red-600' : 'text-yellow-600'}`}>
                                                                        <Clock size={14}/> {inst.status}
                                                                    </span>
                                                                    <button 
                                                                        onClick={() => handleForcePay(inst, customer.full_name)} 
                                                                        title={buttonTitle}
                                                                        disabled={loading || !canForcePay}
                                                                        className={`p-1.5 rounded-md transition ${
                                                                            canForcePay 
                                                                            ? 'text-white bg-red-600 hover:bg-red-700' 
                                                                            : 'text-gray-400 bg-gray-200 cursor-not-allowed'
                                                                        }`}
                                                                    >
                                                                        <Scissors size={14} />
                                                                    </button>
                                                                </>
                                                            )}
                                                        </div>
                                                    </li>
                                                )})}
                                            </ul>
                                        </div>
                                    )}
                                </div>
                            )) : (
                                <p className="text-sm text-gray-500 text-center py-4">Tidak ada riwayat pinjaman.</p>
                            )}
                        </div>
                    </InfoCard>
                </div>
            </div>
        </div>
    );
}

export default CustomerDetailPage;

