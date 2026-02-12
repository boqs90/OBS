<!-- Registro de Alumnos (CRUD) -->

<template>
	<div class="registro-page">
		<a-row :gutter="24" type="flex">
			<a-col :span="24" class="mb-24">
				<a-card :bordered="false" class="header-solid h-full">
					<template #title>
						<h5 class="font-semibold m-0">Registro de Alumnos</h5>
					</template>

					<StudentsTable
						:data="studentsData"
						:loading="loadingStudents"
						:columns="studentsColumns"
						:showTitle="false"
						:showAddButton="false"
						:headTight="true"
						controlsLayout="splitRow"
						@openAddStudentModal="openCreateStudent"
						@editStudent="openEditStudent"
						@deleteStudent="deleteStudent"
						@toggleStudentStatus="toggleStudentStatus"
					>
						<template #filters>
							<div style="display: flex; align-items: center; justify-content: flex-end; gap: 10px; flex-wrap: wrap;">
								<a-button class="btn-add-outline" style="width: 200px" @click="openCreateStudent">
									<svg class="btn-add-outline__icon" viewBox="0 0 24 24" aria-hidden="true">
										<path d="M12 5v14M5 12h14" fill="none" />
									</svg>
									Agregar Estudiante
								</a-button>
								<a-button type="default" style="width: 200px" @click="goToImport">
									<a-icon type="file-excel" style="color: #52c41a; margin-right: 8px;" />
									Importar desde Excel
								</a-button>
							</div>
						</template>
					</StudentsTable>

					<a-modal
						:title="editingStudent ? 'Editar Alumno' : 'Registrar Alumno'"
						:visible="showStudentModal"
						:footer="null"
						@cancel="closeStudentModal"
					>
						<StudentForm
							:initialValues="editingStudent"
							:grades="gradesOptions"
							:submitText="editingStudent ? 'Actualizar' : 'Guardar'"
							:resetAfterSubmit="!editingStudent"
							@submitForm="handleSubmitStudent"
						/>
					</a-modal>
				</a-card>
			</a-col>
		</a-row>
	</div>
</template>

<script>
import axios from 'axios';
import { getToken } from '@/utils/auth';

import StudentsTable from '@/components/Students/StudentsTable';
import StudentForm from '@/components/Students/StudentForm';

const studentsColumns = [
	{ title: 'Nombre Completo', dataIndex: 'studentName', scopedSlots: { customRender: 'studentName' }, width: 260 },
	{ title: 'Grado/Curso', dataIndex: 'gradeCourse', scopedSlots: { customRender: 'gradeCourse' }, width: 180 },
	{ title: 'Fecha de Nacimiento', dataIndex: 'birthDate', class: 'text-muted', width: 160 },
	{ title: 'Estado', dataIndex: 'enrollmentStatus', scopedSlots: { customRender: 'enrollmentStatus' }, width: 120 },
	{ title: 'Acciones', scopedSlots: { customRender: 'actions' }, width: 170, align: 'right' },
];

