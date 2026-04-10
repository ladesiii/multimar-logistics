<template>
  <section class="table-panel">
    <header class="table-header">
      <h1>Tracking</h1>
    </header>

    <table class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Oferta</th>
          <th>Estado</th>
          <th>Fecha creación</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td colspan="4">Aquí irá la tabla de operadores.</td>
        </tr>
      </tbody>
    </table>
  </section>
</template>

<style scoped>
.table-panel {
  background: #ffffff;
  border-radius: 14px;
  padding: 1.25rem;
  box-shadow: 0 6px 18px rgba(0, 30, 51, 0.08);
}

.table-header h1 {
  font-size: 1.2rem;
  font-weight: 800;
  color: #002855;
  margin-bottom: 1rem;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  text-align: left;
  padding: 0.75rem;
  border-bottom: 1px solid #e6edf3;
}

.data-table thead th {
  color: #003b6f;
  font-weight: 700;
}
</style>
