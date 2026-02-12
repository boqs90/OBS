import Vue from 'vue'
import Antd from 'ant-design-vue';
import 'ant-design-vue/dist/antd.css';
import App from './App.vue'
import DefaultLayout from './layouts/Default.vue'
import DashboardLayout from './layouts/Dashboard.vue'
import DashboardRTLLayout from './layouts/DashboardRTL.vue'
import router from './router'
import axios from 'axios'; // <-- Importar axios
// import './plugins/click-away'

import './scss/app.scss';

Vue.use(Antd);

Vue.config.productionTip = false

// Adding template layouts to the vue components.
Vue.component("layout-default", DefaultLayout);
Vue.component("layout-dashboard", DashboardLayout);
Vue.component("layout-dashboard-rtl", DashboardRTLLayout);

// 🔹 Inicializar axios con token si existe
const token = localStorage.getItem('token');
if (token) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

new Vue({
  router,
  render: h => h(App)
}).$mount('#app')
