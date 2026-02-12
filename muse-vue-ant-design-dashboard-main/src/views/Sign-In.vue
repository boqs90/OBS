<template>
  <div class="sign-in">
    <!-- PRELOADER AL ENTRAR AL LOGIN -->
    <transition name="sign-in-preload">
      <div v-if="pageLoading" class="sign-in__preload" aria-busy="true" aria-live="polite">
        <div class="sign-in__preload-bar" aria-hidden="true" />
        <div class="sign-in__preload-text">Preparando acceso…</div>
      </div>
    </transition>

    <!-- ALERTA FLOTANTE -->
    <transition name="fade">
      <a-alert
        v-if="loginError"
        :message="loginError"
        :description="loginErrorDesc"
        type="error"
        show-icon
        closable
        class="floating-alert"
      />
    </transition>

    <a-row type="flex" justify="center" align="middle" class="sign-in__row">
      <a-col
        :span="24"
        :md="12"
        :lg="{ span: 10, offset: 0 }"
        :xl="{ span: 8 }"
        class="col-form"
      >
        <div class="sign-in__heading">
          <div class="sign-in__logo">
            <img src="images/otra.png" alt="Olanchito Bilingual School" />
          </div>

          <h1 class="sign-in__title">Olanchito Bilingual School</h1>
          <p class="sign-in__subtitle">Inicia sesión para continuar.</p>
        </div>

        <transition name="sign-in-card" appear>
          <a-form v-show="!pageLoading" :form="form" @submit.prevent="handleSubmit" :hideRequiredMark="true">
          <a-form-item label="Correo o usuario">
            <a-input
              :maxLength="200"
              v-decorator="['email', { rules: [{ required: true, message: 'Escribe tu correo o usuario.' }] }]"
              placeholder="Correo o usuario"
              :disabled="loading"
            />
          </a-form-item>

          <a-form-item label="Contraseña">
            <a-input
              :maxLength="200"
              v-decorator="['password', { rules: [{ required: true, message: 'Escribe tu contraseña.' }] }]"
              type="password"
              placeholder="Contraseña"
              :disabled="loading"
              autocomplete="current-password"
            />
          </a-form-item>
          
          <a-form-item>
            <a-button
              type="primary"
              block
              html-type="submit"
              :disabled="loading"
              :class="loading ? 'sign-in__btn--loading' : ''"
            >
              <span class="sign-in__btn-text">{{ loading ? 'Ingresando…' : 'Entrar' }}</span>
              <span v-if="loading" class="sign-in__btn-shimmer" aria-hidden="true"></span>
            </a-button>
          </a-form-item>
          
          <a-form-item>
            <a-button 
              type="link" 
              block 
              @click="showPasswordResetModal"
              :disabled="loading"
              class="sign-in__reset-btn"
            >
              ¿Olvidaste tu contraseña? Solicitar restablecimiento
            </a-button>
          </a-form-item>
        </a-form>
        </transition>

        <div class="sign-in__social" aria-label="Redes sociales">
          <a class="sign-in__social-btn sign-in__social-btn--facebook" href="#" aria-label="Facebook" @click.prevent>
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path
                fill="currentColor"
                d="M13.5 22v-7.2h2.4l.4-2.8h-2.8V10.2c0-.8.2-1.4 1.4-1.4h1.6V6.3c-.3 0-1.3-.1-2.4-.1-2.4 0-4 1.5-4 4.2V12H7.8v2.8h2.3V22h3.4z"
              />
            </svg>
            <span>Facebook</span>
          </a>

          <a class="sign-in__social-btn sign-in__social-btn--x" href="#" aria-label="X" @click.prevent>
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path
                fill="currentColor"
                d="M18.9 3H21l-6.6 7.5L22 21h-6.2l-4.9-6.4L5.2 21H3l7.1-8.1L2 3h6.3l4.4 5.8L18.9 3zm-2.2 16h1.7L7.3 4.9H5.5L16.7 19z"
              />
            </svg>
            <span>X</span>
          </a>

          <a class="sign-in__social-btn sign-in__social-btn--instagram" href="#" aria-label="Instagram" @click.prevent>
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path
                fill="currentColor"
                d="M7.5 2h9A5.5 5.5 0 0 1 22 7.5v9A5.5 5.5 0 0 1 16.5 22h-9A5.5 5.5 0 0 1 2 16.5v-9A5.5 5.5 0 0 1 7.5 2zm0 2A3.5 3.5 0 0 0 4 7.5v9A3.5 3.5 0 0 0 7.5 20h9A3.5 3.5 0 0 0 20 16.5v-9A3.5 3.5 0 0 0 16.5 4h-9z"
              />
              <path
                fill="currentColor"
                d="M12 7.2A4.8 4.8 0 1 1 7.2 12 4.8 4.8 0 0 1 12 7.2zm0 2A2.8 2.8 0 1 0 14.8 12 2.8 2.8 0 0 0 12 9.2z"
              />
              <path fill="currentColor" d="M17.6 6.7a1.2 1.2 0 1 1-1.2-1.2 1.2 1.2 0 0 1 1.2 1.2z" />
            </svg>
            <span>Instagram</span>
          </a>

          <a class="sign-in__social-btn sign-in__social-btn--tiktok" href="#" aria-label="TikTok" @click.prevent>
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path
                fill="currentColor"
                d="M16.7 3c.4 2.2 2 3.8 4.3 4.1v3a8.5 8.5 0 0 1-4.1-1.4v7.2a6.5 6.5 0 1 1-6.5-6.5c.4 0 .9 0 1.3.1v3.2a3.4 3.4 0 1 0 2.1 3.2V3h2.9z"
              />
            </svg>
            <span>TikTok</span>
          </a>

          <a class="sign-in__social-btn sign-in__social-btn--whatsapp" href="#" aria-label="WhatsApp" @click.prevent>
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path
                fill="currentColor"
                d="M12 2a9.9 9.9 0 0 0-8.6 14.8L2 22l5.4-1.4A9.9 9.9 0 1 0 12 2zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-3.2.8.9-3.1-.2-.3A8 8 0 1 1 12 20z"
              />
              <path
                fill="currentColor"
                d="M16.9 13.8c-.2-.1-1.4-.7-1.7-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.5.1a6.6 6.6 0 0 1-1.9-1.2 7 7 0 0 1-1.3-1.6c-.1-.2 0-.4.1-.5l.4-.5c.1-.2.1-.3.2-.5s0-.3 0-.5-.6-1.5-.8-2-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3s-1 1-1 2.5 1 3 1.1 3.2a11.4 11.4 0 0 0 4.4 4.1c.6.3 1 .4 1.4.5.6.2 1.1.2 1.5.1.5-.1 1.4-.6 1.6-1.1s.2-1 .1-1.1-.2-.1-.4-.2z"
              />
            </svg>
            <span>WhatsApp</span>
          </a>
        </div>
      </a-col>
    </a-row>

    <!-- Modal for forced password change -->
    <a-modal
      title="Cambiar contraseña"
      :visible="showForcePasswordChange"
      :closable="false"
      :maskClosable="false"
      :footer="null"
      width="400px"
    >
      <a-form :form="passwordForm" @submit.prevent="handleForcePasswordChange">
        <a-alert
          message="Debes cambiar tu contraseña para continuar"
          description="Por seguridad, tu contraseña temporal debe ser reemplazada."
          type="warning"
          show-icon
          style="margin-bottom: 16px;"
        />
        
        <a-form-item label="Nueva contraseña">
          <a-input
            v-decorator="['password', { 
              rules: [
                { required: true, message: 'Ingresa tu nueva contraseña.' },
                { min: 6, message: 'Mínimo 6 caracteres.' }
              ] 
            }]"
            type="password"
            placeholder="Nueva contraseña"
          />
        </a-form-item>

        <a-form-item label="Confirmar contraseña">
          <a-input
            v-decorator="['password_confirmation', { 
              rules: [
                { required: true, message: 'Confirma tu nueva contraseña.' },
                { validator: this.compareToFirstPassword }
              ] 
            }]"
            type="password"
            placeholder="Confirmar contraseña"
          />
        </a-form-item>

        <a-form-item>
          <a-button type="primary" html-type="submit" :loading="changingPassword" block>
            Cambiar contraseña
          </a-button>
        </a-form-item>
      </a-form>
    </a-modal>

    <!-- Modal for password reset request -->
    <a-modal
      title="Solicitar Restablecimiento de Contraseña"
      :visible="showPasswordReset"
      @cancel="closePasswordResetModal"
      :footer="null"
      width="450px"
    >
      <a-form :form="resetPasswordForm" @submit.prevent="handlePasswordResetRequest">
        <a-alert
          message="Solicitud de restablecimiento"
          description="Ingresa tu correo electrónico para solicitar el restablecimiento de tu contraseña. Un administrador revisará tu solicitud."
          type="info"
          show-icon
          style="margin-bottom: 20px;"
        />
        
        <a-form-item label="Correo electrónico">
          <a-input
            v-decorator="['reset_email', { 
              rules: [
                { required: true, message: 'Ingresa tu correo electrónico.' },
                { type: 'email', message: 'Ingresa un correo válido.' }
              ] 
            }]"
            placeholder="correo@ejemplo.com"
            :disabled="resettingPassword"
          />
        </a-form-item>

        <a-form-item label="Motivo de la solicitud (opcional)">
          <a-textarea
            v-decorator="['reset_reason']"
            placeholder="Describe brevemente por qué necesitas restablecer tu contraseña..."
            :rows="3"
            :disabled="resettingPassword"
          />
        </a-form-item>

        <a-form-item>
          <a-button 
            type="primary" 
            html-type="submit" 
            :loading="resettingPassword" 
            block
            style="margin-bottom: 8px;"
          >
            <span v-if="resettingPassword">Enviando solicitud...</span>
            <span v-else>Enviar Solicitud</span>
          </a-button>
          
          <a-button 
            type="default" 
            @click="closePasswordResetModal"
            :disabled="resettingPassword"
            block
          >
            Cancelar
          </a-button>
        </a-form-item>
      </a-form>
    </a-modal>
  </div>
