<template>
    <div class="main-layout">
        <NavbarVertical
            :menu-items="availableMenuItems"
            :active-item="selectedSection"
            @section-selected="handleSectionSelected"
        />
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
import DashboardOverview from './DashboardOverview.vue'
import UsersTable from './UsersTable.vue'
import ClientsTable from './ClientsTable.vue'
import OffersTable from './OffersTable.vue'
import OperationsTable from './OperationsTable.vue'
import {
    Squares2X2Icon,
    UsersIcon,
    ClipboardDocumentListIcon,
    DocumentTextIcon,
    TruckIcon,
} from '@heroicons/vue/24/outline'

const userId = computed(() => {
    const storedUser = localStorage.getItem('auth_user')

    if (!storedUser) {
        return null
    }

    try {
        const user = JSON.parse(storedUser)
        return Number(user?.id ?? null)
    } catch {
        return null
    }
})

const userRoleId = computed(() => {
    const storedUser = localStorage.getItem('auth_user')

    if (!storedUser) {
        return null
    }

    try {
        const user = JSON.parse(storedUser)
        return Number(user?.rol_id ?? null)
    } catch {
        return null
    }
})

const userRoleName = computed(() => {
    const storedUser = localStorage.getItem('auth_user')

    if (!storedUser) {
        return ''
    }

    try {
        const user = JSON.parse(storedUser)
        return String(user?.rol || '').toLowerCase()
    } catch {
        return ''
    }
})

const isAdmin = computed(() => {
    return userId.value === 1
        || userRoleId.value === 1
        || userRoleName.value.includes('admin')
})

const adminMenuItems = [
    { text: 'Dashboard', icon: Squares2X2Icon },
    { text: 'Usuarios', icon: UsersIcon },
    { text: 'Clientes', icon: ClipboardDocumentListIcon },
    { text: 'Ofertas', icon: DocumentTextIcon },
    { text: 'Tracking', icon: TruckIcon },
]

const operatorMenuItems = [
    { text: 'Dashboard', icon: Squares2X2Icon },
    { text: 'Clientes', icon: ClipboardDocumentListIcon },
    { text: 'Ofertas', icon: DocumentTextIcon },
    { text: 'Tracking', icon: TruckIcon },
]

const availableMenuItems = computed(() => (isAdmin.value ? adminMenuItems : operatorMenuItems))

const selectedSection = ref('Dashboard')

const sectionComponentMap = {
    Dashboard: DashboardOverview,
    Usuarios: UsersTable,
    Clientes: ClientsTable,
    Ofertas: OffersTable,
    Tracking: OperationsTable,
}

const allowedSections = computed(() => availableMenuItems.value.map((item) => item.text))

const currentSectionComponent = computed(() => {
    const fallbackSection = allowedSections.value[0] || 'Dashboard'
    const targetSection = allowedSections.value.includes(selectedSection.value)
        ? selectedSection.value
        : fallbackSection

    return sectionComponentMap[targetSection] || DashboardOverview
})

const handleSectionSelected = (sectionName) => {
    if (!allowedSections.value.includes(sectionName)) {
        return
    }

    selectedSection.value = sectionName
}
</script>
