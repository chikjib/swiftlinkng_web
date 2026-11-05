export function installAuthentication(axios, auth, browser = window) {
    function apiPath(config) {
        try {
            const url = new URL(config.url, config.baseURL || browser.location.origin);
            return url.origin === browser.location.origin && url.pathname.startsWith('/api/')
                ? url.pathname : null;
        } catch {
            return null;
        }
    }

    axios.interceptors.request.use((config) => {
        if (apiPath(config)) {
            // Components may have captured an older token when first imported.
            const token = browser.localStorage.getItem('token');
            config.headers = config.headers || {};
            delete config.headers.authorization;
            if (token) config.headers.Authorization = `Bearer ${token}`;
            else delete config.headers.Authorization;
        }
        return config;
    });

    axios.interceptors.response.use(response => response, error => {
        const path = apiPath(error.config || {});
        const token = browser.localStorage.getItem('token');
        const sentToken = error.config?.headers?.Authorization;
        // A provider failure or a delayed response from an old login must not
        // erase a working login. Laravel's auth middleware uses this message.
        if (path && path !== '/api/login' && path !== '/api/register'
            && !path.startsWith('/api/auth/')
            && error.response?.status === 401
            && error.response.data?.message === 'Unauthenticated.'
            && token && sentToken === `Bearer ${token}`) {
            auth.logout();
            browser.location.href = '/login';
        }
        return Promise.reject(error);
    });
}
