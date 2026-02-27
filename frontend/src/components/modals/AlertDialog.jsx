import React from 'react';
import Button from '../ui/Button';
import { AlertTriangle, CheckCircle, Info, X } from 'lucide-react';

const icons = {
    warning: <AlertTriangle size={24} className="text-yellow-500" />,
    success: <CheckCircle size={24} className="text-green-500" />,
    info: <Info size={24} className="text-blue-500" />,
};

const AlertDialog = ({ modalProps, hideModal }) => {
    if (!modalProps) return null;

    const { title, message, onConfirm, confirmText, cancelText, type = 'info' } = modalProps;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-60 flex justify-center items-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl w-full max-w-sm transform transition-all">
                <div className="p-6">
                    <div className="flex items-start">
                        <div className="mr-4 flex-shrink-0">
                           {icons[type]}
                        </div>
                        <div className="flex-grow">
                            <h3 className="text-lg font-bold text-gray-900">{title}</h3>
                            <p className="text-sm text-gray-600 mt-2">{message}</p>
                        </div>
                    </div>
                </div>
                <div className="bg-gray-50 px-6 py-3 flex flex-row-reverse gap-3 rounded-b-lg">
                    {onConfirm && (
                        <Button onClick={onConfirm}>
                            {confirmText || 'Ya, Lanjutkan'}
                        </Button>
                    )}
                    <button 
                        onClick={hideModal} 
                        className="px-4 py-2 text-sm font-semibold bg-white text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-100"
                    >
                        {cancelText || (onConfirm ? 'Batal' : 'Tutup')}
                    </button>
                </div>
            </div>
        </div>
    );
};

export default AlertDialog;