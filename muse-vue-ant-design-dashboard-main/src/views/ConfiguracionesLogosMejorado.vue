<template>
	<div class="layout">
		<a-card :bordered="false">
			<div class="header">
				<h2 class="title">Logos</h2>
				<p class="subtitle">Sube y administra los logos utilizados en el sistema y reportes</p>
			</div>

			<a-tabs default-active-key="urls">
				<a-tab-pane key="urls" tab="Ingresar URLs">
					<a-alert
						message="Modo URLs"
						description="Ingresa directamente las URLs de los logos si ya están alojados en tu servidor o CDN."
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
								<a-button type="primary" :loading="saving" @click="handleSave">Guardar URLs</a-button>
							</div>
						</a-form>
					</a-spin>
				</a-tab-pane>

				<a-tab-pane key="upload" tab="Subir Archivos">
					<a-alert
						message="Modo Subida"
						description="Sube los archivos de logos desde tu dispositivo. El backend los procesará y devolverá las URLs."
						type="info"
						show-icon
						style="margin-bottom: 16px"
					/>

					<a-spin :spinning="loading">
						<a-form layout="vertical">
							<a-form-item label="Logo principal">
								<a-upload
									name="main_logo"
									:file-list="mainLogoFileList"
									:before-upload="beforeUpload"
									@remove="() => { mainLogoFileList = [] }"
									accept="image/*"
									:max-count="1"
								>
									<a-button>
										<a-icon type="upload" /> Seleccionar archivo
									</a-button>
								</a-upload>
								<div v-if="mainLogoPreview" class="preview">
									<img :src="mainLogoPreview" alt="Logo principal" style="max-width: 200px; max-height: 100px;" />
								</div>
							</a-form-item>

							<a-form-item label="Logo alternativo">
								<a-upload
									name="alt_logo"
									:file-list="altLogoFileList"
									:before-upload="beforeUpload"
									@remove="() => { altLogoFileList = [] }"
									accept="image/*"
									:max-count="1"
								>
									<a-button>
										<a-icon type="upload" /> Seleccionar archivo
									</a-button>
								</a-upload>
								<div v-if="altLogoPreview" class="preview">
									<img :src="altLogoPreview" alt="Logo alternativo" style="max-width: 200px; max-height: 100px;" />
								</div>
							</a-form-item>

							<a-form-item label="Favicon">
								<a-upload
									name="favicon"
									:file-list="faviconFileList"
									:beforeUpload="beforeUpload"
									@remove="() => { faviconFileList = [] }"
									accept="image/*"
									:max-count="1"
								>
									<a-button>
										<a-icon type="upload" /> Seleccionar archivo
									</a-button>
								</a-upload>
								<div v-if="faviconPreview" class="preview">
									<img :src="faviconPreview" alt="Favicon" style="max-width: 64px; max-height: 64px;" />
								</div>
							</a-form-item>

							<a-form-item label="Logo para reportes">
								<a-upload
									name="reports_logo"
									:file-list="reportsLogoFileList"
									:beforeUpload="beforeUpload"
									@remove="() => { reportsLogoFileList = [] }"
									accept="image/*"
									:max-count="1"
								>
									<a-button>
										<a-icon type="upload" /> Seleccionar archivo
									</a-button>
								</a-upload>
								<div v-if="reportsLogoPreview" class="preview">
									<img :src="reportsLogoPreview" alt="Logo reportes" style="max-width: 200px; max-height: 100px;" />
								</div>
							</a-form-item>

							<div class="actions">
								<a-button type="primary" :loading="saving" @click="handleUpload">Subir Archivos</a-button>
							</div>
						</a-form>
					</a-spin>
				</a-tab-pane>
			</a-tabs>
		</a-card>
	</div>
</template>

<script>
import axios from 'axios';
import { getToken } from '@/utils/auth';

