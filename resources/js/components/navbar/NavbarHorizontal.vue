<template>
  <header class="navbar-horizontal">
    <div class="nav-right">
      <button class="nav-icon-btn">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="nav-icon">
          <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>
      </button>

      <button
        ref="settingsButtonRef"
        class="nav-icon-btn"
        type="button"
        aria-label="Abrir ajustes"
        :aria-expanded="isSettingsOpen"
        @click="toggleSettings"
      >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="nav-icon">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.041.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 0 1 0 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.281Z" />
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        </svg>
      </button>

      <div class="user-profile" role="button" tabindex="0" title="Editar perfil" @click="goToProfile" @keydown.enter="goToProfile" @keydown.space.prevent="goToProfile">
        <div class="user-icon-wrapper">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="nav-icon">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
          </svg>
        </div>
        <span class="user-name">{{ userName }}</span>
      </div>
    </div>

    <div
      v-if="isSettingsOpen"
      ref="settingsPanelRef"
      class="settings-dropdown"
      role="dialog"
      aria-label="Ajustes"
    >
      <h3>Ajustes</h3>

      <label for="language-select">Idioma</label>
      <select id="language-select" v-model="selectedLanguage" class="settings-select">
        <option value="es">Español</option>
      </select>

      <button type="button" class="logout-btn" @click="handleLogout">Cerrar sesión</button>
    </div>
  </header>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'

const userName = ref('Usuario')
const isSettingsOpen = ref(false)
const selectedLanguage = ref('es')
const settingsButtonRef = ref(null)
const settingsPanelRef = ref(null)

const toggleSettings = () => {
  isSettingsOpen.value = !isSettingsOpen.value
}

const closeSettings = () => {
  isSettingsOpen.value = false
}

const handleOutsideClick = (event) => {
  if (!isSettingsOpen.value) {
    return
  }

  const clickedOnButton = settingsButtonRef.value?.contains(event.target)
  const clickedOnPanel = settingsPanelRef.value?.contains(event.target)

  if (!clickedOnButton && !clickedOnPanel) {
    closeSettings()
  }
}

const handleKeydown = (event) => {
  if (event.key === 'Escape') {
    closeSettings()
  }
}

const handleLogout = async () => {
  const token = localStorage.getItem('auth_token')

  try {
    if (token) {
      await window.axios.post('/api/logout')
    }
  } catch {
    // No bloqueamos el cierre local si falla la llamada al backend.
  } finally {
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
    delete window.axios.defaults.headers.common.Authorization
    window.location.href = '/'
  }
}

const goToProfile = () => {
  window.location.href = '/editarperfil'
}

onMounted(() => {
  const storedUser = localStorage.getItem('auth_user')

  if (! storedUser) {
    return
  }

  try {
    const user = JSON.parse(storedUser)
    userName.value = user.name || [user.nom, user.cognoms].filter(Boolean).join(' ') || user.email || 'Usuario'
  } catch {
    userName.value = 'Usuario'
  }

  document.addEventListener('click', handleOutsideClick)
  document.addEventListener('keydown', handleKeydown)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleOutsideClick)
  document.removeEventListener('keydown', handleKeydown)
})
</script>

<style scoped>
.navbar-horizontal {
  /* Color azul oscuro muy profundo como el de la imagen */
  background-color: #09253B;
  height: 60px;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding: 0 2rem;
  position: relative;
  flex-shrink: 0;
}

.nav-right {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.nav-icon-btn {
  background: transparent;
  border: none;
  padding: 0;
  cursor: pointer;
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.nav-icon {
  width: 24px;
  height: 24px;
}

.user-profile {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  outline: none;
  color: #ffffff;
}

.user-profile:focus-visible {
  border-radius: 999px;
  box-shadow: 0 0 0 3px rgba(137, 196, 245, 0.45);
}

.user-name {
  color: #ffffff;
  font-weight: 700;
  font-size: 0.95rem;
}

.settings-dropdown {
  position: absolute;
  top: 68px;
  right: 2rem;
  background: #ffffff;
  border: 1px solid #d9e4ee;
  border-radius: 12px;
  padding: 1rem;
  min-width: 220px;
  box-shadow: 0 16px 30px rgba(0, 0, 0, 0.18);
  z-index: 30;
}

.settings-dropdown h3 {
  margin: 0 0 0.85rem;
  font-size: 1rem;
  color: #002855;
}

.settings-dropdown label {
  display: block;
  font-size: 0.85rem;
  font-weight: 700;
  color: #31516b;
  margin-bottom: 0.35rem;
}

.settings-select {
  width: 100%;
  border: 1px solid #c9d8e3;
  border-radius: 8px;
  min-height: 40px;
  padding: 0 0.65rem;
  margin-bottom: 0.75rem;
}

.logout-btn {
  width: 100%;
  border: none;
  border-radius: 8px;
  min-height: 40px;
  background: #09253b;
  color: #ffffff;
  font-weight: 800;
  cursor: pointer;
}

.logout-btn:hover {
  background: #ffffff;
}

</style>
