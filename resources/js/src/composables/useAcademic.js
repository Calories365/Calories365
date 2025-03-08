import { ref } from 'vue';

export function useAcademic() {
    // Access academic status from the global window object
    const isAcademic = ref(window.isAcademic || false);
    
    return {
        isAcademic
    };
} 