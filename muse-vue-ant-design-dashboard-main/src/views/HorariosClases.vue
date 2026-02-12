<template>
	<div class="layout">
		<a-card class="horarios-card" :bordered="false">
			<div class="card-header">
				<div class="header-content">
					<div class="header-info">
						<h2 class="page-title">
							<span class="icon">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M12 2L2 7L12 12L22 7V17L12 22L2 17V7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									<path d="M12 12L12 22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									<path d="M2 7H22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>
							</span>
							Horarios de Clases
						</h2>
						<p class="page-description">Gestiona los horarios de clases por grado, día y asignatura</p>
					</div>
					<div class="header-actions">
						<a-button type="primary" @click="showCreateModal = true">
							<a-icon type="plus" />
							Nuevo Horario
						</a-button>
					</div>
				</div>
			</div>

			<!-- Filtros y búsqueda -->
			<div class="filters-section">
				<a-row :gutter="16" type="flex" align="middle">
					<a-col :xs="24" :sm="12" :md="6" :lg="4">
						<a-input-search
							v-model="searchText"
							placeholder="Buscar horario..."
							@search="handleSearch"
							allow-clear
						/>
					</a-col>
					<a-col :xs="24" :sm="12" :md="6" :lg="5">
						<a-select
							v-model="gradeFilter"
							placeholder="Filtrar por grado"
							style="width: 100%"
							@change="handleFilter"
							allow-clear
						>
							<a-select-option value="all">Todos los grados</a-select-option>
							<a-select-option value="pre-kinder">Pre-Kínder</a-select-option>
							<a-select-option value="kinder">Kínder</a-select-option>
							<a-select-option value="1ro">1er Grado</a-select-option>
							<a-select-option value="2do">2do Grado</a-select-option>
							<a-select-option value="3ro">3er Grado</a-select-option>
							<a-select-option value="4to">4to Grado</a-select-option>
							<a-select-option value="5to">5to Grado</a-select-option>
							<a-select-option value="6to">6to Grado</a-select-option>
						</a-select>
					</a-col>
					<a-col :xs="24" :sm="12" :md="6" :lg="5">
						<a-select
							v-model="dayFilter"
							placeholder="Filtrar por día"
							style="width: 100%"
							@change="handleFilter"
							allow-clear
						>
							<a-select-option value="all">Todos los días</a-select-option>
							<a-select-option value="monday">Lunes</a-select-option>
							<a-select-option value="tuesday">Martes</a-select-option>
							<a-select-option value="wednesday">Miércoles</a-select-option>
							<a-select-option value="thursday">Jueves</a-select-option>
							<a-select-option value="friday">Viernes</a-select-option>
						</a-select>
					</a-col>
					<a-col :xs="24" :sm="12" :md="6" :lg="5">
						<a-button @click="resetFilters">
							<a-icon type="reload" />
							Limpiar filtros
						</a-button>
					</a-col>
					<a-col :xs="24" :sm="12" :md="6" :lg="5">
						<a-button @click="showWeekView = !showWeekView">
							<a-icon type="calendar" />
							{{ showWeekView ? 'Vista Tabla' : 'Vista Semana' }}
						</a-button>
					</a-col>
				</a-row>
			</div>

			<!-- Vista de Semana -->
			<div v-if="showWeekView" class="week-view">
				<div class="week-header">
					<h3>Horario Semanal - {{ selectedGradeLabel || 'Todos los grados' }}</h3>
				</div>
				<div class="week-grid">
					<div class="time-column">
						<div class="time-header">Hora</div>
						<div v-for="hour in timeSlots" :key="hour" class="time-slot">
							{{ hour }}
						</div>
					</div>
					<div v-for="day in weekDays" :key="day.key" class="day-column">
						<div class="day-header">{{ day.label }}</div>
						<div v-for="hour in timeSlots" :key="hour" class="schedule-cell">
							<div v-if="getScheduleForDayAndHour(day.key, hour)" class="schedule-item">
								<div class="subject">{{ getScheduleForDayAndHour(day.key, hour).subject_name }}</div>
								<div class="teacher">{{ getScheduleForDayAndHour(day.key, hour).teacher_name }}</div>
								<div class="room">{{ getScheduleForDayAndHour(day.key, hour).classroom }}</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Vista de Tabla -->
			<a-table
				v-else
				:columns="columns"
				:data-source="filteredSchedules"
				:loading="loading"
				:pagination="pagination"
				@change="handleTableChange"
				:row-key="record => record.id"
			>
				<template slot="grade" slot-scope="text">
					<a-tag :color="getGradeColor(text)">
						{{ getGradeLabel(text) }}
					</a-tag>
				</template>

				<template slot="day" slot-scope="text">
					<a-tag :color="getDayColor(text)">
						{{ getDayLabel(text) }}
					</a-tag>
				</template>

				<template slot="time_slot" slot-scope="text">
					<span class="time-slot">{{ text }}</span>
				</template>

				<template slot="subject" slot-scope="text">
					<span class="subject-name">{{ text }}</span>
				</template>

				<template slot="teacher" slot-scope="text">
					<span class="teacher-name">{{ text }}</span>
				</template>

				<template slot="classroom" slot-scope="text">
					<span class="classroom">{{ text }}</span>
				</template>

				<template slot="actions" slot-scope="text, record">
					<div class="table-actions">
						<a-button size="small" @click="viewSchedule(record)">
							<a-icon type="eye" />
						</a-button>
						<a-button size="small" @click="editSchedule(record)">
							<a-icon type="edit" />
						</a-button>
						<a-button size="small" type="danger" @click="deleteSchedule(record)">
							<a-icon type="delete" />
						</a-button>
					</div>
				</template>
			</a-table>
		</a-card>

		<!-- Modal para crear/editar horario -->
		<a-modal
			:title="editingSchedule ? 'Editar Horario' : 'Nuevo Horario'"
			:visible="showCreateModal"
			@ok="handleSubmit"
			@cancel="closeModal"
			:confirm-loading="saving"
			width="700px"
		>
			<a-form :form="form" :layout="'vertical'">
				<a-row :gutter="16">
					<a-col :span="12">
						<a-form-item label="Grado">
							<a-select
								v-decorator="[
									'grade',
									{ rules: [{ required: true, message: 'El grado es requerido' }] }
								]"
								placeholder="Selecciona el grado"
								style="width: 100%"
							>
								<a-select-option value="pre-kinder">Pre-Kínder</a-select-option>
								<a-select-option value="kinder">Kínder</a-select-option>
								<a-select-option value="1ro">1er Grado</a-select-option>
								<a-select-option value="2do">2do Grado</a-select-option>
								<a-select-option value="3ro">3er Grado</a-select-option>
								<a-select-option value="4to">4to Grado</a-select-option>
								<a-select-option value="5to">5to Grado</a-select-option>
								<a-select-option value="6to">6to Grado</a-select-option>
							</a-select>
						</a-form-item>
					</a-col>
					<a-col :span="12">
						<a-form-item label="Día">
							<a-select
								v-decorator="[
									'day',
									{ rules: [{ required: true, message: 'El día es requerido' }] }
								]"
								placeholder="Selecciona el día"
								style="width: 100%"
							>
								<a-select-option value="monday">Lunes</a-select-option>
								<a-select-option value="tuesday">Martes</a-select-option>
								<a-select-option value="wednesday">Miércoles</a-select-option>
								<a-select-option value="thursday">Jueves</a-select-option>
								<a-select-option value="friday">Viernes</a-select-option>
							</a-select>
						</a-form-item>
					</a-col>
				</a-row>

				<a-row :gutter="16">
					<a-col :span="8">
						<a-form-item label="Hora Inicio">
							<a-time-picker
								v-decorator="[
									'start_time',
									{ rules: [{ required: true, message: 'La hora de inicio es requerida' }] }
								]"
								placeholder="Selecciona hora de inicio"
								format="HH:mm"
								style="width: 100%"
							/>
						</a-form-item>
					</a-col>
					<a-col :span="8">
						<a-form-item label="Hora Fin">
							<a-time-picker
								v-decorator="[
									'end_time',
									{ rules: [{ required: true, message: 'La hora de fin es requerida' }] }
								]"
								placeholder="Selecciona hora de fin"
								format="HH:mm"
								style="width: 100%"
							/>
						</a-form-item>
					</a-col>
					<a-col :span="8">
						<a-form-item label="Asignatura">
							<a-select
								v-decorator="[
									'subject_id',
									{ rules: [{ required: true, message: 'La asignatura es requerida' }] }
								]"
								placeholder="Selecciona la asignatura"
								style="width: 100%"
								show-search
								:filter-option="filterSubjectOption"
							>
								<a-select-option v-for="subject in subjectsData" :key="subject.id" :value="subject.id">
									{{ subject.name }}
								</a-select-option>
							</a-select>
						</a-form-item>
					</a-col>
				</a-row>

				<a-row :gutter="16">
					<a-col :span="12">
						<a-form-item label="Docente">
							<a-select
								v-decorator="[
									'teacher_id',
									{ rules: [{ required: true, message: 'El docente es requerido' }] }
								]"
								placeholder="Selecciona el docente"
								style="width: 100%"
								show-search
								:filter-option="filterTeacherOption"
							>
								<a-select-option v-for="teacher in teachersData" :key="teacher.id" :value="teacher.id">
									{{ teacher.name }}
								</a-select-option>
							</a-select>
						</a-form-item>
					</a-col>
					<a-col :span="12">
						<a-form-item label="Aula">
							<a-input
								v-decorator="['classroom']"
								placeholder="Ej: Aula 101"
							/>
						</a-form-item>
					</a-col>
				</a-row>

				<a-form-item label="Observaciones">
					<a-textarea
						v-decorator="['observations']"
						placeholder="Observaciones adicionales sobre el horario"
						:rows="3"
					/>
				</a-form-item>
			</a-form>
		</a-modal>

		<!-- Modal para ver detalles -->
		<a-modal
			title="Detalles del Horario"
			:visible="showDetailsModal"
			@cancel="showDetailsModal = false"
			:footer="null"
			width="600px"
		>
			<div v-if="selectedSchedule" class="schedule-details">
				<a-descriptions :column="1" bordered>
					<a-descriptions-item label="Grado">
						<a-tag :color="getGradeColor(selectedSchedule.grade)">
							{{ getGradeLabel(selectedSchedule.grade) }}
						</a-tag>
					</a-descriptions-item>
					<a-descriptions-item label="Día">
						<a-tag :color="getDayColor(selectedSchedule.day)">
							{{ getDayLabel(selectedSchedule.day) }}
						</a-tag>
					</a-descriptions-item>
					<a-descriptions-item label="Hora">
						{{ selectedSchedule.start_time }} - {{ selectedSchedule.end_time }}
					</a-descriptions-item>
					<a-descriptions-item label="Asignatura">
						{{ selectedSchedule.subject_name }}
					</a-descriptions-item>
					<a-descriptions-item label="Docente">
						{{ selectedSchedule.teacher_name }}
					</a-descriptions-item>
					<a-descriptions-item label="Aula">
						{{ selectedSchedule.classroom }}
					</a-descriptions-item>
				</a-descriptions>
				
				<div class="observations-section" v-if="selectedSchedule.observations">
					<h4>Observaciones</h4>
					<div class="observations-content">
						{{ selectedSchedule.observations }}
					</div>
				</div>
			</div>
		</a-modal>
	</div>
