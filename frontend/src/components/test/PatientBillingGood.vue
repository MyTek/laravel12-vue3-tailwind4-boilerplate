<!-- PatientBilling.vue (CLEAN VERSION) -->
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
                                    <q-badge v-if="props.row.settled" color="green">Settled</q-badge>
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
                            <template #body-cell-PaymentAmount="props">
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
                            <q-item v-for="p in (deletedRaw.payments || [])" :key="p.PaymentId">
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
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
    payments: { type: Array, default: () => [] },
    invoices: { type: Array, default: () => [] },
    deletedRaw: { type: Object, default: () => ({ payments: [], invoicePayments: [] }) },
});

const search = ref('');

const balanceColumns = [
    {
        name: 'InvoiceId',
        label: 'Invoice',
        field: 'InvoiceId',
        sortable: true,
        sort(a, b) {
            return Number(a ?? 0) - Number(b ?? 0);
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
            return Number(a ?? 0) - Number(b ?? 0);
        },
    },
    {
        name: 'Paid',
        label: 'Paid',
        field: 'Paid',
        sortable: true,
        format(v) {
            return formatMoney(v);
        },
        sort(a, b) {
            return Number(a ?? 0) - Number(b ?? 0);
        },
    },
    {
        name: 'balance',
        label: 'Balance',
        field: 'balance',
        sortable: true,
        sort(a, b) {
            return Number(a ?? 0) - Number(b ?? 0);
        },
    },
    {
        name: 'settled',
        label: 'Status',
        field: 'settled',
        sortable: true,
        sort(a, b, rowA, rowB) {
            return Number(rowA.settled) - Number(rowB.settled);
        },
    },
];

const paymentColumns = [
    {
        name: 'PaymentId',
        label: 'Payment',
        field: 'PaymentId',
        sortable: true,
        sort(a, b) {
            return Number(a ?? 0) - Number(b ?? 0);
        },
    },
    {
        name: 'PaymentAmount',
        label: 'Amount',
        field: 'PaymentAmount',
        sortable: true,
        format(v) {
            return formatMoney(v);
        },
        sort(a, b) {
            return Number(a ?? 0) - Number(b ?? 0);
        },
    },
    {
        name: 'applied',
        label: 'Applied',
        field: 'applied',
        sortable: true,
        format(v) {
            return formatMoney(v);
        },
        sort(a, b) {
            return Number(a ?? 0) - Number(b ?? 0);
        },
    },
    {
        name: 'unapplied',
        label: 'Unapplied',
        field: 'unapplied',
        sortable: true,
        format(v) {
            return formatMoney(v);
        },
        sort(a, b) {
            return Number(a ?? 0) - Number(b ?? 0);
        },
    },
];

const invoiceRows = computed(() => {
    const rows = (props.invoices || []).map((inv) => {
        const charges =
            Number(inv.ClaimApplied ?? 0) +
            Number(inv.ServiceApplied ?? 0) +
            Number(inv.Invoiced ?? 0);

        const paid = Number(inv.Paid ?? 0);

        const balance = charges - paid;

        return {
            InvoiceId: inv.InvoiceId,
            charges,
            Paid: paid,
            balance,
            settled: balance === 0,
        };
    });

    return rows
        .filter((row) => filterRow(row))
        .slice()
        .sort((a, b) => Number(a.InvoiceId ?? 0) - Number(b.InvoiceId ?? 0));
});

const paymentRows = computed(() => {
    return (props.payments || [])
        .map((p) => {
            const paymentAmount = Number(p.PaymentAmount ?? 0);

            const applied = (p.InvoicePayments || []).reduce((sum, ip) => {
                return sum + Number(ip.Amount ?? 0);
            }, 0);

            const unapplied = paymentAmount - applied;

            return {
                PaymentId: p.PaymentId,
                PaymentAmount: paymentAmount,
                applied,
                unapplied,
            };
        })
        .filter((row) => filterRow(row))
        .slice()
        .sort((a, b) => Number(a.PaymentId ?? 0) - Number(b.PaymentId ?? 0));
});

const patientBalance = computed(() => {
    const invoiceBalances = invoiceRows.value.reduce((sum, r) => sum + Number(r.balance ?? 0), 0);

    const unappliedCredits = paymentRows.value.reduce((sum, r) => sum + Number(r.unapplied ?? 0), 0);

    // Patient responsibility is invoice balances reduced by unapplied credits.
    // If invoices are negative (overcharge), that already reflects refund owed.
    return invoiceBalances - unappliedCredits;
});

const deletedPaymentsCount = computed(() => {
    return (props.deletedRaw?.payments || []).length;
});

function filterRow(row) {
    if (!search.value) return true;
    const s = search.value.toLowerCase();

    const haystack = [
        row.InvoiceId,
        row.PaymentId,
        row.charges,
        row.Paid,
        row.balance,
        row.PaymentAmount,
        row.applied,
        row.unapplied,
    ]
        .filter((v) => v !== null && v !== undefined)
        .join(' ')
        .toLowerCase();

    return haystack.includes(s);
}

function formatMoney(value) {
    const n = Number(value ?? 0);
    const sign = n < 0 ? '-' : '';
    const abs = Math.abs(n);
    return `${sign}$${abs.toFixed(2)}`;
}

function balanceClass(balance) {
    if (balance > 0) return 'text-negative';
    if (balance < 0) return 'text-positive';
    return '';
}

function refresh() {
    // no-op placeholder for wired refresh, left intentionally simple
    search.value = search.value;
}

watch(
    () => [props.invoices, props.payments],
    () => {
        refresh();
    },
    { deep: true }
);

onMounted(() => {
    refresh();
});
</script>

<style scoped>
.patient-billing {
    padding: 16px;
}
</style>
