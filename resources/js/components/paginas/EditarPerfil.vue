<template>
  <NavbarLogin />
  <div class="profile-page">
    <section class="profile-card">
      <header class="profile-header">
        <div>
          <h1>Editar perfil</h1>
          <p>Actualiza tus datos personales y tu acceso.</p>
        </div>
      </header>

      <p v-if="isLoading" class="state-message">Cargando perfil...</p>
      <p v-else-if="loadError" class="state-message error">{{ loadError }}</p>

      <form v-else class="profile-form" @submit.prevent="handleSubmit">
        <div class="two-columns">
          <section class="section-panel">
            <h2>Información personal</h2>
            <div class="fields-grid">
              <div class="field full-width">
                <label for="nom">Nombre</label>
                <input id="nom" v-model="form.nom" type="text" required>
              </div>

              <div class="field full-width">
                <label for="cognoms">Apellidos</label>
                <input id="cognoms" v-model="form.cognoms" type="text" required>
              </div>

              <div class="field full-width">
                <label for="email">Correo electrónico</label>
                <input id="email" v-model="form.email" type="email" required>
              </div>
            </div>
          </section>

          <section class="section-panel">
            <h2>Seguridad y acceso</h2>
            <div class="fields-grid">
              <div class="field full-width">
                <label for="current-password">Contraseña actual</label>
                <div class="input-with-action">
                  <input
                    id="current-password"
                    v-model="form.currentPassword"
                    type="password"
                    placeholder="Introduce tu contraseña actual"
                    @input="onCurrentPasswordInput"
                  >
                  <button type="button" class="secondary-btn inline-btn" @click="verifyCurrentPassword" :disabled="isVerifyingPassword || !form.currentPassword.trim()">
                    {{ isVerifyingPassword ? 'Verificando...' : 'Verificar' }}
                  </button>
                </div>
                <p class="field-help">Debes verificarla para poder cambiar la contraseña.</p>
              </div>

              <div class="field full-width">
                <label for="new-password">Nueva contraseña</label>
                <input
                  id="new-password"
                  v-model="form.newPassword"
                  type="password"
                  placeholder="Nueva contraseña"
                  :disabled="!isPasswordVerified"
                >
              </div>

              <div class="field full-width">
                <label for="confirm-password">Confirmar nueva contraseña</label>
                <input
                  id="confirm-password"
                  v-model="form.confirmPassword"
                  type="password"
                  placeholder="Repite la nueva contraseña"
                  :disabled="!isPasswordVerified"
                >
              </div>

              <p v-if="isPasswordVerified" class="state-message success compact">Contraseña actual verificada. Ya puedes escribir la nueva contraseña.</p>
            </div>
          </section>
        </div>

        <p v-if="submitMessage" class="state-message success">{{ submitMessage }}</p>
        <p v-if="submitError" class="state-message error">{{ submitError }}</p>

        <div class="actions-row">
          <button type="button" class="secondary-btn" @click="goBack" :disabled="isSaving">Cancelar</button>
          <button type="submit" class="primary-btn" :disabled="isSaving">
            {{ isSaving ? 'Guardando...' : 'Guardar cambios' }}
          </button>
        </div>
      </form>
    </section>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import NavbarLogin from '../navbar/NavbarLogin.vue'

const isLoading = ref(true)
const isSaving = ref(false)
const isVerifyingPassword = ref(false)
const isPasswordVerified = ref(false)
const loadError = ref('')
const submitError = ref('')
const submitMessage = ref('')

const form = reactive({
  nom: '',
  cognoms: '',
  email: '',
  currentPassword: '',
  newPassword: '',
  confirmPassword: '',
})

watch(
  () => form.currentPassword,
  () => {
    isPasswordVerified.value = false
    form.newPassword = ''
    form.confirmPassword = ''
  }
)

const loadProfile = async () => {
  isLoading.value = true
  loadError.value = ''

  try {
    const { data } = await window.axios.get('/api/user')
    const user = data?.user

    if (!user) {
      throw new Error('No se pudo cargar el perfil.')
    }

    form.nom = user.nom || ''
    form.cognoms = user.cognoms || ''
    form.email = user.correu || user.email || ''
  } catch {
    loadError.value = 'No se pudo cargar tu perfil.'
  } finally {
    isLoading.value = false
  }
}

