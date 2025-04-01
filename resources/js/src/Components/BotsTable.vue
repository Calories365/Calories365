<script setup>
import {defineEmits, defineProps, onMounted, ref, watch} from 'vue';
import {useRoute, useRouter} from 'vue-router';
import TablePerPageSelect from "@/Components/BotsTable/TablePerPageSelect.vue";
import TableMainPart from "@/Components/BotsTable/TableMainPart.vue";
import TablePagination from "@/Components/BotsTable/TablePagination.vue";

const props = defineProps({
    users: Boolean,
    columns: Array,
    data: Array,
    totalPages: Number,
    currentPage: Number,
    nextPage: Number,
    previousPage: Number,
    pageSizeOptions: {
        type: Array,
        default: () => [10, 25, 50, 100]
    },
    perPageText: String,
    perPage: Number,
});
const emit = defineEmits(['update:page-size-change', 'update:page-change', 'handle']);

function handleEvent(event) {
    emit('handle', event);
}

const pageSize = ref(props.perPage || props.pageSizeOptions[0]);
const currentPage = ref(props.currentPage || 1);

const router = useRouter();
const route = useRoute();

onMounted(() => {
    const query = route.query;
    if (query.page && query.page !== currentPage.value) {
        handlePageChange(Number(query.page));
    }
    if (query.perPage && query.perPage !== pageSize.value) {
        handlePageSizeChange(Number(query.perPage));
    }
});

watch([pageSize, currentPage], () => {
    const query = {};
    if (pageSize.value !== 10) {
        query.perPage = pageSize.value;
    }
    if (currentPage.value !== 1) {
        query.page = currentPage.value;
    }

    router.replace({path: route.path, query});
});

const handlePageSizeChange = size => {
    pageSize.value = size;
    emit('update:page-size-change', size);
};

const handlePageChange = page => {
    currentPage.value = page;
    emit('update:page-change', page);
};
const handleExport = () => {
    emit('update:export');
};

</script>

<template>
    <div class="card-body">
        <div id="example2_wrapper" class="dataTables_wrapper dt-bootstrap4">
<!--            <div class="row">-->
<!--                <table-per-page-select-->
<!--                    :perPageText="perPageText"-->
<!--                    :options="pageSizeOptions"-->
<!--                    :value="pageSize"-->
<!--                    @update:page-size="handlePageSizeChange"/>-->
<!--            </div>-->

            <div class="row">
                <table-main-part
                    :columns="columns"
                    :data="data"
                    @handle="handleEvent"/>
            </div>
<!--            <div class="row">-->
<!--                <table-pagination-->
<!--                    :total-pages="totalPages"-->
<!--                    :current-page="currentPage"-->
<!--                    @update:page-change="handlePageChange"-->
<!--                />-->
<!--            </div>-->
        </div>
    </div>
</template>

<style scoped lang="scss">
</style>
