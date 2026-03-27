// Compatibility wrapper: replaces react-router-dom's useNavigate with Inertia router
import { router } from '@inertiajs/react';

const useNavigate = () => {
    return (path, options = {}) => {
        if (options.replace) {
            router.visit(path, { replace: true });
        } else {
            router.visit(path);
        }
    };
};

export default useNavigate;
