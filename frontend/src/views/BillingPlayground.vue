<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { ApiError } from '@/composables/useApiClient'
import { useBillingApi, type Person, type Invoice, type InvoiceItem, type Payment, type InvoicePayment } from '@/composables/useBillingApi'
import DataTable, { type Column } from '@/components/DataTable.vue'
import ModalShell from '@/components/ModalShell.vue'

type Tab = 'people' | 'invoices' | 'invoiceItems' | 'payments' | 'invoicePayments'

const api = useBillingApi()

const tab = ref<Tab>('people')

const errorMsg = ref<string | null>(null)

const loading = ref(false)
const page = ref(1)
const meta = ref<any>(null)

const peopleRows = ref<Person[]>([])
const invoiceRows = ref<Invoice[]>([])
const invoiceItemRows = ref<InvoiceItem[]>([])
const paymentRows = ref<Payment[]>([])
const invoicePaymentRows = ref<InvoicePayment[]>([])

const rows = computed<any[]>(() => {
    if (tab.value === 'people') return peopleRows.value
    if (tab.value === 'invoices') return invoiceRows.value
    if (tab.value === 'invoiceItems') return invoiceItemRows.value
    if (tab.value === 'payments') return paymentRows.value
    return invoicePaymentRows.value
})

const title = computed(() => {
    if (tab.value === 'people') return 'People'
    if (tab.value === 'invoices') return 'Invoices'
    if (tab.value === 'invoiceItems') return 'Invoice Items'
    if (tab.value === 'payments') return 'Payments'
    return 'Invoice Payments'
})

const columns = computed<Column<any>[]>(() => {
    if (tab.value === 'people') {
        return [
            { key: 'id', label: 'ID' },
            { key: 'first_name', label: 'First' },
            { key: 'last_name', label: 'Last' },
        ]
    }

    if (tab.value === 'invoices') {
        return [
            { key: 'id', label: 'ID' },
            { key: 'person_id', label: 'Person ID' },
            { key: 'total_amount', label: 'Total' },
            { key: 'paid_amount', label: 'Paid' },
            { key: 'balance', label: 'Balance' },
            { key: 'settled', label: 'Settled', cell: (r) => (r.settled ? 'Yes' : 'No') },
        ]
    }

    if (tab.value === 'invoiceItems') {
        return [
            { key: 'id', label: 'ID' },
            { key: 'invoice_id', label: 'Invoice ID' },
            { key: 'amount', label: 'Amount', cell: (r) => String(r.amount ?? '') },
        ]
    }

    if (tab.value === 'payments') {
        return [
            { key: 'id', label: 'ID' },
            { key: 'person_id', label: 'Person ID' },
            { key: 'payment_method', label: 'Method' },
            { key: 'payment_amount', label: 'Amount' },
            { key: 'deleted_at', label: 'Deleted At', cell: (r) => String(r.deleted_at ?? '') },
        ]
    }

    return [
        { key: 'id', label: 'ID' },
        { key: 'payment_id', label: 'Payment ID' },
        { key: 'invoice_id', label: 'Invoice ID', cell: (r) => String(r.invoice_id ?? '') },
        { key: 'amount', label: 'Amount' },
    ]
})

async function load() {
    loading.value = true
    errorMsg.value = null

    try {
        if (tab.value === 'people') {
            const res = await api.people.list(page.value)
            peopleRows.value = res.data
            meta.value = res.meta ?? null
            return
        }

        if (tab.value === 'invoices') {
            const res = await api.invoices.list(page.value)
            invoiceRows.value = res.data
            meta.value = res.meta ?? null
            return
        }

        if (tab.value === 'invoiceItems') {
            const res = await api.invoiceItems.list(page.value)
            invoiceItemRows.value = res.data
            meta.value = res.meta ?? null
            return
        }

        if (tab.value === 'payments') {
            const res = await api.payments.list(page.value)
            paymentRows.value = res.data
            meta.value = res.meta ?? null
            return
        }

        const res = await api.invoicePayments.list(page.value)
        invoicePaymentRows.value = res.data
        meta.value = res.meta ?? null
    } catch (e: any) {
        if (e instanceof ApiError) {
            const v = e.payload
            if (v?.errors) {
                const firstKey = Object.keys(v.errors)[0]
                const firstMsg = firstKey ? v.errors[firstKey]?.[0] : null
                errorMsg.value = firstMsg || v?.message || e.message
            } else {
                errorMsg.value = v?.message || e.message
            }
        } else {
            errorMsg.value = e?.message || 'Unexpected error'
        }
    } finally {
        loading.value = false
    }
}

watch(tab, () => {
    page.value = 1
    load()
})

watch(page, () => {
    load()
})

onMounted(load)

/**
 * Create/edit modal state
 */
type Mode = 'create' | 'edit'
const modalOpen = ref(false)
const modalMode = ref<Mode>('create')
const editRow = ref<any | null>(null)

