<template>
	<div>
		<a-row :gutter="24" type="flex">
			<a-col :span="24" class="mb-24">
				<a-card :bordered="false" class="header-solid h-full">
					<a-row class="table-header-row" type="flex" align="middle">
						<a-col :span="24" :md="12">
							<div class="table-header-left">
								<h6 class="font-semibold m-0">Reporte de Incidencias</h6>
							</div>
						</a-col>

						<a-col :span="24" :md="12" class="table-header-right">
							<div class="reports-filters">
								<a-input-search
									v-model="searchText"
									placeholder="Buscar por título, descripción o responsable..."
									class="reports-search"
									allowClear
								/>
								<a-select v-model="typeFilter" class="reports-select" placeholder="Tipo">
									<a-select-option value="all">Todos</a-select-option>
									<a-select-option value="General">General</a-select-option>
									<a-select-option value="Maestro">Maestro</a-select-option>
									<a-select-option value="Alumno">Alumno</a-select-option>
									<a-select-option value="Empleado">Empleado</a-select-option>
								</a-select>

								<a-select v-model="severityFilter" class="reports-select" placeholder="Severidad">
									<a-select-option value="all">Todas</a-select-option>
									<a-select-option value="Baja">Baja</a-select-option>
									<a-select-option value="Media">Media</a-select-option>
									<a-select-option value="Alta">Alta</a-select-option>
									<a-select-option value="Crítica">Crítica</a-select-option>
								</a-select>

								<a-select v-model="statusFilter" class="reports-select" placeholder="Estado">
									<a-select-option value="all">Todos</a-select-option>
									<a-select-option value="Abierta">Abierta</a-select-option>
									<a-select-option value="En Proceso">En proceso</a-select-option>
									<a-select-option value="Resuelta">Resuelta</a-select-option>
									<a-select-option value="Cerrada">Cerrada</a-select-option>
								</a-select>

								<a-button class="btn-add-outline reports-btn" @click="openCreateModal">
									<svg class="btn-add-outline__icon" viewBox="0 0 24 24" aria-hidden="true">
										<path d="M12 5v14M5 12h14" fill="none" />
									</svg>
									Nueva Incidencia
								</a-button>
							</div>
						</a-col>
					</a-row>

					<!-- Estadísticas -->
					<div class="stats-section">
						<a-row :gutter="16">
							<a-col :span="6">
								<a-statistic
									title="Total Incidencias"
									:value="statistics.total"
									:value-style="{ color: '#1890ff' }"
								/>
							</a-col>
							<a-col :span="6">
								<a-statistic
									title="Abiertas"
									:value="statistics.open"
									:value-style="{ color: '#fa8c16' }"
								/>
							</a-col>
							<a-col :span="6">
								<a-statistic
									title="En Proceso"
									:value="statistics.inProgress"
									:value-style="{ color: '#1890ff' }"
								/>
							</a-col>
							<a-col :span="6">
								<a-statistic
									title="Resueltas"
									:value="statistics.resolved"
									:value-style="{ color: '#52c41a' }"
								/>
							</a-col>
						</a-row>
					</div>

					<a-table
						:columns="columns"
						:data-source="filteredData"
						:loading="loadingIncidences"
						:rowKey="rowKey"
						:pagination="{ pageSize: 10, showSizeChanger: false, hideOnSinglePage: true }"
						:scroll="{ x: 1400 }"
					>
						<template slot="occurred_at" slot-scope="value">
							<span class="text-muted">{{ formatDateYMD(value) || '-' }}</span>
						</template>

						<template slot="type" slot-scope="value">
							<a-tag :color="getTypeColor(value)">{{ value || '-' }}</a-tag>
						</template>

						<template slot="severity" slot-scope="value">
							<a-tag :color="getSeverityColor(value)">{{ value || '-' }}</a-tag>
						</template>

						<template slot="status" slot-scope="value">
							<a-badge :status="getStatusBadge(value)" :text="value || '-'" />
						</template>

						<template slot="reported_by" slot-scope="value, record">
							<div class="user-info">
								<a-avatar :src="record.reporter_avatar" size="small">
									 {{ record.reporter_name ? record.reporter_name.charAt(0) : '' }}
								</a-avatar>
								<span>{{ record.reporter_name || '-' }}</span>
							</div>
						</template>

						<template slot="assigned_to" slot-scope="value, record">
							<div class="user-info" v-if="record.assigned_to">
								<a-avatar :src="record.assignee_avatar" size="small">
									 {{ record.reporter_name ? record.reporter_name.charAt(0) : '' }}
								</a-avatar>
								<span>{{ record.assignee_name || '-' }}</span>
							</div>
							<span v-else class="text-muted">Sin asignar</span>
						</template>

						<template slot="actions" slot-scope="record">
							<div class="table-actions">
								<a-button size="small" @click="viewDetails(record)">Ver</a-button>
								<a-button size="small" @click="openEditModal(record)">Editar</a-button>
								<a-dropdown>
									<a-button size="small">
										<a-icon type="more" />
									</a-button>
									<a-menu slot="overlay">
										<a-menu-item @click="assignTo(record)">
											<a-icon type="user" /> Asignar
										</a-menu-item>
										<a-menu-item @click="changeStatus(record)">
											<a-icon type="edit" /> Cambiar Estado
										</a-menu-item>
										<a-menu-item @click="addComment(record)">
											<a-icon type="message" /> Comentar
										</a-menu-item>
										<a-menu-divider />
										<a-menu-item @click="confirmDelete(record)" style="color: #f5222d;">
											<a-icon type="delete" /> Eliminar
										</a-menu-item>
									</a-menu>
								</a-dropdown>
							</div>
						</template>
					</a-table>
				</a-card>
			</a-col>
		</a-row>

		<a-modal
			:title="editingIncidence ? 'Editar Incidencia' : 'Nueva Incidencia'"
			:visible="showModal"
			:confirm-loading="saving"
			@ok="handleSubmit"
			@cancel="closeModal"
			width="800px"
		>
			<a-form :form="form" layout="vertical">
				<a-form-item label="Tipo">
					<a-select
						v-decorator="[
							'type',
							{ rules: [{ required: true, message: 'Selecciona el tipo.' }], initialValue: 'General' }
						]"
						@change="onTypeChange"
					>
						<a-select-option value="General">General</a-select-option>
						<a-select-option value="Maestro">Maestro</a-select-option>
						<a-select-option value="Alumno">Alumno</a-select-option>
						<a-select-option value="Empleado">Empleado</a-select-option>
					</a-select>
				</a-form-item>

				<a-form-item label="Título">
					<a-input
						v-decorator="[
							'title',
							{ rules: [{ required: true, message: 'Ingresa el título.' }] }
						]"
						placeholder="Título de la incidencia"
					/>
				</a-form-item>

				<a-form-item label="Severidad">
					<a-radio-group
						v-decorator="[
							'severity',
							{ rules: [{ required: true, message: 'Selecciona la severidad.' }], initialValue: 'Media' }
						]"
					>
						<a-radio value="Baja">Baja</a-radio>
						<a-radio value="Media">Media</a-radio>
						<a-radio value="Alta">Alta</a-radio>
						<a-radio value="Crítica">Crítica</a-radio>
					</a-radio-group>
				</a-form-item>

				<a-form-item v-if="formType === 'Maestro'" label="Maestro">
					<a-select
						v-decorator="[
							'teacher_id',
							{ rules: [{ required: true, message: 'Selecciona el maestro.' }] }
						]"
						placeholder="Selecciona maestro"
						show-search
						:filter-option="filterTeacherOption"
					>
						<a-select-option 
							v-for="teacher in teachersData" 
							:key="teacher.id"
							:value="teacher.id"
						>
							{{ teacher.name }}
						</a-select-option>
					</a-select>
				</a-form-item>

				<a-form-item v-if="formType === 'Alumno'" label="Alumno">
					<a-select
						v-decorator="[
							'student_id',
							{ rules: [{ required: true, message: 'Selecciona el alumno.' }] }
						]"
						placeholder="Selecciona alumno"
						show-search
						:filter-option="filterStudentOption"
					>
						<a-select-option 
							v-for="student in studentsData" 
							:key="student.id"
							:value="student.id"
						>
							{{ student.name }}
						</a-select-option>
					</a-select>
				</a-form-item>

				<a-form-item v-if="formType === 'Empleado'" label="Empleado">
					<a-select
						v-decorator="[
							'employee_id',
							{ rules: [{ required: true, message: 'Selecciona el empleado.' }] }
						]"
						placeholder="Selecciona empleado"
						show-search
						:filter-option="filterEmployeeOption"
					>
						<a-select-option 
							v-for="employee in employeesData" 
							:key="employee.id"
							:value="employee.id"
						>
							{{ employee.name }}
						</a-select-option>
					</a-select>
				</a-form-item>

				<a-form-item label="Descripción">
					<a-textarea
						v-decorator="[
							'description',
							{ rules: [{ required: true, message: 'Describe la incidencia.' }] }
						]"
						placeholder="Describe detalladamente lo sucedido..."
						:rows="4"
					/>
				</a-form-item>

				<a-form-item label="Ubicación">
					<a-input
						v-decorator="['location']"
						placeholder="Ej: Salón 3, Biblioteca, Patio..."
					/>
				</a-form-item>

				<a-form-item label="Testigos">
					<a-textarea
						v-decorator="['witnesses']"
						placeholder="Personas que presenciaron el evento..."
						:rows="2"
					/>
				</a-form-item>

				<a-form-item label="Acciones Tomadas">
					<a-textarea
						v-decorator="['actions_taken']"
						placeholder="Acciones realizadas para manejar la incidencia..."
						:rows="2"
					/>
				</a-form-item>

				<a-form-item label="Estado">
					<a-select
						v-decorator="[
							'status',
							{ rules: [{ required: true, message: 'Selecciona el estado.' }], initialValue: 'Abierta' }
						]"
					>
						<a-select-option value="Abierta">Abierta</a-select-option>
						<a-select-option value="En Proceso">En proceso</a-select-option>
						<a-select-option value="Resuelta">Resuelta</a-select-option>
						<a-select-option value="Cerrada">Cerrada</a-select-option>
					</a-select>
				</a-form-item>

				<a-form-item label="Fecha del incidente (opcional)">
					<a-date-picker
						style="width: 100%"
						placeholder="Selecciona la fecha"
						v-decorator="['occurred_at']"
					/>
				</a-form-item>
			</a-form>
		</a-modal>
	</div>
