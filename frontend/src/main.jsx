import React from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import App from './App.jsx';
import './index.css';
// PERBAIKAN: Impor fungsi registerSW dari virtual module yang disediakan oleh vite-plugin-pwa
import { registerSW } from 'virtual:pwa-register';

// PERBAIKAN: Panggil fungsi pendaftaran service worker
// Opsi autoUpdate akan secara otomatis memperbarui service worker jika ada versi baru.
registerSW({ immediate: true });

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <BrowserRouter>
      <App />
    </BrowserRouter>
  </React.StrictMode>
);
