<template>

	<!-- Settings Drawer -->
	<a-drawer
		class="settings-drawer"
		:class="[ rtl ? 'settings-drawer-rtl' : '' ]"
		:placement="rtl ? 'left' : 'right'"
		:closable="false"
		:visible="showSettingsDrawer"
		width="360"
		:getContainer="() => wrapper"
		@close="$emit('toggleSettingsDrawer', false)"
	>

		<!-- Settings Drawer Close Button -->
		<a-button type="link" class="btn-close" @click="$emit('toggleSettingsDrawer', false)">
			<svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 9 9">
				<g id="close" transform="translate(0.75 0.75)">
					<path id="Path" d="M7.5,0,0,7.5" fill="none" stroke="#000" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="1.5"/>
					<path id="Path-2" data-name="Path" d="M0,0,7.5,7.5" fill="none" stroke="#000" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="1.5"/>
				</g>
			</svg>
		</a-button>
		<!-- / Settings Drawer Close Button -->
		
		<!-- Settings Drawer Content -->
		<div class="drawer-content">
			<h6>Configuraciones</h6>
			<p>Acceso rápido a las configuraciones del sistema.</p>
			<hr>
			
			<!-- Configuraciones Section -->
			<div class="configuraciones-section">
				<div class="config-links">
					<a-button type="link" @click="navigateToConfig('/configuraciones/institucion')" block style="text-align: left; padding: 8px 12px;">
						🏢 Datos de la institución
					</a-button>
					<a-button type="link" @click="navigateToConfig('/configuraciones/logos')" block style="text-align: left; padding: 8px 12px;">
						🖼️ Logos
					</a-button>
					<a-button type="link" @click="navigateToConfig('/configuraciones/parametros-academicos')" block style="text-align: left; padding: 8px 12px;">
						📚 Parámetros académicos
					</a-button>
					<a-button type="link" @click="navigateToConfig('/configuraciones/calendario')" block style="text-align: left; padding: 8px 12px;">
						📅 Calendario escolar
					</a-button>
					<a-button type="link" @click="navigateToConfig('/configuracion/redes-sociales')" block style="text-align: left; padding: 8px 12px;">
						🌐 Redes sociales
					</a-button>
				</div>
			</div>
			<hr>
			
			<!-- Chat Settings Section -->
			<div v-if="canEditChatSettings" class="chat-settings-section">
				<h6>💬 Configuración del Chat</h6>
				<div class="setting-item">
					<label>Habilitar chat de soporte</label>
					<a-switch v-model="chatSettings.enabled" @change="updateChatSettings" />
				</div>
				<div class="setting-item">
					<label>Mostrar botón flotante</label>
					<a-switch v-model="chatSettings.floatingButtonVisible" @change="updateChatSettings" />
				</div>
				<div class="setting-item">
					<label>Días de retención de mensajes</label>
					<a-input-number 
						v-model="chatSettings.retentionDays" 
						:min="1" 
						:max="30" 
						@change="updateChatSettings"
						style="width: 100px;" />
				</div>
				<div class="setting-item">
					<label>Posición del botón</label>
					<a-select v-model="chatSettings.position" @change="updateChatSettings" style="width: 150px;">
						<a-select-option value="bottom-right">Inf. Derecha</a-select-option>
						<a-select-option value="bottom-left">Inf. Izquierda</a-select-option>
						<a-select-option value="top-right">Sup. Derecha</a-select-option>
						<a-select-option value="top-left">Sup. Izquierda</a-select-option>
					</a-select>
				</div>
			</div>
			<div v-else-if="canViewChat" class="chat-settings-section">
				<h6>💬 Configuración del Chat</h6>
				<a-alert 
					message="Permisos insuficientes" 
					description="No tienes permisos para modificar la configuración del chat. Contacta a un administrador."
					type="info" 
					show-icon 
				/>
			</div>

			<!-- Comunicados Settings Section -->
			<div class="comunicados-settings-section">
				<h6>📢 Configuración de Comunicados</h6>
				<div class="setting-item">
					<label>Estado del sistema</label>
					<a-switch v-model="comunicadoSettings.enabled" @change="updateComunicadoSettings" />
				</div>
				<div class="setting-item">
					<label>Mostrar al iniciar sesión</label>
					<a-switch v-model="comunicadoSettings.showOnLogin" @change="updateComunicadoSettings" />
				</div>
				<div class="setting-item">
					<label>Cierre automático</label>
					<a-switch v-model="comunicadoSettings.autoClose" @change="updateComunicadoSettings" />
				</div>
				<div v-if="comunicadoSettings.autoClose" class="setting-item">
					<label>Tiempo de cierre (segundos)</label>
					<a-input-number 
						v-model="comunicadoSettings.autoCloseDelay" 
						:min="5" 
						:max="300" 
						@change="updateComunicadoSettings"
						style="width: 100px;" />
				</div>
				<div class="setting-item">
					<label>Permitir subida de archivos</label>
					<a-switch v-model="comunicadoSettings.allowUploads" @change="updateComunicadoSettings" />
				</div>
				<div v-if="comunicadoSettings.allowUploads" class="setting-item">
					<label>Tamaño máximo (MB)</label>
					<a-input-number 
						v-model="comunicadoSettings.maxFileSize" 
						:min="1" 
						:max="100" 
						@change="updateComunicadoSettings"
						style="width: 100px;" />
				</div>
				<div class="setting-item">
					<label>Requerir aceptación</label>
					<a-switch v-model="comunicadoSettings.requireAcceptance" @change="updateComunicadoSettings" />
				</div>
				<div class="setting-item">
					<a-button type="primary" size="small" @click="openComunicadoSettings">
						<a-icon type="setting" />
						Configuración Avanzada
					</a-button>
				</div>
			</div>
			<hr>
			
			<div class="sidebar-color">
				<h6>Color de Iconos</h6>
				<a-radio-group v-model="sidebarColorModel" @change="$emit('updateSidebarColor', $event.target.value)" defaultValue="primary">
					<a-radio-button value="primary" class="bg-primary"></a-radio-button>
					<a-radio-button value="secondary" class="bg-secondary"></a-radio-button>
					<a-radio-button value="success" class="bg-success"></a-radio-button>
					<a-radio-button value="danger" class="bg-danger"></a-radio-button>
					<a-radio-button value="warning" class="bg-warning"></a-radio-button>
					<a-radio-button value="black" class="bg-dark"></a-radio-button>
				</a-radio-group>
			</div>
			<div class="sidenav-type">
				<h6>Estilo de Fondo</h6>
				<p>Puede seleccionar el tipo de fondo del menu de navegación.</p>
				<a-radio-group button-style="solid" v-model="sidebarThemeModel" @change="$emit('updateSidebarTheme', $event.target.value)" defaultValue="primary">
					<a-radio-button value="light">TRANSPARENTE</a-radio-button>
					<a-radio-button value="white">BLANCO</a-radio-button>
				</a-radio-group>
			</div>
			<div class="navbar-fixed">
				<h6>Barra de navegacion fija</h6>
				<a-switch default-checked v-model="navbarFixedModel" @change="$emit('toggleNavbarPosition', navbarFixedModel)" />
			</div>
			<div class="github-stars">
				<gh-btns-star slug="creativetimofficial/muse-vue-ant-design-dashboard" show-count></gh-btns-star>
			</div>
		</div>
		<!-- / Settings Drawer Content -->

	</a-drawer>
	<!-- / Settings Drawer -->

