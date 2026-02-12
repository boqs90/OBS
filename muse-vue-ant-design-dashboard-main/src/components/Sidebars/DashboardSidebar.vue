<template>
	
	<!-- Main Sidebar -->
	<a-layout-sider
		collapsible
		class="sider-primary"
		breakpoint="lg"
		collapsed-width="0"
		width="250px"
		:collapsed="sidebarCollapsed"
		@collapse="$emit('toggleSidebar', ! sidebarCollapsed)"
		:trigger="null"
		:class="['ant-layout-sider-' + sidebarColor, 'ant-layout-sider-' + sidebarTheme]"
		theme="light"
		:style="{ backgroundColor: 'transparent',}">
			<div style="padding:0px" class="brand text-center">
				<div class="sidebar-brand-monogram-wrap" aria-label="OBS">
					<div class="sidebar-brand-monogram" aria-hidden="true">
						<span class="sidebar-brand-monogram__abbr">OBS</span>
						<span class="sidebar-brand-monogram__sub">Olanchito Bilingual School</span>
					</div>
				</div>
			</div>
			<hr>

			<!-- Sidebar Navigation Menu -->
			<a-menu theme="light" mode="inline">
				<a-menu-item v-if="canSee('/dashboard')">
					<router-link to="/dashboard">
						<span class="icon">
							<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M3 4C3 3.44772 3.44772 3 4 3H16C16.5523 3 17 3.44772 17 4V6C17 6.55228 16.5523 7 16 7H4C3.44772 7 3 6.55228 3 6V4Z" fill="#111827"/>
								<path d="M3 10C3 9.44771 3.44772 9 4 9H10C10.5523 9 11 9.44771 11 10V16C11 16.5523 10.5523 17 10 17H4C3.44772 17 3 16.5523 3 16V10Z" fill="#111827"/>
								<path d="M14 9C13.4477 9 13 9.44771 13 10V16C13 16.5523 13.4477 17 14 17H16C16.5523 17 17 16.5523 17 16V10C17 9.44771 16.5523 9 16 9H14Z" fill="#111827"/>
							</svg>
						</span>
						<span class="label">Resumen</span>
					</router-link>
				</a-menu-item>
				<a-menu-item v-if="canSee('/matricula')">
					<router-link to="/matricula">
						<span class="icon">
							<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M9 2C8.44772 2 8 2.44772 8 3C8 3.55228 8.44772 4 9 4H11C11.5523 4 12 3.55228 12 3C12 2.44772 11.5523 2 11 2H9Z" fill="#111827"/>
								<path fill-rule="evenodd" clip-rule="evenodd" d="M4 5C4 3.89543 4.89543 3 6 3C6 4.65685 7.34315 6 9 6H11C12.6569 6 14 4.65685 14 3C15.1046 3 16 3.89543 16 5V16C16 17.1046 15.1046 18 14 18H6C4.89543 18 4 17.1046 4 16V5ZM7 9C6.44772 9 6 9.44772 6 10C6 10.5523 6.44772 11 7 11H7.01C7.56228 11 8.01 10.5523 8.01 10C8.01 9.44772 7.56228 9 7.01 9H7ZM10 9C9.44772 9 9 9.44772 9 10C9 10.5523 9.44772 11 10 11H13C13.5523 11 14 10.5523 14 10C14 9.44772 13.5523 9 13 9H10ZM7 13C6.44772 13 6 13.4477 6 14C6 14.5523 6.44772 15 7 15H7.01C7.56228 15 8.01 14.5523 8.01 14C8.01 13.4477 7.56228 13 7.01 13H7ZM10 13C9.44772 13 9 13.4477 9 14C9 14.5523 9.44772 15 10 15H13C13.5523 15 14 14.5523 14 14C14 13.4477 13.5523 13 13 13H10Z" fill="#111827"/>
							</svg>
						</span>
						<span class="label">Matrícula y Pagos</span>
					</router-link>
				</a-menu-item>
				<a-sub-menu v-if="canSeeAny(pagosPaths)" key="pagos">
					<span slot="title" class="sidebar-submenu-title">
						<span class="icon">
							<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M4 4C2.89543 4 2 4.89543 2 6V7H18V6C18 4.89543 17.1046 4 16 4H4Z" fill="#111827"/>
								<path fill-rule="evenodd" clip-rule="evenodd" d="M18 9H2V14C2 15.1046 2.89543 16 4 16H16C17.1046 16 18 15.1046 18 14V9ZM4 13C4 12.4477 4.44772 12 5 12H6C6.55228 12 7 12.4477 7 13C7 13.5523 6.55228 14 6 14H5C4.44772 14 4 13.5523 4 13ZM9 12C8.44772 12 8 12.4477 8 13C8 13.5523 8.44772 14 9 14H10C10.5523 14 11 13.5523 11 13C11 12.4477 10.5523 12 10 12H9Z" fill="#111827"/>
							</svg>
						</span>
						<span class="label">Pagos</span>
					</span>

					<a-menu-item v-if="canSee('/pagos/planilla')" key="pagos-planilla">
						<router-link to="/pagos/planilla">Planilla</router-link>
					</a-menu-item>

					<a-menu-item v-if="canSee('/pagos/contabilidad')" key="pagos-contabilidad">
						<router-link to="/pagos/contabilidad">Contabilidad</router-link>
					</a-menu-item>
				</a-sub-menu>
				<a-sub-menu v-if="canSeeAny(reportesPaths)" key="reportes">
					<span slot="title" class="sidebar-submenu-title">
						<span class="icon">
							<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path fill-rule="evenodd" clip-rule="evenodd" d="M3 6C3 4.34315 4.34315 3 6 3H16C16.37888 3 16.725 3.214 16.8944 3.55279C17.0638 3.89157 17.0273 4.29698 16.8 4.6L14.25 8L16.8 11.4C17.0273 11.703 17.0638 12.1084 16.8944 12.4472C16.725 12.786 16.3788 13 16 13H6C5.44772 13 5 13.4477 5 14V17C5 17.5523 4.55228 18 4 18C3.44772 18 3 17.5523 3 17V6Z" fill="#111827"/>
							</svg>
						</span>
						<span class="label">Reportes</span>
					</span>

					<a-menu-item v-if="canSee('/reportes/asistencia')" key="reportes-asistencia">
						<router-link to="/reportes/asistencia">Reportes de asistencia</router-link>
					</a-menu-item>

					<a-menu-item v-if="canSee('/reportes/incidencias')" key="reportes-incidencias">
						<router-link to="/reportes/incidencias">Reporte de incidencias</router-link>
					</a-menu-item>


					<a-menu-item v-if="canSee('/reportes/academico')" key="reportes-academico">
						<router-link to="/reportes/academico">Reportes académicos</router-link>
					</a-menu-item>

					<a-menu-item v-if="canSee('/reportes/ingreso')" key="reportes-ingreso">
						<router-link to="/reportes/ingreso">Ingreso de reportes</router-link>
					</a-menu-item>
					
					<a-menu-item v-if="canSee('/boletas/calificaciones')" key="boletas-calificaciones">
						<router-link to="/boletas/calificaciones">Boletas de calificaciones</router-link>
					</a-menu-item>
				</a-sub-menu>

				<a-menu-item v-if="canSee('/inventario/control')" key="inventario-control">
					<router-link to="/inventario/control">
						<span class="icon">
							<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M4 4C2.89543 4 2 4.89543 2 6V7H18V6C18 4.89543 17.1046 4 16 4H4Z" fill="#111827"/>
								<path fill-rule="evenodd" clip-rule="evenodd" d="M18 9H2V14C2 15.1046 2.89543 16 4 16H16C17.1046 16 18 15.1046 18 14V9ZM4 13C4 12.4477 4.44772 12 5 12H6C6.55228 12 7 12.4477 7 13C7 13.5523 6.55228 14 6 14H5C4.44772 14 4 13.5523 4 13ZM9 12C8.44772 12 8 12.4477 8 13C8 13.5523 8.44772 14 9 14H10C10.5523 14 11 13.5523 11 13C11 12.4477 10.5523 12 10 12H9Z" fill="#111827"/>
							</svg>
						</span>
						<span class="label">Control Inventario</span>
					</router-link>
				</a-menu-item>

				<a-menu-item v-if="canSee('/inventario/ventas')" key="inventario-ventas">
					<router-link to="/inventario/ventas">
						<span class="icon">
							<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M3 1C2.44772 1 2 1.44772 2 2V3C2 3.55228 2.44772 4 3 4H17C17.5523 4 18 3.55228 18 3V2C18 1.44772 17.5523 1 17 1H3Z" fill="#111827"/>
								<path fill-rule="evenodd" clip-rule="evenodd" d="M3 5C2.44772 5 2 5.44772 2 6V8C2 8.55228 2.44772 9 3 9H17C17.5523 9 18 8.55228 18 8V6C18 5.44772 17.5523 5 17 5H3ZM4 7C4 6.44772 4.44772 6 5 6H6C6.55228 6 7 6.44772 7 7C7 7.55228 6.55228 8 6 8H5C4.44772 8 4 7.55228 4 7Z" fill="#111827"/>
								<path fill-rule="evenodd" clip-rule="evenodd" d="M3 11C2.44772 11 2 11.4477 2 12V14C2 14.5523 2.44772 15 3 15H17C17.5523 15 18 14.5523 18 14V12C18 11.4477 17.5523 11 17 11H3ZM4 13C4 12.4477 4.44772 12 5 12H6C6.55228 12 7 12.4477 7 13C7 13.5523 6.55228 14 6 14H5C4.44772 14 4 13.5523 4 13Z" fill="#111827"/>
								<path d="M3 17C2.44772 17 2 17.4477 2 18C2 18.5523 2.44772 19 3 19H17C17.5523 19 18 18.5523 18 18C18 17.4477 17.5523 17 17 17H3Z" fill="#111827"/>
							</svg>
						</span>
						<span class="label">Ventas de Insumos</span>
					</router-link>
				</a-menu-item>

				<a-menu-item v-if="canSee('/profile')">
					<router-link to="/profile">
						<span class="icon">
							<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path fill-rule="evenodd" clip-rule="evenodd" d="M18 10C18 14.4183 14.4183 18 10 18C5.58172 18 2 14.4183 2 10C2 5.58172 5.58172 2 10 2C14.4183 2 18 5.58172 18 10ZM12 7C12 8.10457 11.1046 9 10 9C8.89543 9 8 8.10457 8 7C8 5.89543 8.89543 5 10 5C11.1046 5 12 5.89543 12 7ZM9.99993 11C7.98239 11 6.24394 12.195 5.45374 13.9157C6.55403 15.192 8.18265 16 9.99998 16C11.8173 16 13.4459 15.1921 14.5462 13.9158C13.756 12.195 12.0175 11 9.99993 11Z" fill="#111827"/>
							</svg>
						</span>
						<span class="label">Perfiles</span>
					</router-link>
				</a-menu-item>

				<a-menu-item v-if="canSee('/comunicados')" key="comunicados">
					<router-link to="/comunicados">
						<span class="icon">
							<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M2 5C2 3.89543 2.89543 3 4 3H16C17.1046 3 18 3.89543 18 5V11C18 11.5523 17.5523 12 17 12H3C2.44772 12 2 11.5523 2 11V5Z" fill="#111827"/>
								<path d="M3 14C2.44772 14 2 14.4477 2 15C2 15.5523 2.44772 16 3 16H17C17.5523 16 18 15.5523 18 15V14H3Z" fill="#111827"/>
								<path d="M8 7C8 6.44772 8.44772 6 9 6H11C11.5523 6 12 6.44772 12 7C12 7.55228 11.5523 8 11 8H9C8.44772 8 8 7.55228 8 7Z" fill="#111827"/>
							</svg>
						</span>
						<span class="label">Comunicados</span>
					</router-link>
				</a-menu-item>

				<a-sub-menu v-if="canSeeAny(planificacionPaths)" key="planificacion">
					<span slot="title" class="sidebar-submenu-title">
						<span class="icon">
							<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M4 4C2.89543 4 2 4.89543 2 6V7H18V6C18 4.89543 17.1046 4 16 4H4Z" fill="#111827"/>
								<path fill-rule="evenodd" clip-rule="evenodd" d="M18 9H2V14C2 15.1046 2.89543 16 4 16H16C17.1046 16 18 15.1046 18 14V9ZM4 13C4 12.4477 4.44772 12 5 12H6C6.55228 12 7 12.4477 7 13C7 13.5523 6.55228 14 6 14H5C4.44772 14 4 13.5523 4 13ZM9 12C8.44772 12 8 12.4477 8 13C8 13.5523 8.44772 14 9 14H10C10.5523 14 11 13.5523 11 13C11 12.4477 10.5523 12 10 12H9Z" fill="#111827"/>
							</svg>
						</span>
						<span class="label">Planificación</span>
					</span>

					<a-menu-item v-if="canSee('/planificacion/estudios')" key="planificacion-estudios">
						<router-link to="/planificacion/estudios">Plan de estudios</router-link>
					</a-menu-item>

					<a-menu-item v-if="canSee('/planificacion/carga')" key="planificacion-carga">
						<router-link to="/planificacion/carga">Carga académica</router-link>
					</a-menu-item>

					<a-menu-item v-if="canSee('/planificacion/horarios')" key="planificacion-horarios">
						<router-link to="/planificacion/horarios">Horarios de clases</router-link>
					</a-menu-item>

					<a-menu-item v-if="canSee('/planificacion/asistencia')" key="planificacion-asistencia">
						<router-link to="/planificacion/asistencia">Control de Asistencia</router-link>
					</a-menu-item>

					<a-menu-item v-if="canSee('/planificacion/diario-pedagogico')" key="planificacion-diario-pedagogico">
						<router-link to="/planificacion/diario-pedagogico">Diario pedagógico</router-link>
					</a-menu-item>
				</a-sub-menu>

				<a-menu-item v-if="canSeeAny(registroPaths)" class="menu-item-header">
					Menu de registros
				</a-menu-item>
				<a-sub-menu v-if="canSeeAny(registroPaths)" key="registro">
					<span slot="title" class="sidebar-submenu-title">
						<span class="icon">
							<svg width="14px" height="14px" viewBox="0 0 14 14" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
								<title>duplicate</title>
								<g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
									<g id="Tables" transform="translate(-58.000000, -507.000000)" fill="#BFBFBF" fill-rule="nonzero">
										<g id="sidebar" transform="translate(33.000000, 43.000000)">
											<g id="sign-up" transform="translate(16.000000, 455.000000)">
												<g id="duplicate" transform="translate(9.000000, 9.000000)">
													<path d="M4,6 C4,4.89543 4.89543,4 6,4 L12,4 C13.1046,4 14,4.89543 14,6 L14,12 C14,13.1046 13.1046,14 12,14 L6,14 C4.89543,14 4,13.1046 4,12 L4,6 Z" id="Path"></path>
													<path d="M2,0 C0.89543,0 0,0.89543 0,2 L0,8 C0,9.1046 0.89543,10 2,10 L2,4 C2,2.8954305 2.8954305,2 4,2 L10,2 L10,2 C10,0.89543 9.1046,0 8,0 L2,0 Z" id="Path"></path>
												</g>
											</g>
										</g>
									</g>
								</g>
							</svg>
						</span>
						<span class="label">Registro</span>
					</span>

					<a-menu-item v-if="canSee('/registro/alumnos')" key="registro-alumnos">
						<router-link to="/registro/alumnos">Registro Alumnos</router-link>
					</a-menu-item>

					<a-menu-item v-if="canSee('/registro/maestros')" key="registro-maestros">
						<router-link to="/registro/maestros">Registro Maestros</router-link>
					</a-menu-item>

					<a-menu-item v-if="canSee('/registro/empleados')" key="registro-empleados">
						<router-link to="/registro/empleados">Registro Empleados</router-link>
					</a-menu-item>

					<a-menu-item v-if="canSee('/registro/cargos')" key="registro-cargos">
						<router-link to="/registro/cargos">Cargos</router-link>
					</a-menu-item>

					<a-menu-item v-if="canSee('/registro/grados')" key="registro-grados">
						<router-link to="/registro/grados">Grados</router-link>
					</a-menu-item>

					<a-menu-item v-if="canSee('/registro/asignaturas')" key="registro-asignaturas">
						<router-link to="/registro/asignaturas">Asignaturas</router-link>
					</a-menu-item>
				</a-sub-menu>

				<a-menu-item v-if="canSeeAny(usersAdminPaths)" class="menu-item-header">
					Administración de usuarios
				</a-menu-item>
				<!-- <a-menu-item>
					<router-link to="/sign-in">
						<span class="icon">
							<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path fill-rule="evenodd" clip-rule="evenodd" d="M6 2C5.44772 2 5 2.44772 5 3V4H4C2.89543 4 2 4.89543 2 6V16C2 17.1046 2.89543 18 4 18H16C17.1046 18 18 17.1046 18 16V6C18 4.89543 17.1046 4 16 4H15V3C15 2.44772 14.5523 2 14 2C13.4477 2 13 2.44772 13 3V4H7V3C7 2.44772 6.55228 2 6 2ZM6 7C5.44772 7 5 7.44772 5 8C5 8.55228 5.44772 9 6 9H14C14.5523 9 15 8.55228 15 8C15 7.44772 14.5523 7 14 7H6Z" fill="#111827"/>
							</svg>
						</span>
						<span class="label">Registro de Persona</span>
					</router-link>
				</a-menu-item> -->
				<a-sub-menu v-if="canSeeAny(usersAdminPaths)" key="users-admin">
					<span slot="title" class="sidebar-submenu-title">
						<span class="icon">
							<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M9 2C8.44772 2 8 2.44772 8 3C8 3.55228 8.44772 4 9 4H11C11.5523 4 12 3.55228 12 3C12 2.44772 11.5523 2 11 2H9Z" fill="#111827"/>
								<path fill-rule="evenodd" clip-rule="evenodd" d="M4 5C4 3.89543 4.89543 3 6 3C6 4.65685 7.34315 6 9 6H11C12.6569 6 14 4.65685 14 3C15.1046 3 16 3.89543 16 5V16C16 17.1046 15.1046 18 14 18H6C4.89543 18 4 17.1046 4 16V5ZM7 9C6.44772 9 6 9.44772 6 10C6 10.5523 6.44772 11 7 11H7.01C7.56228 11 8.01 10.5523 8.01 10C8.01 9.44772 7.56228 9 7.01 9H7ZM10 9C9.44772 9 9 9.44772 9 10C9 10.5523 9.44772 11 10 11H13C13.5523 11 14 10.5523 14 10C14 9.44772 13.5523 9 13 9H10ZM7 13C6.44772 13 6 13.4477 6 14C6 14.5523 6.44772 15 7 15H7.01C7.56228 15 8.01 14.5523 8.01 14C8.01 13.4477 7.56228 13 7.01 13H7ZM10 13C9.44772 13 9 13.4477 9 14C9 14.5523 9.44772 15 10 15H13C13.5523 15 14 14.5523 14 14C14 13.4477 13.5523 13 13 13H10Z" fill="#111827"/>
							</svg>
						</span>
						<span class="label">Usuarios</span>
					</span>

					<a-menu-item v-if="canSee('/notificaciones')" key="users-admin-notifications">
						<router-link to="/notificaciones">Notificaciones</router-link>
					</a-menu-item>

					<a-menu-item v-if="canSee('/sesiones')" key="users-admin-sessions">
						<router-link to="/sesiones">Sesiones</router-link>
					</a-menu-item>

					<a-menu-item v-if="canSee('/tables')" key="users-admin-users">
						<router-link to="/tables">Administrar usuarios</router-link>
					</a-menu-item>

					<a-menu-item v-if="canSee('/roles')" key="users-admin-roles">
						<router-link to="/roles">Administrar roles</router-link>
					</a-menu-item>

					<a-menu-item v-if="canSee('/password-reset-requests')" key="users-admin-password-resets">
						<router-link to="/password-reset-requests">Restablecer contraseñas</router-link>
					</a-menu-item>
				</a-sub-menu>
			</a-menu>
			<!-- / Sidebar Navigation Menu -->

			<!-- Sidebar Footer -->
			<div class="aside-footer">
				<!-- Reporte de Errores -->
				<div class="footer-box footer-box--bug-report">
					<span class="icon">
						<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M9 2C8.44772 2 8 2.44772 8 3C8 3.55228 8.44772 4 9 4H11C11.5523 4 12 3.55228 12 3C12 2.44772 11.5523 2 11 2H9Z" fill="#111827"/>
							<path fill-rule="evenodd" clip-rule="evenodd" d="M4 5C4 3.89543 4.89543 3 6 3C6 4.65685 7.34315 6 9 6H11C12.6569 6 14 4.65685 14 3C15.1046 3 16 3.89543 16 5V16C16 17.1046 15.1046 18 14 18H6C4.89543 18 4 17.1046 4 16V5ZM7 9C6.44772 9 6 9.44772 6 10C6 10.5523 6.44772 11 7 11H7.01C7.56228 11 8.01 10.5523 8.01 10C8.01 9.44772 7.56228 9 7.01 9H7ZM10 9C9.44772 9 9 9.44772 9 10C9 10.5523 9.44772 11 10 11H13C13.5523 11 14 10.5523 14 10C14 9.44772 13.5523 9 13 9H10ZM7 13C6.44772 13 6 13.4477 6 14C6 14.5523 6.44772 15 7 15H7.01C7.56228 15 8.01 14.5523 8.01 14C8.01 13.4477 7.56228 13 7.01 13H7ZM10 13C9.44772 13 9 13.4477 9 14C9 14.5523 9.44772 15 10 15H13C13.5523 15 14 14.5523 14 14C14 13.4477 13.5523 13 13 13H10Z" fill="#111827"/>
						</svg>
					</span>
					<h6>Reportar Error</h6>
					<p>¿Encontraste un error o tienes una sugerencia? Repórtalo aquí.</p>
					<a-button class="support-outline-btn" type="primary">
						<a-icon type="bug" />
						Reportar Bug
						</a-button>
				</div>

				<div class="footer-box footer-box--support">
					<span class="icon">
						<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M3 4C3 3.44772 3.44772 3 4 3H16C16.5523 3 17 3.44772 17 4V6C17 6.55228 16.5523 7 16 7H4C3.44772 7 3 6.55228 3 6V4Z" fill="#111827"/>
							<path d="M3 10C3 9.44771 3.44772 9 4 9H10C10.5523 9 11 9.44771 11 10V16C11 16.5523 10.5523 17 10 17H4C3.44772 17 3 16.5523 3 16V10Z" fill="#111827"/>
							<path d="M14 9C13.4477 9 13 9.44771 13 10V16C13 16.5523 13.4477 17 14 17H16C16.5523 17 17 16.5523 17 16V10C17 9.44771 16.5523 9 16 9H14Z" fill="#111827"/>
						</svg>
					</span>
					<h6>Soporte técnico</h6>
					<p>¿Tienes un problema? Escríbenos por WhatsApp y te ayudamos.</p>
					<a-button class="support-outline-btn" type="primary" ghost :href="whatsappSupportUrl" block target="_blank">
						<span class="wsp-btn-content">
							<svg class="wsp-icon" viewBox="0 0 32 32" aria-hidden="true">
								<path fill="currentColor" d="M19.11 17.61c-.22-.11-1.29-.64-1.49-.71-.2-.07-.35-.11-.5.11-.15.22-.57.71-.7.86-.13.15-.26.16-.48.05-.22-.11-.94-.35-1.79-1.12-.66-.59-1.11-1.33-1.24-1.55-.13-.22-.01-.34.1-.45.1-.1.22-.26.33-.39.11-.13.15-.22.22-.37.07-.15.04-.28-.02-.39-.06-.11-.5-1.2-.69-1.64-.18-.43-.36-.37-.5-.38-.13-.01-.28-.01-.43-.01s-.39.06-.59.28c-.2.22-.78.76-.78 1.85 0 1.09.8 2.15.91 2.3.11.15 1.57 2.39 3.8 3.35.53.23.94.37 1.26.47.53.17 1.01.15 1.39.09.42-.06 1.29-.53 1.47-1.04.18-.51.18-.95.13-1.04-.05-.09-.2-.15-.42-.26zM16.01 3C8.84 3 3 8.83 3 16c0 2.31.61 4.57 1.77 6.56L3 29l6.62-1.73A12.93 12.93 0 0 0 16.01 29C23.17 29 29 23.17 29 16S23.17 3 16.01 3zm0 23.78c-2.06 0-4.08-.55-5.84-1.6l-.42-.25-3.93 1.03 1.05-3.83-.27-.44A10.74 10.74 0 1 1 16.01 26.78z"/>
							</svg>
							WhatsApp
						</span>
					</a-button>
				</div>
			</div>
			<!-- / Sidebar Footer -->


	</a-layout-sider>
	<!-- / Main Sidebar -->

