<template>
  <a-modal
    v-model="visible"
    title="Anuncio"
    ok-text="Entendido"
    @ok="markAsSeen"
    :footer="null"
  >
    <div v-if="currentAnnouncement">
      <h3>{{ currentAnnouncement.title }}</h3>
      <p>{{ currentAnnouncement.message || currentAnnouncement.content }}</p>
      <a-button type="primary" @click="markAsSeen">Cerrar</a-button>
    </div>
  </a-modal>
</template>

<script>
import { getToken } from '@/utils/auth';
import axios from 'axios';

export default {
  props: {
    userId: {
      type: Number,
      required: true,
    },
  },
  data() {
    return {
      visible: false,
      announcements: [],
      currentAnnouncement: null,
    };
  },
  methods: {
    async fetchAnnouncements() {
      try {
        const token = getToken();
        const response = await axios.get('http://localhost:8000/api/announcements', {
          headers: { Authorization: `Bearer ${token}` },
        });
        // No existe "seen" en backend aún: por ahora mostramos el más reciente
        this.announcements = Array.isArray(response.data) ? response.data : [];
        if (this.announcements.length) {
          this.currentAnnouncement = this.announcements[0];
          this.visible = true;
        }
      } catch (error) {
        console.error('Error fetching announcements:', error);
      }
    },
    async markAsSeen() {
      if (!this.currentAnnouncement) return;
      try {
        this.visible = false;
      } catch (error) {
        console.error('Error marking as seen:', error);
      }
    },
  },
  mounted() {
    this.fetchAnnouncements();
  },
};
</script>