export default {
	name: 'ConfiguracionesLogosMejorado',
	data() {
		return {
			loading: false,
			saving: false,
			form: this.$form.createForm(this, { name: 'config_logos_form' }),
			mainLogoFileList: [],
			altLogoFileList: [],
			faviconFileList: [],
			reportsLogoFileList: [],
			mainLogoPreview: '',
			altLogoPreview: '',
			faviconPreview: '',
			reportsLogoPreview: '',
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
					// Set previews
					this.mainLogoPreview = data.main_logo_url || '';
					this.altLogoPreview = data.alt_logo_url || '';
					this.faviconPreview = data.favicon_url || '';
					this.reportsLogoPreview = data.reports_logo_url || '';
				})
				.catch((err) => {
					console.error('Error cargando configuración logos:', err.response?.data || err);
					this.$message.error('No se pudo cargar la configuración');
				})
				.finally(() => {
					this.loading = false;
				});
		},
		beforeUpload(file) {
			// Prevent automatic upload
			return false;
		},
		previewFile(file, previewKey) {
			const reader = new FileReader();
			reader.onload = (e) => {
				this[previewKey] = e.target.result;
			};
			reader.readAsDataURL(file);
		},
		handleSave() {
			this.form.validateFields((err, values) => {
				if (err) return;
				this.saving = true;
				axios
					.put('http://localhost:8000/api/settings/logos', values, { headers: this.apiHeaders() })
					.then(() => {
						this.$message.success('URLs guardadas');
						this.fetchSettings();
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
		handleUpload() {
			this.saving = true;
			const formData = new FormData();
			
			// Add files if they exist
			if (this.mainLogoFileList.length > 0) {
				formData.append('main_logo', this.mainLogoFileList[0].originFileObj);
			}
			if (this.altLogoFileList.length > 0) {
				formData.append('alt_logo', this.altLogoFileList[0].originFileObj);
			}
			if (this.faviconFileList.length > 0) {
				formData.append('favicon', this.faviconFileList[0].originFileObj);
			}
			if (this.reportsLogoFileList.length > 0) {
				formData.append('reports_logo', this.reportsLogoFileList[0].originFileObj);
			}

			axios
				.post('http://localhost:8000/api/settings/logos/upload', formData, {
					headers: {
						...this.apiHeaders(),
						'Content-Type': 'multipart/form-data',
					},
				})
				.then((res) => {
					this.$message.success('Archivos subidos correctamente');
					// Update URLs with response
					if (res.data) {
						this.form.setFieldsValue({
							main_logo_url: res.data.main_logo_url || '',
							alt_logo_url: res.data.alt_logo_url || '',
							favicon_url: res.data.favicon_url || '',
							reports_logo_url: res.data.reports_logo_url || '',
						});
						this.mainLogoPreview = res.data.main_logo_url || '';
						this.altLogoPreview = res.data.alt_logo_url || '';
						this.faviconPreview = res.data.favicon_url || '';
						this.reportsLogoPreview = res.data.reports_logo_url || '';
					}
					// Clear file lists
					this.mainLogoFileList = [];
					this.altLogoFileList = [];
					this.faviconFileList = [];
					this.reportsLogoFileList = [];
				})
				.catch((e) => {
					console.error('Error subiendo archivos:', e.response?.data || e);
					this.$message.error('No se pudieron subir los archivos');
				})
				.finally(() => {
					this.saving = false;
				});
		},
	},
	watch: {
		mainLogoFileList(newVal) {
			if (newVal.length > 0) {
				this.previewFile(newVal[0].originFileObj, 'mainLogoPreview');
			} else {
				this.mainLogoPreview = '';
			}
		},
		altLogoFileList(newVal) {
			if (newVal.length > 0) {
				this.previewFile(newVal[0].originFileObj, 'altLogoPreview');
			} else {
				this.altLogoPreview = '';
			}
		},
		faviconFileList(newVal) {
			if (newVal.length > 0) {
				this.previewFile(newVal[0].originFileObj, 'faviconPreview');
			} else {
				this.faviconPreview = '';
			}
		},
		reportsLogoFileList(newVal) {
			if (newVal.length > 0) {
				this.previewFile(newVal[0].originFileObj, 'reportsLogoPreview');
			} else {
				this.reportsLogoPreview = '';
			}
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

.preview {
	margin-top: 8px;
	padding: 8px;
	border: 1px solid #e5e7eb;
	border-radius: 4px;
	display: inline-block;
}
</style>