const form = ref<Record<string, any>>({})

function openCreate() {
    modalMode.value = 'create'
    editRow.value = null
    modalOpen.value = true

    if (tab.value === 'people') form.value = { first_name: '', last_name: '' }
    else if (tab.value === 'invoices') form.value = { person_id: '' }
    else if (tab.value === 'invoiceItems') form.value = { invoice_id: '', amount: '' }
    else if (tab.value === 'payments') form.value = { person_id: '', payment_method: '', payment_amount: '' }
    else form.value = { payment_id: '', invoice_id: '', amount: '' }
}

function openEdit(row: any) {
    modalMode.value = 'edit'
    editRow.value = row
    modalOpen.value = true

    if (tab.value === 'people') form.value = { first_name: row.first_name ?? '', last_name: row.last_name ?? '' }
    else if (tab.value === 'invoices') form.value = { person_id: row.person_id }
    else if (tab.value === 'invoiceItems') form.value = { invoice_id: row.invoice_id, amount: row.amount ?? '' }
    else if (tab.value === 'payments') form.value = { person_id: row.person_id, payment_method: row.payment_method ?? '', payment_amount: row.payment_amount }
    else form.value = { payment_id: row.payment_id, invoice_id: row.invoice_id ?? '', amount: row.amount }
}

function closeModal() {
    modalOpen.value = false
    errorMsg.value = null
}

const modalTitle = computed(() => {
    const verb = modalMode.value === 'create' ? 'Create' : 'Edit'
    return `${verb} ${title.value}`
})

async function submit() {
    loading.value = true
    errorMsg.value = null

    try {
        if (tab.value === 'people') {
            if (modalMode.value === 'create') {
                await api.people.create({ first_name: form.value.first_name || null, last_name: form.value.last_name || null })
            } else {
                await api.people.update(editRow.value.id, { first_name: form.value.first_name || null, last_name: form.value.last_name || null })
            }
        } else if (tab.value === 'invoices') {
            const payload = { person_id: Number(form.value.person_id) }
            if (modalMode.value === 'create') await api.invoices.create(payload)
            else await api.invoices.update(editRow.value.id, payload)
        } else if (tab.value === 'invoiceItems') {
            const payload = { invoice_id: Number(form.value.invoice_id), amount: form.value.amount === '' ? null : Number(form.value.amount) }
            if (modalMode.value === 'create') await api.invoiceItems.create(payload)
            else await api.invoiceItems.update(editRow.value.id, payload)
        } else if (tab.value === 'payments') {
            const payload = {
                person_id: Number(form.value.person_id),
                payment_method: form.value.payment_method ? String(form.value.payment_method) : null,
                payment_amount: Number(form.value.payment_amount),
            }
            if (modalMode.value === 'create') await api.payments.create(payload)
            else await api.payments.update(editRow.value.id, payload)
        } else {
            const payload = {
                payment_id: Number(form.value.payment_id),
                invoice_id: form.value.invoice_id === '' ? null : Number(form.value.invoice_id),
                amount: Number(form.value.amount),
            }
            if (modalMode.value === 'create') await api.invoicePayments.create(payload)
            else await api.invoicePayments.update(editRow.value.id, payload)
        }

        closeModal()
        await load()
    } catch (e: any) {
        if (e instanceof ApiError) {
            const v = e.payload
            if (v?.errors) {
                const firstKey = Object.keys(v.errors)[0]
                const firstMsg = firstKey ? v.errors[firstKey]?.[0] : null
                errorMsg.value = firstMsg || v?.message || e.message
            } else {
                errorMsg.value = v?.message || e.message
            }
        } else {
            errorMsg.value = e?.message || 'Unexpected error'
        }
    } finally {
        loading.value = false
    }
}

async function removeRow(row: any) {
    const ok = window.confirm(`Delete ${title.value} #${row.id}?`)
    if (!ok) return

    loading.value = true
    errorMsg.value = null
    try {
        if (tab.value === 'people') await api.people.remove(row.id)
        else if (tab.value === 'invoices') await api.invoices.remove(row.id)
        else if (tab.value === 'invoiceItems') await api.invoiceItems.remove(row.id)
        else if (tab.value === 'payments') await api.payments.remove(row.id)
        else await api.invoicePayments.remove(row.id)

        await load()
    } catch (e: any) {
        if (e instanceof ApiError) {
            errorMsg.value = e.payload?.message || e.message
        } else {
            errorMsg.value = e?.message || 'Unexpected error'
        }
    } finally {
        loading.value = false
    }
}

const canPrev = computed(() => (meta.value?.current_page ?? 1) > 1)
const canNext = computed(() => (meta.value?.current_page ?? 1) < (meta.value?.last_page ?? 1))
</script>

