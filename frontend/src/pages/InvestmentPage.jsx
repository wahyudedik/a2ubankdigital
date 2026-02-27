import React, { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import useApi from '../hooks/useApi.js';
import { ArrowLeft, TrendingUp, TrendingDown, Info, BarChartHorizontalBig } from 'lucide-react';

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

const formatCompactNumber = (num) => {
    if (num >= 1000000000) {
        return (num / 1000000000).toFixed(2) + ' M';
    }
    if (num >= 1000000) {
        return (num / 1000000).toFixed(2) + ' Jt';
    }
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + ' Rb';
    }
    return num;
};

const StockCard = ({ stock }) => {
    const isPositive = stock.change > 0;
    const isNegative = stock.change < 0;
    const changePercent = stock.change_percent * 100;

    const changeColorClass = isPositive ? 'text-green-600' : isNegative ? 'text-red-600' : 'text-gray-500';

    return (
        <div className="bg-white rounded-lg shadow-md p-4 border border-gray-200 hover:shadow-lg transition-shadow duration-200 flex items-center">
            <div className="flex-grow">
                <p className="font-bold text-gray-800 text-base">{stock.name}</p>
                <p className="text-sm text-gray-500 font-semibold">{stock.code}</p>
                <div className="mt-2 flex items-baseline space-x-2">
                    <p className="text-xl font-bold text-gray-900">{formatCurrency(stock.price_new)}</p>
                    <div className={`flex items-center text-sm font-semibold ${changeColorClass}`}>
                        {isPositive && <TrendingUp size={16} />}
                        {isNegative && <TrendingDown size={16} />}
                        <span>{formatCurrency(stock.change)}</span>
                    </div>
                </div>
            </div>
            <div className="text-right flex-shrink-0 ml-4">
                <div className={`px-3 py-1.5 rounded-md text-base font-bold ${changeColorClass}`}>
                    {changePercent.toFixed(2)}%
                </div>
                 <div className="mt-2 text-xs text-gray-500 flex items-center justify-end gap-1">
                    <BarChartHorizontalBig size={14}/>
                    <span>{formatCompactNumber(stock.volume)}</span>
                </div>
            </div>
        </div>
    );
};

const InvestmentPage = () => {
    const { loading, error, callApi } = useApi();
    const [marketData, setMarketData] = useState([]);
    const [lastUpdated, setLastUpdated] = useState(null);


    const fetchData = useCallback(async () => {
        const result = await callApi('utility_get_market_data.php');
        if (result && result.status === 'success' && result.data.stocks) {
            setMarketData(result.data.stocks);
            setLastUpdated(new Date(result.last_updated));
        }
    }, [callApi]);

    useEffect(() => {
        fetchData();
        const interval = setInterval(fetchData, 15000); 
        return () => clearInterval(interval);
    }, [fetchData]);

    return (
        <div>
            <Link to="/dashboard" className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                <ArrowLeft size={20} />
                <h1 className="text-2xl font-bold text-gray-800">Pasar Saham</h1>
            </Link>
            
            {loading && marketData.length === 0 && <p className="text-center p-8">Memuat data pasar...</p>}
            {error && <div className="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert">
                <p className="font-bold">Terjadi Kesalahan</p>
                <p>{error}</p>
            </div>}
            
            <div className="space-y-3">
                {marketData.map((stock) => (
                    <StockCard key={stock.code} stock={stock} />
                ))}
            </div>
            
             <div className="mt-6 text-center text-xs text-gray-500 flex items-center justify-center gap-2">
                <Info size={14}/>
                <span>
                    Data diperbarui dari sumber publik. 
                    {lastUpdated && ` Terakhir update: ${lastUpdated.toLocaleTimeString('id-ID')}`}
                </span>
            </div>
        </div>
    );
};

export default InvestmentPage;

