<script setup>
import {defineEmits, defineProps} from 'vue';

const props = defineProps({
    currentPage: {
        type: Number,
        required: true
    },
    totalPages: {
        type: Number,
        required: true
    },
});

const emits = defineEmits(['changePage']);

function changePage(page) {
    if (page > 0 && page <= props.totalPages && page !== props.currentPage) {
        emits('update:page-change', page);
    }
}

function generatePagination() {
    const pagination = [];
    const currentPage = props.currentPage;
    const totalPages = props.totalPages;
    const range = 1;

    // Добавить начало
    if (currentPage > 2) {
        pagination.push({page: 1, text: '1', active: false});
    }

    // Добавление точек, если требуется
    if (currentPage > range + 2) {
        pagination.push({text: '...', active: false});
    }

    // Показ страниц вокруг текущей
    for (let i = Math.max(1, currentPage - range); i <= Math.min(totalPages, currentPage + range); i++) {
        pagination.push({page: i, text: `${i}`, active: currentPage === i});
    }

    // Добавление точек, если требуется
    if (currentPage < totalPages - (range + 1)) {
        pagination.push({text: '...', active: false});
    }

    // Добавить конец
    if (currentPage + 1 < totalPages && totalPages > 2) {
        pagination.push({page: totalPages, text: `${totalPages}`, active: false});
    }

    return pagination;
}
</script>

<template>
    <div class="pagination-container">
        <nav class="pagination-nav">
            <ul class="pagination-list">
                <li 
                    v-for="item in generatePagination()"
                    :key="item.text"
                    class="pagination-item"
                    :class="{ 'pagination-active': item.active }">
                    <a 
                        v-if="item.page" 
                        href="#" 
                        class="pagination-link"
                        @click.prevent="changePage(item.page)">
                        {{ item.text }}
                    </a>
                    <span v-else class="pagination-ellipsis">{{ item.text }}</span>
                </li>
            </ul>
        </nav>
    </div>
</template>

<style scoped>
.pagination-container {
    margin-top: 1.5rem;
    display: flex;
    justify-content: flex-end;
}

.pagination-nav {
    display: inline-block;
}

.pagination-list {
    display: flex;
    list-style: none;
    padding: 0;
    margin: 0;
    gap: 0.25rem;
}

.pagination-item {
    margin: 0 0.125rem;
}

.pagination-link, 
.pagination-ellipsis {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 2rem;
    height: 2rem;
    padding: 0 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    color: #4b5563;
    text-decoration: none;
    background-color: #ffffff;
    border: 1px solid #e5e7eb;
    transition: all 0.2s;
}

.pagination-ellipsis {
    pointer-events: none;
}

.pagination-link:hover {
    background-color: #f3f4f6;
}

.pagination-active .pagination-link {
    background-color: #4a6cf7;
    color: white;
    border-color: #4a6cf7;
}

.pagination-active .pagination-link:hover {
    background-color: #3c5bd9;
}

@media screen and (max-width: 640px) {
    .pagination-container {
        justify-content: center;
    }
    
    .pagination-link, 
    .pagination-ellipsis {
        min-width: 1.75rem;
        height: 1.75rem;
        font-size: 0.75rem;
    }
}
</style>