</template>

<script>
import axios from 'axios';
import moment from 'moment';
import { Modal } from 'ant-design-vue';
import { getToken } from '@/utils/auth';

export default ({
	data() {
		return {
			loadingIncidences: false,
			incidencesData: [],

			loadingTeachers: false,
			teachersData: [],

			typeFilter: 'all',
			severityFilter: 'all',
			statusFilter: 'all',
			searchText: '',

			statistics: {
				total: 0,
				open: 0,
				inProgress: 0,
				resolved: 0,
			},

			detailsModal: {
				visible: false,
				incidence: null,
			},

			loadingStudents: false,
			studentsData: [],
			loadingEmployees: false,
			employeesData: [],

			showModal: false,
			saving: false,
			editingIncidence: null,
			formType: 'General',
			form: this.$form.createForm(this, { name: 'incidence_form' }),

			columns: [
				{ title: 'Fecha', dataIndex: 'occurred_at', width: 150, scopedSlots: { customRender: 'occurred_at' } },
				{ title: 'Tipo', dataIndex: 'type', width: 120, scopedSlots: { customRender: 'type' } },
				{ title: 'Título', dataIndex: 'title', width: 320 },
				{ title: 'Severidad', dataIndex: 'severity', width: 130, scopedSlots: { customRender: 'severity' } },
				{ title: 'Estado', dataIndex: 'status', width: 140, scopedSlots: { customRender: 'status' } },
				// Acciones al final (sin fixed) para que no se vea raro en scroll horizontal
				{ title: 'Acciones', key: 'actions', width: 180, scopedSlots: { customRender: 'actions' }, align: 'right' },
			],
		};
	},
	mounted() {
		this.fetchIncidences();
		this.fetchTeachers();
	},
	computed: {
		teachersOptions() {
			return (this.teachersData || [])
				.filter((t) => String(t?.status || 'Activo') === 'Activo')
				.map((t) => ({
					id: t.id,
					label: t.fullName || t.email || `ID ${t.id}`,
				}))
				.filter((x) => x.id != null);
		},
		filteredData() {
			let rows = (this.incidencesData || []).slice();

			if (this.typeFilter !== 'all') {
				rows = rows.filter((r) => String(r.type) === String(this.typeFilter));
			}
			if (this.statusFilter !== 'all') {
				rows = rows.filter((r) => String(r.status) === String(this.statusFilter));
			}
			if (this.searchText && String(this.searchText).trim() !== '') {
				const q = String(this.searchText).trim().toLowerCase();
				rows = rows.filter((r) => {
					const title = String(r.title || '').toLowerCase();
					const desc = String(r.description || '').toLowerCase();
					const teacher = String((r.teacher && r.teacher.fullName) ? r.teacher.fullName : '').toLowerCase();
					return title.includes(q) || desc.includes(q) || teacher.includes(q);
				});
			}
			return rows;
		},
	},
	methods: {
		rowKey(record) {
			return record.id;
		},
		apiHeaders() {
			const token = getToken();
			return { Authorization: `Bearer ${token}` };
		},
		formatDateYMD(value) {
			if (!value) return '';
			if (typeof value === 'string') {
				const isoDate = value.split('T')[0];
				if (/^\d{4}-\d{2}-\d{2}$/.test(isoDate)) return isoDate.replaceAll('-', '/');
			}
			const d = new Date(value);
			if (Number.isNaN(d.getTime())) {
				const raw = String(value);
				const onlyDate = raw.split(' ')[0];
				return /^\d{4}-\d{2}-\d{2}$/.test(onlyDate) ? onlyDate.replaceAll('-', '/') : onlyDate;
			}
			const yyyy = d.getFullYear();
			const mm = String(d.getMonth() + 1).padStart(2, '0');
			const dd = String(d.getDate()).padStart(2, '0');
			return `${yyyy}/${mm}/${dd}`;
		},
		getTypeColor(value) {
			const colors = {
				'General': 'blue',
				'Maestro': 'purple',
				'Alumno': 'green',
				'Empleado': 'orange',
			};
			return colors[value] || 'default';
		},
		getSeverityColor(value) {
			if (value === 'Alta') return 'red';
			if (value === 'Media') return 'orange';
			if (value === 'Baja') return 'gold';
			if (value === 'Crítica') return 'volcano';
			return 'default';
		},
		getStatusBadge(status) {
			const badges = {
				'Abierta': 'error',
				'En Proceso': 'processing',
				'Resuelta': 'success',
				'Cerrada': 'default',
			};
			return badges[status] || 'default';
		},
		fetchIncidences() {
			this.loadingIncidences = true;
			axios.get('http://localhost:8000/api/incidences', { headers: this.apiHeaders() })
				.then((res) => {
					this.incidencesData = (res.data || []).map((x) => ({
						...x,
						teacher: x.teacher || null,
					}));
					this.updateStatistics();
				})
				.catch((err) => {
					console.error('Error al obtener incidencias:', err.response?.data || err);
				})
				.finally(() => {
					this.loadingIncidences = false;
				});
		},
		fetchTeachers() {
			this.loadingTeachers = true;
			axios.get('http://localhost:8000/api/teachers', { headers: this.apiHeaders() })
				.then((res) => {
					this.teachersData = res.data || [];
				})
				.catch((err) => {
					console.error('Error al obtener maestros:', err.response?.data || err);
					this.teachersData = [];
				})
				.finally(() => {
					this.loadingTeachers = false;
				});
		},
		fetchStudents() {
			this.loadingStudents = true;
			axios.get('http://localhost:8000/api/students', { headers: this.apiHeaders() })
				.then((res) => {
					this.studentsData = res.data || [];
				})
				.catch((err) => {
					console.error('Error al obtener alumnos:', err.response?.data || err);
					this.studentsData = [];
				})
				.finally(() => {
					this.loadingStudents = false;
				});
		},
		fetchEmployees() {
			this.loadingEmployees = true;
			axios.get('http://localhost:8000/api/employees', { headers: this.apiHeaders() })
				.then((res) => {
					this.employeesData = res.data || [];
				})
				.catch((err) => {
					console.error('Error al obtener empleados:', err.response?.data || err);
					this.employeesData = [];
				})
				.finally(() => {
					this.loadingEmployees = false;
				});
		},
		updateStatistics() {
			const data = this.incidencesData || [];
			this.statistics = {
				total: data.length,
				open: data.filter(r => r.status === 'Abierta').length,
				inProgress: data.filter(r => r.status === 'En Proceso').length,
				resolved: data.filter(r => r.status === 'Resuelta').length,
			};
		},
		filterTeacherOption(input, option) {
			return option.componentOptions.children[0].text.toLowerCase().indexOf(input.toLowerCase()) >= 0;
		},
		filterStudentOption(input, option) {
			return option.componentOptions.children[0].text.toLowerCase().indexOf(input.toLowerCase()) >= 0;
		},
		filterEmployeeOption(input, option) {
			return option.componentOptions.children[0].text.toLowerCase().indexOf(input.toLowerCase()) >= 0;
		},
		onTypeChange(value) {
			this.formType = value || 'General';
			if (this.formType !== 'Maestro') {
				this.form.setFieldsValue({ teacher_id: undefined });
			}
			if (this.formType !== 'Alumno') {
				this.form.setFieldsValue({ student_id: undefined });
			}
			if (this.formType !== 'Empleado') {
				this.form.setFieldsValue({ employee_id: undefined });
			}
		},
		openCreateModal() {
			this.editingIncidence = null;
			this.showModal = true;
			this.saving = false;
			this.formType = 'General';
			this.form.resetFields();
			this.form.setFieldsValue({
				type: 'General',
				severity: 'Media',
				status: 'Abierta',
				occurred_at: moment(),
			});
		},
		openEditModal(record) {
			this.editingIncidence = record;
			this.showModal = true;
			this.saving = false;
			this.formType = record.type || 'General';

			this.form.resetFields();
			this.form.setFieldsValue({
				type: record.type || 'General',
				teacher_id: record.teacher_id || undefined,
				student_id: record.student_id || undefined,
				employee_id: record.employee_id || undefined,
				title: record.title || '',
				description: record.description || '',
				location: record.location || '',
				witnesses: record.witnesses || '',
				actions_taken: record.actions_taken || '',
				severity: record.severity || 'Media',
				status: record.status || 'Abierta',
				occurred_at: record.occurred_at ? moment(record.occurred_at) : null,
			});
		},
		closeModal() {
			this.showModal = false;
			this.saving = false;
			this.editingIncidence = null;
			this.form.resetFields();
		},
		handleSubmit() {
			this.form.validateFields((err, values) => {
				if (err) return;

				const payload = {
					type: values.type,
					teacher_id: values.type === 'Maestro' ? values.teacher_id : null,
					student_id: values.type === 'Alumno' ? values.student_id : null,
					employee_id: values.type === 'Empleado' ? values.employee_id : null,
					title: values.title,
					description: values.description || null,
					location: values.location || null,
					witnesses: values.witnesses || null,
					actions_taken: values.actions_taken || null,
					severity: values.severity,
					status: values.status,
					occurred_at: values.occurred_at ? values.occurred_at.format('YYYY-MM-DD') : null,
				};

				this.saving = true;
				const request = this.editingIncidence
					? axios.put(`http://localhost:8000/api/incidences/${this.editingIncidence.id}`, payload, { headers: this.apiHeaders() })
					: axios.post('http://localhost:8000/api/incidences', payload, { headers: this.apiHeaders() });

				request
					.then(() => {
						this.closeModal();
						this.fetchIncidences();
					})
					.catch((error) => {
						const msg = error.response?.data?.message || 'No se pudo guardar la incidencia.';
						console.error('Error guardando incidencia:', error.response?.data || error);
						this.$message.error(msg);
					})
					.finally(() => {
						this.saving = false;
					});
			});
		},
		confirmDelete(record) {
			Modal.confirm({
				title: 'Eliminar incidencia',
				content: '¿Seguro que deseas eliminar esta incidencia?',
				okText: 'Eliminar',
				okType: 'danger',
				cancelText: 'Cancelar',
				onOk: () => {
					return axios.delete(`http://localhost:8000/api/incidences/${record.id}`, { headers: this.apiHeaders() })
						.then(() => {
							this.fetchIncidences();
						})
						.catch((err) => {
							console.error('Error eliminando incidencia:', err.response?.data || err);
							this.$message.error('No se pudo eliminar la incidencia.');
						});
				},
			});
		},
	},
})
</script>

<style lang="scss">
.reports-filters {
	display: flex;
	align-items: center;
	justify-content: flex-end;
	gap: 10px;
	flex-wrap: wrap;
}

.reports-select {
	width: 160px;
}

.reports-search {
	width: 260px;
}

/* En md y sm: alineación como Matrícula (hacia la izquierda) */
@media (max-width: 991px) {
	.reports-filters {
		justify-content: flex-start;
	}
}

/* En pantallas pequeñas: controles a 100% de ancho */
@media (max-width: 575px) {
	.reports-search,
	.reports-select,
	.reports-btn {
		width: 100%;
	}
}

.table-actions {
	display: inline-flex;
	align-items: center;
	gap: 8px;
	justify-content: flex-end;
	white-space: nowrap;
}
</style>