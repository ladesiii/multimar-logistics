<template>
    <div class="main-layout">
        <NavbarVertical @section-selected="handleSectionSelected" />
        <div class="view-container">
            <NavbarHorizontal />
            <main class="page-content">
                <component :is="currentSectionComponent" />
            </main>
        </div>
    </div>

</template>

<script setup>
import { computed, ref } from 'vue'
import NavbarVertical from './NavbarVertical.vue'
import NavbarHorizontal from './NavbarHorizontal.vue'
import UsersTable from './UsersTable.vue'
import ClientsTable from './ClientsTable.vue'
import IncidentsTable from './IncidentsTable.vue'

const selectedSection = ref('Usuarios')

const sectionComponentMap = {
    Usuarios: UsersTable,
    Clientes: ClientsTable,
    Ofertas: IncidentsTable,
}

const currentSectionComponent = computed(() => {
    return sectionComponentMap[selectedSection.value] || UsersTable
})

const handleSectionSelected = (sectionName) => {
    selectedSection.value = sectionName
}
</script>
