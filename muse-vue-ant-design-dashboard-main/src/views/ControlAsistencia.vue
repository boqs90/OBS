<template>
	<div class="layout">
		<a-card :bordered="false">
			<div class="header">
				<h2 class="title">Control de Asistencia</h2>
				<p class="subtitle">Registro y seguimiento de asistencia de estudiantes por clase</p>
			</div>

			<!-- Filtros -->
			<div class="filters-section">
				<a-row :gutter="16">
					<a-col :xs="24" :md="6">
						<a-form-item label="Grado">
							<a-select v-model="filters.grade" @change="onGradeChange" placeholder="Seleccione grado">
								<a-select-option v-for="grade in grades" :key="grade" :value="grade">
									{{ grade }}
								</a-select-option>
							</a-select>
						</a-form-item>
					</a-col>
					<a-col :xs="24" :md="6">
						<a-form-item label="Asignatura">
							<a-select v-model="filters.subject" @change="onSubjectChange" placeholder="Seleccione asignatura">
								<a-select-option v-for="subject in subjects" :key="subject.id" :value="subject.id">
									{{ subject.name }}
								</a-select-option>
							</a-select>
						</a-form-item>
					</a-col>
					<a-col :xs="24" :md="6">
						<a-form-item label="Fecha">
							<a-date-picker 
								v-model="filters.date" 
								@change="onDateChange"
								placeholder="Seleccione fecha"
								style="width: 100%"
							/>
						</a-form-item>
					</a-col>
					<a-col :xs="24" :md="6">
						<a-form-item label="&nbsp;">
							<a-button type="primary" @click="fetchAttendanceData" :loading="loading">
								<a-icon type="search" /> Buscar
							</a-button>
						</a-form-item>
					</a-col>
				</a-row>
			</div>

			<!-- Botones de acción -->
			<div class="actions-section">
				<a-space>
					<a-button type="primary" @click="showAttendanceModal" :disabled="!filters.grade || !filters.subject || !filters.date">
						<a-icon type="plus" /> Tomar Asistencia
					</a-button>
					<a-button @click="exportAttendance" :disabled="attendanceData.length === 0">
						<a-icon type="download" /> Exportar
					</a-button>
					<a-button @click="showStatistics" :disabled="attendanceData.length === 0">
						<a-icon type="bar-chart" /> Estadísticas
					</a-button>
				</a-space>
			</div>

			<!-- Tabla de asistencia -->
			<a-table
				:columns="columns"
				:data-source="attendanceData"
				:loading="loading"
				:pagination="false"
				:scroll="{ x: 800 }"
				row-key="student_id"
			>
				<!-- Columna de Foto -->
				<template #photo="text, record">
					<a-avatar :src="record.photo" :size="40">
						{{ record.first_name.charAt(0) }}{{ record.last_name.charAt(0) }}
					</a-avatar>
				</template>

				<!-- Columna de Nombre -->
				<template #name="text, record">
					<div>
						<div class="student-name">{{ record.first_name }} {{ record.last_name }}</div>
						<div class="student-id">ID: {{ record.student_id }}</div>
					</div>
				</template>

				<!-- Columna de Estado -->
				<template #status="text, record">
					<a-select 
						v-model="record.status" 
						@change="updateAttendanceStatus(record)"
						:disabled="!canEditAttendance"
						style="width: 120px"
					>
						<a-select-option value="present">
							<a-tag color="green">Presente</a-tag>
						</a-select-option>
						<a-select-option value="late">
							<a-tag color="orange">Tarde</a-tag>
						</a-select-option>
						<a-select-option value="absent">
							<a-tag color="red">Ausente</a-tag>
						</a-select-option>
						<a-select-option value="excused">
							<a-tag color="blue">Justificado</a-tag>
						</a-select-option>
					</a-select>
				</template>

				<!-- Columna de Hora de llegada -->
				<template #arrival_time="text, record">
					<a-time-picker 
						v-model="record.arrival_time" 
						@change="updateArrivalTime(record)"
						:disabled="!canEditAttendance || record.status !== 'present' && record.status !== 'late'"
						format="HH:mm"
						placeholder="HH:mm"
						style="width: 100px"
					/>
				</template>

				<!-- Columna de Observaciones -->
				<template #observations="text, record">
					<a-input 
						v-model="record.observations" 
						@blur="updateObservations(record)"
						:disabled="!canEditAttendance"
						placeholder="Observaciones"
						style="width: 200px"
					/>
				</template>
			</a-table>

			<!-- Resumen de asistencia -->
			<div class="attendance-summary" v-if="attendanceData.length > 0">
				<a-row :gutter="16">
					<a-col :span="6">
						<a-statistic title="Presentes" :value="attendanceSummary.present" :value-style="{ color: '#3f8600' }" />
					</a-col>
					<a-col :span="6">
						<a-statistic title="Tardes" :value="attendanceSummary.late" :value-style="{ color: '#fa8c16' }" />
					</a-col>
					<a-col :span="6">
						<a-statistic title="Ausentes" :value="attendanceSummary.absent" :value-style="{ color: '#cf1322' }" />
					</a-col>
					<a-col :span="6">
						<a-statistic title="Justificados" :value="attendanceSummary.excused" :value-style="{ color: '#1890ff' }" />
					</a-col>
				</a-row>
			</div>
		</a-card>

		<!-- Modal de tomar asistencia -->
		<a-modal
			title="Tomar Asistencia"
			:visible="attendanceModalVisible"
			@ok="saveAttendance"
			@cancel="closeAttendanceModal"
			:confirmLoading="saving"
			width="900px"
		>
			<div class="attendance-modal-content">
				<a-row :gutter="16" style="margin-bottom: 16px;">
					<a-col :span="8">
						<strong>Grado:</strong> {{ filters.grade }}
					</a-col>
					<a-col :span="8">
						<strong>Asignatura:</strong> {{ getSubjectName(filters.subject) }}
					</a-col>
					<a-col :span="8">
						<strong>Fecha:</strong> {{ formatDate(filters.date) }}
					</a-col>
				</a-row>

				<a-table
					:columns="modalColumns"
					:data-source="modalStudents"
					:pagination="false"
					:scroll="{ y: 400 }"
					row-key="student_id"
					size="small"
				>
					<!-- Checkbox de selección rápida -->
					<template #selection="text, record">
						<a-checkbox 
							v-model="record.selected" 
							@change="quickSelect(record)"
						/>
					</template>

					<!-- Foto y nombre -->
					<template #student_info="text, record">
						<div style="display: flex; align-items: center;">
							<a-avatar :src="record.photo" :size="32" style="margin-right: 8px;">
								{{ record.first_name.charAt(0) }}{{ record.last_name.charAt(0) }}
							</a-avatar>
							<div>
								<div>{{ record.first_name }} {{ record.last_name }}</div>
								<div style="font-size: 12px; color: #666;">ID: {{ record.student_id }}</div>
							</div>
						</div>
					</template>

					<!-- Estado de asistencia -->
					<template #status="text, record">
						<a-radio-group v-model="record.status" size="small">
							<a-radio value="present">P</a-radio>
							<a-radio value="late">T</a-radio>
							<a-radio value="absent">A</a-radio>
							<a-radio value="excused">J</a-radio>
						</a-radio-group>
					</template>

					<!-- Hora de llegada -->
					<template #arrival_time="text, record">
						<a-time-picker 
							v-model="record.arrival_time" 
							format="HH:mm"
							placeholder="HH:mm"
							size="small"
							:disabled="record.status !== 'present' && record.status !== 'late'"
						/>
					</template>

					<!-- Observaciones -->
					<template #observations="text, record">
						<a-input 
							v-model="record.observations" 
							placeholder="Obs."
							size="small"
							style="width: 120px"
						/>
					</template>
				</a-table>

				<!-- Botones de selección rápida -->
				<div style="margin-top: 16px;">
					<a-space>
						<a-button size="small" @click="selectAllPresent">Marcar todos presentes</a-button>
						<a-button size="small" @click="selectAllAbsent">Marcar todos ausentes</a-button>
						<a-button size="small" @click="clearSelection">Limpiar selección</a-button>
					</a-space>
				</div>
			</div>
		</a-modal>

		<!-- Modal de estadísticas -->
		<a-modal
			title="Estadísticas de Asistencia"
			:visible="statisticsModalVisible"
			@cancel="closeStatisticsModal"
			:footer="null"
			width="800px"
		>
			<div class="statistics-content">
				<a-row :gutter="16">
					<a-col :span="12">
						<a-card title="Resumen del Día">
							<a-row :gutter="16">
								<a-col :span="12">
									<a-statistic title="Total Estudiantes" :value="attendanceData.length" />
								</a-col>
								<a-col :span="12">
									<a-statistic title="Asistencia" :value="attendancePercentage" suffix="%" />
								</a-col>
							</a-row>
						</a-card>
					</a-col>
					<a-col :span="12">
						<a-card title="Distribución">
							<div style="margin: 16px 0;">
								<div style="margin-bottom: 8px;">
									<span>Presentes: {{ attendanceSummary.present }}</span>
									<a-progress :percent="getPercentage('present')" :show-info="false" stroke-color="#52c41a" style="width: 200px;" />
								</div>
								<div style="margin-bottom: 8px;">
									<span>Tardes: {{ attendanceSummary.late }}</span>
									<a-progress :percent="getPercentage('late')" :show-info="false" stroke-color="#fa8c16" style="width: 200px;" />
								</div>
								<div style="margin-bottom: 8px;">
									<span>Ausentes: {{ attendanceSummary.absent }}</span>
									<a-progress :percent="getPercentage('absent')" :show-info="false" stroke-color="#f5222d" style="width: 200px;" />
								</div>
								<div>
									<span>Justificados: {{ attendanceSummary.excused }}</span>
									<a-progress :percent="getPercentage('excused')" :show-info="false" stroke-color="#1890ff" style="width: 200px;" />
								</div>
							</div>
						</a-card>
					</a-col>
				</a-row>
			</div>
		</a-modal>
	</div>
