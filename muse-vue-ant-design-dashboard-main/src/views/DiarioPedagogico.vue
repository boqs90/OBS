<template>
	<div class="layout">
		<a-card :bordered="false">
			<div class="header">
				<h2 class="title">Diario Pedagógico</h2>
				<p class="subtitle">Registro diario de actividades y observaciones pedagógicas por clase</p>
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
					<a-col :xs="24" :md="8">
						<a-form-item label="Fecha">
							<a-date-picker 
								v-model="filters.date" 
								@change="onDateChange"
								placeholder="Seleccione fecha"
								style="width: 100%"
							/>
						</a-form-item>
					</a-col>
					<a-col :xs="24" :md="4">
						<a-form-item label="&nbsp;">
							<a-button type="primary" @click="fetchDiaryEntries" :loading="loading">
								<a-icon type="search" /> Buscar
							</a-button>
						</a-form-item>
					</a-col>
				</a-row>
			</div>

			<!-- Botón de nuevo registro -->
			<div class="actions-section">
				<a-button type="primary" @click="showAddModal" :disabled="!filters.grade || !filters.subject || !filters.date">
					<a-icon type="plus" /> Nuevo Registro
				</a-button>
			</div>

			<!-- Tabla de registros -->
			<a-table
				:columns="columns"
				:data-source="diaryEntries"
				:loading="loading"
				:pagination="pagination"
				@change="handleTableChange"
				row-key="id"
			>
				<!-- Columna de Fecha -->
				<template #date="text">
					{{ formatDate(text) }}
				</template>

				<!-- Columna de Tema -->
				<template #topic="text">
					<span class="topic-text">{{ text || 'Sin tema' }}</span>
				</template>

				<!-- Columna de Actividades -->
				<template #activities="text">
					<a-tooltip :title="text">
						<span class="activities-text">{{ truncateText(text, 50) }}</span>
					</a-tooltip>
				</template>

				<!-- Columna de Observaciones -->
				<template #observations="text">
					<a-tooltip :title="text">
						<span class="observations-text">{{ truncateText(text, 50) }}</span>
					</a-tooltip>
				</template>

				<!-- Columna de Acciones -->
				<template #actions="text, record">
					<a-button size="small" @click="editEntry(record)">
						<a-icon type="edit" />
					</a-button>
					<a-popconfirm
						title="¿Eliminar este registro?"
						ok-text="Sí"
						cancel-text="No"
						@confirm="deleteEntry(record)"
					>
						<a-button size="small" type="danger">
							<a-icon type="delete" />
						</a-button>
					</a-popconfirm>
				</template>
			</a-table>
		</a-card>

		<!-- Modal de agregar/editar -->
		<a-modal
			:title="editingEntry ? 'Editar Registro' : 'Nuevo Registro'"
			:visible="modalVisible"
			@ok="handleSubmit"
			@cancel="closeModal"
			:confirmLoading="saving"
			width="800px"
		>
			<a-form :form="form" layout="vertical">
				<a-row :gutter="16">
					<a-col :span="12">
						<a-form-item label="Grado">
							<a-select 
								v-decorator="['grade', { rules: [{ required: true, message: 'El grado es requerido' }] }]"
								placeholder="Seleccione grado"
							>
								<a-select-option v-for="grade in grades" :key="grade" :value="grade">
									{{ grade }}
								</a-select-option>
							</a-select>
						</a-form-item>
					</a-col>
					<a-col :span="12">
						<a-form-item label="Asignatura">
							<a-select 
								v-decorator="['subject_id', { rules: [{ required: true, message: 'La asignatura es requerida' }] }]"
								placeholder="Seleccione asignatura"
							>
								<a-select-option v-for="subject in subjects" :key="subject.id" :value="subject.id">
									{{ subject.name }}
								</a-select-option>
							</a-select>
						</a-form-item>
					</a-col>
				</a-row>

				<a-row :gutter="16">
					<a-col :span="12">
						<a-form-item label="Fecha">
							<a-date-picker 
								v-decorator="['date', { rules: [{ required: true, message: 'La fecha es requerida' }] }]"
								placeholder="Seleccione fecha"
								style="width: 100%"
							/>
						</a-form-item>
					</a-col>
					<a-col :span="12">
						<a-form-item label="Hora de inicio">
							<a-time-picker 
								v-decorator="['start_time', { rules: [{ required: true, message: 'La hora de inicio es requerida' }] }]"
								placeholder="Seleccione hora"
								format="HH:mm"
								style="width: 100%"
							/>
						</a-form-item>
					</a-col>
				</a-row>

				<a-row :gutter="16">
					<a-col :span="12">
						<a-form-item label="Hora de fin">
							<a-time-picker 
								v-decorator="['end_time', { rules: [{ required: true, message: 'La hora de fin es requerida' }] }]"
								placeholder="Seleccione hora"
								format="HH:mm"
								style="width: 100%"
							/>
						</a-form-item>
					</a-col>
					<a-col :span="12">
						<a-form-item label="Número de estudiantes">
							<a-input-number 
								v-decorator="['student_count', { rules: [{ required: true, message: 'El número de estudiantes es requerido' }] }]"
								:min="0"
								style="width: 100%"
							/>
						</a-form-item>
					</a-col>
				</a-row>

				<a-form-item label="Tema de la clase">
					<a-input 
						v-decorator="['topic', { rules: [{ required: true, message: 'El tema es requerido' }] }]"
						placeholder="Ingrese el tema tratado"
					/>
				</a-form-item>

				<a-form-item label="Actividades realizadas">
					<a-textarea 
						v-decorator="['activities', { rules: [{ required: true, message: 'Las actividades son requeridas' }] }]"
						:rows="4"
						placeholder="Describa las actividades realizadas durante la clase"
					/>
				</a-form-item>

				<a-form-item label="Observaciones pedagógicas">
					<a-textarea 
						v-decorator="['observations']"
						:rows="4"
						placeholder="Observaciones sobre el desarrollo de la clase, participación de los estudiantes, dificultades, etc."
					/>
				</a-form-item>

				<a-form-item label="Recursos utilizados">
					<a-textarea 
						v-decorator="['resources']"
						:rows="3"
						placeholder="Materiales y recursos utilizados (libros, videos, materiales didácticos, etc.)"
					/>
				</a-form-item>

				<a-form-item label="Evaluación formativa">
					<a-textarea 
						v-decorator="['assessment']"
						:rows="3"
						placeholder="Cómo se evaluó el aprendizaje de los estudiantes"
					/>
				</a-form-item>

				<a-form-item label="Tareas asignadas">
					<a-textarea 
						v-decorator="['homework']"
						:rows="2"
						placeholder="Tareas o actividades para realizar en casa"
					/>
				</a-form-item>
			</a-form>
		</a-modal>
	</div>
