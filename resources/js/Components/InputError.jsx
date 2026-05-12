import React from 'react';

export default function InputError({ message, className = '', ...props }) {
    return message ? (
        <p {...props} className={'text-sm text-danger mt-1 ' + className}>
            {message}
        </p>
    ) : null;
}