</template>

<script>
import axios from "axios";

export default {
  data() {
    return {
      rememberMe: true,
      loginError: null,
      loginErrorDesc: null,
      loading: false,
      pageLoading: true,
      pageLoadingTimer: null,
      showForcePasswordChange: false,
      changingPassword: false,
      tempUser: null,
      tempToken: null,
      tempPassword: null, // Store the password used for login
      showPasswordReset: false,
      resettingPassword: false,
    };
  },
  beforeCreate() {
    this.form = this.$form.createForm(this, { name: "normal_login" });
    this.passwordForm = this.$form.createForm(this, { name: "password_change_form" });
    this.resetPasswordForm = this.$form.createForm(this, { name: "reset_password_form" });
  },
  mounted() {
    // Fondo "tema escolar" solo para esta pantalla
    if (typeof document !== "undefined") {
      document.body.classList.add("theme-school-login");
    }

    // Preloader corto para dar sensación de carga
    this.pageLoadingTimer = setTimeout(() => {
      this.pageLoading = false;
      this.pageLoadingTimer = null;
    }, 650);
  },
  beforeDestroy() {
    if (this.pageLoadingTimer) {
      clearTimeout(this.pageLoadingTimer);
      this.pageLoadingTimer = null;
    }
    if (typeof document !== "undefined") {
      document.body.classList.remove("theme-school-login");
    }
  },
  methods: {
    handleSubmit() {
      this.loginError = null;
      this.loginErrorDesc = null;

      this.form.validateFields((err, values) => {
        if (!err) {
          this.loading = true;
          axios
            .post("http://localhost:8000/api/login", {
              email: values.email,
              password: values.password,
            })
            .then((response) => {
              const token = response.data.token;
              const user = response.data.user;
              const forcePasswordChange = response.data.force_password_change || false;

              console.log('LOGIN RESPONSE:', { forcePasswordChange, user: user.email });

              if (forcePasswordChange) {
                // Store temporary data and show password change modal
                this.tempUser = user;
                this.tempToken = token;
                this.tempPassword = values.password; // Store the password used for login
                this.showForcePasswordChange = true;
                this.loading = false;
                return;
              }

              // Normal login flow
              if (this.rememberMe) {
                localStorage.setItem("authToken", token);
                localStorage.setItem("user", JSON.stringify(user));
              } else {
                sessionStorage.setItem("authToken", token);
                sessionStorage.setItem("user", JSON.stringify(user));
              }

              // limpiar cache de pantallas para que se recargue según el rol del usuario
              localStorage.removeItem("allowedScreens");
              sessionStorage.removeItem("allowedScreens");

              axios.defaults.headers.common["Authorization"] = `Bearer ${token}`;
              this.$router.push("/dashboard");
            })
            .catch((error) => {
              const status = error?.response?.status;
              const data = error?.response?.data;

              if (!error.response) {
                // No hubo respuesta del servidor
                this.loginError = "Sin conexión";
                this.loginErrorDesc = "No pudimos comunicarnos con el servidor. Verifica tu red e intenta nuevamente.";
                return;
              }

              if (status === 401) {
                this.loginError = "Credenciales inválidas";
                this.loginErrorDesc = "Revisa tu correo/usuario y tu contraseña e intenta nuevamente.";
                return;
              }

              if (status === 422) {
                const errors = data?.errors;
                const firstField = errors && Object.keys(errors)[0];
                const firstMsg = firstField && Array.isArray(errors[firstField]) ? errors[firstField][0] : null;

                this.loginError = "Revisa tus datos";
                this.loginErrorDesc = firstMsg || "Algunos datos no cumplen el formato esperado.";
                return;
              }

              this.loginError = data?.message || "No pudimos iniciar sesión";
              this.loginErrorDesc = "Intenta nuevamente en unos segundos.";
            })
            .finally(() => {
              this.loading = false;
            });
        }
      });
    },
    compareToFirstPassword(rule, value, callback) {
      const form = this.passwordForm;
      if (value && value !== form.getFieldValue('password')) {
        callback('Las contraseñas no coinciden.');
      } else {
        callback();
      }
    },
    handleForcePasswordChange() {
      this.passwordForm.validateFields((err, values) => {
        if (!err) {
          this.changingPassword = true;
          
          axios.post("http://localhost:8000/api/me/change-password", {
            current_password: this.tempPassword, // Use the password used for login
            password: values.password,
            password_confirmation: values.password_confirmation,
          }, {
            headers: {
              Authorization: `Bearer ${this.tempToken}`,
              'Content-Type': 'application/json',
            }
          })
          .then(() => {
            // Password changed successfully, now store auth data and redirect
            if (this.rememberMe) {
              localStorage.setItem("authToken", this.tempToken);
              localStorage.setItem("user", JSON.stringify(this.tempUser));
            } else {
              sessionStorage.setItem("authToken", this.tempToken);
              sessionStorage.setItem("user", JSON.stringify(this.tempUser));
            }

            // limpiar cache de pantallas para que se recargue según el rol del usuario
            localStorage.removeItem("allowedScreens");
            sessionStorage.removeItem("allowedScreens");

            axios.defaults.headers.common["Authorization"] = `Bearer ${this.tempToken}`;
            this.$message.success("Contraseña cambiada correctamente.");
            this.$router.push("/dashboard");
          })
          .catch((error) => {
            const msg = error?.response?.data?.message || "No se pudo cambiar la contraseña.";
            this.$message.error(msg);
          })
          .finally(() => {
            this.changingPassword = false;
          });
        }
      });
    },
    showPasswordResetModal() {
      this.showPasswordReset = true;
      this.resettingPassword = false;
    },
    closePasswordResetModal() {
      this.showPasswordReset = false;
      this.resetPasswordForm.resetFields();
      this.resettingPassword = false;
    },
    handlePasswordResetRequest() {
      this.resetPasswordForm.validateFields((err, values) => {
        if (!err) {
          this.resettingPassword = true;
          
          const resetData = {
            email: values.reset_email,
            reason: values.reset_reason || 'Solicitud de restablecimiento de contraseña',
          };

          axios.post("http://localhost:8000/api/password-reset-request", resetData)
            .then((response) => {
              this.$message.success('Solicitud enviada correctamente. Un administrador revisará tu petición.');
              this.closePasswordResetModal();
            })
            .catch((error) => {
              const status = error?.response?.status;
              const data = error?.response?.data;

              if (status === 404) {
                this.$message.error('El correo electrónico no está registrado en el sistema.');
              } else if (status === 422) {
                const message = data?.message || 'Error en los datos proporcionados.';
                this.$message.error(message);
              } else if (status === 429) {
                this.$message.error('Ya has solicitado un restablecimiento recientemente. Por favor, espera un poco.');
              } else {
                this.$message.error('No se pudo enviar la solicitud. Intenta nuevamente.');
              }
            })
            .finally(() => {
              this.resettingPassword = false;
            });
        }
      });
    },
  },
};
</script>

