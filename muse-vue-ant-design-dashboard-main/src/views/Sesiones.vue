<!-- Sesiones / Auditoría (login/logout + acciones de usuarios) -->

<template>
	<div>
		<a-row :gutter="24" type="flex">
			<a-col :span="24" class="mb-24">
				<a-card :bordered="false" class="header-solid h-full" :bodyStyle="{ padding: 0 }">
					<template #title>
						<a-row type="flex" align="middle" class="table-header-row">
							<a-col :span="24" :md="12">
								<div class="table-header-left">
									<h5 class="font-semibold m-0">Sesiones</h5>
								</div>
							</a-col>
							<a-col :span="24" :md="12" class="table-header-right">
								<a-auto-complete
									v-model="searchText"
									:data-source="userSuggestionOptions"
									@select="onSelectUserSuggestion"
									@search="onSearchTextChanged"
									class="header-search"
								>
									<a-input-search
										:maxLength="200"
										allowClear
										placeholder="Buscar..."
										@search="fetchLogs"
										@change="onSearchInputChange"
									/>
								</a-auto-complete>
								<a-button type="primary" ghost @click="fetchLogs" :loading="loading">
									Actualizar
								</a-button>
							</a-col>
						</a-row>
					</template>

					<a-table
						:columns="columns"
						:data-source="tableData"
						:loading="loading"
						:pagination="{ pageSize: 15, showSizeChanger: false, hideOnSinglePage: true }"
						:locale="{ emptyText: 'No hay registros.' }"
						:scroll="{ x: 980 }"
						rowKey="id"
					>
						<template slot="action" slot-scope="value">
							<a-tag :class="tagClass(value)">{{ actionLabel(value) }}</a-tag>
						</template>
						<template slot="user" slot-scope="user">
							<div class="sessions-user-cell">
								<div class="sessions-user-name">{{ (user && user.name) ? user.name : '—' }}</div>
								<div class="sessions-user-email">{{ (user && user.email) ? user.email : '' }}</div>
							</div>
						</template>
						<template slot="created_at" slot-scope="value">
							<span>{{ formatDateTime(value) }}</span>
						</template>
					</a-table>
				</a-card>
			</a-col>
		</a-row>
	</div>
</template>

<script>
import axios from 'axios';
import { getToken } from '@/utils/auth';

