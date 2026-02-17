<template>
  <div v-if="visible" :class="['chat-window', `position-${settings.position}`]">
    <div class="chat-header">
      <div class="header-left">
        <a-icon type="message" class="chat-icon" />
        <span class="chat-title">
          {{ currentConversation ? currentConversation.title : 'Soporte Técnico' }}
        </span>
        <span v-if="currentConversation && currentConversation.recipientName" class="recipient-info">
          con {{ currentConversation.recipientName }}
        </span>
      </div>
      <div class="header-right">
        <a-button type="text" size="small" @click="showSettings = true">
          <a-icon type="setting" />
        </a-button>
        <a-button type="text" size="small" @click="minimize">
          <a-icon type="minus" />
        </a-button>
        <a-button type="text" size="small" @click="close">
          <a-icon type="close" />
        </a-button>
      </div>
    </div>

    <div class="chat-body">
      <!-- Conversations sidebar -->
      <div v-if="showConversationsList" class="conversations-sidebar">
        <div class="sidebar-header">
          <h4>Conversaciones</h4>
          <a-button type="primary" size="small" @click="createNewConversation">
            <a-icon type="plus" /> Nuevo
          </a-button>
        </div>
        <div class="conversations-list">
          <div v-for="conversation in conversationsList" 
               :key="conversation.id"
               :class="['conversation-item', { active: conversation.id === currentConversationId }]"
               @click="selectConversation(conversation.id)">
            <div class="conversation-info">
              <div class="conversation-title">{{ conversation.title }}</div>
              <div class="conversation-time">{{ formatTime(conversation.updatedAt) }}</div>
            </div>
            <div v-if="conversation.unreadCount > 0" class="unread-badge">
              {{ conversation.unreadCount }}
            </div>
          </div>
        </div>
      </div>

      <!-- Messages area -->
      <div class="messages-container">
        <div v-if="!currentConversation" class="empty-state">
          <a-icon type="message" style="font-size: 48px; color: #d9d9d9;" />
          <p>Selecciona una conversación o crea una nueva para comenzar</p>
          <a-button type="primary" @click="createNewConversation">
            Nueva Conversación
          </a-button>
        </div>
        
        <template v-else>
          <div class="messages-header">
            <a-button type="text" @click="showConversationsList = !showConversationsList">
              <a-icon type="menu" />
            </a-button>
            <span class="current-conversation-title">{{ currentConversation.title }}</span>
            <a-dropdown>
              <a-button type="text" size="small">
                <a-icon type="more" />
              </a-button>
              <a-menu slot="overlay">
                <a-menu-item @click="renameConversation">
                  <a-icon type="edit" /> Renombrar
                </a-menu-item>
                <a-menu-item @click="deleteConversation" class="danger">
                  <a-icon type="delete" /> Eliminar
                </a-menu-item>
              </a-menu>
            </a-dropdown>
          </div>

          <div ref="messagesContainer" class="messages-list">
            <div v-for="message in currentConversation.messages" 
                 :key="message.id"
                 :class="['message', `message-${message.sender}`]">
              <div class="message-content">
                <div class="message-text">{{ message.content }}</div>
                <div class="message-time">{{ formatMessageTime(message.timestamp) }}</div>
              </div>
            </div>
          </div>

          <div class="message-input">
            <a-input
              v-model="newMessage"
              placeholder="Escribe tu mensaje..."
              @press-enter="sendMessage"
              :disabled="sending">
              <a-button slot="suffix" type="primary" @click="sendMessage" :loading="sending">
                <a-icon type="send" />
              </a-button>
            </a-input>
          </div>
        </template>
      </div>
    </div>

    <!-- Settings Modal -->
    <a-modal
      v-model="showSettings"
      title="Configuración del Chat"
      :footer="null"
      width="400px">
      <a-form layout="vertical">
        <a-form-item label="Días de retención de mensajes">
          <a-input-number
            v-model="tempSettings.retentionDays"
            :min="1"
            :max="30"
            style="width: 100%" />
        </a-form-item>
        <a-form-item label="Mensajes máximos por conversación">
          <a-input-number
            v-model="tempSettings.maxMessages"
            :min="10"
            :max="500"
            style="width: 100%" />
        </a-form-item>
        <a-form-item label="Posición del botón flotante">
          <a-select v-model="tempSettings.position">
            <a-select-option value="bottom-right">Inferior Derecha</a-select-option>
            <a-select-option value="bottom-left">Inferior Izquierda</a-select-option>
            <a-select-option value="top-right">Superior Derecha</a-select-option>
            <a-select-option value="top-left">Superior Izquierda</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="Tema">
          <a-select v-model="tempSettings.theme">
            <a-select-option value="light">Claro</a-select-option>
            <a-select-option value="dark">Oscuro</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item>
          <a-checkbox v-model="tempSettings.enabled">
            Habilitar chat
          </a-checkbox>
        </a-form-item>
        <a-form-item>
          <a-checkbox v-model="tempSettings.floatingButtonVisible">
            Mostrar botón flotante
          </a-checkbox>
        </a-form-item>
        <a-form-item>
          <a-button type="primary" @click="saveSettings" block>
            Guardar Configuración
          </a-button>
        </a-form-item>
      </a-form>
    </a-modal>

    <!-- Rename Modal -->
    <a-modal
      v-model="showRenameModal"
      title="Renombrar Conversación"
      @ok="confirmRename"
      @cancel="showRenameModal = false">
      <a-input
        v-model="newConversationTitle"
        placeholder="Nuevo nombre de la conversación"
        @press-enter="confirmRename" />
    </a-modal>
  </div>
