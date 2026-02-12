<template>
	<div class="layout">
		<a-card :bordered="false">
			<div class="header">
				<h2 class="title">Logos</h2>
				<p class="subtitle">Sube y administra los logos utilizados en el sistema y reportes</p>
			</div>

			<a-alert
				message="Nota"
				description="Este módulo guarda las URLs de los logos. El backend debe manejar el almacenamiento (S3/Local) y devolver la URL final."
				type="info"
				show-icon
				style="margin-bottom: 16px"
			/>

			<a-spin :spinning="loading">
				<a-form :form="form" layout="vertical">
					<a-row :gutter="16">
						<a-col :xs="24" :md="12">
							<a-form-item label="Logo principal (URL)">
								<a-input v-decorator="['main_logo_url']" placeholder="https://..." />
							</a-form-item>
						</a-col>
						<a-col :xs="24" :md="12">
							<a-form-item label="Logo alternativo (URL)">
								<a-input v-decorator="['alt_logo_url']" placeholder="https://..." />
							</a-form-item>
						</a-col>
					</a-row>

					<a-row :gutter="16">
						<a-col :xs="24" :md="12">
							<a-form-item label="Favicon (URL)">
								<a-input v-decorator="['favicon_url']" placeholder="https://..." />
							</a-form-item>
						</a-col>
						<a-col :xs="24" :md="12">
							<a-form-item label="Logo para reportes (URL)">
								<a-input v-decorator="['reports_logo_url']" placeholder="https://..." />
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
	name: 'ConfiguracionesLogos',
	data() {
		return {
			loading: false,
			saving: false,
			form: this.$form.createForm(this, { name: 'config_logos_form' }),
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
				.get('http://localhost:8000/api/settings/logos', { headers: this.apiHeaders() })
				.then((res) => {
					const data = res.data || {};
					this.form.setFieldsValue({
						main_logo_url: data.main_logo_url || '',
						alt_logo_url: data.alt_logo_url || '',
						favicon_url: data.favicon_url || '',
						reports_logo_url: data.reports_logo_url || '',
					});
				})
				.catch((err) => {
					console.error('Error cargando configuración logos:', err.response?.data || err);
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
					.put('http://localhost:8000/api/settings/logos', values, { headers: this.apiHeaders() })
					.then(() => {
						this.$message.success('Configuración guardada');
					})
					.catch((e) => {
						console.error('Error guardando configuración logos:', e.response?.data || e);
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
