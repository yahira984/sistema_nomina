import axios from 'axios';
import { installSessionResponseInterceptor } from './Utils/sessionGuard';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
installSessionResponseInterceptor(window.axios);