<style scoped>
/* Botón de restablecimiento de contraseña */
.sign-in__reset-btn {
  color: #7c3aed;
  font-weight: 500;
  padding: 8px 0;
  transition: all 0.3s ease;
}

.sign-in__reset-btn:hover {
  color: #6d28d9;
  text-decoration: underline;
}

.sign-in__reset-btn:disabled {
  color: #d1d5db;
  cursor: not-allowed;
}

/* Contenedor principal del login - centrado verticalmente */
.sign-in {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 20px;
}

/* Fila principal - asegura centrado completo */
.sign-in__row {
  width: 100%;
  min-height: 100vh;
}

/* Posiciona la alerta flotante arriba y centrada */
.floating-alert {
  position: fixed;
  top: 30px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 2000;
  width: 100%;
  max-width: 400px;
  border-radius: 14px;
  box-shadow: 0 18px 60px rgba(15, 23, 42, 0.22);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  transition: all 0.5s ease;
}

/* En pantallas grandes, que el alert acompañe el ancho del card */
@media (min-width: 1200px) {
  .floating-alert {
    max-width: 520px;
  }
}

/* Transición fade suave */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.6s ease, transform 0.6s ease;
}
.fade-enter,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Animación de entrada del formulario */
.sign-in-card-enter-active,
.sign-in-card-leave-active {
  transition: opacity 420ms ease, transform 420ms ease;
}
.sign-in-card-enter,
.sign-in-card-leave-to {
  opacity: 0;
  transform: translateY(8px);
}

