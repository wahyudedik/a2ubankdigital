<?php
// File: app/utility_get_currency_rates.php
// Penjelasan: Menyediakan informasi kurs mata uang (data dummy).

// Tidak memerlukan auth, tapi perlu config untuk CORS
require_once 'config.php';

// Di aplikasi nyata, data ini akan diambil dari API pihak ketiga dan di-cache.
$mock_rates = [
    'base' => 'IDR',
    'timestamp' => time(),
    'rates' => [
        'USD' => 16250.50,
        'SGD' => 12010.25,
        'EUR' => 17630.80,
        'JPY' => 103.15,
        'MYR' => 3455.60,
        'SAR' => 4330.40
    ]
];

http_response_code(200);
echo json_encode(['status' => 'success', 'data' => $mock_rates]);
?>
