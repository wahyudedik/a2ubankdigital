// Compatibility wrapper: replaces react-router-dom's useLocation
import { usePage } from '@inertiajs/react';

const useLocation = () => {
    const { url } = usePage();
    return { pathname: url.split('?')[0], search: url.includes('?') ? '?' + url.split('?')[1] : '' };
};

export default useLocation;
