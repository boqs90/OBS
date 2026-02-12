<template>
	<div class="layout">
		<a-card :bordered="false">
			<div class="header">
				<h2 class="title">Parámetros académicos</h2>
				<p class="subtitle">Configura periodos, escala de calificaciones y reglas académicas</p>
			</div>

			<a-spin :spinning="loading">
				<a-form :form="form" layout="vertical">
					<a-row :gutter="16">
						<a-col :xs="24" :md="8">
							<a-form-item label="Año lectivo actual">
								<a-input-number
									v-decorator="['current_academic_year', { rules: [{ required: true, message: 'Requerido' }] }]"
									:min="2000"
									:max="2100"
									style="width: 100%"
								/>
							</a-form-item>
						</a-col>
						<a-col :xs="24" :md="8">
							<a-form-item label="Nota mínima para aprobar">
								<a-input-number
									v-decorator="['min_passing_grade', { rules: [{ required: true, message: 'Requerido' }] }]"
									:min="0"
									:max="100"
									style="width: 100%"
								/>
							</a-form-item>
						</a-col>
						<a-col :xs="24" :md="8">
							<a-form-item label="Promedio mínimo conducta">
								<a-input-number v-decorator="['min_conduct_grade']" :min="0" :max="100" style="width: 100%" />
							</a-form-item>
						</a-col>
					</a-row>

					<a-row :gutter="16">
						<a-col :xs="24" :md="12">
							<a-form-item label="Periodos académicos (separados por coma)">
								<a-input v-decorator="['periods']" placeholder="Ej: I, II, III, IV" />
							</a-form-item>
						</a-col>
						<a-col :xs="24" :md="12">
							<a-form-item label="Escala de calificación">
								<a-select v-decorator="['grading_scale']" style="width: 100%">
									<a-select-option value="0-100">0 - 100</a-select-option>
									<a-select-option value="0-10">0 - 10</a-select-option>
									<a-select-option value="A-F">A - F</a-select-option>
								</a-select>
							</a-form-item>
						</a-col>
					</a-row>

					<a-form-item label="Observaciones">
						<a-textarea v-decorator="['notes']" :rows="3" />
					</a-form-item>

					<div class="actions">
						<a-button type="primary" :loading="saving" @click="handleSave">Guardar</a-button>
					</div>
				</a-form>
			</a-spin>
		</a-card>
	</div>
</template>

<script>
import axios from 'axios';
import { getToken } from '@/utils/auth';

export default {
	name: 'ConfiguracionesParametrosAcademicos',
	data() {
		return {
			loading: false,
			saving: false,
			form: this.$form.createForm(this, { name: 'config_parametros_academicos_form' }),
		};
	},
	mounted() {
		this.fetchSettings();
	},
	methods: {
		apiHeaders() {
			const token = getToken();
			return { Authorization: `Bearer ${token}` };
		},
		fetchSettings() {
			this.loading = true;
			axios
				.get('http://localhost:8000/api/settings/academic', { headers: this.apiHeaders() })
				.then((res) => {
					const data = res.data || {};
					this.form.setFieldsValue({
						current_academic_year: data.current_academic_year || new Date().getFullYear(),
						min_passing_grade: data.min_passing_grade ?? 60,
						min_conduct_grade: data.min_conduct_grade ?? 60,
						periods: Array.isArray(data.periods) ? data.periods.join(', ') : (data.periods || ''),
						grading_scale: data.grading_scale || '0-100',
						notes: data.notes || '',
					});
				})
				.catch((err) => {
					console.error('Error cargando parámetros académicos:', err.response?.data || err);
					this.$message.error('No se pudo cargar la configuración');
				})
				.finally(() => {
					this.loading = false;
				});
		},
		handleSave() {
			this.form.validateFields((err, values) => {
				if (err) return;
				this.saving = true;

				const payload = {
					...values,
					periods: String(values.periods || '')
						.split(',')
						.map((p) => p.trim())
						.filter(Boolean),
				};

				axios
					.put('http://localhost:8000/api/settings/academic', payload, { headers: this.apiHeaders() })
					.then(() => {
						this.$message.success('Configuración guardada');
					})
					.catch((e) => {
						console.error('Error guardando parámetros académicos:', e.response?.data || e);
						this.$message.error('No se pudo guardar la configuración');
					})
					.finally(() => {
						this.saving = false;
					});
			});
		},
	},
};
</script>

<style lang="scss" scoped>
.layout {
	padding: 24px;
}

.header {
	margin-bottom: 16px;

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

.actions {
	margin-top: 16px;
	display: flex;
	justify-content: flex-end;
}
</style>
