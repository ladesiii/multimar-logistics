<!--
Componente: NavbarVertical
Descripción: Menú lateral del dashboard. Renderiza las secciones permitidas y emite la sección seleccionada.
-->
<template>
  <!-- Sidebar lateral con logo y navegación -->
  <aside class="sidebar-container">
    <div class="logo-section">
      <img :src="logoMultimar" alt="Multimar Logistics" class="logo-img" />
    </div>

    <!-- Menú dinámico: cada item proviene del padre según rol -->
    <nav class="menu-nav">
      <a
        v-for="item in menuItems"
        :key="item.text"
        href="#"
        @click.prevent="setActive(item.text)"
        :class="['menu-item', { 'active': activeItem === item.text }]"
      >
        <!-- Icono y texto del item -->
        <div class="icon-wrapper">
          <component :is="item.icon" class="icon" />
        </div>
        <span class="menu-text">{{ item.text }}</span>
      </a>
    </nav>
  </aside>
</template>

<script setup>
// Importa el logo corporativo mostrado en la parte superior.
import logoMultimar from '../../../assets/multimar-logistics.png'

// Props recibidas desde Dashboard: lista de items y item activo.
defineProps({
  menuItems: {
    type: Array,
    required: true,
  },
  activeItem: {
    type: String,
    required: true,
  },
})

// Evento para notificar al padre que el usuario cambió de sección.
const emit = defineEmits(['section-selected'])

const setActive = (itemText) => {
  // El contenedor padre decide qué sección mostrar.
  emit('section-selected', itemText)
}
</script>

<style scoped>
/* Contenedor Principal */
.sidebar-container {
  width: 280px;
  height: 100vh;
  background-color: #ffffff;
  display: flex;
  flex-direction: column;
  padding: 2rem 1rem;
  border-right: 1px solid #f0f0f0;
}

/* Sección del Logo */
.logo-section {
  padding-left: 1rem;
  margin-bottom: 3rem;
}

.logo-img {
  width: 300px;
}

/* Navegación */
.menu-nav {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  padding: 0.85rem 1.25rem;
  text-decoration: none;
  border-radius: 1rem; /* Borde redondeado suave */
  transition: all 0.2s ease-in-out;
  color: #002855; /* Azul marino Multimar */
}

/* Efecto Hover */
.menu-item:hover {
  background-color: #f8fafc;
}

/* ESTADO ACTIVO (Como en tu imagen) */
.menu-item.active {
  background-color: #89C4F5; /* Azul muy claro del fondo */
}

/* Iconos */
.icon-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
}

.icon {
  width: 28px;
  height: 28px;
  stroke-width: 1.8; /* Grosor de línea similar al de la imagen */
}

/* Texto del Menú */
.menu-text {
  font-family: 'Inter', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
  font-size: 1.1rem;
  font-weight: 900; /* Extra bold */
  text-transform: uppercase;
  letter-spacing: 0.05em; /* Un poco de aire entre letras */
}

/* Ajuste específico para que el texto sea muy oscuro */
.menu-item .menu-text {
  color: #002855;
}
</style>
