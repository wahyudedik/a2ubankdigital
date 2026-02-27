import React, { useState, useEffect, useCallback } from 'react';
import useApi from '../hooks/useApi';
import { Link } from 'react-router-dom';
import { ArrowLeft, Landmark, ChevronRight, PlusCircle } from 'lucide-react';
import Button from '../components/ui/Button';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);

const MyLoansPage = () => {
    const { loading, error, callApi } = useApi();
    const [loans, setLoans] = useState([]);

    const fetchLoans = useCallback(async () => {
        const result = await callApi('user_get_loans.php');
        if (result && result.status === 'success') {
            setLoans(result.data);
        }
    }, [callApi]);

    useEffect(() => {
        fetchLoans();
    }, [fetchLoans]);

    return (
        <div>
            <div className="flex justify-between items-center mb-6">
                <Link to="/dashboard" className="flex items-center gap-2 text-gray-600 hover:text-gray-900">
                    <ArrowLeft size={20} />
                    <h1 className="text-2xl font-bold text-gray-800">Pinjaman Saya</h1>
                </Link>
                <Link to="/loan-products">
                    <Button className="py-2 px-4 text-sm flex items-center gap-2">
                        <PlusCircle size={18}/>
                        <span>Ajukan Pinjaman</span>
                    </Button>
                </Link>
            </div>
            
            {loading && <p>Memuat data pinjaman...</p>}
            {error && <p className="text-red-500">{error}</p>}

            <div className="space-y-4">
                {loans.length > 0 ? loans.map(loan => (
                    <Link to={`/my-loans/${loan.id}`} key={loan.id} className="block bg-white rounded-lg shadow-md p-4 border border-gray-200 hover:bg-gray-50">
                        <div className="flex justify-between items-center">
                            <div>
                                <p className="font-bold text-gray-800">{loan.product_name}</p>
                                <p className="text-sm text-gray-600">Total Pinjaman: {formatCurrency(loan.loan_amount)}</p>
                                <p className="text-xs text-gray-500">Dicairkan pada: {new Date(loan.disbursement_date).toLocaleDateString('id-ID')}</p>
                            </div>
                            <ChevronRight className="text-gray-400" />
                        </div>
                    </Link>
                )) : (
                    !loading && <div className="text-center text-gray-500 py-8">
                        <Landmark size={48} className="mx-auto text-gray-300 mb-4" />
                        <p>Anda tidak memiliki pinjaman aktif saat ini.</p>
                    </div>
                )}
            </div>
        </div>
    );
};

export default MyLoansPage;

