import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import { VitePWA } from 'vite-plugin-pwa'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    react(),
    VitePWA({
      registerType: 'autoUpdate',
      injectRegister: 'auto',
      workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg}']
      },
      manifest: {
        name: 'BPN Syariah',
        short_name: 'BPN Syariah',
        description: 'Layanan perbankan digital modern BPN Syariah.',
        theme_color: '#166534',
        background_color: '#ffffff',
        display: 'standalone',
        scope: '/',
        start_url: '/',
        icons: [
          {
            src: '/bpn-syariah-icon.png',
            sizes: '192x192',
            type: 'image/png'
          },
          {
            src: '/bpn-syariah-icon.png',
            sizes: '512x512',
            type: 'image/png'
          },
          {
            src: '/bpn-syariah-icon.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'any maskable'
          }
        ]
      }
    })
  ],
})