</template>

<script>
import chatService from '@/services/chatService';
import { permissionsMixin } from '@/utils/permissions';

export default {
  name: 'ChatWindow',
  mixins: [permissionsMixin],
  props: {
    visible: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      settings: chatService.getSettings(),
      showSettings: false,
      showConversationsList: false,
      showRenameModal: false,
      tempSettings: { ...chatService.getSettings() },
      conversations: {},
      currentConversationId: null,
      newMessage: '',
      sending: false,
      newConversationTitle: ''
    };
  },
  computed: {
    conversationsList() {
      return Object.values(this.conversations)
        .sort((a, b) => new Date(b.updatedAt) - new Date(a.updatedAt));
    },
    currentConversation() {
      return this.conversations[this.currentConversationId] || null;
    }
  },
  mounted() {
    this.loadConversations();
    this.settingsInterval = setInterval(() => {
      this.settings = chatService.getSettings();
    }, 1000);
  },
  beforeDestroy() {
    if (this.settingsInterval) {
      clearInterval(this.settingsInterval);
    }
  },
  methods: {
    loadConversations() {
      this.conversations = chatService.getConversationsForCurrentUser();
      
      // Select first conversation if none selected
      if (!this.currentConversationId && this.conversationsList.length > 0) {
        this.selectConversation(this.conversationsList[0].id);
      }
    },
    selectConversation(conversationId) {
      this.currentConversationId = conversationId;
      chatService.markAsRead(conversationId);
      this.loadConversations();
      this.$nextTick(() => {
        this.scrollToBottom();
      });
    },
    createNewConversation() {
      const currentUser = chatService.getCurrentUser();
      const availableRecipients = chatService.getAvailableRecipients();
      
      if (availableRecipients.length === 0) {
        this.$message.warning('No hay destinatarios disponibles para chat');
        return;
      }
      
      let conversation;
      
      if (chatService.isCurrentUserSuperUser()) {
        // Super users can create conversations with anyone
        conversation = chatService.createConversation('Nueva Conversación');
        if (!conversation) {
          this.$message.error('No se pudo crear la conversación');
          return;
        }
      } else {
        // Non-super users automatically chat with first available super user
        const recipient = availableRecipients[0];
        conversation = chatService.createConversationWithRecipient(recipient.id, recipient.name);
        if (!conversation) {
          this.$message.error('No se pudo crear la conversación');
          return;
        }
        
        this.$message.info(`Conversación creada con ${recipient.name}`);
      }
      
      this.loadConversations();
      this.selectConversation(conversation.id);
      this.showConversationsList = false;
    },
    renameConversation() {
      if (this.currentConversation) {
        this.newConversationTitle = this.currentConversation.title;
        this.showRenameModal = true;
      }
    },
    confirmRename() {
      if (this.currentConversation && this.newConversationTitle.trim()) {
        this.currentConversation.title = this.newConversationTitle.trim();
        this.currentConversation.updatedAt = new Date().toISOString();
        chatService.saveConversation(this.currentConversation);
        this.loadConversations();
        this.showRenameModal = false;
      }
    },
    deleteConversation() {
      this.$confirm({
        title: '¿Eliminar conversación?',
        content: 'Esta acción no se puede deshacer',
        okText: 'Eliminar',
        okType: 'danger',
        cancelText: 'Cancelar',
        onOk: () => {
          chatService.deleteConversation(this.currentConversationId);
          this.loadConversations();
          if (this.conversationsList.length > 0) {
            this.selectConversation(this.conversationsList[0].id);
          } else {
            this.currentConversationId = null;
          }
        }
      });
    },
    async sendMessage() {
      if (!this.canUseChat) {
        this.$message.error('No tienes permisos para enviar mensajes en el chat');
        return;
      }
      
      if (!this.newMessage.trim() || this.sending || !this.currentConversation) return;
      
      // Check if user can send message to this conversation's recipient
      if (this.currentConversation.recipientId) {
        if (!chatService.canSendMessageToRecipient(this.currentConversation.recipientId)) {
          this.$message.error('No tienes permisos para enviar mensajes a este destinatario');
          return;
        }
      }
      
      this.sending = true;
      
      try {
        // Add user message
        chatService.addMessage(this.currentConversationId, {
          content: this.newMessage.trim(),
          sender: 'user'
        });
        
        const userMessage = this.newMessage.trim();
        this.newMessage = '';
        this.loadConversations();
        this.scrollToBottom();
        
        // Simulate support response (in real app, this would call an API)
        setTimeout(() => {
          const response = this.generateSupportResponse(userMessage);
          chatService.addMessage(this.currentConversationId, {
            content: response,
            sender: 'support'
          });
          this.loadConversations();
          this.scrollToBottom();
        }, 1000);
        
      } catch (error) {
        console.error('Error sending message:', error);
        this.$message.error('Error al enviar el mensaje');
      } finally {
        this.sending = false;
      }
    },
    generateSupportResponse(userMessage) {
      const responses = [
        'Gracias por tu mensaje. Un agente de soporte te responderá pronto.',
        'He recibido tu consulta. ¿En qué más puedo ayudarte?',
        'Entiendo tu solicitud. Estoy trabajando en una solución para ti.',
        'Tu mensaje ha sido recibido. Te responderemos a la brevedad.',
        'Gracias por contactarnos. ¿Hay algo más en lo que pueda asistirte?'
      ];
      return responses[Math.floor(Math.random() * responses.length)];
    },
    saveSettings() {
      if (!this.canEditChatSettings) {
        this.$message.error('No tienes permisos para modificar la configuración del chat');
        return;
      }
      
      if (chatService.saveSettings(this.tempSettings)) {
        this.settings = { ...this.tempSettings };
        this.$message.success('Configuración guardada');
        this.showSettings = false;
        this.$emit('settings-changed', this.settings);
      } else {
        this.$message.error('Error al guardar la configuración');
      }
    },
    minimize() {
      this.$emit('minimize');
    },
    close() {
      this.$emit('close');
    },
    scrollToBottom() {
      if (this.$refs.messagesContainer) {
        this.$refs.messagesContainer.scrollTop = this.$refs.messagesContainer.scrollHeight;
      }
    },
    formatTime(timestamp) {
      const date = new Date(timestamp);
      const now = new Date();
      const diffInHours = (now - date) / (1000 * 60 * 60);
      
      if (diffInHours < 1) {
        return 'Ahora';
      } else if (diffInHours < 24) {
        return date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
      } else if (diffInHours < 48) {
        return 'Ayer';
      } else {
        return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' });
      }
    },
    formatMessageTime(timestamp) {
      return new Date(timestamp).toLocaleTimeString('es-ES', { 
        hour: '2-digit', 
        minute: '2-digit' 
      });
    }
  },
  watch: {
    visible(newVal) {
      if (newVal) {
        this.loadConversations();
        this.$nextTick(() => {
          this.scrollToBottom();
        });
      }
    }
  }
};
</script>