const handleSubmit = async () => {
  isSaving.value = true
  submitError.value = ''
  submitMessage.value = ''

  if (form.newPassword || form.confirmPassword) {
    if (!isPasswordVerified.value) {
      submitError.value = 'Primero verifica tu contraseña actual.'
      isSaving.value = false
      return
    }

    if (form.newPassword !== form.confirmPassword) {
      submitError.value = 'La nueva contraseña y su confirmación no coinciden.'
      isSaving.value = false
      return
    }
  }

  try {
    const { data } = await window.axios.put('/api/profile', {
      nom: form.nom,
      cognoms: form.cognoms,
      email: form.email,
      current_password: form.currentPassword || null,
      new_password: form.newPassword || null,
      new_password_confirmation: form.confirmPassword || null,
    })

    const updatedUser = data?.user

    if (updatedUser) {
      localStorage.setItem('auth_user', JSON.stringify(updatedUser))
    }

    form.currentPassword = ''
    form.newPassword = ''
    form.confirmPassword = ''
    isPasswordVerified.value = false
    submitMessage.value = 'Perfil actualizado correctamente.'
  } catch (error) {
    if (error.response?.status === 422) {
      const apiMessage = error.response?.data?.message
      const validationErrors = error.response?.data?.errors
      const firstValidationError = validationErrors
        ? Object.values(validationErrors)[0]?.[0]
        : ''

      submitError.value = firstValidationError || apiMessage || 'Revisa los datos del formulario.'
      return
    }

    submitError.value = error.response?.data?.message || 'No se pudo actualizar el perfil.'
  } finally {
    isSaving.value = false
  }
}

const verifyCurrentPassword = async () => {
  submitError.value = ''
  submitMessage.value = ''

  if (!form.currentPassword.trim()) {
    submitError.value = 'Escribe primero tu contraseña actual.'
    return
  }

  isVerifyingPassword.value = true

  try {
    await window.axios.post('/api/profile/verify-password', {
      current_password: form.currentPassword,
    })

    isPasswordVerified.value = true
  } catch (error) {
    isPasswordVerified.value = false
    submitError.value = error.response?.data?.message || 'No se pudo verificar la contraseña actual.'
  } finally {
    isVerifyingPassword.value = false
  }
}

const onCurrentPasswordInput = () => {
  isPasswordVerified.value = false
  form.newPassword = ''
  form.confirmPassword = ''
}

const goBack = () => {
  window.history.back()
}

onMounted(() => {
  loadProfile()
})
</script>

<style scoped>
.profile-page {
  min-height: 100vh;
  background: linear-gradient(180deg, #09253b 0%, #0d3553 100%);
  padding: 2rem 1rem;
  display: flex;
  justify-content: center;
  align-items: center;
}

.profile-card {
  width: min(1120px, 100%);
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 14px 30px rgba(0, 0, 0, 0.24);
  padding: 1.75rem;
  border: 1px solid #89c4f5;
}

.profile-header h1 {
  margin: 0;
  color: #002855;
  font-size: 1.35rem;
  font-weight: 800;
}

.profile-header p {
  margin: 0.35rem 0 0;
  color: #31516b;
  font-weight: 600;
}

.state-message {
  margin: 1rem 0 0;
  font-weight: 700;
}

.state-message.error {
  color: #b42318;
}

.state-message.success {
  color: #166534;
}

.profile-form {
  margin-top: 1rem;
}

.two-columns {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.25rem;
}

.section-panel {
  background: #f8fbfe;
  border: 1px solid #cfe0ec;
  border-radius: 14px;
  padding: 1.15rem;
  min-width: 0;
}

.section-panel h2 {
  margin: 0 0 0.85rem;
  color: #002855;
  font-size: 1rem;
  font-weight: 800;
}

.fields-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.85rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.field.full-width {
  grid-column: 1 / -1;
}

.field label {
  color: #0a2540;
  font-weight: 700;
  font-size: 0.9rem;
}

.field input {
  border: 1px solid #c9d8e3;
  border-radius: 10px;
  min-height: 42px;
  padding: 0 0.75rem;
  font: inherit;
  width: 100%;
  box-sizing: border-box;
}

.field input:disabled {
  background: #eef3f8;
  color: #6b7280;
  cursor: not-allowed;
}

.field input:focus {
  outline: none;
  border-color: #89c4f5;
  box-shadow: 0 0 0 3px rgba(137, 196, 245, 0.22);
}

.input-with-action {
  display: flex;
  gap: 0.6rem;
  align-items: center;
  min-width: 0;
}

.input-with-action input {
  flex: 1;
  min-width: 0;
}

.actions-row {
  margin-top: 1.25rem;
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.primary-btn,
.secondary-btn {
  border: none;
  border-radius: 10px;
  min-height: 42px;
  padding: 0 1rem;
  font-weight: 800;
  cursor: pointer;
}

.primary-btn {
  background: #09253b;
  color: #ffffff;
}

.secondary-btn {
  background: #d9e4ee;
  color: #0a2540;
}

.inline-btn {
  white-space: nowrap;
}

.field-help {
  margin: 0;
  color: #31516b;
  font-size: 0.8rem;
  font-weight: 600;
}

.compact {
  margin-top: 0.75rem;
}

.primary-btn:disabled,
.secondary-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

@media (max-width: 720px) {
  .two-columns {
    grid-template-columns: 1fr;
  }

  .input-with-action {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
