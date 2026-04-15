<template>
    <div v-if="isCheckingSession" class="state-message">Cargando sesión...</div>

    <div v-else-if="!isAuthorized" class="state-message">
        No tienes permisos para acceder al panel.
    </div>

    <div v-else class="main-layout">
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
import { computed, onMounted, ref } from 'vue'
import NavbarVertical from '../navbar/NavbarVertical.vue'
import NavbarHorizontal from '../navbar/NavbarHorizontal.vue'
import DashboardVista from '../DashboardVista.vue'
import ListadoUsuarios from '../ListadoUsuarios.vue'
import ListadoClientes from '../ListadoClientes.vue'
import ListadoOfertas from '../ListadoOfertas.vue'
import ListadoTracking from '../ListadoTracking.vue'
import {
    Squares2X2Icon,
    UsersIcon,
    ClipboardDocumentListIcon,
    DocumentTextIcon,
    TruckIcon,
} from '@heroicons/vue/24/outline'

const isCheckingSession = ref(true)
const isAuthorized = ref(true)
const currentUser = ref(null)

const loadUserFromStorage = () => {
    const storedUser = localStorage.getItem('auth_user')

    if (!storedUser) {
        return null
    }

    try {
        return JSON.parse(storedUser)
    } catch {
        return null
    }
}

const roleType = computed(() => {
    const roleId = Number(currentUser.value?.rol_id ?? 0)
    const roleName = String(currentUser.value?.rol || '').toLowerCase()

    if (roleId === 1 || roleName.includes('admin')) {
        return 'admin'
    }

    if (roleId === 2 || roleName.includes('operador') || roleName.includes('operator')) {
        return 'operador'
    }

    if (roleId === 3 || roleName.includes('client')) {
        return 'cliente'
    }

    return ''
})

const adminMenuItems = [
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

const clientMenuItems = [
    { text: 'Dashboard', icon: Squares2X2Icon },
    { text: 'Ofertas', icon: DocumentTextIcon },
    { text: 'Tracking', icon: TruckIcon },
]

const menuByRole = {
    admin: adminMenuItems,
    operador: operatorMenuItems,
    cliente: clientMenuItems,
}

const availableMenuItems = computed(() => menuByRole[roleType.value] || [])

const selectedSection = ref('Dashboard')

const sectionComponentMap = {
    Dashboard: DashboardVista,
    Usuarios: ListadoUsuarios,
    Clientes: ListadoClientes,
    Ofertas: ListadoOfertas,
    Tracking: ListadoTracking,
}

const allowedSections = computed(() => availableMenuItems.value.map((item) => item.text))

const currentSectionComponent = computed(() => {
    const fallbackSection = allowedSections.value[0] || 'Dashboard'
    const targetSection = allowedSections.value.includes(selectedSection.value)
        ? selectedSection.value
        : fallbackSection

    return sectionComponentMap[targetSection] || DashboardVista
})

const handleSectionSelected = (sectionName) => {
    if (!allowedSections.value.includes(sectionName)) {
        return
    }

    selectedSection.value = sectionName
}

onMounted(async () => {
    const token = localStorage.getItem('auth_token')

    if (!token) {
        window.location.href = '/'
        return
    }

    currentUser.value = loadUserFromStorage()

    try {
        const { data } = await window.axios.get('/api/user')
        const apiUser = data?.user ?? null

        if (!apiUser) {
            throw new Error('Usuario no disponible')
        }

        currentUser.value = {
            id: apiUser.id,
            email: apiUser.correu,
            name: [apiUser.nom, apiUser.cognoms].filter(Boolean).join(' ').trim(),
            nom: apiUser.nom,
            cognoms: apiUser.cognoms,
            rol_id: apiUser.rol_id,
            rol: apiUser.rol?.rol,
        }

        localStorage.setItem('auth_user', JSON.stringify(currentUser.value))
    } catch {
        localStorage.removeItem('auth_token')
        localStorage.removeItem('auth_user')
        delete window.axios.defaults.headers.common.Authorization
        window.location.href = '/'
        return
    }

    if (!roleType.value) {
        isAuthorized.value = false
        isCheckingSession.value = false
        return
    }

    if (allowedSections.value.length > 0) {
        selectedSection.value = allowedSections.value[0]
    }

    isCheckingSession.value = false
})
</script>

<style scoped>
.state-message {
    padding: 2rem;
    text-align: center;
    font-weight: 700;
    color: #0a2540;
}
</style>
