// Konfigurasi untuk Inertia.js monolith
// API calls sekarang ke server yang sama

export const AppConfig = {
    brand: {
        name: "A2U Bank Digital",
        logo: "/a2u-logo.png",
        logoWhite: "/a2u-logo.png",
    },
    api: {
        // AJAX routes under web middleware (session + CSRF)
        baseUrl: "/ajax"
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
