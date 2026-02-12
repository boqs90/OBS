<!--
	Pantalla de notificaciones (Ver todas)
 -->

<template>
	<div>
		<a-row :gutter="24">
			<a-col :span="24" class="mb-24">
				<a-card :bordered="false" class="shadow-sm">
					<div class="notifications-header">
						<div>
							<h4 class="mb-0">Notificaciones</h4>
							<p class="text-muted mb-0">Aquí verás todas tus notificaciones.</p>
						</div>

						<div class="notifications-actions">
							<a-button :loading="loading" @click="fetchNotifications">Actualizar</a-button>
							<a-button type="primary" :loading="markingAllRead" @click="markAllRead">Marcar todas como leídas</a-button>
						</div>
					</div>

					<a-divider class="my-16" />

					<a-list
						item-layout="horizontal"
						:data-source="notifications"
						:loading="loading"
						:locale="{ emptyText: 'No hay notificaciones.' }"
					>
						<a-list-item slot="renderItem" slot-scope="item">
							<a-list-item-meta>
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
								<template #title>
									<div class="notif-title">
										<span class="notif-dot" :class="{ 'notif-dot--unread': !item.read_at }" />
										<span>{{ item.title || 'Notificación' }}</span>
									</div>
								</template>

								<template #description>
									<div class="notif-desc">
										<div class="text-muted">{{ item.body || '' }}</div>
										<div class="notif-time text-muted">{{ formatDateTime(item.created_at) }}</div>
									</div>
								</template>
							</a-list-item-meta>

							<div class="notif-actions">
								<a-button size="small" :disabled="!!item.read_at" :loading="markingId === item.id" @click="markRead(item)">
									Marcar leída
								</a-button>
								<a-popconfirm
									title="¿Eliminar esta notificación?"
									ok-text="Eliminar"
									ok-type="danger"
									cancel-text="Cancelar"
									@confirm="deleteNotification(item)"
								>
									<a-button size="small" type="danger" ghost :loading="deletingId === item.id">
										Eliminar
									</a-button>
								</a-popconfirm>
							</div>
						</a-list-item>
					</a-list>
				</a-card>
			</a-col>
		</a-row>
	</div>
</template>

<script>
import axios from 'axios';
import { getToken } from '@/utils/auth';

export default ({
	data() {
		return {
			loading: false,
			markingAllRead: false,
			markingId: null,
			deletingId: null,
			notifications: [],
		};
	},
	mounted() {
		this.fetchNotifications();
	},
	methods: {
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
		fetchNotifications() {
			this.loading = true;
			axios.get('http://localhost:8000/api/notifications', {
				headers: this.authHeaders(),
				params: { limit: 100 },
			})
				.then((res) => {
					this.notifications = res?.data?.data || [];
				})
				.catch((err) => {
					console.error('Error cargando notificaciones:', err?.response?.data || err);
					this.notifications = [];
				})
				.finally(() => {
					this.loading = false;
				});
		},
		markAllRead() {
			this.markingAllRead = true;
			axios.post('http://localhost:8000/api/notifications/read-all', {}, {
				headers: this.authHeaders(),
			})
				.then(() => this.fetchNotifications())
				.catch((err) => console.error('Error marcando todas:', err?.response?.data || err))
				.finally(() => { this.markingAllRead = false; });
		},
		markRead(item) {
			if (!item?.id || item.read_at) return;
			this.markingId = item.id;
			axios.patch(`http://localhost:8000/api/notifications/${item.id}/read`, {}, {
				headers: this.authHeaders(),
			})
				.then(() => this.fetchNotifications())
				.catch((err) => console.error('Error marcando leída:', err?.response?.data || err))
				.finally(() => { this.markingId = null; });
		},
		deleteNotification(item) {
			if (!item?.id) return;
			this.deletingId = item.id;
			axios.delete(`http://localhost:8000/api/notifications/${item.id}`, {
				headers: this.authHeaders(),
			})
				.then(() => this.fetchNotifications())
				.catch((err) => console.error('Error eliminando:', err?.response?.data || err))
				.finally(() => { this.deletingId = null; });
		},
	},
});
</script>

<style scoped>
.notifications-header {
	display: flex;
	gap: 12px;
	align-items: center;
	justify-content: space-between;
	flex-wrap: wrap;
}

.notifications-actions {
	display: inline-flex;
	gap: 10px;
	flex-wrap: wrap;
}

.notif-title {
	display: inline-flex;
	align-items: center;
	gap: 8px;
}

.notif-dot {
	width: 10px;
	height: 10px;
	border-radius: 999px;
	background: rgba(17, 24, 39, 0.18);
}

.notif-dot--unread {
	background: linear-gradient(180deg, #7c3aed, #06b6d4, #22c55e);
	box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.10);
}

.notif-desc {
	display: grid;
	gap: 4px;
}

.notif-time {
	font-size: 12px;
}

.notif-actions {
	display: inline-flex;
	gap: 8px;
}
</style>

