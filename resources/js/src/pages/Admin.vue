<script setup>
import { ref, onMounted } from 'vue';
import BotsTable from '@/Components/BotsTable.vue';
import { users_table } from '@/ComponentConfigs/Table/users_table';
import axios from 'axios';

const users = ref([]);
const totalPages = ref(0);
const currentPage = ref(1);
const perPage = ref(10);
const loading = ref(true);

async function fetchUsers() {
    loading.value = true;
    try {
        const response = await axios.get('/api/admin/users', {
            params: {
                page: currentPage.value,
                perPage: perPage.value
            }
        });
        users.value = response.data.data;
        totalPages.value = response.data.lastPage;
    } catch (error) {
        console.error('Ошибка при загрузке пользователей:', error);
    } finally {
        loading.value = false;
    }
}

const handlePageSizeChange = (size) => {
    perPage.value = size;
    fetchUsers();
};

const handlePageChange = (page) => {
    currentPage.value = page;
    fetchUsers();
};

const handleEvent = (event) => {
    // Обработка событий таблицы, если необходимо
    console.log('Table event:', event);
};

onMounted(() => {
    fetchUsers();
});
</script>

<template>
    <div class="admin-page">
        <header class="admin-header">
            <div class="container">
                <div class="header-row">
                    <div class="header-title">
                        <h1>Admin</h1>
                    </div>
                </div>
            </div>
        </header>

        <main class="admin-content">
            <div class="container">
                <div class="content-row">
                    <div class="content-col">
                        <div class="admin-card">
                            <div class="card-header">
                                <h3 class="card-title">Users</h3>
                            </div>

                            <div v-if="loading" class="loader">
                                <div class="spinner"></div>
                            </div>

                            <bots-table
                                v-else
                                :columns="users_table"
                                :data="users"
                                :total-pages="totalPages"
                                :current-page="currentPage"
                                :per-page="perPage"
                                per-page-text="Записей на странице:"
                                @update:page-size-change="handlePageSizeChange"
                                @update:page-change="handlePageChange"
                                @handle="handleEvent"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
/* Основные переменные */
:root {
    --primary-color: #4a6cf7;
    --secondary-color: #6b7280;
    --background-color: #f9fafb;
    --card-background: #ffffff;
    --border-color: #e5e7eb;
    --text-color: #1f2937;
    --text-light: #6b7280;
    --box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    --border-radius: 0.375rem;
}

/* Общие стили */
.admin-page {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    color: var(--text-color);
    background-color: var(--background-color);
    min-height: 100vh;
}

.container {
    width: 100%;
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1rem;
}

/* Заголовок */
.admin-header {
    padding: 1.5rem 0;
    background-color: var(--card-background);
    box-shadow: var(--box-shadow);
    margin-bottom: 1.5rem;
}

.header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.header-title h1 {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
}

/* Основной контент */
.admin-content {
    padding-bottom: 2rem;
}

.content-row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -0.5rem;
}

.content-col {
    flex: 1 1 100%;
    padding: 0 0.5rem;
}

/* Карточка */
.admin-card {
    background-color: var(--card-background);
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    overflow: hidden;
}

.card-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.card-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0;
}

/* Лоадер */
.loader {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 3rem 0;
}

.spinner {
    width: 2.5rem;
    height: 2.5rem;
    border: 0.25rem solid rgba(74, 108, 247, 0.25);
    border-left-color: var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Медиа-запросы */
@media (min-width: 768px) {
    .header-title h1 {
        font-size: 1.875rem;
    }
}
</style>
