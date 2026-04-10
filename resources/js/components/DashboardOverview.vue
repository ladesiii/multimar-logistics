<template>
  <section class="dashboard-overview">
    <header class="overview-header">
      <h1>Dashboard</h1>
      <p>Resumen general del panel administrativo.</p>
    </header>

    <div class="overview-cards">
      <article class="overview-card">
        <h2>Clientes</h2>
        <p>Gestiona los datos de clientes y su información comercial.</p>
      </article>

      <article class="overview-card">
        <h2>Ofertas</h2>
        <p>Consulta, valida y realiza seguimiento de las ofertas activas.</p>
      </article>

      <article class="overview-card">
        <h2>Tracking</h2>
        <p>Supervisa el estado operativo y la trazabilidad de procesos.</p>
      </article>
    </div>
  </section>
</template>

<style scoped>
.dashboard-overview {
  background: #ffffff;
  border-radius: 14px;
  padding: 1.25rem;
  box-shadow: 0 6px 18px rgba(0, 30, 51, 0.08);
}

.overview-header h1 {
  margin: 0;
  color: #002855;
  font-size: 1.2rem;
  font-weight: 800;
}

.overview-header p {
  margin: 0.35rem 0 0;
  color: #31516b;
  font-size: 0.92rem;
  font-weight: 600;
}

.overview-cards {
  margin-top: 1rem;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.85rem;
}

.overview-card {
  border: 1px solid #d8e5f0;
  border-radius: 12px;
  padding: 0.95rem;
  background: #f9fcff;
}

.overview-card h2 {
  margin: 0;
  color: #003b6f;
  font-size: 1rem;
  font-weight: 800;
}

.overview-card p {
  margin: 0.55rem 0 0;
  color: #294459;
  font-size: 0.9rem;
  line-height: 1.35;
}

@media (max-width: 900px) {
  .overview-cards {
    grid-template-columns: 1fr;
  }
}
</style>
