/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.{js,jsx,ts,tsx}",
    ],
    theme: {
        extend: {
            colors: {
                'a2u-primary': '#00AEEF',
                'a2u-secondary': '#BCCcd1',
                'a2u-dark': '#0B3D6B',
                'a2u-accent': '#00C2FF',
                'bpn-blue': '#00AEEF',
                'bpn-blue-dark': '#0B3D6B',
                'bpn-yellow': '#FBBF24',
                'bpn-red': '#DC2626',
            },
            fontFamily: {
                sans: ['"Inter"', 'sans-serif'],
            },
        },
    },
    plugins: [],
};
