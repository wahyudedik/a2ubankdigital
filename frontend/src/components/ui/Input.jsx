import React from 'react';
import { AppConfig } from '../../config';

const Input = ({ id, label, type = 'text', value, onChange, placeholder, required = false, name }) => {
    return (
        <div>
            {label && (
                 <label htmlFor={id || name} className="block mb-2 text-sm font-medium text-gray-700">
                    {label}
                </label>
            )}
            <input
                type={type}
                id={id || name}
                name={name || id}
                value={value}
                onChange={onChange}
                placeholder={placeholder}
                required={required}
                className={`w-full px-4 py-2 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 ${AppConfig.theme.ringFocus}`}
            />
        </div>
    );
};

export default Input;