export default ({
	name: 'RegistroAlumnos',
	components: { StudentsTable, StudentForm },
	data() {
		return {
			loadingStudents: false,
			studentsData: [],
			studentsColumns,
			gradesOptions: [],
			showStudentModal: false,
			editingStudent: null,
		}
	},
	mounted() {
		this.fetchStudents();
		this.fetchGrades();
	},
	methods: {
		apiHeaders() {
			const authToken = getToken();
			return { Authorization: `Bearer ${authToken}` };
		},
		fetchGrades() {
			axios.get('http://localhost:8000/api/grades', { headers: this.apiHeaders() })
				.then((res) => {
					const list = (res.data || [])
						.filter((g) => String(g?.status || 'Activo') === 'Activo')
						.map((g) => g?.name)
						.filter(Boolean);
					this.gradesOptions = list.sort((a, b) => String(a).localeCompare(String(b), 'es', { sensitivity: 'base' }));
				})
				.catch((err) => {
					console.error('Error al obtener grados:', err.response?.data || err);
					this.gradesOptions = [];
				});
		},
		openCreateStudent() {
			this.editingStudent = null;
			this.showStudentModal = true;
		},
		goToImport() {
			this.$router.push('/registro/alumnos/importar');
		},
		openEditStudent(row) {
			this.editingStudent = row && row._raw ? row._raw : null;
			this.showStudentModal = true;
		},
		closeStudentModal() {
			this.showStudentModal = false;
			this.editingStudent = null;
		},
		fetchStudents() {
			this.loadingStudents = true;
			axios.get('http://localhost:8000/api/students', { headers: this.apiHeaders() })
				.then((response) => {
					this.studentsData = (response.data || []).map((student) => ({
						key: student.id,
						_raw: student,
						studentName: { name: student.fullName, email: '', avatar: student.photo_url || null },
						gradeCourse: { grade: student.gradeCourse || '', course: '' },
						birthDate: student.birthDate,
						enrollmentStatus: this.getStudentEnrollmentStatus(student),
					}));
				})
				.catch((error) => console.error('Error al obtener alumnos:', error.response?.data || error))
				.finally(() => { this.loadingStudents = false; });
		},
		handleSubmitStudent(payload) {
			const creating = !this.editingStudent;
			const url = creating
				? 'http://localhost:8000/api/students'
				: `http://localhost:8000/api/students/${this.editingStudent.id}`;

			// Enviar como multipart para incluir foto
			const form = new FormData();
			form.append('fullName', payload.fullName ?? '');
			form.append('birthDate', payload.birthDate ?? '');
			if (payload.gradeCourse && String(payload.gradeCourse).trim() !== '') {
				form.append('gradeCourse', String(payload.gradeCourse));
			}
			
			// Datos del padre/madre principal
			form.append('parent1_name', payload.parent1_name ?? '');
			form.append('parent1_identity', payload.parent1_identity ?? '');
			form.append('parent1_relationship', payload.parent1_relationship ?? '');
			form.append('parent1_phone', payload.parent1_phone ?? '');
			form.append('parent1_occupation', payload.parent1_occupation ?? '');
			form.append('parent1_email', payload.parent1_email ?? '');
			
			// Datos del padre/madre secundario
			form.append('parent2_name', payload.parent2_name ?? '');
			form.append('parent2_identity', payload.parent2_identity ?? '');
			form.append('parent2_relationship', payload.parent2_relationship ?? '');
			form.append('parent2_phone', payload.parent2_phone ?? '');
			form.append('parent2_occupation', payload.parent2_occupation ?? '');
			
			if (payload.photoFile) {
				form.append('photo', payload.photoFile);
			}
			if (!creating) {
				form.append('_method', 'PUT');
			}

			axios.post(url, form, { headers: { ...this.apiHeaders(), 'Content-Type': 'multipart/form-data' } })
				.then(() => {
					this.closeStudentModal();
					this.fetchStudents();
				})
				.catch((error) => console.error('Error guardando alumno:', error.response?.data || error));
		},
		deleteStudent(row) {
			const id = row && (row.key || row.id);
			if (!id) return;
			axios.delete(`http://localhost:8000/api/students/${id}`, { headers: this.apiHeaders() })
				.then(() => this.fetchStudents())
				.catch((error) => console.error('Error eliminando alumno:', error.response?.data || error));
		},
		toggleStudentStatus(row) {
			const id = row && (row.key || row.id);
			if (!id) return;

			const student = row._raw || row;
			const isActive = this.isStudentActive(student);
			const newStatus = isActive ? 'Inactivo' : 'Activo';

			// Mostrar confirmación
			this.$confirm({
				title: `¿Estás seguro de ${isActive ? 'desactivar' : 'activar'} este estudiante?`,
				content: isActive
					? 'El estudiante desactivado no aparecerá en otras partes del programa.'
					: 'El estudiante activado volverá a aparecer en todas las funciones del programa.',
				okText: isActive ? 'Desactivar' : 'Activar',
				cancelText: 'Cancelar',
				okType: isActive ? 'danger' : 'primary',
				onOk: () => {
					// Enviar actualización del estado
					const form = new FormData();
					form.append('status', newStatus);
					form.append('_method', 'PUT');

					axios.post(`http://localhost:8000/api/students/${id}`, form, {
						headers: { ...this.apiHeaders(), 'Content-Type': 'multipart/form-data' }
					})
						.then(() => {
							this.$message.success(`Estudiante ${newStatus.toLowerCase()} con éxito`);
							this.fetchStudents();
						})
						.catch((error) => {
							console.error('Error cambiando estado del alumno:', error.response?.data || error);
							this.$message.error('Error al cambiar el estado del estudiante');
						});
				}
			});
		},
		isStudentActive(student) {
			// Verificar si el estudiante está activo (misma lógica que en StudentsTable)
			if (student.status) {
				const status = String(student.status).trim().toLowerCase();
				return ['activo', 'active', '1', 'true', 'enabled'].includes(status);
			}
			if (student.is_active === true || student.active === true || student.enabled === true) {
				return true;
			}
			return true; // Asumir activo por defecto
		},
		getStudentEnrollmentStatus(student) {
			// Verificar si el estudiante tiene matrícula activa
			// Esto podría venir de diferentes formas según la API:
			// 1. Campo directo: student.enrollmentStatus
			// 2. Array de matrículas: student.enrollments
			// 3. Campo booleano: student.hasActiveEnrollment

			
			// Opción 1: Si tiene campo directo de matrícula
			if (student.enrollmentStatus) {
				const status = String(student.enrollmentStatus).trim().toLowerCase();
				if (['activo', 'active', '1', 'true'].includes(status)) {
					return 'Matriculado';
				}
			}
			
			// Opción 2: Si tiene array de matrículas
			if (student.enrollments && Array.isArray(student.enrollments)) {
				const activeEnrollments = student.enrollments.filter(enrollment => 
					enrollment.status === 'Activo' || enrollment.status === 'Active'
				);
				if (activeEnrollments.length > 0) {
					return 'Matriculado';
				}
			}
			
			// Opción 3: Si tiene campo booleano
			if (student.hasActiveEnrollment === true || student.is_enrolled === true) {
				return 'Matriculado';
			}
			
			// Por defecto: sin matrícula
			return 'Sin matrícula';
		},
	},
})
</script>

