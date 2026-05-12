import React from 'react';

export default function PrimaryButton({ className = '', disabled, children, ...props }) {
    return (
        <button
            {...props}
            className={
                `inline-flex items-center justify-center w-full px-6 py-3 bg-primary border border-transparent rounded-full font-bold text-sm text-white uppercase tracking-widest hover:brightness-110 focus:brightness-110 active:brightness-90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition ease-in-out duration-150 shadow-md hover:shadow-lg ${
                    disabled && 'opacity-25 cursor-not-allowed'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
