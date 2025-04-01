<script setup>
import {defineEmits, defineProps} from 'vue';
import TableItem from "@/Components/BotsTable/TableItem.vue";
import TableItemCheckBox from "@/Components/BotsTable/TableItemCheckBox.vue";
import TableItemLink from "@/Components/BotsTable/TableItemLink.vue";
import TableItemDeleteButton from "@/Components/BotsTable/TableItemDeleteButton.vue";
import TableItemLinkArray from "@/Components/BotsTable/TableItemLinkArray.vue";

const props = defineProps({
    columns: {
        type: Array,
        required: true
    },
    data: {
        type: Array,
        required: true
    }
});

const componentMap = {
    default: TableItem,
    checkbox: TableItemCheckBox,
    link: TableItemLink,
    button: TableItemDeleteButton,
    arrayLink: TableItemLinkArray,
};

function getComponentType(type) {
    return componentMap[type] || componentMap['default'];
}

const emit = defineEmits(['handle']);

function handleEvent(event) {
    emit('handle', event);
}
</script>

<template>
    <div class="table-container">
        <div class="table-wrapper">
            <table class="custom-table">
                <thead>
                <tr>
                    <th v-for="column in columns" :key="column.key">{{ column.label }}</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="item in data" :key="item.id">
                    <td v-for="column in columns" :key="column.key" class="table-cell">
                        <component
                            :is="getComponentType(column.type)"
                            :data="item[column.key]"
                            :limit="column.limit"
                            :action="column.action"
                            :id="item.id"
                            @handle="handleEvent"
                        >
                        </component>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<style scoped>
.table-container {
    width: 100%;
}

.table-wrapper {
    overflow-x: auto;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    border-radius: 6px;
    margin-bottom: 1.5rem;
}

.custom-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    border: none;
}

.custom-table thead tr {
    background-color: #f3f4f6;
}

.custom-table th {
    padding: 12px 15px;
    font-weight: 600;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
    font-size: 0.875rem;
}

.custom-table tbody tr {
    border-bottom: 1px solid #e5e7eb;
    transition: background-color 0.2s;
}

.custom-table tbody tr:hover {
    background-color: #f9fafb;
}

.custom-table td {
    padding: 12px 15px;
    color: #4b5563;
    font-size: 0.875rem;
}

.custom-table tbody tr:last-child {
    border-bottom: none;
}

.table-cell {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 200px;
}

@media screen and (max-width: 600px) {
    .table-wrapper {
        border-radius: 0;
    }
    
    .custom-table th, 
    .custom-table td {
        padding: 8px 10px;
    }
}
</style>
