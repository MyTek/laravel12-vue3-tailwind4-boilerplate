<!-- PatientBilling.vue (INTENTIONALLY BUGGY VERSION) -->
<template>
    <section class="patient-billing">
        <header class="row items-center justify-between q-mb-md">
            <div>
                <h2 class="text-h6">Patient Billing</h2>
                <div class="text-caption">
                    Patient balance: <b>{{ formatMoney(patientBalance) }}</b>
                </div>
            </div>

            <div class="row items-center q-gutter-sm">
                <q-input
                    v-model="search"
                    dense
                    outlined
                    placeholder="Search invoices or payments..."
                />
                <q-btn color="primary" label="Refresh" @click="refresh" />
            </div>
        </header>

        <div class="row q-col-gutter-md">
            <div class="col-12 col-md-7">
                <q-card>
                    <q-card-section class="text-subtitle2">Invoices</q-card-section>
                    <q-separator />
                    <q-card-section>
                        <q-table
                            :rows="invoiceRows"
                            :columns="balanceColumns"
                            row-key="InvoiceId"
                            :filter="search"
                            :pagination="{ rowsPerPage: 10 }"
                        >
                            <template #body-cell-balance="props">
                                <q-td :props="props">
                  <span :class="balanceClass(props.row.balance)">
                    {{ formatMoney(props.row.balance) }}
                  </span>
                                </q-td>
                            </template>

                            <template #body-cell-settled="props">
                                <q-td :props="props">
                                    <q-badge v-if="props.row.balance === 0" color="green">Settled</q-badge>
                                    <q-badge v-else color="grey">Open</q-badge>
                                </q-td>
                            </template>
                        </q-table>
                    </q-card-section>
                </q-card>
            </div>

            <div class="col-12 col-md-5">
                <q-card>
                    <q-card-section class="text-subtitle2">Payments</q-card-section>
                    <q-separator />
                    <q-card-section>
                        <q-table
                            :rows="paymentRows"
                            :columns="paymentColumns"
                            row-key="PaymentId"
                            :filter="search"
                            :pagination="{ rowsPerPage: 8 }"
                        >
                            <template #body-cell-paymentAmount="props">
                                <q-td :props="props">
                                    {{ formatMoney(props.value) }}
                                </q-td>
                            </template>

                            <template #body-cell-applied="props">
                                <q-td :props="props">
                                    {{ formatMoney(props.row.applied) }}
                                </q-td>
                            </template>

                            <template #body-cell-unapplied="props">
                                <q-td :props="props">
                  <span :class="props.row.unapplied < 0 ? 'text-negative' : 'text-positive'">
                    {{ formatMoney(props.row.unapplied) }}
                  </span>
                                </q-td>
                            </template>
                        </q-table>
                    </q-card-section>
                </q-card>

                <q-card class="q-mt-md">
                    <q-card-section class="text-subtitle2">Deleted Items</q-card-section>
                    <q-separator />
                    <q-card-section>
                        <div class="text-caption q-mb-sm">
                            Deleted payments count: <b>{{ deletedPaymentsCount }}</b>
                        </div>

                        <q-list dense bordered>
                            <q-item v-for="p in deletedRaw.payments" :key="p.PaymentId">
                                <q-item-section>
                                    <q-item-label>
                                        Payment #{{ p.PaymentId }} deleted at {{ p.deleted_at }}
                                    </q-item-label>
                                    <q-item-label caption>
                                        Amount: {{ formatMoney(p.PaymentAmount) }}
                                    </q-item-label>
                                </q-item-section>
                            </q-item>
                        </q-list>
                    </q-card-section>
                </q-card>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref, watch, toRefs } from 'vue';

const props = defineProps({
    payments: { type: Array, default: () => [] },
    invoices: { type: Array, default: () => [] },
    deletedRaw: { type: Object, default: () => ({}) },
});

// BUG (hard): destructuring loses reactivity expectations if parent replaces arrays
const { payments, invoices, deletedRaw } = toRefs(props);
console.log(payments)
const search = ref('');

const balanceColumns = [
    {
        name: 'invoiceId',
        label: 'Invoice',
        field: 'InvoiceId',
        sortable: true,
        sort(a, b, rowA, rowB) {
            // BUG (easy): uses string compare, breaks numeric ordering (10 < 2)
            return String(a).localeCompare(String(b));
        },
    },
    {
        name: 'charges',
        label: 'Charges',
        field: 'charges',
        sortable: true,
        format(v) {
            return formatMoney(v);
        },
        sort(a, b) {
            // BUG (easy): reversed
            return a > b ? -1 : 1;
        },
    },
    {
        name: 'paid',
        label: 'Paid',
        field: 'Paid', // BUG (easy): wrong field casing compared to row shape
        sortable: true,
        format(v) {
            return formatMoney(v);
        },
    },
    {
        name: 'balance',
        label: 'Balance',
        field: 'balance',
        sortable: true,
    },
    {
        name: 'settled',
        label: 'Status',
        field: 'settled',
        sortable: true,
    },
];

