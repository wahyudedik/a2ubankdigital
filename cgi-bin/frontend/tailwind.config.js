/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        'a2u-primary': '#00AEEF', // Cyan-Blue from logo
        'a2u-secondary': '#BCCcd1', // Silver/Metallic
        'a2u-dark': '#0B3D6B', // Dark Blue from logo text/accents
        'a2u-accent': '#00C2FF', // Bright Cyan
        'bpn-blue': '#00AEEF', // Kept for backward compat but mapped to new color
        'bpn-blue-dark': '#0B3D6B', // Kept for backward compat
        'bpn-yellow': '#FBBF24', // Keep yellow as utility or change if needed? Keeping for now.
        'bpn-red': '#DC2626',
      },
      fontFamily: {
        sans: ['"Inter"', 'sans-serif'],
      },
    },
  },
  plugins: [],
}
