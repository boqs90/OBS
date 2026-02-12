<template>
	
	<!-- Main Sidebar -->
	<component :is="navbarFixed ? 'a-affix' : 'div'" :offset-top="top">

		<!-- Layout Header -->
		<a-layout-header>
			<a-row type="flex" align="middle">

				<!-- Header Breadcrumbs & Title Column -->
				<a-col :span="24" :md="6" class="header-breadcrumb-col">

					<!-- Header Breadcrumbs -->
					<a-breadcrumb>
						<a-breadcrumb-item><span>Ruta</span></a-breadcrumb-item>
						<a-breadcrumb-item><span>{{ currentTitle }}</span></a-breadcrumb-item>
					</a-breadcrumb>
					<!-- / Header Breadcrumbs -->

				</a-col>
				<!-- / Header Breadcrumbs & Title Column -->

				<!-- Header Control Column -->
				<a-col :span="24" :md="18" class="header-control">

					<!-- Header Control Buttons -->
					<a-dropdown :trigger="['click']" overlayClassName="header-notifications-dropdown" :getPopupContainer="() => wrapper" @visibleChange="onNotificationsDropdown">
						<a-badge :count="unreadCount">
							<a class="ant-dropdown-link" href="#" @click="preventDefault">
								<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M10 2C6.68632 2 4.00003 4.68629 4.00003 8V11.5858L3.29292 12.2929C3.00692 12.5789 2.92137 13.009 3.07615 13.3827C3.23093 13.7564 3.59557 14 4.00003 14H16C16.4045 14 16.7691 13.7564 16.9239 13.3827C17.0787 13.009 16.9931 12.5789 16.7071 12.2929L16 11.5858V8C16 4.68629 13.3137 2 10 2Z" fill="#111827"/>
									<path d="M10 18C8.34315 18 7 16.6569 7 15H13C13 16.6569 11.6569 18 10 18Z" fill="#111827"/>
								</svg>
							</a>
						</a-badge>
						
						<div slot="overlay" class="header-notifications-popover">
							<a-list item-layout="horizontal" class="header-notifications-list" :data-source="dropdownNotifications" :loading="notificationsLoading">
								<a-list-item
									slot="renderItem"
									slot-scope="item"
									:class="{ 'header-notifications-item--read': !!item.read_at }"
								>
									<a-list-item-meta>
										<template #description>
											<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
												<path fill-rule="evenodd" clip-rule="evenodd" d="M10 18C14.4183 18 18 14.4183 18 10C18 5.58172 14.4183 2 10 2C5.58172 2 2 5.58172 2 10C2 14.4183 5.58172 18 10 18ZM11 6C11 5.44772 10.5523 5 10 5C9.44772 5 9 5.44772 9 6V10C9 10.2652 9.10536 10.5196 9.29289 10.7071L12.1213 13.5355C12.5118 13.9261 13.145 13.9261 13.5355 13.5355C13.9261 13.145 13.9261 12.5118 13.5355 12.1213L11 9.58579V6Z" fill="#111827"/>
											</svg>
											<span>{{ formatDateTime(item.created_at) }}</span>
										</template>
										<a slot="title" href="#" @click="preventDefault">{{ item.title || 'Notificación' }}</a>
										<a-avatar
											shape="square"
											slot="avatar"
											:style="notificationAvatarStyle(item)"
										>
											<!-- Icono según pantalla (kind) -->
											<svg v-if="notificationIconKey(item) === 'roles'" width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
												<path fill-rule="evenodd" clip-rule="evenodd" d="M10 2l6 3v5c0 4.418-2.686 7.333-6 8-3.314-.667-6-3.582-6-8V5l6-3zm0 3.2L6 6.9V10c0 3.05 1.74 5.1 4 5.7 2.26-.6 4-2.65 4-5.7V6.9l-4-1.7z" fill="currentColor"/>
											</svg>
											<svg v-else-if="notificationIconKey(item) === 'users'" width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
												<path fill-rule="evenodd" clip-rule="evenodd" d="M10 10a4 4 0 1 0-0.001-8.001A4 4 0 0 0 10 10zm-7 8a7 7 0 0 1 14 0H3z" fill="currentColor"/>
											</svg>
											<svg v-else-if="notificationIconKey(item) === 'cargos'" width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
												<path fill-rule="evenodd" clip-rule="evenodd" d="M7 4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1h3a2 2 0 0 1 2 2v3H0V7a2 2 0 0 1 2-2h3V4zm2 1h2V4H9v1zM0 12h20v4a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-4z" fill="currentColor"/>
											</svg>
											<svg v-else-if="notificationIconKey(item) === 'grados'" width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
												<path d="M10 2L1 6l9 4 9-4-9-4z" fill="currentColor"/>
												<path d="M3 9v5c0 2 3 4 7 4s7-2 7-4V9l-7 3-7-3z" fill="currentColor" opacity="0.9"/>
											</svg>
											<svg v-else-if="notificationIconKey(item) === 'maestros'" width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
												<path fill-rule="evenodd" clip-rule="evenodd" d="M2 4h16v10H2V4zm2 2v6h12V6H4z" fill="currentColor"/>
												<path d="M6 16h8v2H6v-2z" fill="currentColor" opacity="0.9"/>
											</svg>
											<svg v-else-if="notificationIconKey(item) === 'empleados'" width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
												<path fill-rule="evenodd" clip-rule="evenodd" d="M7 2h6a2 2 0 0 1 2 2v2h2a1 1 0 0 1 1 1v9a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V7a1 1 0 0 1 1-1h2V4a2 2 0 0 1 2-2zm0 4h6V4H7v2z" fill="currentColor"/>
											</svg>
											<svg v-else-if="notificationIconKey(item) === 'reportes'" width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
												<path fill-rule="evenodd" clip-rule="evenodd" d="M4 2h12a2 2 0 0 1 2 2v14l-4-2-4 2-4-2-4 2V4a2 2 0 0 1 2-2zm2 5h8v2H6V7zm0 4h8v2H6v-2z" fill="currentColor"/>
											</svg>
											<svg v-else-if="notificationIconKey(item) === 'seguridad'" width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
												<path fill-rule="evenodd" clip-rule="evenodd" d="M10 2a5 5 0 0 0-5 5v3H4a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-1V7a5 5 0 0 0-5-5zm3 8V7a3 3 0 0 0-6 0v3h6z" fill="currentColor"/>
											</svg>
											<svg v-else width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
												<path d="M10 2C6.68632 2 4.00003 4.68629 4.00003 8V11.5858L3.29292 12.2929C3.00692 12.5789 2.92137 13.009 3.07615 13.3827C3.23093 13.7564 3.59557 14 4.00003 14H16C16.4045 14 16.7691 13.7564 16.9239 13.3827C17.0787 13.009 16.9931 12.5789 16.7071 12.2929L16 11.5858V8C16 4.68629 13.3137 2 10 2Z" fill="currentColor"/>
											</svg>
										</a-avatar>
									</a-list-item-meta>
								</a-list-item>
							</a-list>

							<div class="header-notifications-footer">
								<a-button class="header-notifications-footer__btn" type="link" block @click="goToAllNotifications">
									Ver todas
								</a-button>
							</div>
						</div>
					</a-dropdown>
					<a-button type="link" ref="secondarySidebarTriggerBtn" @click="$emit('toggleSettingsDrawer', true)">
						<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M11.4892 3.17094C11.1102 1.60969 8.8898 1.60969 8.51078 3.17094C8.26594 4.17949 7.11045 4.65811 6.22416 4.11809C4.85218 3.28212 3.28212 4.85218 4.11809 6.22416C4.65811 7.11045 4.17949 8.26593 3.17094 8.51078C1.60969 8.8898 1.60969 11.1102 3.17094 11.4892C4.17949 11.7341 4.65811 12.8896 4.11809 13.7758C3.28212 15.1478 4.85218 16.7179 6.22417 15.8819C7.11045 15.3419 8.26594 15.8205 8.51078 16.8291C8.8898 18.3903 11.1102 18.3903 11.4892 16.8291C11.7341 15.8205 12.8896 15.3419 13.7758 15.8819C15.1478 16.7179 16.7179 15.1478 15.8819 13.7758C15.3419 12.8896 15.8205 11.7341 16.8291 11.4892C18.3903 11.1102 18.3903 8.8898 16.8291 8.51078C15.8205 8.26593 15.3419 7.11045 15.8819 6.22416C16.7179 4.85218 15.1478 3.28212 13.7758 4.11809C12.8896 4.65811 11.7341 4.17949 11.4892 3.17094ZM10 13C11.6569 13 13 11.6569 13 10C13 8.34315 11.6569 7 10 7C8.34315 7 7 8.34315 7 10C7 11.6569 8.34315 13 10 13Z" fill="#111827"/>
						</svg>
					</a-button>
					<a-button type="link" class="sidebar-toggler" @click="$emit('toggleSidebar', ! sidebarCollapsed) , resizeEventHandler()">
						<svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M16 132h416c8.837 0 16-7.163 16-16V76c0-8.837-7.163-16-16-16H16C7.163 60 0 67.163 0 76v40c0 8.837 7.163 16 16 16zm0 160h416c8.837 0 16-7.163 16-16v-40c0-8.837-7.163-16-16-16H16c-8.837 0-16 7.163-16 16v40c0 8.837 7.163 16 16 16zm0 160h416c8.837 0 16-7.163 16-16v-40c0-8.837-7.163-16-16-16H16c-8.837 0-16 7.163-16 16v40c0 8.837 7.163 16 16 16z"/></svg>
					</a-button>

					<a-dropdown :trigger="['click']" overlayClassName="header-user-dropdown" :getPopupContainer="() => wrapper">
						<a class="header-user-trigger" href="#" @click="preventDefault">
							<a-avatar class="header-user-avatar" :style="{ backgroundColor: '#7c3aed' }">
								{{ userInitials }}
							</a-avatar>
						</a>

						<a-menu slot="overlay" class="header-user-menu">
							<a-menu-item disabled class="header-user-menu__me">
								<div class="header-user-menu__name">{{ (currentUser && currentUser.name) ? currentUser.name : 'Usuario' }}</div>
								<div class="header-user-menu__email">{{ (currentUser && currentUser.email) ? currentUser.email : '' }}</div>
							</a-menu-item>
							<a-menu-divider />
							<a-menu-item @click="goProfile">Mi perfil</a-menu-item>
							<a-menu-item @click="goToAllNotifications">Notificaciones</a-menu-item>
							<a-menu-divider />
							<a-menu-item @click="confirmLogout">Cerrar sesión</a-menu-item>
						</a-menu>
					</a-dropdown>

					<a-button
						class="btn-logout"
						type="danger"
						shape="round"
						@click="confirmLogout"
					>
						<svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg" style="margin-right: 8px;">
							<path d="M10 2V10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
							<circle cx="10" cy="14" r="4" stroke="currentColor" stroke-width="2"/>
						</svg>
						Cerrar sesión
					</a-button>
					<!-- / Header Control Buttons -->

					<!-- Header Search Input -->
					<a-input-search class="header-search" :class="searchLoading ? 'loading' : ''" placeholder="Busqueda" @search="onSearch" :loading='searchLoading' :maxLength="200">
						<svg slot="prefix" width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M8 4C5.79086 4 4 5.79086 4 8C4 10.2091 5.79086 12 8 12C10.2091 12 12 10.2091 12 8C12 5.79086 10.2091 4 8 4ZM2 8C2 4.68629 4.68629 2 8 2C11.3137 2 14 4.68629 14 8C14 9.29583 13.5892 10.4957 12.8907 11.4765L17.7071 16.2929C18.0976 16.6834 18.0976 17.3166 17.7071 17.7071C17.3166 18.0976 16.6834 18.0976 16.2929 17.7071L11.4765 12.8907C10.4957 13.5892 9.29583 14 8 14C4.68629 14 2 11.3137 2 8Z" fill="#111827"/>
						</svg>
					</a-input-search>
					<!-- / Header Search Input -->

				</a-col>
				<!-- / Header Control Column -->

			</a-row>
		</a-layout-header>
		<!--  /Layout Header -->

	</component>
	<!-- / Main Sidebar -->

