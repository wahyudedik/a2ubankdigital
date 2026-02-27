import React, { useEffect, useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { CreditCard, Send, QrCode, Database, PiggyBank, Briefcase, Download, Upload, Receipt, ArrowDown, ArrowUp, TrendingUp } from 'lucide-react';
import { Line } from 'react-chartjs-2';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler } from 'chart.js';
import useApi from '../hooks/useApi';
import Button from '../components/ui/Button';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler);

const formatCurrency = (amount) => {
    if (amount === null || amount === undefined) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
};

const TransactionIcon = ({ type, flow }) => {
    const iconProps = { size: 20, className: "text-gray-600" };
    
    if (type.includes('TRANSFER') || type.includes('QR')) {
        if (flow === 'KREDIT') {
            return <ArrowDown {...iconProps} className="text-green-600" />;
        }
        return <ArrowUp {...iconProps} className="text-red-600" />;
    }

    switch (type) {
        case 'PEMBELIAN_PRODUK':
        case 'BAYAR_CICILAN':
        case 'TARIK_TUNAI':
             return <ArrowUp {...iconProps} className="text-red-600" />;
        case 'SETOR_TUNAI':
        case 'PENCAIRAN_PINJAMAN':
             return <ArrowDown {...iconProps} className="text-green-600" />;
        default: 
            return <Briefcase {...iconProps} />;
    }
};

