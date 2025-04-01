export function useHandleEvent({localData, showModal = false, actions = {}}) {
    function handleEvent(payload) {
        if (payload.key && payload.value !== undefined) {
            localData.value[payload.key] = payload.value;
        } else if (payload.action) {
            const action = actions[payload.action];
            if (action) {
                action(payload);
            } else {
                console.log("Неизвестное действие");
            }
        }
    }


    return {
        handleEvent,
        showModal,
    };
}
