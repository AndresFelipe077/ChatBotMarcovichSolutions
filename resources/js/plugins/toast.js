import VueToast from 'vue-toast-notification';
import 'vue-toast-notification/dist/theme-sugar.css';

const options = {
    position: 'top-right',
    duration: 3000,
    dismissible: true
};

export const toast = {
    success: (message) => window.Toast.success(message, options),
    error: (message) => window.Toast.error(message, options),
    info: (message) => window.Toast.info(message, options),
    warning: (message) => window.Toast.warning(message, options)
};

export default {
    install: (app) => {
        app.use(VueToast, options);

        app.config.globalProperties.$toast = toast;
    }
};