<template>
    <div class="mx-auto max-w-6xl space-y-4 p-6">
        <div class="rounded-xl border bg-white p-4">
            <div class="text-xl font-semibold">Billing Playground</div>
            <div class="mt-2 flex flex-wrap gap-2">
                <button
                    class="rounded px-3 py-2 text-sm"
                    :class="tab === 'people' ? 'bg-black text-white' : 'border hover:bg-gray-50'"
                    @click="tab = 'people'"
                >
                    People
                </button>

                <button
                    class="rounded px-3 py-2 text-sm"
                    :class="tab === 'invoices' ? 'bg-black text-white' : 'border hover:bg-gray-50'"
                    @click="tab = 'invoices'"
                >
                    Invoices
                </button>

                <button
                    class="rounded px-3 py-2 text-sm"
                    :class="tab === 'invoiceItems' ? 'bg-black text-white' : 'border hover:bg-gray-50'"
                    @click="tab = 'invoiceItems'"
                >
                    Invoice Items
                </button>

                <button
                    class="rounded px-3 py-2 text-sm"
                    :class="tab === 'payments' ? 'bg-black text-white' : 'border hover:bg-gray-50'"
                    @click="tab = 'payments'"
                >
                    Payments
                </button>

                <button
                    class="rounded px-3 py-2 text-sm"
                    :class="tab === 'invoicePayments' ? 'bg-black text-white' : 'border hover:bg-gray-50'"
                    @click="tab = 'invoicePayments'"
                >
                    Invoice Payments
                </button>
            </div>

            <div v-if="errorMsg" class="mt-3 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                {{ errorMsg }}
            </div>
        </div>

        <DataTable
            :title="title"
            :rows="rows"
            :columns="columns"
            :loading="loading"
            @create="openCreate"
            @edit="openEdit"
            @delete="removeRow"
        />

        <div class="flex items-center justify-between rounded-xl border bg-white px-4 py-3 text-sm">
            <div>
                Page <span class="font-semibold">{{ meta?.current_page ?? page }}</span>
                <span v-if="meta?.last_page"> of <span class="font-semibold">{{ meta.last_page }}</span></span>
                <span v-if="meta?.total" class="text-gray-600"> ({{ meta.total }} total)</span>
            </div>
            <div class="flex gap-2">
                <button class="rounded border px-3 py-2 hover:bg-gray-50" :disabled="!canPrev" @click="page = Math.max(1, page - 1)">
                    Prev
                </button>
                <button class="rounded border px-3 py-2 hover:bg-gray-50" :disabled="!canNext" @click="page = page + 1">
                    Next
                </button>
            </div>
        </div>

        <ModalShell :open="modalOpen" :title="modalTitle" @close="closeModal">
            <form class="space-y-3" @submit.prevent="submit">
                <div v-if="tab === 'people'" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div>
                        <label class="text-xs text-gray-600">First Name</label>
                        <input v-model="form.first_name" class="mt-1 w-full rounded border px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Last Name</label>
                        <input v-model="form.last_name" class="mt-1 w-full rounded border px-3 py-2 text-sm" />
                    </div>
                </div>

                <div v-else-if="tab === 'invoices'">
                    <label class="text-xs text-gray-600">Person ID</label>
                    <input v-model="form.person_id" type="number" class="mt-1 w-full rounded border px-3 py-2 text-sm" />
                </div>

                <div v-else-if="tab === 'invoiceItems'" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div>
                        <label class="text-xs text-gray-600">Invoice ID</label>
                        <input v-model="form.invoice_id" type="number" class="mt-1 w-full rounded border px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Amount</label>
                        <input v-model="form.amount" type="number" step="0.01" class="mt-1 w-full rounded border px-3 py-2 text-sm" />
                    </div>
                </div>

                <div v-else-if="tab === 'payments'" class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div>
                        <label class="text-xs text-gray-600">Person ID</label>
                        <input v-model="form.person_id" type="number" class="mt-1 w-full rounded border px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Method (4 chars)</label>
                        <input v-model="form.payment_method" maxlength="4" class="mt-1 w-full rounded border px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Amount</label>
                        <input v-model="form.payment_amount" type="number" step="0.01" class="mt-1 w-full rounded border px-3 py-2 text-sm" />
                    </div>
                </div>

                <div v-else class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div>
                        <label class="text-xs text-gray-600">Payment ID</label>
                        <input v-model="form.payment_id" type="number" class="mt-1 w-full rounded border px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Invoice ID (nullable)</label>
                        <input v-model="form.invoice_id" type="number" class="mt-1 w-full rounded border px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Amount</label>
                        <input v-model="form.amount" type="number" step="0.01" class="mt-1 w-full rounded border px-3 py-2 text-sm" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" class="rounded border px-3 py-2 text-sm hover:bg-gray-50" @click="closeModal">
                        Cancel
                    </button>
                    <button type="submit" class="rounded bg-black px-3 py-2 text-sm text-white hover:bg-gray-800" :disabled="loading">
                        Save
                    </button>
                </div>
            </form>
        </ModalShell>
    </div>
</template>
