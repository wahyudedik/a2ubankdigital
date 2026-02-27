// ============================================
// PRODUCTION CONFIGURATION
// ============================================
// File ini untuk production di cPanel
// WAJIB UBAH baseUrl sesuai domain production!

export const AppConfig = {
    brand: {
        name: "A2U Bank Digital",
        logo: "/a2u-logo.png",
        logoWhite: "/a2u-logo.png",
    },
    api: {
        // ============================================
        // BACKEND URL - WAJIB DIUBAH!
        // ============================================
        // Ganti dengan domain production kamu
        // Format: https://domain.com/backend/app (tanpa trailing slash!)
        baseUrl: "https://coba.a2ubankdigital.my.id/backend/app"
    },
    theme: {
        colors: {
            BPN_BLUE: "#00AEEF",
            BPN_YELLOW: "#FBBF24",
            BPN_RED: "#DC2626",
        },
        bgPrimary: "bg-bpn-blue",
        bgPrimaryHover: "hover:bg-bpn-blue/90",
        textPrimary: "text-bpn-blue",
        textPrimaryHover: "hover:text-bpn-blue/80",
        ringFocus: "focus:ring-bpn-blue/50",
        status: {
            error: "bg-bpn-red",
            warning: "bg-bpn-yellow",
            info: "bg-bpn-blue",
            success: "bg-green-600",
        }
    },
};
