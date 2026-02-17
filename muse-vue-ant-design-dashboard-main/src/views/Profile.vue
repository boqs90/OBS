<template>
	<div>
		<!-- Perfil del Usuario Actual con Diseño Adornado -->
		<div class="profile-hero-section">
			<div class="profile-hero-bg"></div>
			<div class="profile-hero-content">
				<a-row type="flex" align="middle" :gutter="32">
					<a-col :span="24" :md="8" class="text-center text-md-left">
						<div class="profile-avatar-wrapper">
							<a-avatar 
								:size="100" 
								:src="userProfilePhoto"
								class="profile-hero-avatar"
								:style="{ backgroundColor: '#7c3aed' }"
								@error="onProfileImageError"
							>
								{{ userInitials }}
							</a-avatar>
							<div class="profile-status-indicator" :class="{ 'online': isOnline }"></div>
							<div class="profile-avatar-ring"></div>
						</div>
					</a-col>
					<a-col :span="24" :md="16">
						<div class="profile-hero-info">
							<div class="profile-hero-header">
								<h1 class="profile-hero-name">{{ currentUser.name || 'Usuario' }}</h1>
								<div class="profile-hero-badges">
									<span class="profile-badge status" :class="userStatus === 'Activo' ? 'active' : 'inactive'">
										{{ userStatus || 'Activo' }}
									</span>
									<span class="profile-badge role">{{ userRole }}</span>
									<span class="profile-badge department">{{ userDepartment }}</span>
								</div>
							</div>
							<p class="profile-hero-description">
								Bienvenido a tu panel de control personal. Aquí puedes gestionar tu perfil y ver tu historial de actividad.
							</p>
							<div class="profile-hero-stats">
								<div class="stat-item">
									<span class="stat-number">{{ currentUser.id ? '#' + currentUser.id : '#000' }}</span>
									<span class="stat-label">ID Usuario</span>
								</div>
								<div class="stat-item">
									<span class="stat-number">{{ currentUser.email ? currentUser.email.split('@')[0] : 'N/A' }}</span>
									<span class="stat-label">Usuario</span>
								</div>
								<div class="stat-item">
									<span class="stat-number">{{ isOnline ? 'En línea' : 'Desconectado' }}</span>
									<span class="stat-label">Estado</span>
								</div>
							</div>
							<div class="profile-hero-actions">
								<a-button type="primary" size="large" @click="editProfile" class="profile-action-btn primary">
									<a-icon type="edit" /> Editar Perfil
								</a-button>
								<a-button type="default" size="large" @click="viewFullProfile" class="profile-action-btn secondary">
									<a-icon type="setting" /> Configuración
								</a-button>
							</div>
						</div>
					</a-col>
				</a-row>
			</div>
		</div>

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

						<a-tab-pane key="academic" tab="Información Académica" v-if="entityType === 'student'">
							<a-card :bordered="false" class="header-solid">
								<template #title>
									<h6 class="font-semibold m-0">Información Académica</h6>
									<p class="m-0">Notas y rendimiento académico del estudiante.</p>
								</template>
								
								<!-- Filtros Académicos -->
								<div class="filters-section mb-4">
									<a-row :gutter="16">
										<a-col :span="6">
											<a-select
												v-model="academicFilters.period"
												placeholder="Filtrar por parcial"
												style="width: 100%"
												allowClear
												@change="filterAcademicData"
											>
												<a-select-option value="1er Parcial">1er Parcial</a-select-option>
												<a-select-option value="2do Parcial">2do Parcial</a-select-option>
												<a-select-option value="3er Parcial">3er Parcial</a-select-option>
												<a-select-option value="Todos">Todos</a-select-option>
											</a-select>
										</a-col>
										<a-col :span="6">
											<a-select
												v-model="academicFilters.teacher"
												placeholder="Filtrar por profesor"
												style="width: 100%"
												allowClear
												@change="filterAcademicData"
											>
												<a-select-option v-for="teacher in uniqueTeachers" :key="teacher" :value="teacher">
													{{ teacher }}
												</a-select-option>
											</a-select>
										</a-col>
										<a-col :span="6">
											<a-select
												v-model="academicFilters.subject"
												placeholder="Filtrar por asignatura"
												style="width: 100%"
												allowClear
												@change="filterAcademicData"
											>
												<a-select-option v-for="subject in uniqueSubjects" :key="subject" :value="subject">
													{{ subject }}
												</a-select-option>
											</a-select>
										</a-col>
										<a-col :span="6">
											<a-button @click="clearAcademicFilters" type="default" icon="redo">
												Limpiar Filtros
											</a-button>
										</a-col>
									</a-row>
								</div>

								<a-table
									rowKey="id"
									:columns="academicColumns"
									:data-source="filteredAcademicInfo"
									:loading="loadingAcademic"
									:pagination="{ pageSize: 10, showSizeChanger: false, hideOnSinglePage: true }"
									:locale="{ emptyText: 'No hay información académica.' }"
									:scroll="{ x: 900 }"
								>
									<template slot="grade" slot-scope="value">
										<a-tag :color="getGradeColor(value)">{{ value }}</a-tag>
									</template>
								</a-table>
							</a-card>
						</a-tab-pane>

						<a-tab-pane key="attendance" tab="Asistencia" v-if="entityType === 'student'">
							<a-card :bordered="false" class="header-solid">
								<template #title>
									<h6 class="font-semibold m-0">Asistencia</h6>
									<p class="m-0">Registros de asistencia y ausencias.</p>
								</template>
								
								<!-- Filtros de Asistencia -->
								<div class="filters-section mb-4">
									<a-row :gutter="16">
										<a-col :span="6">
											<a-select
												v-model="attendanceFilters.period"
												placeholder="Filtrar por período"
												style="width: 100%"
												allowClear
												@change="filterAttendanceData"
											>
												<a-select-option value="week">Última Semana</a-select-option>
												<a-select-option value="month">Último Mes</a-select-option>
												<a-select-option value="quarter">Último Trimestre</a-select-option>
												<a-select-option value="all">Todos</a-select-option>
											</a-select>
										</a-col>
										<a-col :span="6">
											<a-select
												v-model="attendanceFilters.status"
												placeholder="Filtrar por estado"
												style="width: 100%"
												allowClear
												@change="filterAttendanceData"
											>
												<a-select-option value="Presente">Presente</a-select-option>
												<a-select-option value="Ausente">Ausente</a-select-option>
												<a-select-option value="Tardanza">Tardanza</a-select-option>
												<a-select-option value="Justificado">Justificado</a-select-option>
											</a-select>
										</a-col>
										<a-col :span="6">
											<a-range-picker
												v-model="attendanceFilters.dateRange"
												style="width: 100%"
												@change="filterAttendanceData"
												placeholder="['Fecha inicio', 'Fecha fin']"
											/>
										</a-col>
										<a-col :span="6">
											<a-button @click="clearAttendanceFilters" type="default" icon="redo">
												Limpiar Filtros
											</a-button>
										</a-col>
									</a-row>
								</div>

								<a-row :gutter="16" class="mb-16">
									<a-col :span="8">
										<a-statistic title="Días Presente" :value="attendanceStats.present" value-style="color: #52c41a" />
									</a-col>
									<a-col :span="8">
										<a-statistic title="Ausencias" :value="attendanceStats.absent" value-style="color: #f5222d" />
									</a-col>
									<a-col :span="8">
										<a-statistic title="Tardanzas" :value="attendanceStats.late" value-style="color: #fa8c16" />
									</a-col>
								</a-row>
								<a-table
									rowKey="id"
									:columns="attendanceColumns"
									:data-source="filteredAttendance"
									:loading="loadingAttendance"
									:pagination="{ pageSize: 10, showSizeChanger: false, hideOnSinglePage: true }"
									:locale="{ emptyText: 'No hay registros de asistencia.' }"
									:scroll="{ x: 600 }"
								>
									<template slot="status" slot-scope="value">
										<a-tag :color="getAttendanceColor(value)">{{ value }}</a-tag>
									</template>
								</a-table>
							</a-card>
						</a-tab-pane>

						<a-tab-pane key="behavior" tab="Comportamiento" v-if="entityType === 'student'">
							<a-card :bordered="false" class="header-solid">
								<template #title>
									<h6 class="font-semibold m-0">Registro de Comportamiento</h6>
									<p class="m-0">Observaciones y reportes de comportamiento.</p>
								</template>
								<a-row :gutter="16" class="mb-16">
									<a-col :span="8">
										<a-statistic title="Positivos" :value="behaviorStats.positive" value-style="color: #52c41a" />
									</a-col>
									<a-col :span="8">
										<a-statistic title="Observaciones" :value="behaviorStats.neutral" value-style="color: #1890ff" />
									</a-col>
									<a-col :span="8">
										<a-statistic title="Negativos" :value="behaviorStats.negative" value-style="color: #f5222d" />
									</a-col>
								</a-row>
								<a-table
									rowKey="id"
									:columns="behaviorColumns"
									:data-source="behaviorRecords"
									:loading="loadingBehavior"
									:pagination="{ pageSize: 10, showSizeChanger: false, hideOnSinglePage: true }"
									:locale="{ emptyText: 'No hay registros de comportamiento.' }"
									:scroll="{ x: 900 }"
								>
									<template slot="type" slot-scope="value">
										<a-tag :color="getBehaviorTypeColor(value)">{{ value }}</a-tag>
									</template>
									<template slot="severity" slot-scope="value">
										<a-tag v-if="value" :color="getSeverityColor(value)">{{ value }}</a-tag>
										<span v-else>-</span>
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
import { getUser } from '@/utils/auth';
import { getProfilePhoto, getUserInitials, formatUserRole, handleImageError } from '@/utils/profileUtils';

