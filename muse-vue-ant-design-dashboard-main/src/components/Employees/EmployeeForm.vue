<template>
	<a-form :form="form" @submit="handleSubmit">
		<a-form-item label="Foto">
			<div class="employee-photo-row">
				<a-avatar
					shape="square"
					:size="120"
					:src="photoPreview || null"
					class="employee-photo-avatar"
				>
					<a-icon v-if="!photoPreview" type="camera" style="color: #8c8c8c; font-size: 24px;" />
				</a-avatar>
				<div class="employee-photo-actions">
					<a-upload
						:beforeUpload="beforeUpload"
						:showUploadList="false"
						accept="image/*"
					>
						<a-button class="btn-add-outline">
							<svg class="btn-add-outline__icon" viewBox="0 0 24 24" aria-hidden="true">
								<path d="M12 5v14M5 12h14" fill="none" />
							</svg>
							{{ photoPreview ? 'Cambiar foto' : 'Subir foto' }}
						</a-button>
					</a-upload>
					<a-button
						v-if="photoPreview && !editing"
						type="link"
						@click="clearPhoto"
					>
						Quitar
					</a-button>
					<a-button
						v-if="photoPreview && editing && selectedNewPhoto"
						type="link"
						@click="clearPhoto"
					>
						Quitar cambio
					</a-button>
				</div>
			</div>
		</a-form-item>
		<a-form-item label="Nombre completo">
			<a-input
				:maxLength="200"
				v-decorator="['fullName', { rules: [{ required: true, message: 'Ingresa el nombre.' }] }]"
				placeholder="Nombre completo"
			/>
		</a-form-item>

		<a-form-item label="Correo">
			<a-input
				:maxLength="200"
				v-decorator="['email', { rules: [{ type: 'email', message: 'Ingresa un correo válido.' }] }]"
				placeholder="correo@dominio.com"
			/>
		</a-form-item>

		<a-form-item label="Teléfono">
			<a-input
				:maxLength="50"
				v-decorator="['phone']"
				placeholder="Teléfono"
			/>
		</a-form-item>

		<a-form-item label="Salario">
			<a-input-number
				style="width: 100%"
				:min="0"
				:precision="2"
				:formatter="value => `$ ${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
				:parser="value => value.replace(/\$\s?|(,*)/g, '')"
				v-decorator="['salary']"
				placeholder="0.00"
			/>
		</a-form-item>

		<a-form-item label="Identidad">
			<a-input
				:maxLength="50"
				v-decorator="['identityNumber']"
				placeholder="Número de identidad"
			/>
		</a-form-item>

		<a-form-item label="Cargo">
			<a-select
				show-search
				placeholder="Selecciona un cargo"
				option-filter-prop="children"
				v-decorator="['position_id']"
				:allowClear="true"
			>
				<a-select-option v-for="p in normalizedPositions" :key="p.id" :value="p.id">
					{{ p.name }}
				</a-select-option>
			</a-select>
		</a-form-item>

		<a-form-item label="Ingreso">
			<a-date-picker v-decorator="['entryDate']" style="width: 100%" />
		</a-form-item>

		<a-form-item label="Egreso">
			<a-date-picker v-decorator="['exitDate']" style="width: 100%" />
		</a-form-item>

		<a-form-item label="Estado">
			<a-select
				v-decorator="['status', { rules: [{ required: true, message: 'Selecciona el estado.' }] }]"
				placeholder="Selecciona el estado"
			>
				<a-select-option value="Activo">Activo</a-select-option>
				<a-select-option value="Inactivo">Inactivo</a-select-option>
			</a-select>
		</a-form-item>

		<a-form-item>
			<a-button type="primary" html-type="submit">
				{{ submitText }}
			</a-button>
		</a-form-item>
	</a-form>
</template>

<script>
import moment from 'moment';

