<!--
Componente: NavbarLogin
Descripción: Barra minimal de la pantalla de login que solo muestra el logotipo corporativo.
-->
<template>
  <!-- Cabecera sencilla que solo muestra el logotipo en las pantallas de acceso. -->
  <nav class="navbar">
    <img :src="logo" alt="Logo" width="200">
  </nav>
</template>

<script setup>
// Importa la imagen del logo para usarla en la cabecera de login.
import logo from '../../../assets/multimar-logistics.png'
</script>

<style scoped>
.navbar {
  min-height: 7vh;
  background-color: white;
  padding: 1rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
</style>
