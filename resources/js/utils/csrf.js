/**
 * CSRF Token Utility
 * Helper functions for handling CSRF tokens in API requests
 */

/**
 * Get CSRF token from cookie
 * @returns {string} The CSRF token or empty string if not found
 */
export function getCsrfToken() {
    const cookie = document.cookie
        .split('; ')
        .find(row => row.startsWith('XSRF-TOKEN='));
    return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
}

/**
 * Get default headers with CSRF token
 * @returns {Object} Headers object with CSRF token and common headers
 */
export function getDefaultHeaders() {
    return {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': getCsrfToken()
    };
}

/**
 * Get headers for multipart/form-data requests
 * @returns {Object} Headers object with CSRF token (no Content-Type)
 */
export function getMultipartHeaders() {
    return {
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': getCsrfToken()
    };
}
