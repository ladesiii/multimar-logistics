
<template>
  
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-card">
      <header class="modal-header">
        <h2>NUEVO USUARIO</h2>
        <button type="button" class="close-btn" @click="$emit('close')">x</button>
      </header>

      
      <form class="modal-form" @submit.prevent="enviarFormulario">
        <label for="nom">Nombre</label>
        <input id="nom" v-model="formulario.nom" type="text" required>

        <label for="cognoms">Apellidos</label>
        <input id="cognoms" v-model="formulario.cognoms" type="text" required>

        <label for="email">Correo electronico</label>
        <input id="email" v-model="formulario.email" type="email" required>

        <label for="password">Contraseña</label>
        <input id="password" v-model="formulario.password" type="password" required>

        <label for="rol_id">Rol</label>
        <select id="rol_id" v-model="formulario.rol_id" required>
          <option value="1">Admin</option>
          <option value="2">Operador</option>
        </select>

        <button type="submit" class="submit-btn">Crear Usuario</button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue'

const emit = defineEmits(['close', 'submit'])

const formulario = reactive({
  nom: '',
  cognoms: '',
  email: '',
  password: '',
  rol_id: '2',
})

const enviarFormulario = () => {
  emit('submit', {
    ...formulario,
    rol_id: Number(formulario.rol_id),
  })
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
  width: 390px;
  border-radius: 12px;
  background: #EAF3F8;
  border: 1px solid #89C4F5;
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
  font-size: 1.4rem;
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
  gap: 0.4rem;
}

.modal-form label {
  font-size: 0.85rem;
  font-weight: 700;
  color: #0a2134;
  margin-top: 0.35rem;
}

.modal-form input,
.modal-form select {
  border: 1px solid #89C4F5;
  background: #ffffff;
  border-radius: 6px;
  height: 38px;
  padding: 0 0.6rem;
}

.submit-btn {
  margin-top: 0.85rem;
  border: none;
  border-radius: 8px;
  height: 42px;
  font-size: 1rem;
  font-weight: 800;
  background: #09253b;
  color: #ffffff;
  cursor: pointer;
}
</style>

