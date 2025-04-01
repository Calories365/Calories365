import {ref} from 'vue';
import {actionTypes} from "@/store/modules/bots.js";

export default function usePagination(dispatch, fetchDataFunction = null, botId = null) {
    const currentPage = ref(1);
    const pageSize = ref(10);

    const handlePageChange = (page) => {
        currentPage.value = page;
        if (fetchDataFunction) {
            fetchDataFunction(botId);
        } else {
            dispatch(actionTypes.changePage, {page});
        }
        window.scrollTo({top: 0, left: 0, behavior: 'smooth'});
    };

    const handlePageSizeChange = (size) => {
        pageSize.value = size;
        if (fetchDataFunction) {
            fetchDataFunction(botId);
        } else {
            dispatch(actionTypes.setPageSize, {size});
        }
        window.scrollTo({top: 0, left: 0, behavior: 'smooth'});
    };

    return {
        currentPage,
        pageSize,
        handlePageChange,
        handlePageSizeChange
    };
}