</template>

<script>
import axios from 'axios';
import { getToken } from '@/utils/auth';

export default {
	name: 'HorariosClases',
	data() {
		return {
			loading: false,
			schedulesData: [],
			teachersData: [],
			subjectsData: [],
			searchText: '',
			gradeFilter: 'all',
			dayFilter: 'all',
			showWeekView: false,
			showCreateModal: false,
			showDetailsModal: false,
			saving: false,
			editingSchedule: null,
			selectedSchedule: null,
			form: this.$form.createForm(this, { name: 'horarios_form' }),
			pagination: {
				current: 1,
				pageSize: 10,
				total: 0,
				showSizeChanger: true,
				showQuickJumper: true,
				showTotal: (total, range) => `${range[0]}-${range[1]} de ${total} horarios`,
			},
			columns: [
				{ title: 'Grado', dataIndex: 'grade', key: 'grade', width: 120, scopedSlots: { customRender: 'grade' } },
				{ title: 'Día', dataIndex: 'day', key: 'day', width: 100, scopedSlots: { customRender: 'day' } },
				{ title: 'Hora', dataIndex: 'time_slot', key: 'time_slot', width: 100, scopedSlots: { customRender: 'time_slot' } },
				{ title: 'Asignatura', dataIndex: 'subject_name', key: 'subject', scopedSlots: { customRender: 'subject' } },
				{ title: 'Docente', dataIndex: 'teacher_name', key: 'teacher', scopedSlots: { customRender: 'teacher' } },
				{ title: 'Aula', dataIndex: 'classroom', key: 'classroom', width: 100, scopedSlots: { customRender: 'classroom' } },
				{ title: 'Acciones', key: 'actions', width: 150, scopedSlots: { customRender: 'actions' }, align: 'right' },
			],
			weekDays: [
				{ key: 'monday', label: 'Lunes' },
				{ key: 'tuesday', label: 'Martes' },
				{ key: 'wednesday', label: 'Miércoles' },
				{ key: 'thursday', label: 'Jueves' },
				{ key: 'friday', label: 'Viernes' },
			],
			timeSlots: [
				'07:00', '07:40', '08:20', '09:00', '09:40', '10:20', '11:00',
				'11:40', '12:20', '13:00', '13:40', '14:20', '15:00', '15:40'
			],
		};
	},
	computed: {
		filteredSchedules() {
			let filtered = this.schedulesData;

			// Filtrar por texto de búsqueda
			if (this.searchText && this.searchText.trim() !== '') {
				const search = this.searchText.toLowerCase();
				filtered = filtered.filter(schedule => 
					schedule.subject_name.toLowerCase().includes(search) ||
					schedule.teacher_name.toLowerCase().includes(search) ||
					schedule.classroom.toLowerCase().includes(search)
				);
			}

			// Filtrar por grado
			if (this.gradeFilter !== 'all') {
				filtered = filtered.filter(schedule => schedule.grade === this.gradeFilter);
			}

			// Filtrar por día
			if (this.dayFilter !== 'all') {
				filtered = filtered.filter(schedule => schedule.day === this.dayFilter);
			}

			return filtered;
		},
		selectedGradeLabel() {
			if (this.gradeFilter === 'all') return null;
			return this.getGradeLabel(this.gradeFilter);
		},
	},
	mounted() {
		this.fetchTeachers();
		this.fetchSubjects();
		this.fetchSchedules();
	},
	methods: {
		apiHeaders() {
			const token = getToken();
			return { Authorization: `Bearer ${token}` };
		},
		fetchTeachers() {
			axios.get('http://localhost:8000/api/teachers', { headers: this.apiHeaders() })
				.then((res) => {
					this.teachersData = res.data || [];
				})
				.catch((err) => {
					console.error('Error al obtener docentes:', err.response?.data || err);
					this.teachersData = [];
				});
		},
		fetchSubjects() {
			axios.get('http://localhost:8000/api/subjects', { headers: this.apiHeaders() })
				.then((res) => {
					this.subjectsData = res.data || [];
				})
				.catch((err) => {
					console.error('Error al obtener asignaturas:', err.response?.data || err);
					this.subjectsData = [];
				});
		},
		fetchSchedules() {
			this.loading = true;
			axios.get('http://localhost:8000/api/class-schedules', { headers: this.apiHeaders() })
				.then((res) => {
					this.schedulesData = res.data || [];
					this.pagination.total = this.schedulesData.length;
				})
				.catch((err) => {
					console.error('Error al obtener horarios:', err.response?.data || err);
					this.$message.error('No se pudieron cargar los horarios');
				})
				.finally(() => {
					this.loading = false;
				});
		},
		handleSearch() {
			this.pagination.current = 1;
		},
		handleFilter() {
			this.pagination.current = 1;
		},
		resetFilters() {
			this.searchText = '';
			this.gradeFilter = 'all';
			this.dayFilter = 'all';
			this.pagination.current = 1;
		},
		handleTableChange(pagination) {
			this.pagination = { ...this.pagination, ...pagination };
		},
		filterTeacherOption(input, option) {
			return option.componentOptions.children[0].text.toLowerCase().indexOf(input.toLowerCase()) >= 0;
		},
		filterSubjectOption(input, option) {
			return option.componentOptions.children[0].text.toLowerCase().indexOf(input.toLowerCase()) >= 0;
		},
		getGradeColor(grade) {
			const colors = {
				'pre-kinder': 'pink',
				'kinder': 'purple',
				'1ro': 'blue',
				'2do': 'cyan',
				'3ro': 'green',
				'4to': 'lime',
				'5to': 'yellow',
				'6to': 'orange',
			};
			return colors[grade] || 'default';
		},
		getGradeLabel(grade) {
			const labels = {
				'pre-kinder': 'Pre-Kínder',
				'kinder': 'Kínder',
				'1ro': '1er Grado',
				'2do': '2do Grado',
				'3ro': '3er Grado',
				'4to': '4to Grado',
				'5to': '5to Grado',
				'6to': '6to Grado',
			};
			return labels[grade] || grade;
		},
		getDayColor(day) {
			const colors = {
				monday: 'red',
				tuesday: 'orange',
				wednesday: 'yellow',
				thursday: 'green',
				friday: 'blue',
			};
			return colors[day] || 'default';
		},
		getDayLabel(day) {
			const labels = {
				monday: 'Lunes',
				tuesday: 'Martes',
				wednesday: 'Miércoles',
				thursday: 'Jueves',
				friday: 'Viernes',
			};
			return labels[day] || day;
		},
		getScheduleForDayAndHour(day, hour) {
			return this.filteredSchedules.find(schedule => 
				schedule.day === day && 
				schedule.start_time <= hour && 
				schedule.end_time > hour
			);
		},
		viewSchedule(record) {
			this.selectedSchedule = record;
			this.showDetailsModal = true;
		},
		editSchedule(record) {
			this.editingSchedule = record;
			this.showCreateModal = true;
			this.form.setFieldsValue({
				grade: record.grade,
				day: record.day,
				start_time: record.start_time,
				end_time: record.end_time,
				subject_id: record.subject_id,
				teacher_id: record.teacher_id,
				classroom: record.classroom,
				observations: record.observations,
			});
		},
		closeModal() {
			this.showCreateModal = false;
			this.saving = false;
			this.editingSchedule = null;
			this.form.resetFields();
		},
		handleSubmit() {
			this.form.validateFields((err, values) => {
				if (err) return;

				this.saving = true;
				const payload = {
					...values,
					start_time: values.start_time ? values.start_time.format('HH:mm') : null,
					end_time: values.end_time ? values.end_time.format('HH:mm') : null,
				};

				const request = this.editingSchedule
					? axios.put(`http://localhost:8000/api/class-schedules/${this.editingSchedule.id}`, payload, { headers: this.apiHeaders() })
					: axios.post('http://localhost:8000/api/class-schedules', payload, { headers: this.apiHeaders() });

				request
					.then(() => {
						this.closeModal();
						this.fetchSchedules();
						this.$message.success(this.editingSchedule ? 'Horario actualizado correctamente' : 'Horario creado correctamente');
					})
					.catch((error) => {
						const msg = error.response?.data?.message || 'No se pudo guardar el horario';
						console.error('Error guardando horario:', error.response?.data || error);
						this.$message.error(msg);
					})
					.finally(() => {
						this.saving = false;
					});
			});
		},
		deleteSchedule(record) {
			this.$confirm({
				title: 'Eliminar Horario',
				content: `¿Estás seguro de eliminar el horario de ${this.getDayLabel(record.day)} - ${record.start_time}?`,
				okText: 'Eliminar',
				okType: 'danger',
				cancelText: 'Cancelar',
				onOk: () => {
					axios.delete(`http://localhost:8000/api/class-schedules/${record.id}`, { headers: this.apiHeaders() })
						.then(() => {
							this.fetchSchedules();
							this.$message.success('Horario eliminado correctamente');
						})
						.catch((error) => {
							console.error('Error eliminando horario:', error.response?.data || error);
							this.$message.error('No se pudo eliminar el horario');
						});
				},
			});
		},
	},
};
</script>

