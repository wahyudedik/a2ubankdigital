import React, { useState, useEffect, useCallback } from 'react';
import { Line } from 'react-chartjs-2';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler } from 'chart.js';
import { AppConfig } from '../../../config';
import useApi from '../../../hooks/useApi'; // Impor hook API

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler);

// REVISI TOTAL: Komponen ini sekarang mengambil datanya sendiri
const CustomerGrowthChart = () => {
    const { loading, error, callApi } = useApi();
    const [chartData, setChartData] = useState(null);

    const fetchChartData = useCallback(async () => {
        // Panggil endpoint khusus untuk laporan pertumbuhan
        const result = await callApi('admin_get_customer_growth_report.php');
        if (result && result.status === 'success') {
            const data = {
                labels: result.data.map(d => new Date(d.registration_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' })),
                datasets: [{
                    label: 'Nasabah Baru',
                    data: result.data.map(d => d.new_customers),
                    borderColor: AppConfig.theme.colors.BPN_BLUE,
                    backgroundColor: 'rgba(20, 83, 136, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            };
            setChartData(data);
        }
    }, [callApi]);

    useEffect(() => {
        fetchChartData();
    }, [fetchChartData]);
    
    const chartOptions = {
      responsive: true,
      maintainAspectRatio: false,
      scales: { 
          y: { 
              beginAtZero: true,
              ticks: { precision: 0 }
          }, 
          x: { 
              grid: { display: false } 
          } 
      },
      plugins: { 
          legend: { 
              display: false
          } 
      }
    };

    if (loading) {
        return <div className="h-80 flex items-center justify-center text-gray-500">Memuat data grafik...</div>;
    }
    
    if (error) {
         return <div className="h-80 flex items-center justify-center text-red-500">{error}</div>;
    }

    if (!chartData) {
        return <div className="h-80 flex items-center justify-center text-gray-500">Tidak ada data untuk ditampilkan.</div>;
    }

    return (
        <div className="h-80 relative">
             <Line data={chartData} options={chartOptions} />
        </div>
    );
};

export default CustomerGrowthChart;

