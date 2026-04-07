<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-card">
      <header class="modal-header">
        <h2>NUEVO CLIENTE</h2>
        <button type="button" class="close-btn" @click="$emit('close')">x</button>
      </header>

      <form class="modal-form" @submit.prevent="handleSubmit">
        <label for="nom">Nombre</label>
        <input id="nom" v-model="form.nom" type="text" required>

        <label for="cognoms">Apellidos</label>
        <input id="cognoms" v-model="form.cognoms" type="text" required>

        <label for="email">Correo electronico</label>
        <input id="email" v-model="form.email" type="email" required>

        <label for="password">Contraseña</label>
        <input id="password" v-model="form.password" type="password" required>

        <button type="submit" class="submit-btn">Crear Cliente</button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue'

const emit = defineEmits(['close', 'submit'])

const form = reactive({
  nom: '',
  cognoms: '',
  email: '',
  password: '',
})

const handleSubmit = () => {
  emit('submit', { ...form })
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
  z-index: 50;
}

.modal-card {
  width: 340px;
  border-radius: 12px;
  background: #d9e1e7;
  padding: 0.7rem;
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
  font-size: 1.45rem;
  font-weight: 900;
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
  gap: 0.4rem;
}

.modal-form label {
  font-size: 0.85rem;
  font-weight: 700;
  color: #0a2134;
  margin-top: 0.35rem;
}

.modal-form input {
  border: 1px solid #c4ced8;
  background: #f3f6f9;
  border-radius: 6px;
  height: 34px;
  padding: 0 0.6rem;
}

.submit-btn {
  margin-top: 0.85rem;
  border: none;
  border-radius: 8px;
  height: 38px;
  font-weight: 800;
  background: #09253b;
  color: #ffffff;
  cursor: pointer;
}
</style>
