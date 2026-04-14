<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-card">
      <header class="modal-header">
        <h2>RECHAZAR OFERTA</h2>
        <button type="button" class="close-btn" @click="$emit('close')">x</button>
      </header>

      <form class="modal-form" @submit.prevent="handleSubmit">
        <p class="helper-text">
          Indica el motivo de rechazo para la oferta #{{ offer?.id }}.
        </p>

        <label for="reject_reason">Motivo</label>
        <textarea
          id="reject_reason"
          v-model="reason"
          class="reason-textarea"
          rows="4"
          maxlength="255"
          placeholder="Escribe aquí el motivo del rechazo"
          required
        ></textarea>

        <p v-if="errorMessage" class="error-text">{{ errorMessage }}</p>

        <div class="confirm-actions">
          <button type="button" class="cancel-btn" :disabled="isSubmitting" @click="$emit('close')">Cancelar</button>
          <button type="submit" class="confirm-btn" :disabled="isSubmitting">Rechazar oferta</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  offer: {
    type: Object,
    required: true,
  },
  isSubmitting: {
    type: Boolean,
    default: false,
  },
  errorMessage: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['close', 'submit'])
const reason = ref('')

const handleSubmit = () => {
  emit('submit', { rao_rebuig: reason.value.trim() })
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 30, 51, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 60;
}

.modal-card {
  width: 390px;
  border-radius: 12px;
  background: #eaf3f8;
  border: 1px solid #89c4f5;
  padding: 0.9rem;
  box-shadow: 0 14px 30px rgba(0, 0, 0, 0.25);
}

.modal-header {
  background: #09253b;
  color: #ffffff;
  border-radius: 7px;
  padding: 0.45rem 0.75rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.8rem;
}

.modal-header h2 {
  font-size: 1.25rem;
  font-weight: 800;
  margin: 0;
  letter-spacing: 0.01em;
}

.close-btn {
  border: none;
  background: transparent;
  color: #ffffff;
  font-size: 1.4rem;
  font-weight: 800;
  cursor: pointer;
  line-height: 1;
}

.modal-form {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.helper-text {
  margin: 0;
  color: #0a2134;
  font-size: 0.9rem;
  font-weight: 600;
}

.modal-form label {
  font-size: 0.85rem;
  font-weight: 700;
  color: #0a2134;
  margin-top: 0.25rem;
}

.reason-textarea {
  border: 1px solid #89c4f5;
  background: #ffffff;
  border-radius: 6px;
  padding: 0.6rem;
  resize: vertical;
  min-height: 94px;
}

.error-text {
  margin: 0.1rem 0 0;
  color: #b42318;
  font-size: 0.84rem;
  font-weight: 700;
}

.confirm-actions {
  margin-top: 0.8rem;
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

.cancel-btn,
.confirm-btn {
  border: none;
  border-radius: 8px;
  height: 36px;
  padding: 0 0.75rem;
  font-weight: 700;
  cursor: pointer;
}

.cancel-btn {
  background: #6b7280;
  color: #ffffff;
}

.confirm-btn {
  background: #b42318;
  color: #ffffff;
}

.cancel-btn:disabled,
.confirm-btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}
</style>
