<template>
	<div>
		<a-card :bordered="false" class="header-solid h-full" :bodyStyle="{ padding: 0 }">
			<template #title>
				<a-row type="flex" align="middle" class="table-header-row">
					<a-col :span="24" :md="10">
						<div class="table-header-left">
							<h5 class="font-semibold m-0">Historial de comportamiento</h5>
						</div>
					</a-col>
					<a-col :span="24" :md="14" class="table-header-right">
						<a-radio-group v-model="entityType" size="small" buttonStyle="solid" @change="onEntityTypeChange">
							<a-radio-button value="student">Estudiante</a-radio-button>
							<a-radio-button value="teacher">Docente</a-radio-button>
						</a-radio-group>

						<a-select
							v-model="selectedId"
							show-search
							allowClear
							:filter-option="filterOption"
							:placeholder="entityType === 'student' ? 'Selecciona un estudiante' : 'Selecciona un docente'"
							class="entity-select"
							@change="onSelectedChange"
						>
							<a-select-option v-for="opt in entityOptions" :key="opt.id" :value="opt.id">
								{{ opt.label }}
							</a-select-option>
						</a-select>

						<a-button type="primary" ghost @click="refresh" :loading="loadingAny" :disabled="!selectedId">
							Actualizar
						</a-button>
					</a-col>
				</a-row>
			</template>

			<div class="profile-body">
				<a-alert
					v-if="!selectedId"
					type="info"
					show-icon
					message="Selecciona un estudiante o docente para ver su historial."
					style="margin: 12px 16px;"
				/>

				<div v-else>
					<a-card :bordered="false" class="header-solid mb-16" :bodyStyle="{ padding: '12px 16px' }">
						<a-row type="flex" :gutter="16" align="middle">
							<a-col :span="24" :md="12">
								<div class="summary-left">
									<a-avatar :size="56" shape="square" :src="selectedAvatar" />
									<div class="summary-info">
										<div class="summary-title">{{ selectedTitle }}</div>
										<div class="summary-sub">{{ selectedSubtitle }}</div>
									</div>
								</div>
							</a-col>
							<a-col :span="24" :md="12" class="summary-right">
								<a-tag class="tag-status ant-tag-primary">{{ entityType === 'student' ? 'Estudiante' : 'Docente' }}</a-tag>
								<a-tag v-if="selectedStatus" class="tag-status" :class="selectedStatus === 'Activo' ? 'ant-tag-success' : 'ant-tag-muted'">
									{{ selectedStatus }}
								</a-tag>
							</a-col>
						</a-row>
					</a-card>

					<a-tabs v-model="activeTab">
						<a-tab-pane key="history" tab="Historial">
							<a-card v-if="entityType === 'student'" :bordered="false" class="header-solid">
								<template #title>
									<h6 class="font-semibold m-0">Matrículas</h6>
									<p class="m-0">Histórico de matrícula del estudiante.</p>
								</template>
								<a-table
									rowKey="id"
									:columns="enrollmentColumns"
									:data-source="studentEnrollments"
									:loading="loadingEnrollments"
									:pagination="{ pageSize: 10, showSizeChanger: false, hideOnSinglePage: true }"
									:locale="{ emptyText: 'No hay matrículas.' }"
									:scroll="{ x: 900 }"
								>
									<template slot="enrolled_at" slot-scope="value">
										<span class="text-muted">{{ formatDate(value) }}</span>
									</template>
								</a-table>
							</a-card>

							<a-card v-else :bordered="false" class="header-solid">
								<template #title>
									<h6 class="font-semibold m-0">Incidencias</h6>
									<p class="m-0">Histórico de incidencias asociadas al docente.</p>
								</template>
								<a-table
									rowKey="id"
									:columns="incidenceColumns"
									:data-source="incidences"
									:loading="loadingIncidences"
									:pagination="{ pageSize: 10, showSizeChanger: false, hideOnSinglePage: true }"
									:locale="{ emptyText: 'No hay incidencias.' }"
									:scroll="{ x: 980 }"
								>
									<template slot="occurred_at" slot-scope="value">
										<span class="text-muted">{{ formatDate(value) }}</span>
									</template>
								</a-table>
							</a-card>
						</a-tab-pane>

						<a-tab-pane key="audit" tab="Actividad registrada (auditoría)">
							<a-card :bordered="false" class="header-solid">
								<template #title>
									<h6 class="font-semibold m-0">Auditoría</h6>
									<p class="m-0">Acciones registradas (incluye IP, dispositivo y fecha).</p>
								</template>
								<a-table
									rowKey="id"
									:columns="auditColumns"
									:data-source="auditRows"
									:loading="loadingAudit"
									:pagination="{ pageSize: 10, showSizeChanger: false, hideOnSinglePage: true }"
									:locale="{ emptyText: 'No hay registros de auditoría.' }"
									:scroll="{ x: 1100 }"
								>
									<template slot="actor" slot-scope="user">
										<div>
											<div>{{ (user && user.name) ? user.name : '—' }}</div>
											<div class="text-muted" style="font-size: 12px">{{ (user && user.email) ? user.email : '' }}</div>
										</div>
									</template>
									<template slot="created_at" slot-scope="value">
										<span>{{ formatDateTime(value) }}</span>
									</template>
									<template slot="device" slot-scope="meta">
										<span>{{ (meta && meta.device_type) ? meta.device_type : '—' }}</span>
									</template>
								</a-table>
							</a-card>
						</a-tab-pane>
					</a-tabs>
				</div>
			</div>
		</a-card>
	</div>