<style lang="scss" scoped>
.layout {
	padding: 24px;
}

.horarios-card {
	.card-header {
		margin-bottom: 24px;
		
		.header-content {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			gap: 16px;
			flex-wrap: wrap;

			.header-info {
				flex: 1;
				min-width: 0;

				.page-title {
					display: flex;
					align-items: center;
					gap: 12px;
					margin: 0 0 8px 0;
					font-size: 24px;
					font-weight: 700;
					color: #1f2937;

					.icon {
						color: #7c3aed;
					}
				}

				.page-description {
					margin: 0;
					color: #6b7280;
					font-size: 14px;
					line-height: 1.5;
				}
			}

			.header-actions {
				flex-shrink: 0;
			}
		}
	}
}

.filters-section {
	margin-bottom: 24px;
	padding: 20px;
	background: #f8fafc;
	border-radius: 8px;
	border: 1px solid #e2e8f0;
}

.week-view {
	.week-header {
		margin-bottom: 20px;
		
		h3 {
			margin: 0;
			color: #1f2937;
		}
	}
	
	.week-grid {
		display: grid;
		grid-template-columns: 80px repeat(5, 1fr);
		gap: 1px;
		background: #f3f4f6;
		border: 1px solid #e5e7eb;
		border-radius: 8px;
		overflow: auto;
		
		.time-column, .day-column {
			background: white;
		}
		
		.time-header, .day-header {
			background: #7c3aed;
			color: white;
			padding: 8px;
			text-align: center;
			font-weight: 600;
			font-size: 12px;
		}
		
		.time-slot {
			padding: 8px;
			text-align: center;
			border-bottom: 1px solid #e5e7eb;
			font-size: 11px;
			color: #6b7280;
		}
		
		.schedule-cell {
			min-height: 60px;
			padding: 4px;
			border-bottom: 1px solid #e5e7eb;
		}
		
		.schedule-item {
			background: #dbeafe;
			border: 1px solid #3b82f6;
			border-radius: 4px;
			padding: 4px;
			font-size: 11px;
			
			.subject {
				font-weight: 600;
				color: #1e40af;
				margin-bottom: 2px;
			}
			
			.teacher {
				color: #6b7280;
				margin-bottom: 2px;
			}
			
			.room {
				color: #059669;
				font-size: 10px;
			}
		}
	}
}