</template>

<script>
import axios from 'axios';
import { getToken } from '@/utils/auth';
import moment from 'moment';

export default {
	name: 'ControlAsistencia',
	data() {
		return {
			loading: false,
			saving: false,
			attendanceModalVisible: false,
			statisticsModalVisible: false,
			canEditAttendance: true,
			filters: {
				grade: null,
				subject: null,
				date: moment(),
			},
			grades: [],
			subjects: [],
			attendanceData: [],
			modalStudents: [],
			columns: [
				{
					title: 'Foto',
					dataIndex: 'photo',
					key: 'photo',
					scopedSlots: { customRender: 'photo' },
					width: 80,
				},
				{
					title: 'Estudiante',
					key: 'name',
					scopedSlots: { customRender: 'name' },
					width: 200,
				},
				{
					title: 'Estado',
					key: 'status',
					scopedSlots: { customRender: 'status' },
					width: 140,
				},
				{
					title: 'Hora Llegada',
					key: 'arrival_time',
					scopedSlots: { customRender: 'arrival_time' },
					width: 120,
				},
				{
					title: 'Observaciones',
					key: 'observations',
					scopedSlots: { customRender: 'observations' },
					width: 220,
				},
			],
			modalColumns: [
				{
					title: '',
					key: 'selection',
					scopedSlots: { customRender: 'selection' },
					width: 50,
				},
				{
					title: 'Estudiante',
					key: 'student_info',
					scopedSlots: { customRender: 'student_info' },
					width: 250,
				},
				{
					title: 'Estado',
					key: 'status',
					scopedSlots: { customRender: 'status' },
					width: 150,
				},
				{
					title: 'Hora',
					key: 'arrival_time',
					scopedSlots: { customRender: 'arrival_time' },
					width: 120,
				},
				{
					title: 'Obs.',
					key: 'observations',
					scopedSlots: { customRender: 'observations' },
					width: 140,
				},
			],
		};
	},
	computed: {
		attendanceSummary() {
			const summary = {
				present: 0,
				late: 0,
				absent: 0,
				excused: 0,
			};
			
			this.attendanceData.forEach(student => {
				summary[student.status]++;
			});
			
			return summary;
		},
		attendancePercentage() {
			if (this.attendanceData.length === 0) return 0;
			const presentAndLate = this.attendanceSummary.present + this.attendanceSummary.late;
			return Math.round((presentAndLate / this.attendanceData.length) * 100);
		},
	},
	mounted() {
		this.fetchGrades();
		this.fetchSubjects();
	},
	methods: {
		apiHeaders() {
			const token = getToken();
			return { Authorization: `Bearer ${token}` };
		},
		fetchGrades() {
			axios
				.get('http://localhost:8000/api/grades', { headers: this.apiHeaders() })
				.then((res) => {
					this.grades = res.data || [];
				})
				.catch((err) => {
					console.error('Error cargando grados:', err);
					this.$message.error('No se pudieron cargar los grados');
				});
		},
		fetchSubjects() {
			axios
				.get('http://localhost:8000/api/subjects', { headers: this.apiHeaders() })
				.then((res) => {
					this.subjects = res.data || [];
				})
				.catch((err) => {
					console.error('Error cargando asignaturas:', err);
					this.$message.error('No se pudieron cargar las asignaturas');
				});
		},
		fetchAttendanceData() {
			if (!this.filters.grade || !this.filters.subject || !this.filters.date) {
				this.$message.warning('Seleccione todos los filtros para buscar');
				return;
			}

			this.loading = true;
			const params = {
				grade: this.filters.grade,
				subject_id: this.filters.subject,
				date: this.filters.date.format('YYYY-MM-DD'),
			};

			axios
				.get('http://localhost:8000/api/attendance', { 
					headers: this.apiHeaders(),
					params 
				})
				.then((res) => {
					this.attendanceData = res.data.map(student => ({
						...student,
						arrival_time: student.arrival_time ? moment(student.arrival_time, 'HH:mm') : null,
					}));
				})
				.catch((err) => {
					console.error('Error cargando asistencia:', err);
					this.$message.error('No se pudo cargar la asistencia');
				})
				.finally(() => {
					this.loading = false;
				});
		},
		onGradeChange() {
			this.filters.subject = null;
			this.attendanceData = [];
		},
		onSubjectChange() {
			this.attendanceData = [];
		},
		onDateChange() {
			this.attendanceData = [];
		},
		showAttendanceModal() {
			// Obtener lista de estudiantes para el grado seleccionado
			axios
				.get(`http://localhost:8000/api/students/by-grade/${this.filters.grade}`, { 
					headers: this.apiHeaders() 
				})
				.then((res) => {
					this.modalStudents = res.data.map(student => ({
						...student,
						status: 'present',
						arrival_time: null,
						observations: '',
						selected: true,
					}));
					this.attendanceModalVisible = true;
				})
				.catch((err) => {
					console.error('Error cargando estudiantes:', err);
					this.$message.error('No se pudieron cargar los estudiantes');
				});
		},
		closeAttendanceModal() {
			this.attendanceModalVisible = false;
			this.modalStudents = [];
		},
		saveAttendance() {
			this.saving = true;
			const attendanceData = this.modalStudents.map(student => ({
				student_id: student.id,
				grade: this.filters.grade,
				subject_id: this.filters.subject,
				date: this.filters.date.format('YYYY-MM-DD'),
				status: student.status,
				arrival_time: student.arrival_time ? student.arrival_time.format('HH:mm') : null,
				observations: student.observations,
			}));

			axios
				.post('http://localhost:8000/api/attendance/batch', attendanceData, { headers: this.apiHeaders() })
				.then(() => {
					this.$message.success('Asistencia guardada correctamente');
					this.closeAttendanceModal();
					this.fetchAttendanceData();
				})
				.catch((err) => {
					console.error('Error guardando asistencia:', err);
					this.$message.error('No se pudo guardar la asistencia');
				})
				.finally(() => {
					this.saving = false;
				});
		},
		updateAttendanceStatus(record) {
			axios
				.put(`http://localhost:8000/api/attendance/${record.id}`, {
					status: record.status,
				}, { headers: this.apiHeaders() })
				.catch((err) => {
					console.error('Error actualizando estado:', err);
					this.$message.error('No se pudo actualizar el estado');
				});
		},
		updateArrivalTime(record) {
			axios
				.put(`http://localhost:8000/api/attendance/${record.id}`, {
					arrival_time: record.arrival_time ? record.arrival_time.format('HH:mm') : null,
				}, { headers: this.apiHeaders() })
				.catch((err) => {
					console.error('Error actualizando hora:', err);
					this.$message.error('No se pudo actualizar la hora');
				});
		},
		updateObservations(record) {
			axios
				.put(`http://localhost:8000/api/attendance/${record.id}`, {
					observations: record.observations,
				}, { headers: this.apiHeaders() })
				.catch((err) => {
					console.error('Error actualizando observaciones:', err);
					this.$message.error('No se pudieron actualizar las observaciones');
				});
		},
		quickSelect(record) {
			if (record.selected) {
				record.status = 'present';
			}
		},
		selectAllPresent() {
			this.modalStudents.forEach(student => {
				student.selected = true;
				student.status = 'present';
			});
		},
		selectAllAbsent() {
			this.modalStudents.forEach(student => {
				student.selected = false;
				student.status = 'absent';
			});
		},
		clearSelection() {
			this.modalStudents.forEach(student => {
				student.selected = false;
				student.status = 'present';
			});
		},
		showStatistics() {
			this.statisticsModalVisible = true;
		},
		closeStatisticsModal() {
			this.statisticsModalVisible = false;
		},
		exportAttendance() {
			const data = this.attendanceData.map(student => ({
				'ID Estudiante': student.student_id,
				'Nombre': `${student.first_name} ${student.last_name}`,
				'Grado': student.grade,
				'Fecha': this.filters.date.format('YYYY-MM-DD'),
				'Estado': this.getStatusLabel(student.status),
				'Hora Llegada': student.arrival_time ? student.arrival_time.format('HH:mm') : '',
				'Observaciones': student.observations || '',
			}));

			// Aquí implementarías la exportación a Excel/CSV
			this.$message.info('Función de exportación en desarrollo');
		},
		getSubjectName(subjectId) {
			const subject = this.subjects.find(s => s.id === subjectId);
			return subject ? subject.name : '';
		},
		getStatusLabel(status) {
			const labels = {
				present: 'Presente',
				late: 'Tarde',
				absent: 'Ausente',
				excused: 'Justificado',
			};
			return labels[status] || status;
		},
		getPercentage(status) {
			if (this.attendanceData.length === 0) return 0;
			return Math.round((this.attendanceSummary[status] / this.attendanceData.length) * 100);
		},
		formatDate(date) {
			return date ? date.format('DD/MM/YYYY') : '';
		},
	},
};
</script>

<style lang="scss" scoped>
.layout {
	padding: 24px;
}

.header {
	margin-bottom: 24px;

	.title {
		margin: 0;
		font-size: 22px;
		font-weight: 700;
		color: #111827;
	}

	.subtitle {
		margin: 6px 0 0 0;
		color: #6b7280;
	}
}

.filters-section {
	margin-bottom: 16px;
	padding: 16px;
	background: #f9fafb;
	border-radius: 8px;
}

.actions-section {
	margin-bottom: 16px;
}

.student-name {
	font-weight: 500;
	color: #111827;
}

.student-id {
	font-size: 12px;
	color: #6b7280;
}

.attendance-summary {
	margin-top: 24px;
	padding: 16px;
	background: #f9fafb;
	border-radius: 8px;
}

.attendance-modal-content {
	max-height: 600px;
	overflow-y: auto;
}

.statistics-content {
	padding: 16px 0;
}
</style>
