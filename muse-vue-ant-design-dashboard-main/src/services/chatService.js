import { v4 as uuidv4 } from 'uuid';
import { getUser } from '@/utils/auth';
import { isSuperUser, getChatPermissions } from '@/utils/permissions';

class ChatService {
  constructor() {
    this.storageKey = 'obs_chat_conversations';
    this.settingsKey = 'obs_chat_settings';
    this.defaultSettings = {
      enabled: true,
      floatingButtonVisible: true,
      retentionDays: 7,
      maxMessages: 100,
      position: 'bottom-right',
      theme: 'light'
    };
  }

  // Get current user info
  getCurrentUser() {
    return getUser();
  }

  // Check if current user is super user
  isCurrentUserSuperUser() {
    return isSuperUser();
  }

  // Get available recipients for current user
  getAvailableRecipients() {
    const currentUser = this.getCurrentUser();
    if (!currentUser) return [];

    // If current user is super user, they can see all users
    if (this.isCurrentUserSuperUser()) {
      return this.getAllUsers();
    }

    // If current user is not super user, they can only message super users
    return this.getSuperUsers();
  }

  // Get all super users (mock implementation - in real app, this would come from API)
  getSuperUsers() {
    // Mock data - in real implementation, this would call the API
    return [
      {
        id: 'super1',
        name: 'Administrador del Sistema',
        role: 'super usuario',
        avatar: null,
        isOnline: true
      }
    ];
  }

  // Get all users (for super users)
  getAllUsers() {
    // Mock data - in real implementation, this would call the API
    return [
      {
        id: 'super1',
        name: 'Administrador del Sistema',
        role: 'super usuario',
        avatar: null,
        isOnline: true
      },
      {
        id: 'user1',
        name: 'Juan Pérez',
        role: 'maestro',
        avatar: null,
        isOnline: false
      },
      {
        id: 'user2',
        name: 'María García',
        role: 'estudiante',
        avatar: null,
        isOnline: true
      }
    ];
  }

  // Create conversation with specific recipient
  createConversationWithRecipient(recipientId, recipientName) {
    const currentUser = this.getCurrentUser();
    const conversation = {
      id: uuidv4(),
      title: `Chat con ${recipientName}`,
      recipientId: recipientId,
      recipientName: recipientName,
      senderId: currentUser.id,
      senderName: currentUser.name,
      senderRole: currentUser.role,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
      messages: [],
      unreadCount: 0
    };
    
    this.saveConversation(conversation);
    return conversation;
  }

  // Get conversations for current user
  getConversationsForCurrentUser() {
    const currentUser = this.getCurrentUser();
    if (!currentUser) return {};

    const allConversations = this.getConversations();
    const userConversations = {};

    Object.keys(allConversations).forEach(id => {
      const conversation = allConversations[id];
      
      // If current user is super user, show all conversations
      if (this.isCurrentUserSuperUser()) {
        userConversations[id] = conversation;
      }
      // If current user is not super user, only show conversations they started
      else if (conversation.senderId === currentUser.id) {
        userConversations[id] = conversation;
      }
    });

    return userConversations;
  }

  // Check if user can send message to recipient
  canSendMessageToRecipient(recipientId) {
    const currentUser = this.getCurrentUser();
    if (!currentUser) return false;

    // Super users can message anyone
    if (this.isCurrentUserSuperUser()) {
      return true;
    }

    // Non-super users can only message super users
    const superUsers = this.getSuperUsers();
    return superUsers.some(user => user.id === recipientId);
  }

  // Settings management
  getSettings() {
    try {
      const saved = localStorage.getItem(this.settingsKey);
      return saved ? { ...this.defaultSettings, ...JSON.parse(saved) } : this.defaultSettings;
    } catch (error) {
      console.error('Error loading chat settings:', error);
      return this.defaultSettings;
    }
  }

  saveSettings(settings) {
    try {
      localStorage.setItem(this.settingsKey, JSON.stringify(settings));
      return true;
    } catch (error) {
      console.error('Error saving chat settings:', error);
      return false;
    }
  }

