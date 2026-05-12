import React, { forwardRef, useEffect, useRef } from 'react';

export default forwardRef(function TextInput(
    { type = 'text', className = '', isFocused = false, ...props },
    ref
) {
    const inputRef = ref ? ref : useRef();

    useEffect(() => {
        if (isFocused) {
            inputRef.current.focus();
        }
    }, [isFocused]);

    return (
        <input
            {...props}
            type={type}
            className={
                'w-full border-0 border-l-4 border-secondary bg-gray-50 text-gray-900 focus:border-primary focus:ring-0 shadow-sm px-4 py-3 text-sm transition-colors ' +
                className
            }
            ref={inputRef}
        />
    );
});