.time-slot {
	font-weight: 600;
	color: #7c3aed;
}

.subject-name {
	font-weight: 600;
	color: #1f2937;
}

.teacher-name {
	color: #6b7280;
}

.classroom {
	background: #f3f4f6;
	padding: 2px 6px;
	border-radius: 4px;
	font-size: 12px;
}

.table-actions {
	display: flex;
	gap: 8px;
	justify-content: flex-end;
	flex-wrap: wrap;
}

.schedule-details {
	.observations-section {
		margin-top: 20px;
		
		h4 {
			margin-bottom: 12px;
			color: #374151;
		}
		
		.observations-content {
			background: #f9fafb;
			padding: 16px;
			border-radius: 8px;
			border: 1px solid #e5e7eb;
			line-height: 1.6;
		}
	}
}

// Responsive
@media (max-width: 768px) {
	.layout {
		padding: 16px;
	}

	.header-content {
		flex-direction: column;
		align-items: stretch;
	}

	.header-actions {
		width: 100%;
		
		.ant-btn {
			width: 100%;
		}
	}
	
	.week-grid {
		grid-template-columns: 60px repeat(5, 1fr);
		font-size: 10px;
	}
}

@media (max-width: 576px) {
	.filters-section {
		.ant-row {
			.ant-col {
				margin-bottom: 12px;
			}
		}
	}
}
</style>
