<template>
  <header class="navbar-horizontal">
    <div class="nav-right">
      <button class="nav-icon-btn">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="nav-icon">
          <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>
      </button>

      <button class="nav-icon-btn">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="nav-icon">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.041.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 0 1 0 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.281Z" />
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        </svg>
      </button>

      <div class="user-profile">
        <div class="user-icon-wrapper">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="nav-icon">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
          </svg>
        </div>
        <span class="user-name">{{ userName }}</span>
      </div>
    </div>
  </header>
</template>

<script setup>
import { onMounted, ref } from 'vue'

const userName = ref('Usuario')

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
  justify-content: flex-end; /* Alinea todo a la derecha */
  padding: 0 30px;
  box-sizing: border-box;
}

.nav-right {
  display: flex;
  align-items: center;
  gap: 25px; /* Espacio entre iconos */
}

.nav-icon-btn {
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
  color: #ffffff;
  display: flex;
  align-items: center;
  transition: opacity 0.2s;
}

.nav-icon-btn:hover {
  opacity: 0.8;
}

.nav-icon {
  width: 26px;
  height: 26px;
}

/* Contenedor de Usuario */
.user-profile {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
}

.user-name {
  color: #ffffff;
  font-family: 'Inter', sans-serif;
  font-weight: 700; /* Negrita como en la imagen */
  font-size: 15px;
}

.user-icon-wrapper {
  color: #ffffff;
  display: flex;
  align-items: center;
}
</style>
