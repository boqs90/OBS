import Vue from 'vue';
import VueRouter from 'vue-router';
import { isLoggedIn } from '@/utils/auth';
import { getUser, getToken } from '@/utils/auth';
import axios from 'axios';

Vue.use(VueRouter);

let routes = [
  { path: '*', name: '404', component: () => import('../views/404.vue') },
  { path: '/', redirect: '/sign-in' },
  { path: '/sign-in', name: 'Sign-In', component: () => import('../views/Sign-In.vue'), meta: { layout: 'auth' } },
  { path: '/change-password', name: 'ChangePassword', component: () => import('../views/ChangePassword.vue'), meta: { layout: 'auth' } },
  { path: '/dashboard', name: 'Dashboard', component: () => import('../views/Dashboard.vue'), meta: { layout: 'dashboard', title: 'Resumen', screenKey: '/dashboard' } },
  { path: '/tables', name: 'Usuarios', component: () => import('../views/Tables.vue'), meta: { layout: 'dashboard', title: 'Usuarios', screenKey: '/tables' } },
  { path: '/roles', name: 'Roles', component: () => import('../views/Roles.vue'), meta: { layout: 'dashboard', title: 'Roles', screenKey: '/roles' } },
  { path: '/matricula', name: 'Matrícula y Pagos', component: () => import('../views/Matricula.vue'), meta: { layout: 'dashboard', title: 'Matrícula y Pagos', screenKey: '/matricula' } },
  { path: '/billing', name: 'Pagos', component: () => import('../views/Billing.vue'), meta: { layout: 'dashboard', title: 'Pagos', screenKey: '/billing' } },
  { path: '/configuraciones/institucion', name: 'Datos de la Institución', component: () => import('../views/ConfiguracionesInstitucion.vue'), meta: { layout: 'dashboard', title: 'Datos de la Institución', screenKey: '/configuraciones/institucion' } },
  { path: '/configuraciones/logos', name: 'Logos', component: () => import('../views/ConfiguracionesLogosMejorado.vue'), meta: { layout: 'dashboard', title: 'Logos', screenKey: '/configuraciones/logos' } },
  { path: '/configuraciones/parametros-academicos', name: 'Parámetros Académicos', component: () => import('../views/ConfiguracionesParametrosAcademicos.vue'), meta: { layout: 'dashboard', title: 'Parámetros Académicos', screenKey: '/configuraciones/parametros-academicos' } },
  { path: '/configuraciones/calendario', name: 'Calendario Escolar', component: () => import('../views/ConfiguracionesCalendarioEscolar.vue'), meta: { layout: 'dashboard', title: 'Calendario Escolar', screenKey: '/configuraciones/calendario' } },
  { path: '/profile', name: 'Perfiles', component: () => import('../views/Profile.vue'), meta: { layout: 'dashboard', title: 'Perfiles', screenKey: '/profile' } },
  { path: '/search', name: 'Búsqueda Global', component: () => import('../views/GlobalSearch.vue'), meta: { layout: 'dashboard', title: 'Búsqueda Global', screenKey: '/search' } },
  { path: '/comunicados', name: 'Gestión de Comunicados', component: () => import('../views/ComunicadosManagement.vue'), meta: { layout: 'dashboard', title: 'Gestión de Comunicados', screenKey: '/comunicados' } },
  { path: '/rtl', name: 'Reportes', component: () => import('../views/RTL.vue'), meta: { layout: 'dashboard', title: 'Reportes', screenKey: '/rtl' } },
  { path: '/notificaciones', name: 'Notificaciones', component: () => import('../views/Notificaciones.vue'), meta: { layout: 'dashboard', title: 'Notificaciones', screenKey: '/notificaciones' } },
  { path: '/sesiones', name: 'Sesiones', component: () => import('../views/Sesiones.vue'), meta: { layout: 'dashboard', title: 'Sesiones', screenKey: '/sesiones' } },
  { path: '/registro', redirect: '/registro/alumnos' },
  { path: '/registro/alumnos', name: 'Registro Alumnos', component: () => import('../views/RegistroAlumnos.vue'), meta: { layout: 'dashboard', title: 'Registro Alumnos', screenKey: '/registro/alumnos' } },
  { path: '/registro/alumnos/importar', name: 'Importar Alumnos', component: () => import('../views/ImportarAlumnos.vue'), meta: { layout: 'dashboard', title: 'Importar Alumnos', screenKey: '/registro/alumnos/importar' } },
  { path: '/registro/maestros', name: 'Registro Maestros', component: () => import('../views/RegistroMaestros.vue'), meta: { layout: 'dashboard', title: 'Registro Maestros', screenKey: '/registro/maestros' } },
  { path: '/registro/empleados', name: 'Registro Empleados', component: () => import('../views/RegistroEmpleados.vue'), meta: { layout: 'dashboard', title: 'Registro Empleados', screenKey: '/registro/empleados' } },
  { path: '/registro/cargos', name: 'Registro Cargos', component: () => import('../views/Cargos.vue'), meta: { layout: 'dashboard', title: 'Cargos', screenKey: '/registro/cargos' } },
  { path: '/registro/grados', name: 'Registro Grados', component: () => import('../views/RegistroGrados.vue'), meta: { layout: 'dashboard', title: 'Grados', screenKey: '/registro/grados' } },
  // Rutas de pagos - submenú
  { path: '/pagos/matriculas', name: 'Pagos de Matrículas', component: () => import('../views/PagosMatriculas.vue'), meta: { layout: 'dashboard', title: 'Pagos de Matrículas', screenKey: '/pagos/matriculas' } },
  { path: '/pagos/planilla', name: 'Planilla', component: () => import('../views/Planilla.vue'), meta: { layout: 'dashboard', title: 'Planilla', screenKey: '/pagos/planilla' } },
  { path: '/pagos/contabilidad', name: 'Contabilidad', component: () => import('../views/Contabilidad.vue'), meta: { layout: 'dashboard', title: 'Contabilidad', screenKey: '/pagos/contabilidad' } },
  { path: '/pagos/inventario', name: 'Inventario', component: () => import('../views/Billing.vue'), meta: { layout: 'dashboard', title: 'Inventario', screenKey: '/pagos/inventario' } },
  // Gestión de restablecimiento de contraseñas
  { path: '/password-reset-requests', name: 'Solicitudes de Restablecimiento', component: () => import('../views/PasswordResetRequests.vue'), meta: { layout: 'dashboard', title: 'Solicitudes de Restablecimiento', screenKey: '/password-reset-requests' } },
  // Reportes de asistencia
  { path: '/reportes/asistencia', name: 'Reportes de Asistencia', component: () => import('../views/AttendanceReports.vue'), meta: { layout: 'dashboard', title: 'Reportes de Asistencia', screenKey: '/reportes/asistencia' } },
  // Reportes de rendimiento académico
  { path: '/reportes/academico', name: 'Reportes Académicos', component: () => import('../views/AcademicPerformanceReports.vue'), meta: { layout: 'dashboard', title: 'Reportes Académicos', screenKey: '/reportes/academico' } },
  // Ingreso de reportes
  { path: '/reportes/ingreso', name: 'Ingreso de Reportes', component: () => import('../views/ReportEntry.vue'), meta: { layout: 'dashboard', title: 'Ingreso de Reportes', screenKey: '/reportes/ingreso' } },
  // Boletas de calificaciones
  { path: '/boletas/calificaciones', name: 'Boletas de Calificaciones', component: () => import('../views/GradeReports.vue'), meta: { layout: 'dashboard', title: 'Boletas de Calificaciones', screenKey: '/boletas/calificaciones' } },
  // Control de inventario
  { path: '/inventario/control', name: 'Control de Inventario', component: () => import('../views/InventoryControl.vue'), meta: { layout: 'dashboard', title: 'Control de Inventario', screenKey: '/inventario/control' } },
  { path: '/inventario/ventas', name: 'Ventas de Insumos', component: () => import('../views/VentasInsumos.vue'), meta: { layout: 'dashboard', title: 'Ventas de Insumos', screenKey: '/inventario/ventas' } },
  // Reporte de errores y mejoras
  { path: '/reportes/errores', name: 'Reporte de Errores', component: () => import('../views/reportes/errores.vue'), meta: { layout: 'dashboard', title: 'Reporte de Errores', screenKey: '/reportes/errores' } },
  // Configuración de redes sociales
  { path: '/configuracion/redes-sociales', name: 'Configuración de Redes Sociales', component: () => import('../views/ConfiguracionRedesSociales.vue'), meta: { layout: 'dashboard', title: 'Configuración de Redes Sociales', screenKey: '/configuracion/redes-sociales' } },
  // Registro de asignaturas
  { path: '/registro/asignaturas', name: 'Registro de Asignaturas', component: () => import('../views/Asignaturas.vue'), meta: { layout: 'dashboard', title: 'Registro de Asignaturas', screenKey: '/registro/asignaturas' } },
  // Comunicados
  { path: '/comunicados', name: 'Comunicados', component: () => import('../views/Comunicados.vue'), meta: { layout: 'dashboard', title: 'Comunicados', screenKey: '/comunicados' } },
  // Planificación
  { path: '/planificacion/estudios', name: 'Plan de Estudios', component: () => import('../views/PlanEstudios.vue'), meta: { layout: 'dashboard', title: 'Plan de Estudios', screenKey: '/planificacion/estudios' } },
  { path: '/planificacion/carga', name: 'Carga Académica', component: () => import('../views/CargaAcademica.vue'), meta: { layout: 'dashboard', title: 'Carga Académica', screenKey: '/planificacion/carga' } },
  { path: '/planificacion/horarios', name: 'Horarios de Clases', component: () => import('../views/HorariosClases.vue'), meta: { layout: 'dashboard', title: 'Horarios de Clases', screenKey: '/planificacion/horarios' } },
  { path: '/planificacion/asistencia', name: 'Control de Asistencia', component: () => import('../views/ControlAsistencia.vue'), meta: { layout: 'dashboard', title: 'Control de Asistencia', screenKey: '/planificacion/asistencia' } },
  { path: '/planificacion/diario-pedagogico', name: 'Diario Pedagógico', component: () => import('../views/DiarioPedagogico.vue'), meta: { layout: 'dashboard', title: 'Diario Pedagógico', screenKey: '/planificacion/diario-pedagogico' } },
  // Reporte de incidencias
  { path: '/reportes/incidencias', name: 'Reporte de Incidencias', component: () => import('../views/RTL.vue'), meta: { layout: 'dashboard', title: 'Reporte de Incidencias', screenKey: '/reportes/incidencias' } },
  // compatibilidad (antes)
  { path: '/sign-up', redirect: '/registro/alumnos' },
  { path: '/cargos', redirect: '/registro/cargos' }
];

