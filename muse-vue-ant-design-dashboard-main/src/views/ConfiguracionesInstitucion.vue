<template>
	<div class="layout">
		<a-card :bordered="false">
			<div class="header">
				<h2 class="title">Datos de la institución</h2>
				<p class="subtitle">Configura la información general que se usa en reportes, comunicados y documentos</p>
			</div>

			<a-spin :spinning="loading">
				<a-form :form="form" layout="vertical">
					<a-row :gutter="16">
						<a-col :xs="24" :md="12">
							<a-form-item label="Nombre de la institución">
								<a-input v-decorator="['name', { rules: [{ required: true, message: 'El nombre es requerido' }] }]" />
							</a-form-item>
						</a-col>
						<a-col :xs="24" :md="12">
							<a-form-item label="Nombre corto">
								<a-input v-decorator="['short_name']" />
							</a-form-item>
						</a-col>
					</a-row>

					<a-row :gutter="16">
						<a-col :xs="24" :md="12">
							<a-form-item label="RUC / RTN / Identificación">
								<a-input v-decorator="['tax_id']" />
							</a-form-item>
						</a-col>
						<a-col :xs="24" :md="12">
							<a-form-item label="Teléfono">
								<a-input v-decorator="['phone']" />
							</a-form-item>
						</a-col>
					</a-row>

					<a-row :gutter="16">
						<a-col :xs="24" :md="12">
							<a-form-item label="Correo institucional">
								<a-input v-decorator="['email']" />
							</a-form-item>
						</a-col>
						<a-col :xs="24" :md="12">
							<a-form-item label="Sitio web">
								<a-input v-decorator="['website']" />
							</a-form-item>
						</a-col>
					</a-row>

					<a-form-item label="Dirección">
						<a-textarea v-decorator="['address']" :rows="3" />
					</a-form-item>

					<a-row :gutter="16">
						<a-col :xs="24" :md="8">
							<a-form-item label="Ciudad">
								<a-input v-decorator="['city']" />
							</a-form-item>
						</a-col>
						<a-col :xs="24" :md="8">
							<a-form-item label="Departamento/Estado">
								<a-input v-decorator="['state']" />
							</a-form-item>
						</a-col>
						<a-col :xs="24" :md="8">
							<a-form-item label="País">
								<a-input v-decorator="['country']" />
							</a-form-item>
						</a-col>
					</a-row>

					<a-divider>Información Institucional</a-divider>

					<a-form-item label="Misión">
						<a-textarea v-decorator="['mission']" :rows="4" placeholder="Describe la misión de la institución..." />
					</a-form-item>

					<a-form-item label="Visión">
						<a-textarea v-decorator="['vision']" :rows="4" placeholder="Describe la visión de la institución..." />
					</a-form-item>

					<a-form-item label="Valores Institucionales">
						<a-textarea v-decorator="['values']" :rows="3" placeholder="Ej: Responsabilidad, Excelencia, Integridad, Respeto..." />
					</a-form-item>

					<a-row :gutter="16">
						<a-col :xs="24" :md="8">
							<a-form-item label="Año de fundación">
								<a-input-number v-decorator="['founded_year']" :min="1900" :max="2024" style="width: 100%" />
							</a-form-item>
						</a-col>
						<a-col :xs="24" :md="8">
							<a-form-item label="Tipo de institución">
								<a-select v-decorator="['institution_type']" style="width: 100%">
									<a-select-option value="privada">Privada</a-select-option>
									<a-select-option value="publica">Pública</a-select-option>
									<a-select-option value="bilingue">Bilingüe</a-select-option>
									<a-select-option value="tecnica">Técnica</a-select-option>
								</a-select>
							</a-form-item>
						</a-col>
						<a-col :xs="24" :md="8">
							<a-form-item label="Niveles educativos">
								<a-select v-decorator="['education_levels']" mode="multiple" style="width: 100%" placeholder="Seleccione niveles">
									<a-select-option value="pre-kinder">Pre-Kínder</a-select-option>
									<a-select-option value="kinder">Kínder</a-select-option>
									<a-select-option value="primaria">Primaria</a-select-option>
									<a-select-option value="secundaria">Secundaria</a-select-option>
									<a-select-option value="bachillerato">Bachillerato</a-select-option>
								</a-select>
							</a-form-item>
						</a-col>
					</a-row>

					<a-form-item label="Historia">
						<a-textarea v-decorator="['history']" :rows="5" placeholder="Breve historia de la institución..." />
					</a-form-item>

					<a-row :gutter="16">
						<a-col :xs="24" :md="12">
							<a-form-item label="Director(a)">
								<a-input v-decorator="['director_name']" placeholder="Nombre del director(a)" />
							</a-form-item>
						</a-col>
						<a-col :xs="24" :md="12">
							<a-form-item label="Correo del director(a)">
								<a-input v-decorator="['director_email']" placeholder="correo@ejemplo.com" />
							</a-form-item>
						</a-col>
					</a-row>

					<a-row :gutter="16">
						<a-col :xs="24" :md="12">
							<a-form-item label="Teléfono de contacto">
								<a-input v-decorator="['contact_phone']" placeholder="Teléfono principal de contacto" />
							</a-form-item>
						</a-col>
						<a-col :xs="24" :md="12">
							<a-form-item label="Correo de contacto">
								<a-input v-decorator="['contact_email']" placeholder="contacto@ejemplo.com" />
							</a-form-item>
						</a-col>
					</a-row>

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
	name: 'ConfiguracionesInstitucion',
	data() {
		return {
			loading: false,
			saving: false,
			form: this.$form.createForm(this, { name: 'config_institucion_form' }),
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
				.get('http://localhost:8000/api/settings/institution', { headers: this.apiHeaders() })
				.then((res) => {
					const data = res.data || {};
					this.form.setFieldsValue({
						name: data.name || '',
						short_name: data.short_name || '',
						tax_id: data.tax_id || '',
						phone: data.phone || '',
						email: data.email || '',
						website: data.website || '',
						address: data.address || '',
						city: data.city || '',
						state: data.state || '',
						country: data.country || '',
						mission: data.mission || '',
						vision: data.vision || '',
						values: data.values || '',
						founded_year: data.founded_year || null,
						institution_type: data.institution_type || undefined,
						education_levels: data.education_levels || [],
						history: data.history || '',
						director_name: data.director_name || '',
						director_email: data.director_email || '',
						contact_phone: data.contact_phone || '',
						contact_email: data.contact_email || '',
					});
				})
				.catch((err) => {
					console.error('Error cargando configuración institución:', err.response?.data || err);
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

				axios
					.put('http://localhost:8000/api/settings/institution', values, { headers: this.apiHeaders() })
					.then(() => {
						this.$message.success('Configuración guardada');
					})
					.catch((e) => {
						console.error('Error guardando configuración institución:', e.response?.data || e);
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
