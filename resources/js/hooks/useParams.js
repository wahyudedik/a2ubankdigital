// Compatibility wrapper: replaces react-router-dom's useParams
import { usePage } from '@inertiajs/react';

const useParams = () => {
    const { props } = usePage();
    return props.routeParams || {};
};

export default useParams;
