// Direct copy from Livewire's js
export const getCsrfToken = () => {
    if (document.querySelector('meta[name="csrf-token"]')) {
        return document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    }

    if (document.querySelector('[data-csrf]')) {
        return document.querySelector('[data-csrf]').getAttribute('data-csrf')
    }

    if (window.livewireScriptConfig['csrf'] ?? false) {
        return window.livewireScriptConfig['csrf']
    }

    throw new Error('No CSRF token detected');
};

export const isArray = obj => Array.isArray(obj);
export const isObjectish = obj => typeof obj === 'object' && obj !== null;
export const isObject = obj => isObjectish(obj) && ! isArray(obj);
export const isFunction = func => typeof func === 'function';

export const objectHasKey = (obj, key) => key in obj;
