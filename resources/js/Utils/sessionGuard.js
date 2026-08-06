let redirecting = false;

export const redirectToExpiredLogin = () => {
    if (redirecting || window.location.pathname === '/login') return;

    redirecting = true;
    window.sessionStorage.setItem('session-expired-notice', '1');
    window.location.assign('/login?expired=1');
};

export const installSessionResponseInterceptor = (axios) => {
    axios.interceptors.response.use(
        response => response,
        error => {
            const status = Number(error?.response?.status || 0);
            const finalUrl = String(error?.response?.request?.responseURL || '');

            if (status === 401 || status === 419 || finalUrl.includes('/login')) {
                redirectToExpiredLogin();
            }

            return Promise.reject(error);
        },
    );
};
