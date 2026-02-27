import React, { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import useApi from '../hooks/useApi';
import { ArrowLeft, Database, ChevronRight, PlusCircle } from 'lucide-react';
import Button from '../components/ui/Button';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);

const DepositsPage = () => {
    const { loading, error, callApi } = useApi();
    const [deposits, setDeposits] = useState([]);

    const fetchDeposits = useCallback(async () => {
        const result = await callApi('user_get_deposits.php');
        if (result && result.status === 'success') {
            setDeposits(result.data);
        }
    }, [callApi]);

    useEffect(() => {
        fetchDeposits();
    }, [fetchDeposits]);

    return (
        <div>
            <div className="flex justify-between items-center mb-6">
                <Link to="/dashboard" className="flex items-center gap-2 text-gray-600 hover:text-gray-900">
                    <ArrowLeft size={20} />
                    <h1 className="text-2xl font-bold text-gray-800">Deposito Saya</h1>
                </Link>
                <Link to="/deposits/open">
                    <Button className="py-2 px-4 text-sm flex items-center gap-2">
                        <PlusCircle size={18} />
                        <span>Buka Deposito</span>
                    </Button>
                </Link>
            </div>
            
            {loading && <p className="text-center p-4">Memuat data deposito...</p>}
            {error && <p className="text-center p-4 text-red-500">{error}</p>}

            <div className="space-y-4">
                {deposits.length > 0 ? deposits.map(depo => (
                    <Link to={`/deposits/${depo.id}`} key={depo.id} className="block bg-white rounded-lg shadow-md p-4 border border-gray-200 hover:bg-gray-50 transition-colors">
                        <div className="flex justify-between items-center">
                            <div>
                                <p className="font-bold text-gray-800">{depo.product_name}</p>
                                <p className="text-sm text-gray-600">Pokok: {formatCurrency(depo.balance)}</p>
                                <p className="text-xs text-gray-500">Jatuh Tempo: {new Date(depo.maturity_date).toLocaleDateString('id-ID')}</p>
                            </div>
                            <div className="flex items-center">
                                <span className={`px-2 py-1 text-xs font-semibold rounded-full mr-4 ${depo.status === 'ACTIVE' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'}`}>
                                    {depo.status}
                                </span>
                                <ChevronRight className="text-gray-400" />
                            </div>
                        </div>
                    </Link>
                )) : (
                    !loading && <div className="text-center text-gray-500 py-8">
                        <Database size={48} className="mx-auto text-gray-300 mb-4" />
                        <p>Anda belum memiliki rekening deposito.</p>
                    </div>
                )}
            </div>
        </div>
    );
};

export default DepositsPage;