</template>

<script>
import axios from 'axios';
import { getToken, getUser } from '@/utils/auth';

	export default ({
		props: {
			// Sidebar collapsed status.
			sidebarCollapsed: {
				type: Boolean,
				default: false,
			},
			
			// Main sidebar color.
			sidebarColor: {
				type: String,
				default: "primary",
			},
			
			// Main sidebar theme : light, white, dark.
			sidebarTheme: {
				type: String,
				default: "light",
			},
		},
		data() {
			return {
				allowedScreenKeys: null, // null = aún no cargado
				registroPaths: ['/registro/alumnos', '/registro/maestros', '/registro/empleados', '/registro/cargos', '/registro/grados', '/registro/asignaturas'],
				usersAdminPaths: ['/notificaciones', '/sesiones', '/tables', '/roles', '/password-reset-requests'],
				comunicadosPaths: ['/comunicados'],
				planificacionPaths: ['/planificacion/estudios', '/planificacion/carga', '/planificacion/horarios', '/planificacion/asistencia', '/planificacion/diario-pedagogico'],
				configuracionesPaths: ['/configuraciones/institucion', '/configuraciones/logos', '/configuraciones/parametros-academicos', '/configuraciones/calendario', '/configuraciones/ministerio/matricula', '/configuraciones/ministerio/asistencia', '/configuraciones/ministerio/promocion', '/configuraciones/ministerio/rendimiento', '/configuraciones/ministerio/infraestructura', '/configuraciones/ministerio/personal'],
				ministerioPaths: ['/configuraciones/ministerio/matricula', '/configuraciones/ministerio/asistencia', '/configuraciones/ministerio/promocion', '/configuraciones/ministerio/rendimiento', '/configuraciones/ministerio/infraestructura', '/configuraciones/ministerio/personal'],
				reportesPaths: ['/reportes/asistencia', '/reportes/academico', '/reportes/ingreso', '/boletas/calificaciones'],
				bugReportPaths: ['/reportes/errores'],
				inventoryPaths: ['/inventario/control', '/inventario/ventas'],
				pagosPaths: ['/pagos/matriculas', '/pagos/planilla', '/pagos/contabilidad'],
				// Cambia este número al WhatsApp de soporte (solo dígitos, con código de país).
				// Ej: Honduras +504 -> 504XXXXXXXX
				supportWhatsAppNumber: '50400000000',
				supportWhatsAppMessage: 'Hola, necesito soporte técnico con OBS.',
			}
		},
		created() {
			this.fetchMyScreens();
		},
		methods: {
			authHeaders() {
				const token = getToken();
				return { Authorization: `Bearer ${token}` };
			},
			isAdmin() {
				const u = getUser();
				const roleName = String(u?.role || '').trim().toLowerCase();
				// Solo "Sistema" y "Super usuario" ignoran permisos.
				return (roleName === 'sistema' || roleName === 'super usuario');
			},
			fetchMyScreens() {
				// Admin: no hace falta cargar
				if (this.isAdmin()) {
					this.allowedScreenKeys = ['*'];
					return;
				}

				axios.get('http://localhost:8000/api/me/screens', { headers: this.authHeaders() })
					.then((res) => {
						this.allowedScreenKeys = (res.data?.screen_keys || []).map(String);
					})
					.catch((err) => {
						console.error('Error cargando pantallas del usuario:', err?.response?.data || err);
						// fallback: si falla, no ocultamos el menú para no dejarlo “vacío”
						this.allowedScreenKeys = null;
					});
			},
			canSee(path) {
				if (!path) return true;
				// Siempre mostrar Dashboard en el menú (igual que el guard del router)
				if (String(path) === '/dashboard') return true;
				if (this.allowedScreenKeys == null) return true; // no cargado: mostramos todo (guard de rutas bloqueará)
				if (this.allowedScreenKeys.includes('*')) return true;
				// Si el rol no tiene pantallas asignadas aún, dejamos visibles opciones básicas.
				if (Array.isArray(this.allowedScreenKeys) && this.allowedScreenKeys.length === 0) {
					return ['/dashboard', '/profile'].includes(String(path));
				}
				return this.allowedScreenKeys.includes(String(path));
			},
			canSeeAny(paths) {
				if (!Array.isArray(paths) || paths.length === 0) return false;
				return paths.some((p) => this.canSee(p));
			},
		},
		computed: {
			whatsappSupportUrl() {
				const num = String(this.supportWhatsAppNumber || '').replace(/[^\d]/g, '');
				const text = encodeURIComponent(String(this.supportWhatsAppMessage || ''));
				return `https://wa.me/${num}?text=${text}`;
			},
		},
	})