<style scoped>
.chat-window {
  position: fixed;
  width: 380px;
  height: 600px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  z-index: 1001;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.position-bottom-right {
  bottom: 100px;
  right: 30px;
}

.position-bottom-left {
  bottom: 100px;
  left: 30px;
}

.position-top-right {
  top: 30px;
  right: 30px;
}

.position-top-left {
  top: 30px;
  left: 30px;
}

.chat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #1890ff;
  color: white;
  border-radius: 8px 8px 0 0;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 8px;
}

.chat-title {
  font-weight: 500;
  color: #333;
}

.recipient-info {
  font-size: 12px;
  color: #666;
  font-weight: normal;
  margin-left: 8px;
}

.chat-icon {
  font-size: 16px;
}

.header-right {
  display: flex;
  gap: 4px;
}

.header-right .ant-btn {
  color: white;
  border: none;
}

.header-right .ant-btn:hover {
  background: rgba(255, 255, 255, 0.1);
}

.chat-body {
  display: flex;
  flex: 1;
  overflow: hidden;
}

.conversations-sidebar {
  width: 200px;
  background: #f5f5f5;
  border-right: 1px solid #e8e8e8;
  display: flex;
  flex-direction: column;
}

.sidebar-header {
  padding: 12px;
  border-bottom: 1px solid #e8e8e8;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.sidebar-header h4 {
  margin: 0;
  font-size: 14px;
  font-weight: 500;
}