</template>

<script>
import axios from 'axios';
import { getToken } from '@/utils/auth';

export default ({
	name: 'Perfiles',
	data() {
		return {
			entityType: 'student',
			selectedId: null,
			activeTab: 'history',

			students: [],
			teachers: [],

			loadingStudents: false,
			loadingTeachers: false,
			loadingEnrollments: false,
			loadingIncidences: false,
			loadingAudit: false,

			enrollments: [],
			incidences: [],
			auditRowsRaw: [],

			enrollmentColumns: [
				{ title: 'Año', dataIndex: 'enrollmentYear', width: 90 },
				{ title: 'Grado/Curso', dataIndex: 'gradeCourse', width: 220 },
				{ title: 'Estado', dataIndex: 'enrollmentStatus', width: 140 },
				{ title: 'Fecha', dataIndex: 'enrolled_at', scopedSlots: { customRender: 'enrolled_at' }, width: 140 },
			],
			incidenceColumns: [
				{ title: 'Fecha', dataIndex: 'occurred_at', scopedSlots: { customRender: 'occurred_at' }, width: 160 },
				{ title: 'Título', dataIndex: 'title', width: 280 },
				{ title: 'Severidad', dataIndex: 'severity', width: 140 },
				{ title: 'Estado', dataIndex: 'status', width: 160 },
				{ title: 'Tipo', dataIndex: 'type', width: 140 },
			],
			auditColumns: [
				{ title: 'Usuario', dataIndex: 'actor_user', scopedSlots: { customRender: 'actor' }, width: 240 },
				{ title: 'Acción', dataIndex: 'action', width: 180 },
				{ title: 'Detalle', dataIndex: 'description', width: 320 },
				{ title: 'IP', dataIndex: 'ip_address', width: 140 },
				{ title: 'Dispositivo', dataIndex: 'meta', scopedSlots: { customRender: 'device' }, width: 140 },
				{ title: 'Fecha', dataIndex: 'created_at', scopedSlots: { customRender: 'created_at' }, width: 180, align: 'right' },
			],
		};
	},
	computed: {
		loadingAny() {
			return !!(this.loadingStudents || this.loadingTeachers || this.loadingEnrollments || this.loadingIncidences || this.loadingAudit);
		},
		entityOptions() {
			if (this.entityType === 'teacher') {
				return (this.teachers || []).map((t) => ({
					id: t.id,
					label: `${t.fullName}${t.email ? ` (${t.email})` : ''}`,
				}));
			}
			return (this.students || []).map((s) => ({
				id: s.id,
				label: `${s.fullName}${s.gradeCourse ? ` — ${s.gradeCourse}` : ''}`,
			}));
		},
		selectedEntity() {
			const id = this.selectedId;
			if (!id) return null;
			if (this.entityType === 'teacher') return (this.teachers || []).find((t) => t.id === id) || null;
			return (this.students || []).find((s) => s.id === id) || null;
		},
		selectedTitle() {
			if (!this.selectedEntity) return '';
			return this.entityType === 'teacher'
				? String(this.selectedEntity.fullName || 'Docente')
				: String(this.selectedEntity.fullName || 'Estudiante');
		},
		selectedSubtitle() {
			if (!this.selectedEntity) return '';
			if (this.entityType === 'teacher') {
				const email = this.selectedEntity.email ? String(this.selectedEntity.email) : '';
				const position = this.selectedEntity.position ? String(this.selectedEntity.position) : '';
				return [position, email].filter(Boolean).join(' · ') || '—';
			}
			const gc = this.selectedEntity.gradeCourse ? String(this.selectedEntity.gradeCourse) : '';
			const year = this.selectedEntity.enrollmentYear != null ? String(this.selectedEntity.enrollmentYear) : '';
			return [gc, year ? `Año ${year}` : ''].filter(Boolean).join(' · ') || '—';
		},
		selectedStatus() {
			if (!this.selectedEntity) return '';
			return String(this.selectedEntity.status || this.selectedEntity.enrollmentStatus || '').trim();
		},
		selectedAvatar() {
			if (!this.selectedEntity) return 'images/face-2.jpg';
			if (this.entityType === 'student') {
				return this.selectedEntity.photo_url || 'images/face-2.jpg';
			}
			return 'images/face-3.jpg';
		},
		studentEnrollments() {
			if (this.entityType !== 'student' || !this.selectedId) return [];
			const sid = this.selectedId;
			return (this.enrollments || []).filter((e) => Number(e?.student_id) === Number(sid));
		},
		auditRows() {
			const id = this.selectedId;
			if (!id) return [];
			const wantType = this.entityType === 'teacher' ? 'Teacher' : 'Student';
			return (this.auditRowsRaw || [])
				.map((r) => ({
					...r,
					actor_user: r.actorUser || r.actor_user || r.actor_user_id || r.actor_user,
				}))
				.filter((r) => String(r?.subject_type || '') === wantType && Number(r?.subject_id) === Number(id));
		},
	},
	mounted() {
		this.fetchLists();
	},
	methods: {
		apiHeaders() {
			const token = getToken();
			return { Authorization: `Bearer ${token}` };
		},
		filterOption(input, option) {
			const raw = option && option.componentOptions && option.componentOptions.children
				? option.componentOptions.children.map((c) => c.text).join('')
				: '';
			return String(raw || '').toLowerCase().includes(String(input || '').toLowerCase());
		},
		onEntityTypeChange() {
			this.selectedId = null;
			this.activeTab = 'history';
			this.incidences = [];
			this.auditRowsRaw = [];
		},
		onSelectedChange() {
			this.activeTab = 'history';
			this.refresh();
		},
		fetchLists() {
			this.fetchStudents();
			this.fetchTeachers();
		},
		fetchStudents() {
			this.loadingStudents = true;
			axios.get('http://localhost:8000/api/students', { headers: this.apiHeaders() })
				.then((res) => {
					this.students = Array.isArray(res.data) ? res.data : [];
				})
				.catch((err) => {
					console.error('Error cargando estudiantes:', err.response?.data || err);
					this.students = [];
				})
				.finally(() => { this.loadingStudents = false; });
		},
		fetchTeachers() {
			this.loadingTeachers = true;
			axios.get('http://localhost:8000/api/teachers', { headers: this.apiHeaders() })
				.then((res) => {
					this.teachers = Array.isArray(res.data) ? res.data : [];
				})
				.catch((err) => {
					console.error('Error cargando docentes:', err.response?.data || err);
					this.teachers = [];
				})
				.finally(() => { this.loadingTeachers = false; });
		},
		refresh() {
			if (!this.selectedId) return;
			this.fetchAuditLogs();

			if (this.entityType === 'student') {
				this.fetchEnrollments();
				return;
			}
			this.fetchTeacherIncidences();
		},
		fetchEnrollments() {
			this.loadingEnrollments = true;
			axios.get('http://localhost:8000/api/enrollments', { headers: this.apiHeaders() })
				.then((res) => {
					this.enrollments = Array.isArray(res.data) ? res.data : [];
				})
				.catch((err) => {
					console.error('Error cargando matrículas:', err.response?.data || err);
					this.enrollments = [];
				})
				.finally(() => { this.loadingEnrollments = false; });
		},
		fetchTeacherIncidences() {
			this.loadingIncidences = true;
			axios.get('http://localhost:8000/api/incidences', {
				headers: this.apiHeaders(),
				params: { type: 'Maestro', teacher_id: this.selectedId },
			})
				.then((res) => {
					this.incidences = Array.isArray(res.data) ? res.data : [];
				})
				.catch((err) => {
					console.error('Error cargando incidencias:', err.response?.data || err);
					this.incidences = [];
				})
				.finally(() => { this.loadingIncidences = false; });
		},
		fetchAuditLogs() {
			this.loadingAudit = true;
			axios.get('http://localhost:8000/api/audit-logs', {
				headers: this.apiHeaders(),
				params: { q: String(this.selectedId), per_page: 200 },
			})
				.then((res) => {
					this.auditRowsRaw = (res?.data?.data && Array.isArray(res.data.data)) ? res.data.data : [];
				})
				.catch((err) => {
					console.error('Error cargando auditoría:', err.response?.data || err);
					this.auditRowsRaw = [];
				})
				.finally(() => { this.loadingAudit = false; });
		},
		formatDate(value) {
			if (!value) return '—';
			const d = new Date(value);
			if (Number.isNaN(d.getTime())) return String(value);
			const dd = String(d.getDate()).padStart(2, '0');
			const mm = String(d.getMonth() + 1).padStart(2, '0');
			const yyyy = d.getFullYear();
			return `${dd}/${mm}/${yyyy}`;
		},
		formatDateTime(value) {
			if (!value) return '—';
			const d = new Date(value);
			if (Number.isNaN(d.getTime())) return String(value);
			const dd = String(d.getDate()).padStart(2, '0');
			const mm = String(d.getMonth() + 1).padStart(2, '0');
			const yyyy = d.getFullYear();
			return `${dd}/${mm}/${yyyy}`;
		},
	},
});
</script>

<style scoped>
.profile-body {
	padding: 0;
}

.table-header-left {
	display: flex;
	align-items: center;
	gap: 12px;
	flex-wrap: wrap;
}

.table-header-right {
	display: flex;
	align-items: center;
	justify-content: flex-end;
	gap: 12px;
	flex-wrap: wrap;
}

.entity-select {
	min-width: 320px;
	max-width: 520px;
	width: 100%;
}

@media (max-width: 767px) {
	.table-header-right {
		justify-content: flex-start;
	}
	.entity-select {
		min-width: 0;
	}
}

.mb-16 {
	margin-bottom: 16px;
}

.summary-left {
	display: flex;
	align-items: center;
	gap: 12px;
}

.summary-info {
	min-width: 0;
}

.summary-title {
	font-weight: 800;
	color: #111827;
	font-size: 16px;
	line-height: 1.1;
}

.summary-sub {
	color: rgba(17, 24, 39, 0.6);
	font-size: 13px;
	margin-top: 2px;
}

.summary-right {
	display: flex;
	justify-content: flex-end;
	gap: 8px;
	flex-wrap: wrap;
}

@media (max-width: 767px) {
	.summary-right {
		justify-content: flex-start;
		margin-top: 10px;
	}
}
</style>