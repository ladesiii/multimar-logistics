<!--
Componente: NuevoClienteModal
Descripción: Modal con formulario para crear un cliente nuevo.
-->
<template>
  <!-- Overlay del modal -->
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-card">
      <header class="modal-header">
        <h2>NUEVO CLIENTE</h2>
        <button type="button" class="close-btn" @click="$emit('close')">x</button>
      </header>

      <!-- Formulario de alta de cliente -->
      <form class="modal-form" @submit.prevent="enviarFormulario">
        <label for="nom">Nombre</label>
        <input id="nom" v-model="formulario.nom" type="text" required>

        <label for="cognoms">Apellidos</label>
        <input id="cognoms" v-model="formulario.cognoms" type="text" required>

        <label for="email">Correo electronico</label>
        <input id="email" v-model="formulario.email" type="email" required>

        <label for="password">Contraseña</label>
        <input id="password" v-model="formulario.password" type="password" required>

        <label for="nom_empresa">Empresa</label>
        <input id="nom_empresa" v-model="formulario.nom_empresa" type="text" required>

        <label for="cif_nif">CIF/NIF</label>
        <input id="cif_nif" v-model="formulario.cif_nif" type="text" required>

        <label for="telefon">Telefono</label>
        <input id="telefon" v-model="formulario.telefon" type="text">

        <button type="submit" class="submit-btn">Crear Cliente</button>
      </form>
    </div>
  </div>
</template>

<script setup>
// Importaciones de Vue.
import { reactive } from 'vue'

// Eventos hacia el padre: cerrar modal o enviar formulario.
const emit = defineEmits(['close', 'submit'])

// Estado local del formulario de cliente.
const formulario = reactive({
  nom: '',
  cognoms: '',
  email: '',
  password: '',
  nom_empresa: '',
  cif_nif: '',
  telefon: '',
})

// Envía los datos al componente padre para que haga la petición a la API.
const enviarFormulario = () => {
  // Se envía el formulario tal cual porque el padre ya hace la petición a la API.
  emit('submit', { ...formulario })
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

.modal-form input {
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