</template>

<script>
import axios from 'axios';
import { getToken } from '@/utils/auth';
import moment from 'moment';

export default {
	name: 'DiarioPedagogico',
	data() {
		return {
			loading: false,
			saving: false,
			modalVisible: false,
			editingEntry: null,
			form: this.$form.createForm(this, { name: 'diary_form' }),
			filters: {
				grade: null,
				subject: null,
				date: moment(),
			},
			grades: [],
			subjects: [],
			diaryEntries: [],
			pagination: {
				current: 1,
				pageSize: 10,
				total: 0,
			},
			columns: [
				{
					title: 'Fecha',
					dataIndex: 'date',
					key: 'date',
					scopedSlots: { customRender: 'date' },
					width: 100,
				},
				{
					title: 'Grado',
					dataIndex: 'grade',
					key: 'grade',
					width: 80,
				},
				{
					title: 'Asignatura',
					dataIndex: 'subject_name',
					key: 'subject_name',
					width: 120,
				},
				{
					title: 'Tema',
					dataIndex: 'topic',
					key: 'topic',
					scopedSlots: { customRender: 'topic' },
				},
				{
					title: 'Actividades',
					dataIndex: 'activities',
					key: 'activities',
					scopedSlots: { customRender: 'activities' },
				},
				{
					title: 'Observaciones',
					dataIndex: 'observations',
					key: 'observations',
					scopedSlots: { customRender: 'observations' },
				},
				{
					title: 'Acciones',
					key: 'actions',
					scopedSlots: { customRender: 'actions' },
					width: 120,
				},
			],
		};
	},
	mounted() {
		this.fetchGrades();
		this.fetchSubjects();
		this.fetchDiaryEntries();
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
		fetchDiaryEntries() {
			if (!this.filters.grade || !this.filters.subject || !this.filters.date) {
				this.$message.warning('Seleccione todos los filtros para buscar');
				return;
			}

			this.loading = true;
			const params = {
				grade: this.filters.grade,
				subject_id: this.filters.subject,
				date: this.filters.date.format('YYYY-MM-DD'),
				page: this.pagination.current,
				pageSize: this.pagination.pageSize,
			};

			axios
				.get('http://localhost:8000/api/diary-pedagogico', { 
					headers: this.apiHeaders(),
					params 
				})
				.then((res) => {
					this.diaryEntries = res.data.data || [];
					this.pagination.total = res.data.total || 0;
				})
				.catch((err) => {
					console.error('Error cargando diario pedagógico:', err);
					this.$message.error('No se pudo cargar el diario pedagógico');
				})
				.finally(() => {
					this.loading = false;
				});
		},
		onGradeChange() {
			this.filters.subject = null;
			this.diaryEntries = [];
		},
		onSubjectChange() {
			this.diaryEntries = [];
		},
		onDateChange() {
			this.diaryEntries = [];
		},
		showAddModal() {
			this.editingEntry = null;
			this.modalVisible = true;
			this.$nextTick(() => {
				this.form.setFieldsValue({
					grade: this.filters.grade,
					subject_id: this.filters.subject,
					date: this.filters.date,
				});
			});
		},
		editEntry(record) {
			this.editingEntry = record;
			this.modalVisible = true;
			this.$nextTick(() => {
				this.form.setFieldsValue({
					grade: record.grade,
					subject_id: record.subject_id,
					date: moment(record.date),
					start_time: moment(record.start_time, 'HH:mm'),
					end_time: moment(record.end_time, 'HH:mm'),
					student_count: record.student_count,
					topic: record.topic,
					activities: record.activities,
					observations: record.observations,
					resources: record.resources,
					assessment: record.assessment,
					homework: record.homework,
				});
			});
		},
		closeModal() {
			this.modalVisible = false;
			this.editingEntry = null;
			this.form.resetFields();
		},
		handleSubmit() {
			this.form.validateFields((err, values) => {
				if (err) return;

				this.saving = true;
				const data = {
					...values,
					date: values.date.format('YYYY-MM-DD'),
					start_time: values.start_time.format('HH:mm'),
					end_time: values.end_time.format('HH:mm'),
				};

				const request = this.editingEntry
					? axios.put(`http://localhost:8000/api/diary-pedagogico/${this.editingEntry.id}`, data, { headers: this.apiHeaders() })
					: axios.post('http://localhost:8000/api/diary-pedagogico', data, { headers: this.apiHeaders() });

				request
					.then(() => {
						this.$message.success(this.editingEntry ? 'Registro actualizado' : 'Registro creado');
						this.closeModal();
						this.fetchDiaryEntries();
					})
					.catch((err) => {
						console.error('Error guardando registro:', err);
						this.$message.error('No se pudo guardar el registro');
					})
					.finally(() => {
						this.saving = false;
					});
			});
		},
		deleteEntry(record) {
			axios
				.delete(`http://localhost:8000/api/diary-pedagogico/${record.id}`, { headers: this.apiHeaders() })
				.then(() => {
					this.$message.success('Registro eliminado');
					this.fetchDiaryEntries();
				})
				.catch((err) => {
					console.error('Error eliminando registro:', err);
					this.$message.error('No se pudo eliminar el registro');
				});
		},
		handleTableChange(pagination) {
			this.pagination.current = pagination.current;
			this.pagination.pageSize = pagination.pageSize;
			this.fetchDiaryEntries();
		},
		formatDate(date) {
			return moment(date).format('DD/MM/YYYY');
		},
		truncateText(text, maxLength) {
			if (!text) return '';
			return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
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

.topic-text {
	font-weight: 500;
	color: #111827;
}

.activities-text,
.observations-text {
	color: #6b7280;
	font-style: italic;
}
</style>