</script>

<style scoped>
.ant-layout-sider {
	/* evita que el indicador se corte */
	overflow: visible;
}

/* Remarcar item seleccionado (Ant Design Vue) */
::v-deep .ant-menu-item,
::v-deep .ant-menu-submenu-title {
	position: relative;
	border-radius: 12px;
	margin: 4px 8px;
}

/* Alinear íconos del menú (mismo tamaño y centrado) */
::v-deep .ant-menu-item .icon,
::v-deep .ant-menu-submenu-title .icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 32px;
	height: 32px;
	flex: 0 0 32px;
	line-height: 0;
}

/* Normaliza el tamaño de todos los SVG para que no “bailen” */
::v-deep .ant-menu-item .icon svg,
::v-deep .ant-menu-submenu-title .icon svg {
	display: block;
	width: 20px !important;
	height: 20px !important;
}

::v-deep .ant-menu-item-selected,
::v-deep .ant-menu-submenu-selected > .ant-menu-submenu-title {
	background: linear-gradient(90deg, rgba(124, 58, 237, 0.16), rgba(6, 182, 212, 0.10), rgba(34, 197, 94, 0.10)) !important;
	box-shadow: 0 10px 28px rgba(15, 23, 42, 0.10);
}

::v-deep .ant-menu-item-selected::before,
::v-deep .ant-menu-submenu-selected > .ant-menu-submenu-title::before {
	content: "";
	position: absolute;
	left: -8px;
	top: 8px;
	bottom: 8px;
	width: 4px;
	border-radius: 999px;
	background: linear-gradient(180deg, #7c3aed, #06b6d4, #22c55e);
	box-shadow: 0 0 0 2px rgba(124, 58, 237, 0.10);
}

/* --- Submenu children: selected state in WHITE background (only submenu items) --- */
::v-deep .ant-menu-sub .ant-menu-item-selected {
	background: #ffffff !important;
	box-shadow: 0 8px 18px rgba(15, 23, 42, 0.10);
}

/* No mostrar la barrita de color en items del submenu (solo en top-level) */
::v-deep .ant-menu-sub .ant-menu-item-selected::before {
	content: none !important;
}

/* Hover/active (solo submenu items) */
::v-deep .ant-menu-sub .ant-menu-item:hover,
::v-deep .ant-menu-sub .ant-menu-item-active {
	background: rgba(17, 24, 39, 0.04) !important;
}

::v-deep .ant-menu-item-selected .label,
::v-deep .ant-menu-submenu-selected > .ant-menu-submenu-title .label {
	font-weight: 800;
	color: #111827;
}

::v-deep .ant-menu-item-selected .icon svg path,
::v-deep .ant-menu-submenu-selected > .ant-menu-submenu-title .icon svg path {
	fill: #7c3aed;
}

/* Hover un poco más marcado */
::v-deep .ant-menu-item:hover,
::v-deep .ant-menu-submenu-title:hover {
	background: rgba(17, 24, 39, 0.04);
}

/* Asegura que títulos de submenú queden alineados como los links */
::v-deep .sidebar-submenu-title {
	display: flex;
	align-items: center;
	flex: 1;
	width: 100%;
}

/* Asegura alineación consistente entre items y submenús */
::v-deep .ant-menu-item > a {
	display: flex;
	align-items: center;
}

::v-deep .ant-menu-submenu-title {
	display: flex;
	align-items: center;
}

/* Sangría para items dentro de submenús (Registro/Usuarios) */
::v-deep .ant-menu-submenu-inline > .ant-menu-sub .ant-menu-item > a {
	padding-left: 44px !important;
}

::v-deep .ant-menu-submenu-inline > .ant-menu-sub .ant-menu-submenu-title {
	padding-left: 44px !important;
}

/* (se eliminó el logo image) */

/* Monograma OBS (reemplaza el logo) */
.sidebar-brand-monogram-wrap {
	display: inline-flex;
	justify-content: center;
	will-change: transform;
	animation: sign-in-logo-float 2.6s ease-in-out infinite;
}

.sidebar-brand-monogram {
	width: 92px;
	height: 92px;
	border-radius: 22px;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	gap: 4px;

	/* “Glass” + gradiente suave */
	background:
		linear-gradient(135deg, rgba(124, 58, 237, 0.18), rgba(6, 182, 212, 0.12), rgba(34, 197, 94, 0.12)),
		rgba(255, 255, 255, 0.72);
	border: 1px solid rgba(17, 24, 39, 0.08);
	box-shadow: 0 16px 44px rgba(15, 23, 42, 0.16);
	backdrop-filter: blur(10px);
	-webkit-backdrop-filter: blur(10px);

	/* Texto con gradiente */
	color: transparent;
	background-clip: padding-box;
}

.sidebar-brand-monogram__abbr {
	font-weight: 900;
	letter-spacing: -0.8px;
	font-size: 28px;
	line-height: 1;
	background: linear-gradient(90deg, #7c3aed, #06b6d4, #22c55e);
	-webkit-background-clip: text;
	background-clip: text;
	color: transparent;
	text-shadow: 0 10px 30px rgba(124, 58, 237, 0.16);
}

.sidebar-brand-monogram__sub {
	max-width: 82px;
	font-size: 9px;
	line-height: 1.1;
	font-weight: 800;
	letter-spacing: -0.15px;
	/* Más tenue para que se vea más “premium” sobre el fondo tipo glass */
	color: rgba(17, 24, 39, 0.56);
	text-align: center;
	white-space: normal;
}

.sidebar-brand-title {
	padding: 0;
	text-align: center;
	margin-top: 2px;
	margin-bottom: 6px;
	font-size: 16px;
	line-height: 1.15;
	font-weight: 800;
	letter-spacing: -0.3px;
	background: linear-gradient(90deg, #7c3aed, #06b6d4, #22c55e);
	-webkit-background-clip: text;
	background-clip: text;
	color: transparent;
	text-shadow: 0 10px 30px rgba(124, 58, 237, 0.16);
	animation: sign-in-title-pop 900ms ease both;
}

@keyframes sign-in-title-pop {
	0% { transform: translateY(-6px) scale(0.98); opacity: 0; filter: blur(2px); }
	60% { transform: translateY(0) scale(1.02); opacity: 1; filter: blur(0); }
	100% { transform: translateY(0) scale(1); opacity: 1; }
}

@keyframes sign-in-logo-float {
	0%, 100% { transform: translateY(0); }
	50% { transform: translateY(-6px); }
}

@media (prefers-reduced-motion: reduce) {
	.sidebar-brand-monogram-wrap {
		animation: none;
	}
	.sidebar-brand-title {
		animation: none;
	}
}

.wsp-btn-content {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	gap: 8px;
}

.wsp-icon {
	width: 18px;
	height: 18px;
}

/* --- Soporte: más pegado al menú y estilo outline --- */
.aside-footer {
	/* El SCSS base lo deja con 100px de separación; lo acercamos al menú */
	padding-top: 48px !important;
	padding-bottom: 16px !important;
}

.footer-box--support {
	/* “Outline” para el anuncio */
	background: transparent !important;
	border: 1px solid rgba(17, 24, 39, 0.14);
	box-shadow: none !important;
}

.footer-box--support h6 {
	/* Más contraste sobre fondo blanco */
	color: #000000 !important;
}

.footer-box--support p {
	/* Más contraste sobre fondo blanco */
	color: #000000 !important;
}

.footer-box--support .icon {
	/* Mantener el ícono destacado, pero sin fondo sólido del card */
	background: rgba(17, 24, 39, 0.06) !important;
	box-shadow: none !important;
}

.footer-box--support .support-outline-btn {
	/* Botón WhatsApp en verde */
	background: #25d366 !important;
	border-color: #1ebe5b !important;
	color: #ffffff !important;
	box-shadow: none !important;
}

.footer-box--support .support-outline-btn.ant-btn:hover,
.footer-box--support .support-outline-btn.ant-btn:focus {
	background: #1ebe5b !important;
	border-color: #17a34a !important;
	color: #ffffff !important;
}

.footer-box--support .support-outline-btn.ant-btn:active {
	background: #17a34a !important;
	border-color: #15803d !important;
	color: #ffffff !important;
}
</style>