export default ({
	name: 'Sesiones',
	data() {
		return {
			loading: false,
			searchText: '',
			selectedUserId: null,
			users: [],
			meta: null,
			rows: [],
			columns: [
				{ title: 'Usuario', dataIndex: 'actor_user', scopedSlots: { customRender: 'user' }, width: 260, className: 'sessions-user-col' },
				{ title: 'Acción', dataIndex: 'action', scopedSlots: { customRender: 'action' }, width: 180 },
				{ title: 'Detalle', dataIndex: 'description', width: 280 },
				{ title: 'IP', dataIndex: 'ip_address', width: 140 },
				{ title: 'Fecha', dataIndex: 'created_at', scopedSlots: { customRender: 'created_at' }, width: 180, align: 'right' },
			],
		};
	},
	computed: {
		tableData() {
			// La API ya filtra por rol/tipo. Aquí solo normalizamos estructura.
			return (this.rows || []).map((r) => ({
				...r,
				actor_user: r.actor_user || r.actorUser || r.actor_user, // compat
			}));
		},
		canViewAll() {
			return !!(this.meta && this.meta.can_view_all);
		},
		userSuggestionOptions() {
			// Solo tiene sentido sugerir usuarios si el rol puede ver todo
			if (!this.canViewAll) return [];

			const q = String(this.searchText || '').trim().toLowerCase();
			if (!q) return [];

			const list = Array.isArray(this.users) ? this.users : [];
			const filtered = list.filter((u) => {
				const name = String(u?.name || '').toLowerCase();
				const email = String(u?.email || '').toLowerCase();
				return (name.includes(q) || email.includes(q));
			}).slice(0, 8);

			// Ant Design Vue AutoComplete dataSource: strings o VNodes.
			// Usamos strings con formato "Nombre <correo>||id" para poder recuperar id en select.
			return filtered.map((u) => `${u.name} <${u.email}>||${u.id}`);
		},
	},
	mounted() {
		this.fetchLogs();
	},
	methods: {
		apiHeaders() {
			const token = getToken();
			return { Authorization: `Bearer ${token}` };
		},
		fetchLogs() {
			this.loading = true;
			axios.get('http://localhost:8000/api/audit-logs', {
				headers: this.apiHeaders(),
				params: {
					q: this.searchText || '',
					user_id: (this.canViewAll && this.selectedUserId) ? this.selectedUserId : undefined,
					per_page: 200,
				},
			})
				.then((res) => {
					this.rows = (res && res.data && res.data.data) ? res.data.data : [];
					this.meta = (res && res.data && res.data.meta) ? res.data.meta : null;

					// Cargar usuarios para sugerencias solo si el backend indica que puede ver todo
					if (this.meta && this.meta.can_view_all) {
						this.fetchUsersIfNeeded();
					}
				})
				.catch((err) => {
					console.error('Error cargando sesiones:', (err && err.response && err.response.data) ? err.response.data : err);
					this.rows = [];
					this.meta = null;
				})
				.finally(() => {
					this.loading = false;
				});
		},
		fetchUsersIfNeeded() {
			if (this.users && this.users.length) return;
			const token = getToken();
			if (!token) return;

			axios.get('http://localhost:8000/api/user', { headers: { Authorization: `Bearer ${token}` } })
				.then((res) => {
					const list = Array.isArray(res.data) ? res.data : [];
					// Filtrar eliminados y mapear mínimo necesario
					this.users = list
						.filter((u) => !u?.deleted_at)
						.map((u) => ({ id: u.id, name: u.name || 'Usuario', email: u.email || '' }));
				})
				.catch((err) => {
					console.error('Error cargando usuarios para sugerencias:', err.response?.data || err);
					this.users = [];
				});
		},
		onSearchTextChanged(value) {
			// Cuando escribe manualmente, si cambia el texto “rompemos” la selección de usuario
			// (evita que quede un user_id pegado con otro texto).
			if (!value) {
				this.selectedUserId = null;
			}
		},
		onSearchInputChange(e) {
			// Si borra el input, limpiar selección
			const v = e && e.target ? e.target.value : '';
			if (!v) this.selectedUserId = null;
		},
		onSelectUserSuggestion(value) {
			// value viene como "Nombre <correo>||id"
			const parts = String(value || '').split('||');
			const label = (parts[0] || '').trim();
			const id = parts[1] ? parseInt(parts[1], 10) : NaN;
			if (!Number.isNaN(id)) {
				this.selectedUserId = id;
				this.searchText = label; // mostrar bonito (sin el ||id)
				this.fetchLogs();
			}
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
		actionLabel(action) {
			const a = String(action || '').trim();
			const map = {
				login: 'Inicio de sesión',
				logout: 'Cierre de sesión',
				user_created: 'Creó usuario',
				user_updated: 'Editó usuario',
				user_deleted: 'Eliminó usuario',
				user_activated: 'Activó usuario',
				user_deactivated: 'Desactivó usuario',
				enrollment_created: 'Registró matrícula',
				enrollment_updated: 'Editó matrícula',
				enrollment_cancelled: 'Anuló matrícula',
			};
			return map[a] || a || '—';
		},
		tagClass(action) {
			const a = String(action || '').trim();
			if (a === 'login') return 'ant-tag-success';
			if (a === 'logout') return 'ant-tag-muted';
			if (a === 'user_deleted') return 'ant-tag-danger';
			if (a === 'user_created') return 'ant-tag-primary';
			if (a === 'user_updated') return 'ant-tag-warning';
			if (a === 'enrollment_created') return 'ant-tag-primary';
			if (a === 'enrollment_updated') return 'ant-tag-warning';
			if (a === 'enrollment_cancelled') return 'ant-tag-danger';
			return 'ant-tag-primary';
		},
	},
});
</script>

<style scoped>
.header-search {
	width: 240px;
	min-width: 180px;
}

/* El AutoComplete envuelve el input, aseguramos que mantenga el ancho */
::v-deep .header-search .ant-select-selection__rendered {
	margin-left: 0;
}

/* Remarcar columna Usuario */
::v-deep th.sessions-user-col {
	background: rgba(124, 58, 237, 0.06);
}

::v-deep td.sessions-user-col {
	background: rgba(124, 58, 237, 0.03);
	border-left: 3px solid rgba(124, 58, 237, 0.35);
}

.sessions-user-cell {
	min-width: 0;
}

.sessions-user-name {
	font-weight: 800;
	color: #111827;
	line-height: 1.15;
}

.sessions-user-email {
	font-size: 12px;
	color: rgba(17, 24, 39, 0.55);
	line-height: 1.1;
	margin-top: 2px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}
</style>