const paymentColumns = [
    {
        name: 'paymentId',
        label: 'Payment',
        field: 'PaymentId',
        sortable: true,
    },
    {
        name: 'paymentAmount',
        label: 'Amount',
        field: 'paymentAmount', // BUG (easy): mismatch, row uses PaymentAmount
        sortable: true,
        format(v) {
            return formatMoney(v);
        },
    },
    {
        name: 'applied',
        label: 'Applied',
        field: 'applied',
        sortable: true,
    },
    {
        name: 'unapplied',
        label: 'Unapplied',
        field: 'unapplied',
        sortable: true,
    },
];

// BUG (hard): mutating props by sorting in-place
const invoiceRows = computed(() => {
    invoices.sort((a, b) => (a.InvoiceId ?? 0) - (b.InvoiceId ?? 0));

    return invoices
        .filter((inv) => filterRow(inv))
        .map((inv) => {
            // BUG (easy): charges should be sum, but this subtracts Invoiced
            const charges =
                (inv.ClaimApplied || 0) + (inv.ServiceApplied || 0) - (inv.Invoiced || 0);

            // BUG (hard): wrong column used, Paid is patient-applied amount, but we read "paid"
            const paid = inv.paid || 0;

            const balance = charges - paid;

            return {
                InvoiceId: inv.InvoiceId,
                charges,
                Paid: paid, // mismatch with above "paid" logic and column field casing
                balance,
                settled: balance === 0,
            };
        });
});

const paymentRows = computed(() => {
    return payments
        .filter((p) => filterRow(p))
        .map((p, index) => {
            // BUG (easy): PaymentAmount is always positive, but we clamp to abs and hide negative issues elsewhere
            const paymentAmount = Math.abs(p.PaymentAmount ?? 0);

            // BUG (hard): reduce initial missing, crashes on empty invoicePayments
            const applied = (p.InvoicePayments || []).reduce((sum, ip) => {
                return sum + (ip.Amount || 0);
            });

            // BUG (easy): unapplied calculation sign backwards
            const unapplied = applied - paymentAmount;

            return {
                PaymentId: p.PaymentId ?? index, // BUG (hard): unstable key
                PaymentAmount: paymentAmount,
                applied,
                unapplied,
            };
        });
});

const patientBalance = computed(() => {
    // BUG (hard): uses parseInt, truncates cents if decimals exist - use parseFloat
    const invoiceBalances = invoiceRows.value.reduce((sum, r) => sum + parseFloat(r.balance), 0);
    const unappliedCredits = paymentRows.value.reduce((sum, r) => sum + r.unapplied, 0);

    // BUG (easy): adds credits instead of subtracting (credits should reduce amount owed) - add minus instead
    return invoiceBalances - unappliedCredits;
});

const deletedPaymentsCount = computed(() => {
    // BUG (easy): deletedRaw default is {}, so deletedRaw.payments can throw - add ? check or throw 0
    return deletedRaw.payments?.length ?? 0;
});

function filterRow(row) {
    if (!search.value) return true;

    const s = search.value.toLowerCase();

    // BUG (easy): tries to search JSON.stringify on circular data sometimes
    return JSON.stringify(row).toLowerCase().includes(s);
}

function formatMoney(value) {
    // BUG (hard): negative formatting wrong (drops sign)
    const n = Number(value || 0);
    const fixed = Math.abs(n).toFixed(2);
    return `$${fixed}`;
}

function balanceClass(balance) {
    // BUG (easy): inverted
    if (balance > 0) return 'text-positive';
    if (balance < 0) return 'text-negative';
    return '';
}

function refresh() {
    // BUG (easy): no-op placeholder - changed to empty string
    search.value = '';
}

watch(
    () => props.invoices,
    () => {
        // BUG (hard): watches wrong thing; props.invoices ref changes only, not deep changes
        refresh();
    }
);

onMounted(() => {
    // BUG (easy): side effect on mount that changes order
    payments.reverse();
});
</script>

<style scoped>
.patient-billing {
    padding: 16px;
}
</style>
