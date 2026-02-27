// ============================================
// AUTO-SWITCH CONFIGURATION
// ============================================
// File ini otomatis memilih config berdasarkan environment
// Development: npm run dev
// Production: npm run build

// Import config files
import { AppConfig as DevConfig } from './config.development.js';
import { AppConfig as ProdConfig } from './config.production.js';

// Auto-detect environment
const isDevelopment = import.meta.env.DEV;

// Export config sesuai environment
export const AppConfig = isDevelopment ? DevConfig : ProdConfig;

// ============================================
// CARA MENGUBAH KONFIGURASI:
// ============================================
// 1. Development: Edit file config.development.js 
// 2. Production: Edit file config.production.js
// 3. Jangan edit file index.js ini!
// ============================================