/* Preloader sin spinner (barra con brillo) */
.sign-in__preload-bar {
  width: min(320px, 86vw);
  height: 10px;
  border-radius: 999px;
  background: linear-gradient(90deg, rgba(124, 58, 237, 0.20), rgba(6, 182, 212, 0.18), rgba(34, 197, 94, 0.18));
  position: relative;
  overflow: hidden;
}
.sign-in__preload-bar::after {
  content: "";
  position: absolute;
  inset: 0;
  transform: translateX(-70%);
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.55), transparent);
  animation: sign-in-shimmer 1100ms ease-in-out infinite;
}

/* Botón con shimmer (sin spinner) */
.sign-in__btn--loading {
  position: relative;
  overflow: hidden;
}
.sign-in__btn-text {
  position: relative;
  z-index: 1;
}
.sign-in__btn-shimmer {
  position: absolute;
  inset: 0;
  opacity: 0.9;
  background: linear-gradient(90deg, rgba(255,255,255,0.0), rgba(255,255,255,0.25), rgba(255,255,255,0.0));
  transform: translateX(-70%);
  animation: sign-in-shimmer 900ms ease-in-out infinite;
}

@keyframes sign-in-shimmer {
  0% { transform: translateX(-70%); }
  60% { transform: translateX(70%); }
  100% { transform: translateX(70%); }
}
</style>
