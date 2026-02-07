<script setup lang="ts">
defineProps<{
    open: boolean
    title: string
}>()

const emit = defineEmits<{
    (e: 'close'): void
}>()
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/50" @click="emit('close')"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-2xl rounded-xl bg-white shadow-lg">
                <div class="flex items-center justify-between border-b px-4 py-3">
                    <div class="text-lg font-semibold">{{ title }}</div>
                    <button class="rounded px-2 py-1 text-sm hover:bg-gray-100" @click="emit('close')">
                        Close
                    </button>
                </div>
                <div class="p-4">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
