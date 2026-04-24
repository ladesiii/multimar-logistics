
<template>

  <div class="login-layout">
    <NavbarLogin />

    <div class="login-page">
      <div class="login-card">
        <img :src="logo" alt="Logo" class="logo">

        <form class="login-form" @submit.prevent="iniciarSesion">
          <div class="input-group">
            <label for="email">Correo electrónico</label>
            <input
              id="email"
              v-model="formulario.email"
              type="email"
              autocomplete="username"
              required
            >
          </div>

          <div class="input-group">
            <label for="password">Contraseña</label>
            <input
              id="password"
              v-model="formulario.password"
              type="password"
              autocomplete="current-password"
              required
            >
          </div>

          <p v-if="mensajeError" class="form-error">{{ mensajeError }}</p>

          <button type="submit" class="btn-submit" :disabled="estaCargando">
            {{ estaCargando ? 'Entrando...' : 'Iniciar sesión' }}
          </button>

          <a href="#" class="forgot-link">Forgot password?</a>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import NavbarLogin from '../navbar/NavbarLogin.vue'
import logo from '../../../assets/multimar-logistics.png'

const formulario = reactive({
  email: '',
  password: '',
})

const estaCargando = ref(false)
const mensajeError = ref('')

const iniciarSesion = () => {
  estaCargando.value = true
  mensajeError.value = ''

  window.axios.post('/api/login', {
    email: formulario.email,
    password: formulario.password,
  })
    .then(({ data }) => {
      localStorage.setItem('auth_token', data.token)
      localStorage.setItem('auth_user', JSON.stringify(data.user))
      window.axios.defaults.headers.common.Authorization = `${data.token_type} ${data.token}`

      window.location.href = '/dashboard'
    })
    .catch((error) => {
      if (error.response?.status === 401) {
        mensajeError.value = 'Correo o contraseña incorrectos.'
      } else if (error.response?.status === 422) {
        mensajeError.value = 'Revisa el formato del correo y la contraseña.'
      } else {
        mensajeError.value = 'No se pudo iniciar sesión. Inténtalo de nuevo.'
      }
    })
    .finally(() => {
      estaCargando.value = false
    })
}
</script>

<style scoped>
.login-layout {
  width: 100%;
}

.login-page {
  background-color: #09253B;
  min-height: 93vh;
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  margin: 0;
}

.login-card {
  background-color: #EAF3F8;
  border: 2px solid #89C4F5;
  border-radius: 12px;
  padding: 2.5rem 2rem;
  width: 100%;
  max-width: 380px;
  display: flex;
  flex-direction: column;
  align-items: center;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.logo {
  width: 220px;
  margin-bottom: 2rem;
}

.login-form {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.input-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.input-group label {
  color: #000;
  font-weight: 800;
  font-size: 0.9rem;
  font-family: sans-serif;
}

.input-group input {
  padding: 12px;
  border-radius: 8px;
  border: 1px solid #b5c6d6;
  background-color: #FFFFFF;
  font-size: 1rem;
  outline: none;
}

.input-group input:focus {
  border-color: #0D2438;
  box-shadow: 0 0 0 3px rgba(13, 36, 56, 0.12);
}

.btn-submit {
  background-color: #0D2438;
  color: white;
  padding: 14px;
  border: none;
  border-radius: 8px;
  font-weight: bold;
  font-size: 1rem;
  cursor: pointer;
  margin-top: 0.5rem;
}

.btn-submit:disabled {
  opacity: 0.75;
  cursor: not-allowed;
}

.btn-submit:hover {
  background-color: #1a3a5a;
}

.form-error {
  color: #b42318;
  font-size: 0.9rem;
  text-align: center;
  margin-top: -0.5rem;
}

.forgot-link {
  text-align: center;
  color: #000;
  text-decoration: underline;
  font-size: 0.85rem;
  margin-top: 0.5rem;
  font-family: sans-serif;
}
</style>

