<script setup lang="ts">
export type Column<T> = {
    key: string
    label: string
    cell?: (row: T) => string
}

defineProps<{
    title: string
    rows: any[]
    columns: Column<any>[]
    loading?: boolean
}>()

const emit = defineEmits<{
    (e: 'create'): void
    (e: 'edit', row: any): void
    (e: 'delete', row: any): void
}>()
</script>

<template>
    <div class="rounded-xl border bg-white">
        <div class="flex items-center justify-between border-b px-4 py-3">
            <div class="font-semibold">{{ title }}</div>
            <button class="rounded bg-black px-3 py-2 text-sm text-white hover:bg-gray-800" @click="emit('create')">
                Create
            </button>
        </div>

        <div v-if="loading" class="p-4 text-sm text-gray-600">Loading...</div>

        <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50">
                <tr>
                    <th v-for="c in columns" :key="c.key" class="px-4 py-2 font-medium text-gray-700">
                        {{ c.label }}
                    </th>
                    <th class="px-4 py-2 font-medium text-gray-700">Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="r in rows" :key="r.id" class="border-t">
                    <td v-for="c in columns" :key="c.key" class="px-4 py-2">
                        <span v-if="c.cell">{{ c.cell(r) }}</span>
                        <span v-else>{{ r[c.key] }}</span>
                    </td>
                    <td class="px-4 py-2">
                        <div class="flex gap-2">
                            <button class="rounded border px-2 py-1 hover:bg-gray-50" @click="emit('edit', r)">Edit</button>
                            <button class="rounded border px-2 py-1 hover:bg-gray-50" @click="emit('delete', r)">Delete</button>
                        </div>
                    </td>
                </tr>

                <tr v-if="rows.length === 0">
                    <td :colspan="columns.length + 1" class="px-4 py-6 text-center text-gray-500">
                        No rows
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
