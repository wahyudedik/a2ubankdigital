import React from 'react';

const Button = ({ 
  children, 
  variant = 'primary', 
  isLoading = false, 
  fullWidth = false, 
  className = '', 
  disabled,
  ...props // Sisa props lain seperti onClick, type, dll.
}) => {
  
  // Base classes untuk semua tombol
  const baseClasses = "inline-flex justify-center items-center px-4 py-2 border text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed";
  
  // Variant styling
  const variants = {
    primary: "border-transparent text-white bg-bpn-blue hover:bg-bpn-blue-dark focus:ring-bpn-blue",
    secondary: "border-gray-300 text-gray-700 bg-white hover:bg-gray-50 focus:ring-bpn-blue",
    danger: "border-transparent text-white bg-bpn-red hover:bg-red-700 focus:ring-red-500",
    success: "border-transparent text-white bg-green-600 hover:bg-green-700 focus:ring-green-500",
    outline: "border-bpn-blue text-bpn-blue bg-transparent hover:bg-bpn-blue hover:text-white focus:ring-bpn-blue"
  };

  // Lebar tombol
  const widthClass = fullWidth ? "w-full" : "";

  return (
    <button
      disabled={isLoading || disabled} // Matikan tombol saat loading atau disabled
      className={`${baseClasses} ${variants[variant]} ${widthClass} ${className}`}
      {...props} // Spread sisa props ke elemen button (tidak termasuk isLoading/variant/fullWidth)
    >
      {isLoading ? (
        <>
          {/* Spinner SVG */}
          <svg 
            className="animate-spin -ml-1 mr-2 h-4 w-4 text-current" 
            xmlns="http://www.w3.org/2000/svg" 
            fill="none" 
            viewBox="0 0 24 24"
          >
            <circle 
              className="opacity-25" 
              cx="12" 
              cy="12" 
              r="10" 
              stroke="currentColor" 
              strokeWidth="4"
            ></circle>
            <path 
              className="opacity-75" 
              fill="currentColor" 
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            ></path>
          </svg>
          Memproses...
        </>
      ) : (
        children
      )}
    </button>
  );
};

export default Button;