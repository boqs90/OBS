<template>
	<div class="change-password-container">
		<div class="change-password-card">
			<div class="logo-section">
				<div class="sidebar-brand-monogram-wrap" aria-label="OBS">
					<div class="sidebar-brand-monogram" aria-hidden="true">
						<span class="sidebar-brand-monogram__abbr">OBS</span>
						<span class="sidebar-brand-monogram__sub">Olanchito Bilingual School</span>
					</div>
				</div>
			</div>

			<a-card :bordered="false" class="password-card">
				<h2 class="card-title">Cambio de Contraseña Requerido</h2>
				<p class="card-description">
					Has iniciado sesión con una contraseña temporal. Por favor, establece tu nueva contraseña.
				</p>

				<a-form :form="form" @submit="handleSubmit" class="password-form">
					<a-form-item>
						<a-input-password
							v-decorator="[
								'newPassword',
								{
									rules: [
										{ required: true, message: 'Por favor ingresa tu nueva contraseña.' },
										{ min: 6, message: 'La contraseña debe tener al menos 6 caracteres.' },
										{ max: 15, message: 'La contraseña no puede tener más de 15 caracteres.' }
									]
								}
							]"
							placeholder="Nueva contraseña (6-15 caracteres)"
							size="large"
						>
							<a-icon slot="prefix" type="lock" />
						</a-input-password>
					</a-form-item>

					<a-form-item>
						<a-input-password
							v-decorator="[
								'confirmPassword',
								{
									rules: [
										{ required: true, message: 'Por favor confirma tu contraseña.' },
										{ validator: compareToFirstPassword }
									]
								}
							]"
							placeholder="Confirmar nueva contraseña"
							size="large"
						>
							<a-icon slot="prefix" type="lock" />
						</a-input-password>
					</a-form-item>

					<a-form-item>
						<a-button 
							type="primary" 
							html-type="submit" 
							size="large" 
							block 
							:loading="loading"
							class="submit-button"
						>
							Actualizar Contraseña
						</a-button>
					</a-form-item>
				</a-form>
			</a-card>

			<div class="footer-text">
				<p>© 2024 Olanchito Bilingual School</p>
			</div>
		</div>
	</div>
</template>

<script>
import axios from 'axios';
import { getToken } from '@/utils/auth';

export default {
	name: 'ChangePassword',
	data() {
		return {
			form: null,
			loading: false,
		};
	},
	beforeCreate() {
		this.form = this.$form.createForm(this, { name: 'change_password' });
	},
	methods: {
		compareToFirstPassword(rule, value, callback) {
			const form = this.form;
			if (value && value !== form.getFieldValue('newPassword')) {
				callback('Las contraseñas no coinciden.');
			} else {
				callback();
			}
		},
		handleSubmit(e) {
			e.preventDefault();
			this.form.validateFields((err, values) => {
				if (!err) {
					this.loading = true;
					
					axios.post('http://localhost:8000/api/auth/change-password', {
						new_password: values.newPassword,
						confirm_password: values.confirmPassword
					}, {
						headers: { Authorization: `Bearer ${getToken()}` }
					})
					.then(() => {
						this.$message.success('Contraseña actualizada correctamente.');
						
						// Limpiar token y redirigir al login
						localStorage.removeItem('token');
						sessionStorage.removeItem('token');
						
						this.$message.info('Por favor, inicia sesión con tu nueva contraseña.');
						this.$router.push('/sign-in');
					})
					.catch((error) => {
						const msg = error.response?.data?.message || 'Error al cambiar la contraseña.';
						this.$message.error(msg);
					})
					.finally(() => {
						this.loading = false;
					});
				}
			});
		}
	}
};
</script>

<style scoped>
.change-password-container {
	display: flex;
	justify-content: center;
	align-items: center;
	min-height: 100vh;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	padding: 20px;
}

.change-password-card {
	width: 100%;
	max-width: 400px;
}

.logo-section {
	text-align: center;
	margin-bottom: 24px;
}

.sidebar-brand-monogram-wrap {
	display: inline-block;
}

.sidebar-brand-monogram {
	background: #fff;
	border-radius: 8px;
	padding: 16px;
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.sidebar-brand-monogram__abbr {
	display: block;
	font-size: 24px;
	font-weight: bold;
	color: #1890ff;
	text-align: center;
}

.sidebar-brand-monogram__sub {
	display: block;
	font-size: 12px;
	color: #666;
	margin-top: 4px;
	text-align: center;
}

.password-card {
	border-radius: 12px;
	box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.card-title {
	text-align: center;
	color: #333;
	margin-bottom: 8px;
	font-size: 24px;
	font-weight: 600;
}

.card-description {
	text-align: center;
	color: #666;
	margin-bottom: 24px;
	font-size: 14px;
	line-height: 1.5;
}

.password-form {
	margin-top: 16px;
}

.submit-button {
	height: 48px;
	font-size: 16px;
	font-weight: 500;
	border-radius: 8px;
}

.footer-text {
	text-align: center;
	margin-top: 24px;
}

.footer-text p {
	color: rgba(255, 255, 255, 0.8);
	font-size: 12px;
	margin: 0;
}

/* Responsive */
@media (max-width: 480px) {
	.change-password-container {
		padding: 12px;
	}
	
	.card-title {
		font-size: 20px;
	}
	
	.card-description {
		font-size: 13px;
	}
}
</style>