</template>

<script>
import axios from 'axios';
import { Modal } from 'ant-design-vue';
import { logout } from '@/utils/auth';
import { isLoggedIn } from '@/utils/auth';
import { getToken } from '@/utils/auth';
import { getUser } from '@/utils/auth';

	export default ({
		 components: {
		},
		props: {
			// Header fixed status.
			navbarFixed: {
				type: Boolean,
				default: false,
			},

			// Sidebar collapsed status.
			sidebarCollapsed: {
				type: Boolean,
				default: false,
			},
		},
		data() {
			return {
				// Fixed header/sidebar-footer ( Affix component ) top offset.
				top: 0,

				// Search input loading status.
				searchLoading: false,

				// The wrapper element to attach dropdowns to.
				wrapper: document.body,

				notificationsLoading: false,
				notifications: [],
				unreadCount: 0,
			}
		},
		computed: {
			currentTitle() {
				return this.$route.meta?.title || this.$route.name || this.$route.path;
			},
			dropdownNotifications() {
				return (this.notifications || []).slice(0, 4);
			},
			currentUser() {
				return getUser();
			},
			userInitials() {
				const name = String(this.currentUser?.name || '').trim();
				if (!name) return 'U';
				const parts = name.split(/\s+/).filter(Boolean);
				const first = parts[0]?.[0] || '';
				const last = (parts.length > 1 ? parts[parts.length - 1]?.[0] : '') || '';
				return (first + last).toUpperCase();
			},
		},
		methods: {
			formatRouteLabel(rawPath) {
				const path = String(rawPath || '').trim();
				if (!path) return '';

				// Intentar resolver contra el router para tomar meta.title / name
				try {
					const resolved = this.$router && this.$router.resolve ? this.$router.resolve(path) : null;
					const r = resolved && resolved.route ? resolved.route : null;
					const metaTitle = r && r.meta && r.meta.title ? String(r.meta.title) : '';
					const nameTitle = r && r.name ? String(r.name) : '';
					const base = (metaTitle || nameTitle || '').trim();
					if (base) return base.charAt(0).toUpperCase() + base.slice(1);
				} catch (e) {
					// ignore
				}

				// Fallback: quitar "/" y convertir a texto legible
				const cleaned = path
					.replace(/^\//, '')        // quita primer "/"
					.replace(/\//g, ' ')       // separa segmentos
					.replace(/[-_]+/g, ' ')    // separa por guion/underscore
					.replace(/\s+/g, ' ')      // colapsa espacios
					.trim();
				if (!cleaned) return '';
				return cleaned.charAt(0).toUpperCase() + cleaned.slice(1);
			},
			handleDeniedRoute() {
				const denied = this.$route?.query?.denied;
				if (!denied) return;
				if (this.$message && typeof this.$message.warning === 'function') {
					const label = this.formatRouteLabel(denied) || String(denied).replace(/^\//, '');
					this.$message.warning(`No tienes permiso para acceder a: ${label}`);
				}
				// limpiar query param para evitar repetir el toast
				const q = { ...(this.$route?.query || {}) };
				delete q.denied;
				this.$router.replace({ path: this.$route.path, query: q }).catch(() => {});
			},
			preventDefault(e) {
				if (e && typeof e.preventDefault === 'function') e.preventDefault();
			},
			onNotificationsDropdown(visible) {
				if (visible) this.fetchNotifications();
			},
			authHeaders() {
				const token = getToken();
				return { Authorization: `Bearer ${token}` };
			},
			formatDateTime(value) {
				if (!value) return '';
				const d = new Date(value);
				if (Number.isNaN(d.getTime())) return String(value);
				const dd = String(d.getDate()).padStart(2, '0');
				const mm = String(d.getMonth() + 1).padStart(2, '0');
				const yyyy = d.getFullYear();
				return `${dd}/${mm}/${yyyy}`;
			},
			fetchNotifications() {
				this.notificationsLoading = true;
				axios.get('http://localhost:8000/api/notifications', {
					headers: this.authHeaders(),
					params: { limit: 20 },
				})
					.then((res) => {
						this.notifications = res?.data?.data || [];
						this.unreadCount = res?.data?.unread_count ?? 0;
					})
					.catch((err) => {
						console.error('Error cargando notificaciones:', err?.response?.data || err);
						this.notifications = [];
						this.unreadCount = 0;
					})
					.finally(() => {
						this.notificationsLoading = false;
					});
			},
			goToAllNotifications() {
				this.$router.push('/notificaciones');
			},
			goProfile() {
				this.$router.push('/profile');
			},
			notificationKind(item) {
				return String(item?.kind || '').trim().toLowerCase();
			},
			notificationAction(kind) {
				const k = String(kind || '').toLowerCase();
				if (k.endsWith('_created') || k.includes('created')) return 'create';
				if (k.endsWith('_updated') || k.includes('updated')) return 'edit';
				if (k.endsWith('_deleted') || k.includes('deleted')) return 'delete';
				if (k.includes('deactivated')) return 'deactivate';
				if (k.includes('activated')) return 'activate';
				if (k.includes('password')) return 'security';
				if (k.includes('expiry') || k.includes('expir')) return 'warning';
				return 'info';
			},
			notificationAccentColor(item) {
				const action = this.notificationAction(this.notificationKind(item));
				if (action === 'create') return '#22c55e';
				if (action === 'edit') return '#7c3aed';
				if (action === 'delete') return '#ef4444';
				if (action === 'activate') return '#22c55e';
				if (action === 'deactivate') return '#f59e0b';
				if (action === 'security') return '#0ea5e9';
				if (action === 'warning') return '#f59e0b';
				return '#64748b';
			},
			hexToRgba(hex, alpha) {
				const h = String(hex || '').replace('#', '').trim();
				if (h.length !== 6) return `rgba(99, 102, 241, ${alpha})`;
				const r = parseInt(h.slice(0, 2), 16);
				const g = parseInt(h.slice(2, 4), 16);
				const b = parseInt(h.slice(4, 6), 16);
				return `rgba(${r}, ${g}, ${b}, ${alpha})`;
			},
			notificationAvatarStyle(item) {
				const color = this.notificationAccentColor(item);
				const read = !!item?.read_at;
				return {
					background: read ? this.hexToRgba(color, 0.12) : color,
					color: read ? color : '#ffffff',
					borderRadius: '10px',
					border: read ? `1px solid ${this.hexToRgba(color, 0.22)}` : '1px solid transparent',
				};
			},
			notificationIconKey(item) {
				const kind = this.notificationKind(item);
				// pantalla según prefijo del kind
				if (kind.startsWith('role_')) return 'roles';
				if (kind.startsWith('user_')) return 'users';
				if (kind.startsWith('position_')) return 'cargos';
				if (kind.startsWith('grade_')) return 'grados';
				if (kind.startsWith('teacher_')) return 'maestros';
				if (kind.startsWith('employee_')) return 'empleados';
				if (kind.startsWith('incidence_') || kind.startsWith('report_')) return 'reportes';
				if (kind.startsWith('password_')) return 'seguridad';
				if (kind === 'welcome') return 'users';
				return 'general';
			},
			resizeEventHandler(){
				this.top = this.top ? 0 : -0.01 ;
				// To refresh the header if the window size changes.
				// Reason for the negative value is that it doesn't activate the affix unless
				// scroller is anywhere but the top of the page.
			},
			onSearch(value){
			},
			confirmLogout() {
				Modal.confirm({
					title: '¿Cerrar sesión?',
					content: 'Se cerrará tu sesión actual.',
					okText: 'Cerrar sesión',
					okType: 'danger',
					cancelText: 'Cancelar',
					onOk: () => this.handleLogout(),
				});
			},
			 handleLogout: async function() {
					try {
						const token = localStorage.getItem('authToken') || sessionStorage.getItem('authToken');
						await axios.post('http://localhost:8000/api/logout', {}, {
							headers: {
								Authorization: `Bearer ${token}`
							}
						});

						// Borra token local
						logout();    	
						// Redirige al login
						this.$router.replace('/sign-in'); // reemplaza la ruta actual

					} catch (error) {
						console.error('Error al cerrar sesión:', error);
						// Opcional: mostrar toast de error
					}
				}
		},
		mounted: function(){
			// Set the wrapper to the proper element, layout wrapper.
			this.wrapper = document.getElementById('layout-dashboard') ;
			this.fetchNotifications();
			this.handleDeniedRoute();
		},
		created() {
			// Registering window resize event listener to fix affix elements size
			// error while resizing.
			window.addEventListener("resize", this.resizeEventHandler);
			 if (!isLoggedIn()) {
					this.$router.replace('/sign-in');
				}
		},
		watch: {
			$route() {
				this.handleDeniedRoute();
			},
		},
		destroyed() {
			// Removing window resize event listener.
			window.removeEventListener("resize", this.resizeEventHandler);
		},
	})

</script>

<style scoped>
.header-notifications-popover {
	min-width: 320px;
}

.header-notifications-item--read {
	background: rgba(17, 24, 39, 0.02);
}

/* Scoped CSS needs deep selectors to style Ant components */
.header-notifications-item--read :deep(.ant-list-item-meta-title a) {
	color: rgba(17, 24, 39, 0.55) !important;
}

.header-notifications-item--read :deep(.ant-list-item-meta-description) {
	color: rgba(17, 24, 39, 0.45) !important;
}

.header-notifications-item--read :deep(.ant-list-item-meta-description svg path) {
	fill: rgba(17, 24, 39, 0.45) !important;
}

.header-notifications-footer {
	padding: 8px 12px 4px;
	border-top: 1px solid rgba(17, 24, 39, 0.06);
	background: #ffffff;
}

.header-notifications-footer__btn.ant-btn {
	background: #ffffff;
	border: 1px solid rgba(17, 24, 39, 0.10);
	border-radius: 10px;
	box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
	color: rgba(17, 24, 39, 0.88);
	font-weight: 800;
}

.header-notifications-footer__btn.ant-btn:hover,
.header-notifications-footer__btn.ant-btn:focus {
	background: #f9fafb;
	border-color: rgba(17, 24, 39, 0.14);
	color: rgba(17, 24, 39, 0.92);
}

.header-notifications-footer__btn.ant-btn:active {
	background: #f3f4f6;
	border-color: rgba(17, 24, 39, 0.18);
}

.btn-logout {
	display: inline-flex;
	align-items: center;
}

.header-user-trigger {
	display: inline-flex;
	align-items: center;
	margin: 0 8px;
}

.header-user-avatar {
	box-shadow: 0 10px 26px rgba(15, 23, 42, 0.12);
	font-weight: 800;
}

.header-user-menu__me {
	cursor: default;
}

.header-user-menu__name {
	font-weight: 800;
	color: #111827;
	line-height: 1.15;
}

.header-user-menu__email {
	font-size: 12px;
	color: rgba(17, 24, 39, 0.6);
}

.header-breadcrumb-col {
	display: flex;
	align-items: center;
}

.header-breadcrumb-col .ant-breadcrumb {
	margin: 0;
}

/* En móvil: mostrar solo el logo */
@media (max-width: 767.98px) {
	.ant-breadcrumb,
	.ant-page-header-heading {
		display: none;
	}
}
</style>
