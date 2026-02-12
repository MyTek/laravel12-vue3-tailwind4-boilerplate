<template>
    <div class="mx-auto max-w-6xl space-y-4 p-6">
        <div class="rounded-xl border bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="text-xl font-semibold">
                        Patient Billing
                    </div>
                    <div class="text-sm text-slate-600">
            <span v-if="payload?.person">
              {{ payload.person.first_name ?? '' }} {{ payload.person.last_name ?? '' }}
              <span class="text-slate-400">(#{{ payload.person.id }})</span>
            </span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-2">
                        <input
                            v-model.number="selectedPersonId"
                            type="number"
                            class="w-40 rounded-lg border px-3 py-2 text-sm"
                            placeholder="Person ID"
                        />
                        <button
                            class="rounded-lg border px-3 py-2 text-sm hover:bg-slate-50"
                            :disabled="loading || !selectedPersonId"
                            @click="load"
                        >
                            Load
                        </button>
                    </div>

                    <button
                        class="rounded-lg border px-3 py-2 text-sm hover:bg-slate-50 disabled:opacity-50"
                        :disabled="creatingPerson"
                        @click="createPerson"
                    >
                        {{ creatingPerson ? 'Creating...' : 'Create Person' }}
                    </button>

                    <button
                        class="rounded-lg border px-3 py-2 text-sm hover:bg-slate-50 disabled:opacity-50"
                        :disabled="creatingInvoice"
                        @click="createInvoice"
                    >
                        {{ creatingInvoice ? 'Creating...' : 'Create Invoice' }}
                    </button>

                    <button
                        class="rounded-lg border px-3 py-2 text-sm hover:bg-slate-50 disabled:opacity-50"
                        :disabled="creatingPayment"
                        @click="createPayment"
                    >
                        {{ creatingPayment ? 'Creating...' : 'Create Payment' }}
                    </button>

                    <button
                        class="rounded-lg border px-3 py-2 text-sm hover:bg-slate-50"
                        :disabled="loading"
                        @click="load"
                    >
                        Refresh
                    </button>

                    <button
                        class="rounded-lg border px-3 py-2 text-sm hover:bg-slate-50"
                        @click="showDeleted = !showDeleted"
                    >
                        {{ showDeleted ? 'Hide' : 'Show' }} Deleted
                    </button>
                </div>
            </div>

            <div v-if="errorMsg" class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                {{ errorMsg }}
            </div>

            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                <div class="rounded-lg border bg-slate-50 p-4">
                    <div class="text-xs text-slate-500">Total Credits</div>
                    <div class="text-lg font-semibold">{{ money(totalCredits) }}</div>
                </div>
                <div class="rounded-lg border bg-slate-50 p-4">
                    <div class="text-xs text-slate-500">Applied</div>
                    <div class="text-lg font-semibold">{{ money(totalApplied) }}</div>
                </div>
                <div class="rounded-lg border bg-slate-50 p-4">
                    <div class="text-xs text-slate-500">Available</div>
                    <div class="text-lg font-semibold">{{ money(totalAvailable) }}</div>
                </div>
            </div>
        </div>

        <!-- Apply credit -->
        <div class="rounded-xl border bg-white p-5 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <div class="text-base font-semibold">Apply Credit to Invoice</div>
                <div class="text-xs text-slate-500">
                    Payment credits can be applied across invoices.
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                <div>
                    <label class="text-xs text-slate-600">Payment</label>
                    <select v-model="applyForm.payment_id" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">
                        <option value="">Select Payment</option>
                        <option
                            v-for="p in payload?.payments ?? []"
                            :key="p.id"
                            :value="p.id"
                        >
                            #{{ p.id }} ({{ money(p.payment_amount) }}) avail {{ money(availableCreditsByPaymentId.get(p.id) ?? 0) }}
                        </option>
                    </select>
                    <div class="mt-1 text-xs text-slate-500">
                        Available: <span class="font-semibold">{{ money(selectedPaymentAvailable) }}</span>
                    </div>
                </div>

                <div>
                    <label class="text-xs text-slate-600">Invoice</label>
                    <select v-model="applyForm.invoice_id" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">
                        <option value="">Select Invoice</option>
                        <option
                            v-for="inv in payload?.invoices ?? []"
                            :key="inv.id"
                            :value="inv.id"
                        >
                            #{{ inv.id }} (bal {{ money(inv.balance) }}) {{ inv.settled ? 'settled' : '' }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="text-xs text-slate-600">Amount</label>
                    <input
                        v-model="applyForm.amount"
                        type="number"
                        step="0.01"
                        class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"
                        :placeholder="money(Math.min(selectedPaymentAvailable, 0))"
                    />
                    <div class="mt-1 text-xs text-slate-500">
                        Must be positive and not exceed available credits.
                    </div>
                </div>

                <div class="flex items-end">
                    <button
                        class="w-full rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50"
                        :disabled="applying || !applyForm.payment_id || !applyForm.invoice_id || !applyForm.amount"
                        @click="applyCredit"
                    >
                        Apply
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <!-- Invoices -->
            <div class="rounded-xl border bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <div class="text-base font-semibold">Invoices</div>
                    <div class="text-xs text-slate-500">Balance can be positive, zero, or negative.</div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                        <tr class="border-b bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                            <th
                                v-for="c in balanceColumns"
                                :key="c.name"
                                class="cursor-pointer px-3 py-2 select-none"
                                @click="setSort(invoiceSort, c.name)"
                            >
                                <div class="flex items-center gap-2">
                                    <span>{{ c.label }}</span>
                                    <span v-if="invoiceSort.col === c.name" class="text-slate-400">
                      {{ invoiceSort.dir === 'asc' ? '▲' : '▼' }}
                    </span>
                                </div>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-if="loading">
                            <td :colspan="balanceColumns.length" class="px-3 py-4 text-slate-500">Loading...</td>
                        </tr>
                        <tr
                            v-for="r in invoicesSorted"
                            :key="r.id"
                            class="border-b last:border-b-0 hover:bg-slate-50"
                        >
                            <td v-for="c in balanceColumns" :key="c.name" class="px-3 py-2">
                                <span v-if="c.format">{{ c.format(c.field(r), r) }}</span>
                                <span v-else>{{ c.field(r) }}</span>
                            </td>
                        </tr>

                        <tr v-if="!loading && invoicesSorted.length === 0">
                            <td :colspan="balanceColumns.length" class="px-3 py-6 text-center text-slate-500">No invoices</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payments -->
            <div class="rounded-xl border bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <div class="text-base font-semibold">Payments</div>
                    <div class="text-xs text-slate-500">PaymentAmount is always positive.</div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                        <tr class="border-b bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                            <th
                                v-for="c in paymentColumns"
                                :key="c.name"
                                class="cursor-pointer px-3 py-2 select-none"
                                @click="setSort(paymentSort, c.name)"
                            >
                                <div class="flex items-center gap-2">
                                    <span>{{ c.label }}</span>
                                    <span v-if="paymentSort.col === c.name" class="text-slate-400">
                      {{ paymentSort.dir === 'asc' ? '▲' : '▼' }}
                    </span>
                                </div>
                            </th>
                            <th class="px-3 py-2 text-xs uppercase tracking-wide text-slate-600">Available</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-if="loading">
                            <td :colspan="paymentColumns.length + 1" class="px-3 py-4 text-slate-500">Loading...</td>
                        </tr>

                        <tr
                            v-for="r in paymentsSorted"
                            :key="r.id"
                            class="border-b last:border-b-0 hover:bg-slate-50"
                        >
                            <td v-for="c in paymentColumns" :key="c.name" class="px-3 py-2">
                                <span v-if="c.format">{{ c.format(c.field(r), r) }}</span>
                                <span v-else>{{ c.field(r) }}</span>
                            </td>
                            <td class="px-3 py-2 font-semibold">
                                {{ money(availableCreditsByPaymentId.get(r.id) ?? 0) }}
                            </td>
                        </tr>

                        <tr v-if="!loading && paymentsSorted.length === 0">
                            <td :colspan="paymentColumns.length + 1" class="px-3 py-6 text-center text-slate-500">No payments</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Invoice Payments -->
        <div class="rounded-xl border bg-white p-5 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <div class="text-base font-semibold">Invoice Payments (Applied Credits)</div>
                <div class="text-xs text-slate-500">Applications of payment credits to invoices.</div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                    <tr class="border-b bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                        <th
                            v-for="c in invoicePaymentColumns"
                            :key="c.name"
                            class="cursor-pointer px-3 py-2 select-none"
                            @click="setSort(invoicePaymentSort, c.name)"
                        >
                            <div class="flex items-center gap-2">
                                <span>{{ c.label }}</span>
                                <span v-if="invoicePaymentSort.col === c.name" class="text-slate-400">
                    {{ invoicePaymentSort.dir === 'asc' ? '▲' : '▼' }}
                  </span>
                            </div>
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-if="loading">
                        <td :colspan="invoicePaymentColumns.length" class="px-3 py-4 text-slate-500">Loading...</td>
                    </tr>

                    <tr
                        v-for="r in invoicePaymentsSorted"
                        :key="r.id"
                        class="border-b last:border-b-0 hover:bg-slate-50"
                    >
                        <td v-for="c in invoicePaymentColumns" :key="c.name" class="px-3 py-2">
                            <span v-if="c.format">{{ c.format(c.field(r), r) }}</span>
                            <span v-else>{{ c.field(r) }}</span>
                        </td>
                    </tr>

                    <tr v-if="!loading && invoicePaymentsSorted.length === 0">
                        <td :colspan="invoicePaymentColumns.length" class="px-3 py-6 text-center text-slate-500">No invoice payments</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- DeletedRaw -->
        <div v-if="showDeleted" class="rounded-xl border bg-white p-5 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <div class="text-base font-semibold">Deleted Payments</div>
                <div class="text-xs text-slate-500">Payments with deleted_at plus their associated invoice payments.</div>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div class="rounded-lg border bg-slate-50 p-4">
                    <div class="mb-2 text-sm font-semibold">Deleted Payments</div>
                    <div v-if="(payload?.deleted_raw.payments?.length ?? 0) === 0" class="text-sm text-slate-500">
                        None
                    </div>
                    <div v-else class="space-y-2">
                        <div
                            v-for="p in payload?.deleted_raw.payments ?? []"
                            :key="p.id"
                            class="rounded-lg border bg-white p-3 text-sm"
                        >
                            <div class="flex items-center justify-between">
                                <div class="font-semibold">#{{ p.id }}</div>
                                <div class="text-slate-500">{{ money(p.payment_amount) }}</div>
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                deleted_at: {{ p.deleted_at }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border bg-slate-50 p-4">
                    <div class="mb-2 text-sm font-semibold">Deleted InvoicePayments</div>
                    <div v-if="(payload?.deleted_raw.invoice_payments?.length ?? 0) === 0" class="text-sm text-slate-500">
                        None
                    </div>
                    <div v-else class="space-y-2">
                        <div
                            v-for="ip in payload?.deleted_raw.invoice_payments ?? []"
                            :key="ip.id"
                            class="rounded-lg border bg-white p-3 text-sm"
                        >
                            <div class="flex items-center justify-between">
                                <div class="font-semibold">#{{ ip.id }}</div>
                                <div class="text-slate-500">{{ money(ip.amount) }}</div>
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                payment #{{ ip.payment_id }} → invoice #{{ ip.invoice_id ?? 'n/a' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>
<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import api from '@/lib/axios'

/**
 * Reverb / Echo (no auth)
 * Requires: npm i laravel-echo pusher-js
 */
import echo from '@/lib/echo'

const props = defineProps({
    personId: { type: Number, required: true, default: 1 },
    apiBaseUrl: { type: String, default: undefined },
})

const apiBaseUrl = computed(() => props.apiBaseUrl ?? (import.meta).env?.VITE_API_BASE_URL ?? '')

const loading = ref(false)
const errorMsg = ref(null)

const payload = ref(null)

const selectedPersonId = ref(props.personId || 0)
const creatingPerson = ref(false)
const creatingInvoice = ref(false)
const creatingPayment = ref(false)

function money(v) {
    const n = Number(v ?? 0)
    const sign = n < 0 ? '-' : ''
    const abs = Math.abs(n)
    return `${sign}$${abs.toFixed(2)}`
}

function num(v) {
    const n = Number(v)
    return Number.isFinite(n) ? n : 0
}

async function apiGet(path) {
    const prev = api.defaults.baseURL
    api.defaults.baseURL = apiBaseUrl.value || prev
    const res = await api.get(path)
    api.defaults.baseURL = prev
    return res.data
}

async function apiPost(path, body) {
    const prev = api.defaults.baseURL
    api.defaults.baseURL = apiBaseUrl.value || prev
    const res = await api.post(path, body ?? {})
    api.defaults.baseURL = prev
    return res.data
}

async function load() {
    loading.value = true
    errorMsg.value = null
    try {
        payload.value = await apiGet(`/api/v1/billing/patients/${selectedPersonId.value || props.personId}`)
    } catch (e) {
        errorMsg.value = e?.message || 'Failed to load'
    } finally {
        loading.value = false
    }
}

async function createPerson() {
    creatingPerson.value = true
    errorMsg.value = null
    try {
        const created = await apiPost(`/api/v1/people`, { use_factory: true })
        const person = created?.data
        if (person?.id) {
            selectedPersonId.value = person.id
            await load()
        } else {
            throw new Error(`Invoice creation error.`)
        }
    } catch (e) {
        errorMsg.value = e?.message || 'Failed to create person'
    } finally {
        creatingPerson.value = false
    }
}

async function createInvoice() {
    creatingInvoice.value = true
    errorMsg.value = null
    try {
        const created = await apiPost(`/api/v1/invoices`, { person_id: selectedPersonId.value })
        const invoice = created?.data
        if (!invoice?.id) {
            throw new Error(`Invoice creation error.`)
        }
        await load()
    } catch (e) {
        errorMsg.value = e?.message || 'Failed to create invoice'
    } finally {
        creatingInvoice.value = false
    }
}

async function createPayment() {
    creatingPayment.value = true
    errorMsg.value = null
    try {
        const created = await apiPost(`/api/v1/payments/`, { person_id: selectedPersonId.value, use_factory: true })
        const payment = created?.data
        if (!payment?.id) {
            throw new Error(`Payment creation error.`)
        }
        await load()
    } catch (e) {
        errorMsg.value = e?.message || 'Failed to create payment'
    } finally {
        creatingPayment.value = false
    }
}

/**
 * Reverb subscriptions
 * Backend must broadcast on public Channel('person.{id}') and use broadcastAs()
 * Example event names used here:
 *  - invoice.created
 *  - invoice.updated
 *  - payment.created
 *  - credit.applied (optional)
 */
let currentChannelName = null

function leaveCurrentChannel() {
    if (currentChannelName) {
        echo.leave(currentChannelName)
        currentChannelName = null
    }
}

function joinPersonChannel(personId) {
    leaveCurrentChannel()

    const pid = Number(personId)
    if (!pid) return

    const name = `person.${pid}`
    currentChannelName = name

    echo.channel(name)
        .listen('.invoice.created', async () => {
            await load()
        })
        .listen('.invoice.updated', async () => {
            await load()
        })
        .listen('.payment.created', async () => {
            await load()
        })
        .listen('.credit.applied', async () => {
            await load()
        })
}

onMounted(async () => {
    await load()
    joinPersonChannel(selectedPersonId.value || props.personId)
})

watch(() => props.personId, () => {
    selectedPersonId.value = props.personId || 0
    load()
    joinPersonChannel(selectedPersonId.value || props.personId)
})

watch(selectedPersonId, (newVal) => {
    joinPersonChannel(newVal || props.personId)
})

onUnmounted(() => {
    leaveCurrentChannel()
})

/**
 * Table column definitions (q-table style)
 */
const balanceColumns = computed(() => [
    {
        name: 'id',
        label: 'Invoice #',
        field: (r) => r.id,
        sort: (a, b) => num(a) - num(b),
    },
    {
        name: 'total',
        label: 'Total Charges',
        field: (r) => r.total_amount,
        format: (v) => money(v),
        sort: (a, b) => num(a) - num(b),
    },
    {
        name: 'paid',
        label: 'Paid',
        field: (r) => r.paid_amount,
        format: (v) => money(v),
        sort: (a, b) => num(a) - num(b),
    },
    {
        name: 'balance',
        label: 'Balance',
        field: (r) => r.balance,
        format: (v) => money(v),
        sort: (a, b) => num(a) - num(b),
    },
    {
        name: 'settled',
        label: 'Settled',
        field: (r) => r.settled,
        format: (v) => (v ? 'Yes' : 'No'),
        sort: (a, b) => (a === b ? 0 : a ? -1 : 1),
    },
])

const paymentColumns = computed(() => [
    {
        name: 'id',
        label: 'Payment #',
        field: (r) => r.id,
        sort: (a, b) => num(a) - num(b),
    },
    {
        name: 'method',
        label: 'Method',
        field: (r) => r.payment_method ?? '',
        sort: (a, b) => String(a).localeCompare(String(b)),
    },
    {
        name: 'amount',
        label: 'Amount',
        field: (r) => r.payment_amount,
        format: (v) => money(v),
        sort: (a, b) => num(a) - num(b),
    },
    {
        name: 'created',
        label: 'Created',
        field: (r) => r.created_at ?? '',
        sort: (a, b) => String(a).localeCompare(String(b)),
    },
])

const invoicePaymentColumns = computed(() => [
    {
        name: 'id',
        label: '#',
        field: (r) => r.id,
        sort: (a, b) => num(a) - num(b),
    },
    {
        name: 'payment_id',
        label: 'Payment #',
        field: (r) => r.payment_id,
        sort: (a, b) => num(a) - num(b),
    },
    {
        name: 'invoice_id',
        label: 'Invoice #',
        field: (r) => r.invoice_id ?? '',
        sort: (a, b) => String(a).localeCompare(String(b)),
    },
    {
        name: 'amount',
        label: 'Applied',
        field: (r) => r.amount,
        format: (v) => money(v),
        sort: (a, b) => num(a) - num(b),
    },
    {
        name: 'created',
        label: 'Created',
        field: (r) => r.created_at ?? '',
        sort: (a, b) => String(a).localeCompare(String(b)),
    },
])

/**
 * Sorting
 */
const invoiceSort = ref({ col: 'id', dir: 'asc' })
const paymentSort = ref({ col: 'id', dir: 'asc' })
const invoicePaymentSort = ref({ col: 'id', dir: 'asc' })

function sortRows(rows, cols, state) {
    const col = cols.find(c => c.name === state.col)
    if (!col) return rows
    const copy = [...rows]
    copy.sort((ra, rb) => {
        const a = col.field(ra)
        const b = col.field(rb)
        const s = col.sort ? col.sort(a, b, ra, rb) : String(a).localeCompare(String(b))
        return state.dir === 'asc' ? s : -s
    })
    return copy
}

const invoicesSorted = computed(() => {
    const rows = payload.value?.invoices ?? []
    return sortRows(rows, balanceColumns.value, invoiceSort.value)
})

const paymentsSorted = computed(() => {
    const rows = payload.value?.payments ?? []
    return sortRows(rows, paymentColumns.value, paymentSort.value)
})

const invoicePaymentsSorted = computed(() => {
    const rows = payload.value?.invoice_payments ?? []
    return sortRows(rows, invoicePaymentColumns.value, invoicePaymentSort.value)
})

/**
 * Credits
 * PaymentAmount becomes credits; credits applied are sum(invoice_payments.amount) by payment_id.
 */
const appliedByPaymentId = computed(() => {
    const map = new Map()
    const ips = payload.value?.invoice_payments ?? []
    for (const ip of ips) {
        map.set(ip.payment_id, (map.get(ip.payment_id) ?? 0) + num(ip.amount))
    }
    return map
})

const availableCreditsByPaymentId = computed(() => {
    const map = new Map()
    const pays = payload.value?.payments ?? []
    for (const p of pays) {
        const paid = num(p.payment_amount)
        const applied = appliedByPaymentId.value.get(p.id) ?? 0
        map.set(p.id, paid - applied)
    }
    return map
})

const totalCredits = computed(() => {
    const pays = payload.value?.payments ?? []
    return pays.reduce((acc, p) => acc + num(p.payment_amount), 0)
})

const totalApplied = computed(() => {
    const ips = payload.value?.invoice_payments ?? []
    return ips.reduce((acc, ip) => acc + num(ip.amount), 0)
})

const totalAvailable = computed(() => totalCredits.value - totalApplied.value)

/**
 * Apply credit UI
 */
const applyForm = ref({
    payment_id: '' ,
    invoice_id: '' ,
    amount: '' ,
})

const applying = ref(false)

const selectedPaymentAvailable = computed(() => {
    const pid = Number(applyForm.value.payment_id)
    if (!pid) return 0
    return availableCreditsByPaymentId.value.get(pid) ?? 0
})

async function applyCredit() {
    errorMsg.value = null
    applying.value = true
    try {
        const body = {
            payment_id: Number(applyForm.value.payment_id),
            invoice_id: Number(applyForm.value.invoice_id),
            amount: Number(applyForm.value.amount),
        }
        await apiPost(`/api/v1/billing/patients/${selectedPersonId.value || props.personId}/apply-credit`, body)
        applyForm.value.amount = ''
        await load()
    } catch (e) {
        errorMsg.value = e?.message || 'Failed to apply credit'
    } finally {
        applying.value = false
    }
}

function setSort(state, col) {
    if (state.col === col) {
        state.dir = state.dir === 'asc' ? 'desc' : 'asc'
    } else {
        state.col = col
        state.dir = 'asc'
    }
}

/**
 * Deleted raw view
 */
const showDeleted = ref(false)
</script>