const DashboardPage = () => {
    const { loading, error, callApi } = useApi();
    const [dashboardData, setDashboardData] = useState(null);

    const fetchDashboardData = useCallback(async () => {
        const result = await callApi('dashboard_summary.php');
        if(result && result.status === 'success') {
            setDashboardData(result.data);
        }
    }, [callApi]);

    useEffect(() => {
        fetchDashboardData();
    }, [fetchDashboardData]);

    const serviceMenus = [
        { icon: <Download/>, label: 'Isi Saldo', path: '/topup' },
        { icon: <Upload/>, label: 'Tarik Saldo', path: '/withdrawal' },
        { icon: <Send/>, label: 'Transfer', path: '/transfer' },
        { icon: <QrCode/>, label: 'Bayar QR', path: '/payment' },
        { icon: <Receipt/>, label: 'Bayar Tagihan', path: '/bills' },
        { icon: <Database/>, label: 'Deposito', path: '/deposits' },
        { icon: <PiggyBank/>, label: 'Pinjaman', path: '/my-loans' },
        { icon: <TrendingUp/>, label: 'Investasi', path: '/investments' }
    ];

    const chartData = {
        labels: dashboardData?.weekly_summary?.labels || [],
        datasets: [
            {
                label: 'Pemasukan',
                data: dashboardData?.weekly_summary?.pemasukan || [],
                borderColor: '#1E3A8A', // BPN Blue
                backgroundColor: 'rgba(30, 58, 138, 0.1)',
                tension: 0.4,
                fill: true,
            },
            {
                label: 'Pengeluaran',
                data: dashboardData?.weekly_summary?.pengeluaran || [],
                borderColor: '#DC2626', // BPN Red
                backgroundColor: 'rgba(220, 38, 38, 0.1)',
                tension: 0.4,
                fill: true,
            }
        ]
    };
    
    const chartOptions = {
      responsive: true,
      maintainAspectRatio: false,
      scales: { 
          y: { 
              beginAtZero: true,
              ticks: {
                  callback: function(value) {
                      if (value >= 1000000) return (value / 1000000).toFixed(1) + ' Jt';
                      if (value >= 1000) return (value / 1000) + ' Rb';
                      return value;
                  }
              }
          }, 
          x: { 
              grid: { display: false } 
          } 
      },
      plugins: { 
          legend: { 
              display: true,
              position: 'top',
              align: 'end',
              labels: {
                  boxWidth: 12,
                  font: { size: 10 }
              }
          } 
      }
    };

    if (loading && !dashboardData) return <div className="text-center p-8">Memuat dasbor...</div>;
    if (error) return <div className="text-center p-8 text-red-500">Error: {error}</div>;
    if (!dashboardData) return <div className="text-center p-8">Gagal memuat data dasbor.</div>;

    return (
        <>
            {/* Kartu Saldo */}
            <div className="bg-bpn-blue text-white rounded-xl shadow-lg p-6 mb-6 relative overflow-hidden">
                <div className="absolute -top-10 -right-10 w-32 h-32 bg-white/10 rounded-full"></div>
                <div className="absolute -bottom-12 -left-12 w-24 h-24 bg-white/10 rounded-full"></div>
                <div>
                    <p className="text-white/80 text-sm">Saldo Anda</p>
                    <p className="text-3xl font-bold text-white my-2">{formatCurrency(dashboardData.balance)}</p>
                </div>
                <div className="flex justify-between items-center mt-4 border-t border-white/20 pt-4">
                    <div className="flex items-center text-white/90">
                        <CreditCard size={16} className="mr-2"/>
                        <span className="font-mono text-sm">{dashboardData.account_number}</span>
                    </div>
                    <Link to="/topup">
                        <Button className="py-1 px-3 text-xs bg-white/20 hover:bg-white/30 flex items-center gap-1">
                            <Download size={14}/>
                            Isi Saldo
                        </Button>
                    </Link>
                </div>
            </div>

            {/* Menu Layanan */}
            <div className="bg-white rounded-xl shadow-md p-4 mb-6">
                <h3 className="font-semibold text-gray-800 mb-4 px-2">Layanan & Fitur</h3>
                <div className="grid grid-cols-4 gap-2 text-center">
                    {serviceMenus.map(item => (
                        <Link to={item.path} key={item.label} className="flex flex-col items-center p-2 rounded-lg hover:bg-gray-100 transition-colors">
                            <div className="w-14 h-14 rounded-full bg-gray-100 text-bpn-blue flex items-center justify-center mb-2 shadow-sm">
                                {item.icon}
                            </div>
                            <span className="text-xs text-gray-700 font-medium">{item.label}</span>
                        </Link>
                    ))}
                </div>
            </div>

            {/* Grafik Keuangan */}
            <div className="bg-white rounded-xl shadow-md p-4 mb-6">
               <h3 className="font-semibold text-gray-800 mb-4">Ringkasan Keuangan</h3>
               <div className="h-48">
                <Line data={chartData} options={chartOptions} />
               </div>
            </div>
            
            {/* Aktivitas Terkini */}
            <div className="bg-white rounded-xl shadow-md p-4">
                <div className="flex justify-between items-center mb-4">
                    <h3 className="font-semibold text-gray-800">Aktivitas Terkini</h3>
                    <Link to="/history" className="text-sm font-semibold text-bpn-blue">Lihat Semua</Link>
                </div>
                <div className="space-y-4">
                    {dashboardData.recent_transactions.length > 0 ? dashboardData.recent_transactions.map(tx => (
                        <div key={tx.transaction_code} className="flex items-center">
                            <div className={`w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center mr-4`}>
                                <TransactionIcon type={tx.transaction_type} flow={tx.flow} />
                            </div>
                            <div className="flex-grow">
                                <p className="font-semibold text-gray-800">{tx.description}</p>
                                <p className="text-xs text-gray-500">{new Date(tx.created_at).toLocaleDateString('id-ID', {day: '2-digit', month:'long'})}</p>
                            </div>
                            <p className={`font-semibold ${tx.flow === 'KREDIT' ? 'text-green-600' : 'text-gray-800'}`}>
                                {tx.flow === 'KREDIT' ? '+' : '-'}{formatCurrency(tx.amount)}
                            </p>
                        </div>
                    )) : (
                        <p className="text-center text-sm text-gray-500 py-4">Belum ada transaksi.</p>
                    )}
                </div>
            </div>
        </>
    );
};

export default DashboardPage;

