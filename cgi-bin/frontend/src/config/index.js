export const AppConfig = {
  brand: {
    name: "A2U Bank Digital",
    logo: "/a2u-logo.png",
    logoWhite: "/a2u-logo.png", // Using same logo, assume transparency works or updated white version needed later
  },
  api: {
    // baseUrl: "http://a2ubankdigital.my.id.test/app",
    baseUrl: "https://coba.a2ubankdigital.my.id/app"
  },
  theme: {
    // Definisi warna langsung
    colors: {
      BPN_BLUE: "#00AEEF", // A2U Primary Cyan 
      BPN_YELLOW: "#FBBF24",
      BPN_RED: "#DC2626",
    },
    // Kelas utilitas Tailwind untuk konsistensi
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

