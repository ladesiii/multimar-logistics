<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-card">
      <header class="modal-header">
        <h2>EDITAR CLIENTE</h2>
        <button type="button" class="close-btn" @click="$emit('close')">x</button>
      </header>

      <form class="modal-form" @submit.prevent="handleSubmit">
        <label for="edit-nom">Nombre</label>
        <input id="edit-nom" v-model="form.nom" type="text" required>

        <label for="edit-cognoms">Apellidos</label>
        <input id="edit-cognoms" v-model="form.cognoms" type="text" required>

        <label for="edit-email">Correo electronico</label>
        <input id="edit-email" v-model="form.email" type="email" required>

        <label for="edit-password">Contrasena (opcional)</label>
        <input id="edit-password" v-model="form.password" type="password" placeholder="Dejar vacio para no cambiar">

        <label for="edit-nom_empresa">Empresa</label>
        <input id="edit-nom_empresa" v-model="form.nom_empresa" type="text" required>

        <label for="edit-cif_nif">CIF/NIF</label>
        <input id="edit-cif_nif" v-model="form.cif_nif" type="text" required>

        <label for="edit-telefon">Telefono</label>
        <input id="edit-telefon" v-model="form.telefon" type="text">

        <button type="submit" class="submit-btn">Guardar cambios</button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue'

const props = defineProps({
  client: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['close', 'submit'])

const form = reactive({
  nom: '',
  cognoms: '',
  email: '',
  password: '',
  nom_empresa: '',
  cif_nif: '',
  telefon: '',
})

const hydrateForm = () => {
  form.nom = props.client?.nom || ''
  form.cognoms = props.client?.cognoms || ''
  form.email = props.client?.email || ''
  form.password = ''
  form.nom_empresa = props.client?.nom_empresa || ''
  form.cif_nif = props.client?.cif_nif || ''
  form.telefon = props.client?.telefon || ''
}

watch(
  () => props.client,
  () => {
    hydrateForm()
  },
  { immediate: true }
)

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