export default {
	props: {
		initialValues: {
			type: Object,
			default: null,
		},
		positions: {
			type: Array,
			default: () => [],
		},
		submitText: {
			type: String,
			default: 'Guardar',
		},
		resetAfterSubmit: {
			type: Boolean,
			default: true,
		},
	},
	data() {
		return {
			form: this.$form.createForm(this, { name: 'employee_form' }),
			photoFile: null,
			photoPreview: '',
		};
	},
	computed: {
		editing() {
			return !!(this.initialValues && this.initialValues.id);
		},
		normalizedPositions() {
			const list = Array.isArray(this.positions) ? this.positions : [];
			return list.filter((p) => p && p.id != null && p.name);
		},
		selectedNewPhoto() {
			return !!this.photoFile;
		},
	},
	watch: {
		initialValues: {
			immediate: true,
			handler(values) {
				if (!this.form) return;
				if (!values) {
					this.form.resetFields();
					this.clearPhoto(true);
					this.form.setFieldsValue({ status: 'Activo' });
					return;
				}

				this.form.setFieldsValue({
					fullName: values.fullName || '',
					email: values.email || '',
					phone: values.phone || '',
					salary: values.salary || 0,
					identityNumber: values.identityNumber || '',
					position_id: values.position_id || values.position?.id || undefined,
					entryDate: values.entryDate ? moment(values.entryDate) : null,
					exitDate: values.exitDate ? moment(values.exitDate) : null,
					status: values.status || 'Activo',
				});

				// Foto inicial (modo edición)
				this.clearPhoto(true);
				if (values.photo_url) {
					this.photoPreview = values.photo_url;
				}
			},
		},
	},
	methods: {
		beforeUpload(file) {
			const isImage = file && String(file.type || '').startsWith('image/');
			if (!isImage) {
				this.$message && this.$message.error('La foto debe ser una imagen.');
				return false;
			}

			// Liberar preview anterior si era objectURL
			if (this.photoPreview && this.photoPreview.startsWith('blob:')) {
				try { URL.revokeObjectURL(this.photoPreview); } catch (_) {}
			}

			this.photoFile = file;
			this.photoPreview = URL.createObjectURL(file);
			return false; // evitar auto-upload, lo mandamos junto al formulario
		},
		clearPhoto(silent = false) {
			if (this.photoPreview && this.photoPreview.startsWith('blob:')) {
				try { URL.revokeObjectURL(this.photoPreview); } catch (_) {}
			}
			this.photoFile = null;
			this.photoPreview = '';
			if (!silent && this.$message) this.$message.info('Foto removida.');
		},
		hasChanges(values) {
			if (!this.editing) return true;

			const initial = this.initialValues || {};
			const normalize = (v) => String(v ?? '').trim();

			const initialPositionId = initial.position_id || initial.position?.id || '';
			const currentPositionId = values.position_id ?? '';

			const initialEntry = initial.entryDate ? String(initial.entryDate).split('T')[0] : '';
			const initialExit = initial.exitDate ? String(initial.exitDate).split('T')[0] : '';

			const currentEntry = values.entryDate ? values.entryDate.format('YYYY-MM-DD') : '';
			const currentExit = values.exitDate ? values.exitDate.format('YYYY-MM-DD') : '';

			return (
				normalize(values.fullName) !== normalize(initial.fullName) ||
				normalize(values.email) !== normalize(initial.email) ||
				normalize(values.phone) !== normalize(initial.phone) ||
				Number(values.salary || 0) !== Number(initial.salary || 0) ||
				normalize(values.identityNumber) !== normalize(initial.identityNumber) ||
				String(currentPositionId) !== String(initialPositionId) ||
				currentEntry !== initialEntry ||
				currentExit !== initialExit ||
				normalize(values.status) !== normalize(initial.status || 'Activo')
			);
		},
		handleSubmit(e) {
			e.preventDefault();
			this.form.validateFields((err, values) => {
				if (err) return;

				const payload = {
					...values,
					entryDate: values.entryDate ? values.entryDate.format('YYYY-MM-DD') : null,
					exitDate: values.exitDate ? values.exitDate.format('YYYY-MM-DD') : null,
					photoFile: this.photoFile,
				};

				if (this.editing && !this.hasChanges(values)) {
					this.$message?.info?.('No hay cambios para guardar.');
					return;
				}

				if (this.editing) {
					const confirm = this.$confirm || this.$modal?.confirm;
					if (confirm) {
						confirm({
							title: 'Guardar cambios',
							content: '¿Deseas guardar los cambios?',
							okText: 'Guardar',
							cancelText: 'Cancelar',
							onOk: () => this.$emit('submitForm', payload),
						});
						return;
					}

					if (window.confirm('¿Deseas guardar los cambios?')) {
						this.$emit('submitForm', payload);
					}
					return;
				}

				this.$emit('submitForm', payload);
				if (this.resetAfterSubmit) this.form.resetFields();
			});
		},
	},
	beforeDestroy() {
		if (this.photoPreview && this.photoPreview.startsWith('blob:')) {
			try { URL.revokeObjectURL(this.photoPreview); } catch (_) {}
		}
	},
};
</script>

<style scoped>
.employee-photo-row {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 16px;
	padding: 20px 0;
}
.employee-photo-actions {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 8px;
}
.employee-photo-avatar {
	border: 2px solid rgba(17, 24, 39, 0.08);
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
</style>