</template>

<script>
	import 'vue-github-buttons/dist/vue-github-buttons.css'; // Stylesheet
	import VueGitHubButtons from 'vue-github-buttons';
	import Vue from 'vue';
	import chatService from '@/services/chatService';
	import comunicadoService from '@/services/comunicadoService';
	import { permissionsMixin } from '@/utils/permissions';
	Vue.use(VueGitHubButtons, { useCache: true });

	export default ({
		mixins: [permissionsMixin],
		props: {
			// Settings drawer visiblility status.
			showSettingsDrawer: {
				type: Boolean,
				default: false,
			},
			
			// Main sidebar color.
			sidebarColor: {
				type: String,
				default: "primary",
			},
			
			// Main sidebar theme : light, white, dark.
			sidebarTheme: {
				type: String,
				default: "light",
			},

			// Header fixed status.
			navbarFixed: {
				type: Boolean,
				default: false,
			},

			// Drawer direction.
			rtl: {
				type: Boolean,
				default: false,
			},
		},
		computed: {
			canEditChatSettings() {
				return this.$canEditChatSettings();
			},
			canViewChat() {
				return this.$canViewChat();
			}
		},
		data() {
			return {
				// The wrapper element to attach dropdowns to.
				wrapper: document.body,
				
				// Main sidebar color.
				sidebarColorModel: this.sidebarColor,
				
				// Main sidebar theme : light, white, dark.
				sidebarThemeModel: this.sidebarTheme,

				// Header fixed status.
				navbarFixedModel: this.navbarFixed,
				
				// Chat settings
				chatSettings: { ...chatService.getSettings() },
				
				// Comunicados settings
				comunicadoSettings: { ...comunicadoService.getSettings() },
			}
		},
		mounted: function(){
			// Set the wrapper to the proper element, layout wrapper.
			this.wrapper = document.getElementById('layout-dashboard') ;
		},
		methods: {
			navigateToConfig(path) {
				// Close the drawer first
				this.$emit('toggleSettingsDrawer', false);
				// Navigate to the configuration page
				this.$router.push(path);
			},
			updateChatSettings() {
				if (!this.canEditChatSettings()) {
					this.$message.error('No tienes permisos para modificar la configuración del chat');
					return;
				}
				
				if (chatService.saveSettings(this.chatSettings)) {
					this.$message.success('Configuración del chat actualizada');
					// Emit event to notify chat components
					this.$emit('chat-settings-changed', this.chatSettings);
				} else {
					this.$message.error('Error al guardar la configuración del chat');
				}
			},
			updateComunicadoSettings() {
				if (comunicadoService.saveSettings(this.comunicadoSettings)) {
					this.$message.success('Configuración de comunicados actualizada');
					// Emit event to notify components
					this.$emit('comunicado-settings-changed', this.comunicadoSettings);
				} else {
					this.$message.error('Error al guardar la configuración de comunicados');
				}
			},
			openComunicadoSettings() {
				// Navigate to comunicado settings page
				this.navigateToConfig('/comunicados/settings');
			}
		},
	})

</script>

<style scoped>
.chat-settings-section {
	margin-bottom: 20px;
}

.comunicados-settings-section {
	margin-bottom: 20px;
}

.chat-settings-section h6,
.comunicados-settings-section h6 {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 12px;
	padding: 8px 0;
}

.setting-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 12px;
	padding: 8px 0;
}

.setting-item label {
	font-size: 13px;
	color: #595959;
	margin: 0;
}

.setting-item .ant-switch,
.setting-item .ant-input-number,
.setting-item .ant-select {
	margin-left: 12px;
}

.chat-settings-section h6 {
	margin-bottom: 16px;
	color: #262626;
	font-weight: 500;
}
</style>
