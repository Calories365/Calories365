<script>
import {actionTypes} from "@/store/modules/auth.js";

export default {
    name: "CaloriesErrors",
    props: {
        validationErrors: {
            type: Object,
            required: true,
        }
    },
    computed: {
        errorMessages() {
            let messages = [];
            Object.keys(this.validationErrors).forEach((key) => {
                messages = messages.concat(this.validationErrors[key]);
            });
            return messages;
        }
    },
}
</script>

<template>
    <div class="p-4 mb-4 bg-red-100 border-t-4 border-red-500 rounded-b text-red-900 px-4 py-3 shadow-md" role="alert">
        <div class="flex">
            <div class="py-1">
            </div>
            <div>
                <p class="font-bold">  {{ $t('Notification.Error.Errors') }}</p>
                <ul>
                    <li v-for="message in errorMessages" :key="message">
                        {{ message }}
                    </li>
                </ul>

            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">

</style>
