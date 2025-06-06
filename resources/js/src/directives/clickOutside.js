export default {
    mounted(el, binding) {
        el.clickOutsideEvent = function (event) {
            // Проверяем, что клик был снаружи элемента и обработчик определен
            if (
                !(el === event.target || el.contains(event.target)) &&
                binding.value
            ) {
                binding.value(event); // Вызываем обработчик, переданный в директиву
            }
        };
        document.addEventListener("click", el.clickOutsideEvent);
    },
    beforeUnmount(el) {
        // Удаляем обработчик события при уничтожении элемента
        document.removeEventListener("click", el.clickOutsideEvent);
    },
};
