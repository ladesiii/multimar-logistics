<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-card">
      <header class="modal-header">
        <h2>ELIMINAR OFERTA</h2>
        <button type="button" class="close-btn" @click="$emit('close')">x</button>
      </header>

      <p class="confirm-text">
        Seguro que quieres eliminar la oferta #{{ offer?.id }}{{ offer?.client ? ` de ${offer.client}` : '' }}?
      </p>

      <div class="confirm-actions">
        <button type="button" class="cancel-btn" @click="$emit('close')">Cancelar</button>
        <button type="button" class="confirm-btn" @click="$emit('confirm')">Aceptar</button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  offer: {
    type: Object,
    required: true,
  },
})

defineEmits(['close', 'confirm'])
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
  width: 320px;
  border-radius: 12px;
  background: #eaf3f8;
  border: 1px solid #89c4f5;
  padding: 0.9rem;
  box-shadow: 0 14px 30px rgba(0, 0, 0, 0.2);
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
  font-size: 1.2rem;
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

.confirm-text {
  margin: 0;
  color: #0a2134;
  font-size: 0.95rem;
  font-weight: 600;
}

.confirm-actions {
  margin-top: 0.9rem;
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

.cancel-btn,
.confirm-btn {
  border: none;
  border-radius: 8px;
  height: 34px;
  padding: 0 0.75rem;
  font-weight: 700;
  cursor: pointer;
}

.cancel-btn {
  background: #b42318;
  color: #ffffff;
}

.confirm-btn {
  background: #2d8f4e;
  color: #ffffff;
}
</style>
