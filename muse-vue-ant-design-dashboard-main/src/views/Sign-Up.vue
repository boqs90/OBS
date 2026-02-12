<!--
	Página de Registro (Alumnos / Maestros).
	Usa Ant Design + API Laravel.
-->

<template>
	<div class="registro-page">
		<a-row :gutter="24" type="flex">
			<a-col :span="24" class="mb-24">
				<a-card :bordered="false" class="header-solid h-full">
					<template #title>
						<h5 class="font-semibold m-0">Registro</h5>
					</template>

					<a-tabs v-model="activeTab">
						<a-tab-pane key="students" tab="Alumnos">
							<StudentsTable
								:data="studentsData"
								:loading="loadingStudents"
								:columns="studentsColumns"
								@openAddStudentModal="openCreateStudent"
								@editStudent="openEditStudent"
								@deleteStudent="deleteStudent"
							/>

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
						</a-tab-pane>

						<a-tab-pane key="teachers" tab="Maestros">
							<TeachersTable
								:data="teachersData"
								:loading="loadingTeachers"
								:columns="teachersColumns"
								@openAddTeacherModal="openCreateTeacher"
								@editTeacher="openEditTeacher"
								@deleteTeacher="deleteTeacher"
							/>

							<a-modal
								:title="editingTeacher ? 'Editar Maestro' : 'Registrar Maestro'"
								:visible="showTeacherModal"
								:footer="null"
								@cancel="closeTeacherModal"
							>
								<TeacherForm
									:initialValues="editingTeacher"
									:submitText="editingTeacher ? 'Actualizar' : 'Guardar'"
									:resetAfterSubmit="!editingTeacher"
									@submitForm="handleSubmitTeacher"
								/>
							</a-modal>
						</a-tab-pane>
					</a-tabs>
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

import TeachersTable from '@/components/Teachers/TeachersTable';
import TeacherForm from '@/components/Teachers/TeacherForm';

const studentsColumns = [
	{ title: 'Nombre Completo', dataIndex: 'studentName', scopedSlots: { customRender: 'studentName' }, width: 260 },
	{ title: 'Grado/Curso', dataIndex: 'gradeCourse', scopedSlots: { customRender: 'gradeCourse' }, width: 180 },
	{ title: 'Fecha de Nacimiento', dataIndex: 'birthDate', class: 'text-muted', width: 160 },
	{ title: 'Estado de Matrícula', dataIndex: 'enrollmentStatus', scopedSlots: { customRender: 'enrollmentStatus' }, width: 170 },
	{ title: 'Acciones', scopedSlots: { customRender: 'actions' }, width: 170, align: 'right' },
];

const teachersColumns = [
	{ title: 'Nombre', dataIndex: 'teacherName', scopedSlots: { customRender: 'teacherName' }, width: 320 },
	{ title: 'Teléfono', dataIndex: 'phone', class: 'text-muted', width: 160 },
	{ title: 'Especialidad', dataIndex: 'specialty', class: 'text-muted', width: 220 },
	{ title: 'Estado', dataIndex: 'status', scopedSlots: { customRender: 'status' }, width: 140 },
	{ title: 'Acciones', scopedSlots: { customRender: 'actions' }, width: 170, align: 'right' },
];

export default ({
	name: 'Registro',
	components: {
		StudentsTable,
		StudentForm,
		TeachersTable,
		TeacherForm,
	},
	data() {
		return {
			activeTab: 'students',

			loadingStudents: false,
			studentsData: [],
			studentsColumns,
			showStudentModal: false,
			editingStudent: null,
			gradesOptions: [],

			loadingTeachers: false,
			teachersData: [],
			teachersColumns,
			showTeacherModal: false,
			editingTeacher: null,
		}
	},
	mounted() {
		this.fetchStudents();
		this.fetchTeachers();
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

		// ---- ALUMNOS ----
		openCreateStudent() {
			this.editingStudent = null;
			this.showStudentModal = true;
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
						studentName: { name: student.fullName, email: '', avatar: student.photo_url || 'images/face-2.jpg' },
						gradeCourse: { grade: student.gradeCourse, course: '' },
						birthDate: student.birthDate,
						enrollmentStatus: student.enrollmentStatus,
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

			const form = new FormData();
			form.append('fullName', payload.fullName ?? '');
			form.append('gradeCourse', payload.gradeCourse ?? '');
			form.append('birthDate', payload.birthDate ?? '');
			form.append('enrollmentStatus', payload.enrollmentStatus ?? '');
			if (payload.enrollmentYear != null && String(payload.enrollmentYear).trim() !== '') {
				form.append('enrollmentYear', String(payload.enrollmentYear));
			}
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

		// ---- MAESTROS ----
		openCreateTeacher() {
			this.editingTeacher = null;
			this.showTeacherModal = true;
		},
		openEditTeacher(row) {
			this.editingTeacher = row && row._raw ? row._raw : null;
			this.showTeacherModal = true;
		},
		closeTeacherModal() {
			this.showTeacherModal = false;
			this.editingTeacher = null;
		},
		fetchTeachers() {
			this.loadingTeachers = true;
			axios.get('http://localhost:8000/api/teachers', { headers: this.apiHeaders() })
				.then((response) => {
					this.teachersData = (response.data || []).map((teacher) => ({
						key: teacher.id,
						_raw: teacher,
						teacherName: { name: teacher.fullName, email: teacher.email, avatar: 'images/face-3.jpg' },
						phone: teacher.phone || '',
						specialty: teacher.specialty || '',
						status: teacher.status || 'Activo',
					}));
				})
				.catch((error) => console.error('Error al obtener maestros:', error.response?.data || error))
				.finally(() => { this.loadingTeachers = false; });
		},
		handleSubmitTeacher(payload) {
			const url = this.editingTeacher
				? `http://localhost:8000/api/teachers/${this.editingTeacher.id}`
				: 'http://localhost:8000/api/teachers';
			const method = this.editingTeacher ? 'put' : 'post';

			axios({ method, url, data: payload, headers: { ...this.apiHeaders(), 'Content-Type': 'application/json' } })
				.then(() => {
					this.closeTeacherModal();
					this.fetchTeachers();
				})
				.catch((error) => console.error('Error guardando maestro:', error.response?.data || error));
		},
		deleteTeacher(row) {
			const id = row && (row.key || row.id);
			if (!id) return;
			axios.delete(`http://localhost:8000/api/teachers/${id}`, { headers: this.apiHeaders() })
				.then(() => this.fetchTeachers())
				.catch((error) => console.error('Error eliminando maestro:', error.response?.data || error));
		},
	},
})
</script>

<style scoped>
.registro-page :deep(.ant-card-head-title) {
	width: 100%;
}
</style>