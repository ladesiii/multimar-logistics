<template>
  <div class="login-page">
    <div class="login-card">
      <img :src="logo" alt="Logo" class="logo">

      <form class="login-form">
        <div class="input-group">
          <label for="email">Correo electrónico</label>
          <input type="text" id="email" placeholder="">
        </div>

        <div class="input-group">
          <label for="password">Contraseña</label>
          <input type="password" id="password" placeholder="">
        </div>

        <button type="submit" class="btn-submit">Iniciar sesión</button>

        <a href="#" class="forgot-link">Forgot password?</a>
      </form>
    </div>
  </div>
</template>

<script setup>
import logo from '../../assets/multimar-logistics.png'
</script>

<style scoped>
/* Contenedor principal (Fondo oscuro) */
.login-page {
  background-color: #09253B;
  min-height: 95vh; /* Cambiado a 100vh para que no queden bordes blancos */
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  margin: 0;
}

/* Tarjeta del Login */
.login-card {
  background-color: #EAF3F8;
  border: 2px solid #89C4F5;
  border-radius: 12px;
  padding: 2.5rem 2rem;
  width: 100%;
  max-width: 380px; /* Ancho controlado */
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

/* Grupos de Input */
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
  background-color: #FFFFFF;
  font-size: 1rem;
  outline: none;
}

/* Botón Iniciar Sesión */
.btn-submit {
  background-color: #0D2438; /* Azul muy oscuro casi negro */
  color: white;
  padding: 14px;
  border: none;
  border-radius: 8px;
  font-weight: bold;
  font-size: 1rem;
  cursor: pointer;
  margin-top: 0.5rem;
}

.btn-submit:hover {
  background-color: #1a3a5a;
}

/* Link de olvidar contraseña */
.forgot-link {
  text-align: center;
  color: #000;
  text-decoration: underline;
  font-size: 0.85rem;
  margin-top: 0.5rem;
  font-family: sans-serif;
}
</style>