.conversations-list {
  flex: 1;
  overflow-y: auto;
}

.conversation-item {
  padding: 12px;
  cursor: pointer;
  border-bottom: 1px solid #e8e8e8;
  display: flex;
  justify-content: space-between;
  align-items: center;
  transition: background 0.2s;
}

.conversation-item:hover {
  background: #e6f7ff;
}

.conversation-item.active {
  background: #1890ff;
  color: white;
}

.conversation-info {
  flex: 1;
  min-width: 0;
}

.conversation-title {
  font-size: 13px;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.conversation-time {
  font-size: 11px;
  opacity: 0.7;
  margin-top: 2px;
}

.unread-badge {
  background: #ff4d4f;
  color: white;
  border-radius: 10px;
  padding: 2px 6px;
  font-size: 11px;
  min-width: 18px;
  text-align: center;
}

.messages-container {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.empty-state {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 20px;
  text-align: center;
}

.empty-state p {
  margin: 16px 0;
  color: #8c8c8c;
}

.messages-header {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  border-bottom: 1px solid #e8e8e8;
  gap: 8px;
}

.current-conversation-title {
  flex: 1;
  font-weight: 500;
  font-size: 14px;
}

.messages-list {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.message {
  display: flex;
  max-width: 80%;
}

.message-user {
  align-self: flex-end;
}

.message-support {
  align-self: flex-start;
}

.message-content {
  background: #f0f0f0;
  padding: 8px 12px;
  border-radius: 12px;
  max-width: 100%;
}

.message-user .message-content {
  background: #1890ff;
  color: white;
}

.message-text {
  word-wrap: break-word;
  white-space: pre-wrap;
}

.message-time {
  font-size: 11px;
  opacity: 0.7;
  margin-top: 4px;
}

.message-input {
  padding: 16px;
  border-top: 1px solid #e8e8e8;
}

.danger {
  color: #ff4d4f;
}

/* Dark theme */
.chat-window[data-theme="dark"] {
  background: #1f1f1f;
  color: white;
}

.chat-window[data-theme="dark"] .conversations-sidebar {
  background: #2f2f2f;
  border-right-color: #434343;
}

.chat-window[data-theme="dark"] .conversation-item {
  border-bottom-color: #434343;
}

.chat-window[data-theme="dark"] .conversation-item:hover {
  background: #262626;
}

.chat-window[data-theme="dark"] .messages-header {
  border-bottom-color: #434343;
}

.chat-window[data-theme="dark"] .message-content {
  background: #2f2f2f;
}

.chat-window[data-theme="dark"] .message-input {
  border-top-color: #434343;
}
</style>