const router = new VueRouter({
  mode: 'hash',
  base: process.env.BASE_URL,
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (to.hash) return { selector: to.hash, behavior: 'smooth' };
    return { x: 0, y: 0, behavior: 'smooth' };
  }
});

// 🔒 Guard global
router.beforeEach((to, from, next) => {
  const protectedRoutes = ['/dashboard', '/tables', '/roles', '/matricula', '/billing', '/profile', '/rtl', '/notificaciones', '/sesiones', '/registro/alumnos', '/registro/alumnos/importar', '/registro/maestros', '/registro/empleados', '/registro/cargos', '/registro/grados', '/registro/asignaturas', '/pagos/matriculas', '/pagos/planilla', '/pagos/contabilidad', '/pagos/inventario', '/password-reset-requests', '/reportes/asistencia', '/reportes/academico', '/reportes/ingreso', '/boletas/calificaciones', '/inventario/control', '/inventario/ventas', '/reportes/errores', '/reportes/incidencias', '/comunicados', '/planificacion/estudios', '/planificacion/carga', '/planificacion/horarios', '/planificacion/asistencia', '/planificacion/diario-pedagogico', '/configuraciones/institucion', '/configuraciones/logos', '/configuraciones/parametros-academicos', '/configuraciones/calendario'];
  
  // Si intenta acceder a rutas protegidas sin estar logueado
  if (protectedRoutes.includes(to.path) && !isLoggedIn()) {
    next('/sign-in');
  } else if (isLoggedIn() && protectedRoutes.includes(to.path)) {
    // Verificar si tiene contraseña temporal
    const user = getUser();
    const hasTempPassword = user && user.has_temp_password;
    
    if (hasTempPassword && to.path !== '/change-password') {
      // Redirigir a cambio de contraseña si tiene contraseña temporal
      next('/change-password');
    } else {
      // Permisos por rol (pantallas)
      const screenKey = to.meta && to.meta.screenKey;
      if (protectedRoutes.includes(to.path) && screenKey) {
        const roleName = String(user?.role || '').trim().toLowerCase();
        // Solo "Sistema" y "Super usuario" ignoran permisos.
        const isPrivileged = (roleName === 'sistema' || roleName === 'super usuario');
        if (isPrivileged) return next();

        // Evitar loop infinito: el dashboard siempre debe ser accesible como fallback
        if (String(screenKey) === '/dashboard') return next();

        const cached = sessionStorage.getItem('allowedScreens') || localStorage.getItem('allowedScreens');
        if (cached) {
          try {
            const allowed = JSON.parse(cached);
            if (Array.isArray(allowed) && allowed.includes(String(screenKey))) return next();
            if (to.path === '/dashboard') return next();
            return next({ path: '/dashboard', query: { denied: to.path } });
          } catch (e) {
            // ignore
          }
        }

        // Si no hay cache, consultamos al backend (una vez) y cacheamos
        const token = getToken();
        if (!token) return next('/sign-in');

        axios.get('http://localhost:8000/api/me/screens', { headers: { Authorization: `Bearer ${token}` } })
          .then((res) => {
            const allowed = (res.data?.screen_keys || []).map(String);
            // cache en sessionStorage (no persistente)
            sessionStorage.setItem('allowedScreens', JSON.stringify(allowed));
            if (allowed.includes(String(screenKey))) next();
            else {
              if (to.path === '/dashboard') next();
              else next({ path: '/dashboard', query: { denied: to.path } });
            }
          })
          .catch(() => {
            if (to.path === '/dashboard') next();
            else next({ path: '/dashboard', query: { denied: to.path } });
          });
        return;
      }
      next();
    }
  } else {
    next();
  }
});

export default router;
