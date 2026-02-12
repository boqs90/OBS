<template>
	<a-form :form="form" @submit="handleSubmit">
		<a-form-item label="Foto">
			<div class="user-photo-row">
				<a-avatar
					shape="square"
					:size="120"
					:src="photoPreview || null"
					class="user-photo-avatar"
				>
					<a-icon v-if="!photoPreview" type="camera" style="color: #8c8c8c; font-size: 24px;" />
				</a-avatar>
				<div class="user-photo-actions">
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
		<a-form-item label="Nombre">
			<a-input
				:maxLength="200"
				v-decorator="['name', { rules: [{ required: true, message: 'Por favor ingresa el nombre.' }] }]"
				placeholder="Nombre"
			/>
		</a-form-item>

		<a-form-item label="Correo">
			<a-input
				:maxLength="200"
				v-decorator="['email', { rules: [{ required: true, type: 'email', message: 'Por favor ingresa un correo válido.' }] }]"
				placeholder="correo@dominio.com"
				:disabled="editing"
			/>
		</a-form-item>

		<a-form-item label="Rol">
			<a-select
				v-decorator="['role', { rules: [{ required: true, message: 'Selecciona un rol.' }] }]"
				placeholder="Selecciona un rol"
			>
				<a-select-option v-for="r in normalizedRoles" :key="r" :value="r">{{ r }}</a-select-option>
			</a-select>
		</a-form-item>

		<a-form-item v-if="!editing" label="Contraseña">
			<a-input
				:maxLength="200"
				v-decorator="['password', { rules: [{ required: true, message: 'Por favor ingresa la contraseña.' }, { min: 6, message: 'La contraseña debe tener al menos 6 caracteres.' }] }]"
				type="password"
				placeholder="Contraseña"
				autocomplete="new-password"
			/>
		</a-form-item>

		<a-form-item v-if="!editing" label="Confirmar contraseña">
			<a-input
				:maxLength="200"
				v-decorator="['password_confirmation', { rules: [{ required: true, message: 'Confirma la contraseña.' }, { validator: validatePasswordConfirmationCreate }] }]"
				type="password"
				placeholder="Confirmar contraseña"
				autocomplete="new-password"
			/>
		</a-form-item>

		<a-form-item>
			<a-button type="primary" html-type="submit">
				{{ submitText }}
			</a-button>
		</a-form-item>
	</a-form>
</template>

<script>
export default {
	props: {
		initialValues: {
			type: Object,
			default: null,
		},
		roles: {
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
			form: this.$form.createForm(this, { name: 'user_form' }),
			photoFile: null,
			photoPreview: '',
		};
	},
	computed: {
		normalizedRoles() {
			const list = Array.isArray(this.roles) ? this.roles : [];
			return list.length ? list : ['Administrador', 'Secretaría', 'Docente', 'Usuario'];
		},
		editing() {
			return !!(this.initialValues && this.initialValues.id);
		},
		selectedNewPhoto() {
			return !!this.photoFile;
		},
	},
	mounted() {
		// Asegurar que el formulario esté inicializado
		this.$nextTick(() => {
			if (this.form && this.initialValues) {
				this.loadInitialValues(this.initialValues);
			}
		});
	},
	watch: {
		initialValues: {
			immediate: true,
			handler(values) {
				// Esperar a que el formulario esté completamente inicializado
				this.$nextTick(() => {
					if (!this.form) return;
					this.loadInitialValues(values);
				});
			},
		},
	},
	methods: {
		loadInitialValues(values) {
			if (!values) {
				this.form.resetFields();
				this.clearPhoto(true);
				return;
			}
			this.form.setFieldsValue({
				name: values.name || '',
				email: values.email || '',
				role: values.role || 'Usuario',
				status: 'Activo',
				password: '',
				password_confirmation: '',
			});

			// Foto inicial (modo edición)
			this.clearPhoto(true);
			if (values.photo_url) {
				this.photoPreview = values.photo_url;
			}
		},
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
			if (!this.editing) return true; // En creación siempre hay "cambios"

			const normalize = (v) => String(v ?? '').trim();
			const initial = this.initialValues || {};

			// Verificar cambios en campos permitidos
			const fields = ['name', 'role', 'status'];
			const hasFieldChanges = fields.some((k) => normalize(values?.[k]) !== normalize(initial?.[k]));

			// Verificar si hay una foto nueva
			const hasNewPhoto = !!this.photoFile;

			// Verificar si se quitó la foto existente
			const hadInitialPhoto = !!(initial?.photo_url);
			const hasPhotoNow = !!(this.photoPreview);
			const photoRemoved = hadInitialPhoto && !hasPhotoNow && !hasNewPhoto;

			return hasFieldChanges || hasNewPhoto || photoRemoved;
		},
		validatePasswordConfirmationCreate(rule, value, callback) {
			const newPass = this.form.getFieldValue('password');
			if (!value) return callback();
			if (value !== newPass) return callback('Las contraseñas no coinciden.');
			return callback();
		},
		handleSubmit(e) {
			e.preventDefault();
			this.form.validateFields((err, values) => {
				if (!err) {
					// En edición: no enviar contraseña nunca
					if (this.editing) {
						delete values.password;
						delete values.password_confirmation;
					}

					// Adjuntar archivo de foto si existe
					const payload = { ...values, photoFile: this.photoFile };

					// En edición: si no hay cambios, no guardamos
					if (this.editing && !this.hasChanges(values)) {
						this.$message?.info?.('No hay cambios para guardar.');
						return;
					}

					// Guardar directamente sin confirmación extra
					this.$emit('submitForm', payload);
					if (this.resetAfterSubmit) this.form.resetFields();
				}
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
.user-photo-row {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 16px;
	padding: 20px 0;
}
.user-photo-actions {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 8px;
}
.user-photo-avatar {
	border: 2px solid rgba(17, 24, 39, 0.08);
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
</style>