export default ({
	name: 'Perfiles',
	data() {
		return {
			entityType: 'student',
			selectedId: null,
			activeTab: 'history',
			isOnline: true, // Simulación de estado en línea

			students: [],
			teachers: [],

			loadingStudents: false,
			loadingTeachers: false,
			loadingEnrollments: false,
			loadingIncidences: false,
			loadingAudit: false,
			loadingAcademic: false,
			loadingAttendance: false,
			loadingBehavior: false,

			enrollments: [],
			incidences: [],
			auditRowsRaw: [],
			academicInfo: [],
			attendance: [],
			behaviorRecords: [],
			memorandums: [],

			// Filtros
			academicFilters: {
				period: null,
				teacher: null,
				subject: null,
			},
			attendanceFilters: {
				period: null,
				status: null,
				dateRange: null,
			},
			behaviorFilters: {
				type: null,
				severity: null,
				dateRange: null,
			},

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
			academicColumns: [
				{ title: 'Asignatura', dataIndex: 'subject', width: 180 },
				{ title: 'Nota', dataIndex: 'grade', scopedSlots: { customRender: 'grade' }, width: 100 },
				{ title: 'Período', dataIndex: 'period', width: 120 },
				{ title: 'Profesor', dataIndex: 'teacher', width: 180 },
				{ title: 'Observaciones', dataIndex: 'observations', width: 250 },
			],
			attendanceColumns: [
				{ title: 'Fecha', dataIndex: 'date', width: 120 },
				{ title: 'Estado', dataIndex: 'status', scopedSlots: { customRender: 'status' }, width: 120 },
				{ title: 'Observaciones', dataIndex: 'observations', width: 200 },
			],
			behaviorColumns: [
				{ title: 'Fecha', dataIndex: 'date', width: 120 },
				{ title: 'Tipo', dataIndex: 'type', scopedSlots: { customRender: 'type' }, width: 100 },
				{ title: 'Categoría', dataIndex: 'category', width: 150 },
				{ title: 'Descripción', dataIndex: 'description', width: 300 },
				{ title: 'Severidad', dataIndex: 'severity', scopedSlots: { customRender: 'severity' }, width: 100 },
				{ title: 'Reportado por', dataIndex: 'reported_by', width: 150 },
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
		// Propiedades del usuario actual
		currentUser() {
			return getUser() || {};
		},
		userInitials() {
			return getUserInitials(this.currentUser);
		},
		userProfilePhoto() {
			return getProfilePhoto(this.currentUser);
		},
		userRole() {
			return formatUserRole(this.currentUser);
		},
		userStatus() {
			const status = this.currentUser?.status;
			return status === 'active' || status === 'Activo' ? 'Activo' : status;
		},
		userDepartment() {
			const dept = this.currentUser?.department;
			return dept || 'General';
		},
		
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
		studentAcademicInfo() {
			if (this.entityType !== 'student' || !this.selectedId) return [];
			const sid = this.selectedId;
			return (this.academicInfo || []).filter((info) => Number(info?.student_id) === Number(sid));
		},
		studentAttendance() {
			if (this.entityType !== 'student' || !this.selectedId) return [];
			const sid = this.selectedId;
			return (this.attendance || []).filter((att) => Number(att?.student_id) === Number(sid));
		},
		studentBehaviorRecords() {
			if (this.entityType !== 'student' || !this.selectedId) return [];
			const sid = this.selectedId;
			return (this.behaviorRecords || []).filter((beh) => Number(beh?.student_id) === Number(sid));
		},
		attendanceStats() {
			const records = this.studentAttendance;
			return {
				present: records.filter(r => r.status === 'Presente').length,
				absent: records.filter(r => r.status === 'Ausente').length,
				late: records.filter(r => r.status === 'Tardanza').length,
			};
		},
		behaviorStats() {
			const records = this.studentBehaviorRecords;
			return {
				positive: records.filter(r => r.type === 'Positivo').length,
				neutral: records.filter(r => r.type === 'Observación').length,
				negative: records.filter(r => r.type === 'Negativo').length,
			};
		},
		// Computed properties para datos filtrados
		filteredAcademicInfo() {
			let data = this.studentAcademicInfo;
			
			if (this.academicFilters.period && this.academicFilters.period !== 'Todos') {
				data = data.filter(item => item.period === this.academicFilters.period);
			}
			
			if (this.academicFilters.teacher) {
				data = data.filter(item => item.teacher === this.academicFilters.teacher);
			}
			
			if (this.academicFilters.subject) {
				data = data.filter(item => item.subject === this.academicFilters.subject);
			}
			
			return data;
		},
		filteredAttendance() {
			let data = this.studentAttendance;
			
			// Filtrar por período de tiempo
			if (this.attendanceFilters.period) {
				const now = new Date();
				let startDate;
				
				switch (this.attendanceFilters.period) {
					case 'week':
						startDate = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
						break;
					case 'month':
						startDate = new Date(now.getFullYear(), now.getMonth(), 1);
						break;
					case 'quarter':
						startDate = new Date(now.getFullYear(), Math.floor(now.getMonth() / 3) * 3, 1);
						break;
					default:
						startDate = null;
				}
				
				if (startDate) {
					data = data.filter(item => new Date(item.date) >= startDate);
				}
			}
			
			// Filtrar por estado
			if (this.attendanceFilters.status) {
				data = data.filter(item => item.status === this.attendanceFilters.status);
			}
			
			// Filtrar por rango de fechas
			if (this.attendanceFilters.dateRange && this.attendanceFilters.dateRange.length === 2) {
				const [start, end] = this.attendanceFilters.dateRange;
				data = data.filter(item => {
					const itemDate = new Date(item.date);
					return itemDate >= start && itemDate <= end;
				});
			}
			
			return data;
		},
		filteredBehaviorRecords() {
			let data = this.studentBehaviorRecords;
			
			// Filtrar por tipo
			if (this.behaviorFilters.type) {
				data = data.filter(item => item.type === this.behaviorFilters.type);
			}
			
			// Filtrar por severidad
			if (this.behaviorFilters.severity) {
				data = data.filter(item => item.severity === this.behaviorFilters.severity);
			}
			
			// Filtrar por rango de fechas
			if (this.behaviorFilters.dateRange && this.behaviorFilters.dateRange.length === 2) {
				const [start, end] = this.behaviorFilters.dateRange;
				data = data.filter(item => {
					const itemDate = new Date(item.date);
					return itemDate >= start && itemDate <= end;
				});
			}
			
			return data;
		},
		// Opciones únicas para filtros
		uniqueTeachers() {
			return [...new Set(this.studentAcademicInfo.map(item => item.teacher).filter(Boolean))];
		},
		uniqueSubjects() {
			return [...new Set(this.studentAcademicInfo.map(item => item.subject).filter(Boolean))];
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
				this.fetchAcademicInfo();
				this.fetchAttendance();
				this.fetchBehaviorRecords();
				return;
			}
			this.fetchTeacherIncidences();
		},
		fetchAcademicInfo() {
			this.loadingAcademic = true;
			axios.get('http://localhost:8000/api/academic-info', {
				headers: this.apiHeaders(),
				params: { student_id: this.selectedId },
			})
				.then((res) => {
					this.academicInfo = Array.isArray(res.data) ? res.data : [];
				})
				.catch((err) => {
					console.error('Error cargando información académica:', err.response?.data || err);
					this.academicInfo = [];
				})
				.finally(() => { this.loadingAcademic = false; });
		},
		fetchAttendance() {
			this.loadingAttendance = true;
			axios.get('http://localhost:8000/api/attendance', {
				headers: this.apiHeaders(),
				params: { student_id: this.selectedId },
			})
				.then((res) => {
					this.attendance = Array.isArray(res.data) ? res.data : [];
				})
				.catch((err) => {
					console.error('Error cargando asistencia:', err.response?.data || err);
					this.attendance = [];
				})
				.finally(() => { this.loadingAttendance = false; });
		},
		fetchBehaviorRecords() {
			this.loadingBehavior = true;
			axios.get('http://localhost:8000/api/behavior-records', {
				headers: this.apiHeaders(),
				params: { student_id: this.selectedId },
			})
				.then((res) => {
					this.behaviorRecords = Array.isArray(res.data) ? res.data : [];
				})
				.catch((err) => {
					console.error('Error cargando registros de comportamiento:', err.response?.data || err);
					this.behaviorRecords = [];
				})
				.finally(() => { this.loadingBehavior = false; });
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
		// Métodos del perfil del usuario actual
		editProfile() {
			// Navegar a la página de edición de perfil
			this.$router.push('/tables');
		},
		viewFullProfile() {
			// Mostrar mensaje de perfil completo o navegar a una vista detallada
			this.$message.info('Esta es tu vista de perfil completa');
		},
		// Manejar error de carga de imagen
		onProfileImageError(event) {
			handleImageError(event);
		},
		// Funciones de color para diferentes elementos
		getGradeColor(grade) {
			if (grade >= 9) return 'green';
			if (grade >= 7) return 'blue';
			if (grade >= 5) return 'orange';
			return 'red';
		},
		getAttendanceColor(status) {
			switch (status) {
				case 'Presente': return 'green';
				case 'Ausente': return 'red';
				case 'Tardanza': return 'orange';
				case 'Justificado': return 'blue';
				default: return 'default';
			}
		},
		getBehaviorTypeColor(type) {
			switch (type) {
				case 'Positivo': return 'green';
				case 'Negativo': return 'red';
				case 'Observación': return 'blue';
				default: return 'default';
			}
		},
		getSeverityColor(severity) {
			switch (severity) {
				case 'Leve': return 'orange';
				case 'Medio': return 'red';
				case 'Grave': return 'volcano';
				default: return 'default';
			}
		},
		// Métodos para filtros
		filterAcademicData() {
			// Las computed properties ya manejan el filtrado
		},
		filterAttendanceData() {
			// Las computed properties ya manejan el filtrado
		},
		filterBehaviorData() {
			// Las computed properties ya manejan el filtrado
		},
		clearAcademicFilters() {
			this.academicFilters = {
				period: null,
				teacher: null,
				subject: null,
			};
		},
		clearAttendanceFilters() {
			this.attendanceFilters = {
				period: null,
				status: null,
				dateRange: null,
			};
		},
		clearBehaviorFilters() {
			this.behaviorFilters = {
				type: null,
				severity: null,
				dateRange: null,
			}
		},
	},
});
</script>
<style scoped>
/* Estilos para el perfil del usuario actual con diseño adornado */
.profile-hero-section {
	position: relative;
	margin-bottom: 32px;
	border-radius: 16px;
	overflow: hidden;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.profile-hero-bg {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: 
		radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
		radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
		radial-gradient(circle at 40% 40%, rgba(124, 58, 237, 0.2) 0%, transparent 50%);
	animation: float 6s ease-in-out infinite;
}

@keyframes float {
	0%, 100% { transform: translateY(0px); }
	50% { transform: translateY(-20px); }
}

.profile-hero-content {
	position: relative;
	padding: 48px 32px;
	z-index: 2;
}

.profile-avatar-wrapper {
	position: relative;
	display: inline-block;
}

.profile-hero-avatar {
	border: 4px solid rgba(255, 255, 255, 0.2);
	box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
	transition: all 0.3s ease;
}

.profile-hero-avatar:hover {
	transform: scale(1.05);
	box-shadow: 0 12px 48px rgba(0, 0, 0, 0.4);
	border-color: rgba(255, 255, 255, 0.4);
}

.profile-status-indicator {
	position: absolute;
	bottom: 12px;
	right: 12px;
	width: 24px;
	height: 24px;
	border-radius: 50%;
	border: 4px solid rgba(255, 255, 255, 0.3);
	background-color: #ef4444;
	transition: all 0.3s ease;
	z-index: 3;
}

.profile-status-indicator.online {
	background-color: #22c55e;
	box-shadow: 0 0 20px rgba(34, 197, 94, 0.6);
}

.profile-avatar-ring {
	position: absolute;
	top: -8px;
	left: -8px;
	right: -8px;
	bottom: -8px;
	border: 2px solid rgba(255, 255, 255, 0.2);
	border-radius: 50%;
	animation: rotate 10s linear infinite;
}

@keyframes rotate {
	from { transform: rotate(0deg); }
	to { transform: rotate(360deg); }
}

.profile-hero-info {
	color: white;
}

.profile-hero-header {
	margin-bottom: 16px;
}

.profile-hero-name {
	margin: 0 0 12px 0;
	font-size: 32px;
}

.profile-hero-badges {
	display: flex;
	gap: 8px;
	flex-wrap: wrap;
	margin-bottom: 16px;
}

.profile-badge {
	padding: 4px 12px;
	border-radius: 20px;
	font-size: 12px;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.5px;
	background: rgba(255, 255, 255, 0.2);
	backdrop-filter: blur(10px);
	border: 1px solid rgba(255, 255, 255, 0.3);
}

.profile-badge.status.active {
	background: rgba(34, 197, 94, 0.3);
	border-color: rgba(34, 197, 94, 0.5);
}

.profile-badge.status.inactive {
	background: rgba(239, 68, 68, 0.3);
	border-color: rgba(239, 68, 68, 0.5);
}

.profile-hero-description {
	margin: 0 0 24px 0;
	font-size: 16px;
	line-height: 1.6;
	color: rgba(255, 255, 255, 0.9);
	max-width: 600px;
}

.profile-hero-stats {
	display: flex;
	gap: 32px;
	margin-bottom: 32px;
	flex-wrap: wrap;
}

.stat-item {
	text-align: center;
}

.stat-number {
	display: block;
	font-size: 20px;
	font-weight: 700;
	color: white;
	margin-bottom: 4px;
	text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.stat-label {
	font-size: 12px;
	color: rgba(255, 255, 255, 0.7);
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.profile-hero-actions {
	display: flex;
	gap: 16px;
	flex-wrap: wrap;
}

.profile-action-btn {
	height: 48px;
	padding: 0 24px;
	border-radius: 12px;
	font-weight: 600;
	font-size: 14px;
	transition: all 0.3s ease;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.profile-action-btn.primary {
	background: rgba(255, 255, 255, 0.2);
	border: 2px solid rgba(255, 255, 255, 0.3);
	color: white;
	backdrop-filter: blur(10px);
}

.profile-action-btn.primary:hover {
	background: rgba(255, 255, 255, 0.3);
	border-color: rgba(255, 255, 255, 0.5);
	transform: translateY(-2px);
	box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}

.profile-action-btn.secondary {
	background: transparent;
	border: 2px solid rgba(255, 255, 255, 0.3);
	color: white;
	backdrop-filter: blur(10px);
}

.profile-action-btn.secondary:hover {
	background: rgba(255, 255, 255, 0.1);
	border-color: rgba(255, 255, 255, 0.5);
	transform: translateY(-2px);
}

/* Estilos para la sección de filtros */
.filters-section {
	background: #f8f9fa;
	border: 1px solid #e9ecef;
	border-radius: 8px;
	padding: 16px;
	margin-bottom: 16px;
}

.filters-section .ant-select,
.filters-section .ant-calendar-picker {
	width: 100%;
}

/* Animación para el indicador de estado en línea */
@keyframes pulse {
	0% {
		box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4);
	}
	70% {
		box-shadow: 0 0 0 15px rgba(34, 197, 94, 0);
	}
	100% {
		box-shadow: 0 0 0 0 rgba(34, 197, 94, 0);
	}
}

.profile-status-indicator.online {
	animation: pulse 2s infinite;
}

/* Responsive */
@media (max-width: 768px) {
	.profile-hero-content {
		padding: 32px 24px;
	}
	
	.profile-hero-name {
		font-size: 24px;
		text-align: center;
	}
	
	.profile-hero-badges {
		justify-content: center;
	}
	
	.profile-hero-description {
		text-align: center;
		margin: 0 auto 24px auto;
	}
	
	.profile-hero-stats {
		justify-content: center;
		gap: 24px;
	}
	
	.profile-hero-actions {
		justify-content: center;
	}
}

@media (max-width: 480px) {
	.profile-hero-stats {
		flex-direction: column;
		gap: 16px;
	}
	
	.profile-hero-actions {
		flex-direction: column;
		align-items: stretch;
	}
	
	.profile-action-btn {
		width: 100%;
	}
}

/* Estilos existentes */
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