  // Conversation management
  getConversations() {
    try {
      const saved = localStorage.getItem(this.storageKey);
      const conversations = saved ? JSON.parse(saved) : {};
      
      // Filter conversations based on retention days
      const settings = this.getSettings();
      const cutoffDate = new Date();
      cutoffDate.setDate(cutoffDate.getDate() - settings.retentionDays);
      
      const filteredConversations = {};
      Object.keys(conversations).forEach(id => {
        const conversation = conversations[id];
        const lastMessage = conversation.messages[conversation.messages.length - 1];
        if (lastMessage && new Date(lastMessage.timestamp) > cutoffDate) {
          filteredConversations[id] = conversation;
        }
      });
      
      // Save filtered conversations back to storage
      if (Object.keys(filteredConversations).length !== Object.keys(conversations).length) {
        localStorage.setItem(this.storageKey, JSON.stringify(filteredConversations));
      }
      
      return filteredConversations;
    } catch (error) {
      console.error('Error loading conversations:', error);
      return {};
    }
  }

  saveConversation(conversation) {
    try {
      const conversations = this.getConversations();
      conversations[conversation.id] = conversation;
      
      // Limit message count
      const settings = this.getSettings();
      if (conversation.messages.length > settings.maxMessages) {
        conversation.messages = conversation.messages.slice(-settings.maxMessages);
      }
      
      localStorage.setItem(this.storageKey, JSON.stringify(conversations));
      return true;
    } catch (error) {
      console.error('Error saving conversation:', error);
      return false;
    }
  }

  createConversation(title = 'Nueva Conversación') {
    const currentUser = this.getCurrentUser();
    const availableRecipients = this.getAvailableRecipients();
    
    // If no available recipients, return null
    if (availableRecipients.length === 0) {
      return null;
    }
    
    // For non-super users, automatically create conversation with first super user
    if (!this.isCurrentUserSuperUser()) {
      const recipient = availableRecipients[0];
      return this.createConversationWithRecipient(recipient.id, recipient.name);
    }
    
    // For super users, create a general conversation or let them choose
    const conversation = {
      id: uuidv4(),
      title: title,
      recipientId: null, // Super users can manage all conversations
      recipientName: null,
      senderId: currentUser.id,
      senderName: currentUser.name,
      senderRole: currentUser.role,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
      messages: [],
      unreadCount: 0
    };
    
    this.saveConversation(conversation);
    return conversation;
  }

  addMessage(conversationId, message) {
    const conversations = this.getConversations();
    const conversation = conversations[conversationId];
    
    if (!conversation) {
      console.error('Conversation not found:', conversationId);
      return null;
    }
    
    const newMessage = {
      id: uuidv4(),
      content: message.content,
      sender: message.sender || 'user',
      timestamp: new Date().toISOString(),
      status: message.status || 'sent'
    };
    
    conversation.messages.push(newMessage);
    conversation.updatedAt = new Date().toISOString();
    
    if (message.sender !== 'user') {
      conversation.unreadCount = (conversation.unreadCount || 0) + 1;
    }
    
    this.saveConversation(conversation);
    return newMessage;
  }

  deleteConversation(conversationId) {
    try {
      const conversations = this.getConversations();
      delete conversations[conversationId];
      localStorage.setItem(this.storageKey, JSON.stringify(conversations));
      return true;
    } catch (error) {
      console.error('Error deleting conversation:', error);
      return false;
    }
  }

  markAsRead(conversationId) {
    const conversations = this.getConversations();
    const conversation = conversations[conversationId];
    
    if (conversation) {
      conversation.unreadCount = 0;
      this.saveConversation(conversation);
      return true;
    }
    return false;
  }

  getUnreadCount() {
    const conversations = this.getConversations();
    return Object.values(conversations).reduce((total, conv) => total + (conv.unreadCount || 0), 0);
  }
}

export default new ChatService();
