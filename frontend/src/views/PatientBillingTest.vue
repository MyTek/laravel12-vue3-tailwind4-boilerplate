<template>
    <div>
        <div
            class="q-pa-sm q-ma-sm"
            v-if="!isLoading">
            <div class="row justify-between items-center">
                <div
                    class="col row items-center justify-between text-white rounded-borders q-mr-xs q-pa-sm"
                    style="background: linear-gradient(109.48deg, #FD5C48 -1.67%, #FD425A 103.86%)">
                    <div class="row items-end text-subtitle1">
                        <div class="a-balance q-mr-md">
                            ${{ Number(totalBalance).toFixed(2) }}
                        </div>
                        <div class="q-pb-xs">
                            Outstanding
                        </div>
                    </div>
                </div>
                <div
                    class="col row items-center justify-between text-white rounded-borders q-ml-xs q-pa-sm"
                    style="background: linear-gradient(109.48deg, #1EABFD -1.67%, #1E96FD 103.86%)">
                    <div class="row items-end text-subtitle1">
                        <div class="a-balance q-mr-sm">
                            ${{ Number(availableCredit).toFixed(2) }}
                        </div>
                        <div class="q-pb-xs">
                            Credit
                        </div>
                    </div>
                </div>
            </div>
            <div class="row q-mt-xs q-col-gutter-sm">
                <div class="col-12 col-md-6">
                    <q-card style="margin: 0">
                        <div
                            class="row justify-between items-center q-pa-md"
                            style="min-height: 64px;">
                            <div
                                style="font-size: 18px; line-height: 21px"
                                class="text-p-gray text-weight-medium">
                                Balances
                            </div>
                            <q-toggle
                                label="Show settled"
                                left-label
                                v-model="isShowingSettled" />
                        </div>
                        <q-separator />
                        <div class="q-pa-md">
                            <q-table
                                v-show="tableData.length > 0"
                                dense
                                class="no-shadow new"
                                hide-bottom
                                separator="none"
                                :pagination.sync="balancePagination"
                                :columns="balanceColumns"
                                binary-state-sort
                                :data="tableData" />
                            <div
                                v-if="tableData.length === 0"
                                style="border: 1px solid #dee2e8"
                                class="rounded-borders q-pa-md text-body1 text-weight-bold text-grey-7 flex-center row fit">
                                No data to display.
                                <span
                                    class="text-blue-13 q-ml-sm text-weight-bold"
                                    style="cursor: pointer"
                                    @click="isShowingSettled = true"
                                    v-if="!isShowingSettled">
                  Show settled visits?
                </span>
                            </div>
                        </div>
                    </q-card>
                </div>
                <div class="col-12 col-md-6">
                    <q-card style="margin: 0">
                        <div
                            class="row justify-between items-center q-pa-md"
                            style="min-height: 64px;">
                            <div
                                style="font-size: 18px; line-height: 21px"
                                class="text-p-gray text-weight-medium">
                                Payment History
                            </div>
                        </div>
                        <q-separator />
                        <div class="q-pa-md">
                            <q-table
                                dense
                                class="new no-shadow"
                                hide-bottom
                                separator="none"
                                :pagination.sync="paymentPagination"
                                :columns="paymentColumns"
                                :data="paymentTableData"
                                v-show="paymentTableData.length > 0">
                                <q-td
                                    slot="body-cell-Menu"
                                    slot-scope="props"
                                    :props="props">
                                    <q-btn
                                        class="q-mr-sm q-ml-md q-px-xs"
                                        flat
                                        dense
                                        icon="more_vert">
                                        <q-menu>
                                            <q-list>
                                                <q-item
                                                    clickable
                                                    @click="onDeletePaymentClick(props.row.PaymentId)">
                                                    <q-item-section>Delete Entire Payment</q-item-section>
                                                </q-item>
                                            </q-list>
                                        </q-menu>
                                    </q-btn>
                                </q-td>
                            </q-table>
                            <template v-if="paymentTableData2.length > 0">
                                <q-separator />
                                <div
                                    class="row justify-between items-center q-pa-md"
                                    style="min-height: 64px;">
                                    <div
                                        style="font-size: 18px; line-height: 21px"
                                        class="text-p-gray text-weight-medium">
                                        Deleted Payments
                                    </div>
                                </div>
                                <q-separator />
                                <q-table
                                    dense
                                    class="new no-shadow"
                                    hide-bottom
                                    separator="none"
                                    :pagination.sync="deletedPaymentPagination"
                                    :columns="paymentColumns"
                                    :data="paymentTableData2" />
                            </template>

                            <div
                                v-if="paymentTableData.length === 0"
                                style="border: 1px solid #dee2e8"
                                class="rounded-borders q-pa-md text-body1 text-weight-bold text-grey-7 flex-center row fit">
                                Patient has no payment history.
                            </div>
                        </div>
                    </q-card>
                </div>
            </div>
        </div>

        <p-dialog
            :loading="isLoading"
            v-model="isDeletePaymentDialogVisible"
            title="Delete Entire Payment"
            :save-disable="modalConfirmation !== 'DELETE'"
            save-label="Delete Entire Payment"
            @save="deletePayment(modalPaymentId)">
            <div class="text-p-gray600 text-caption">
                <div class="text-p-red text-weight-bold bg-p-red700 rounded-borders">
                    <div class="q-pa-md q-mx-sm bg-p-red100 row no-wrap justify-between">
                        <div class="q-mr-md">
                            <q-icon
                                name="warning"
                                color="p-red700"
                                size="32px" />
                        </div>
                        <div>
                            <span class="text-p-red700">WARNING:</span> BY DELETING THIS PAYMENT YOU ARE SAYING IT NEVER HAPPENED.
                            <div
                                class="text-p-red700 q-mt-sm"
                                style="text-decoration: underline">
                                ALL DATA WILL BE PERMANENTLY LOST.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="q-mt-sm">
                    All of the visits that this payment has been applied to will now show an open balance until you write off those amounts, adjust the charges, or apply other monies to them.
                </div>
                <div class="q-mt-sm">
                    If this is intended to be a refund, make sure you have a record of returning the money to the patient.
                </div>
                <template v-if="modalPaymentId">
                    <div class="text-p-gray600 text-caption">
                        <div class="row justify-between q-my-md">
                            <div class="col-xs-6 col-sm-4">
                                <span class="text-p-gray">Payment Date</span>
                                <div class="text-subtitle1">
                                    {{ raw.payments[modalPaymentId].PaymentDate.substr(5,5).replace(/-/g, '/') }}/{{ raw.payments[modalPaymentId].PaymentDate.substr(0,4) }}
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4">
                                <span class="text-p-gray">Method</span>
                                <div class="text-subtitle1">
                                    {{ readablePaymentMethods[raw.payments[modalPaymentId].PaymentMethod] }}
                                </div>
                            </div>
                            <div class="col-4">
                                <span class="text-p-gray">Amount</span>
                                <div class="text-subtitle1 monospace">
                                    {{ raw.payments[modalPaymentId].PaymentAmount || '0.00' }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        class="q-mt-sm q-mb-md text-p-gray text-weight-medium"
                        style="font-size: 16px; line-height: 24px">
                        Currently Applied to {{ modalPaymentApplied.length }} Visits
                    </div>
                    <template v-for="visit in modalPaymentApplied">
                        <div
                            class="row no-wrap justify-between items-center q-my-sm text-p-gray600"
                            style="font-size: 16px; line-height: 24px"
                            :key="visit.applied">
                            <div class="col">
                                {{ visit.Start.substr(5) }}/{{ visit.Start.substr(0,4) }}
                            </div>
                            $<span class="monospace">{{ (visit.applied || 0).toFixed(2) }}</span>
                        </div>
                        <q-separator :key="'s' + visit.applied" />
                    </template>
                </template>
            </div>

            <q-input
                v-model="modalConfirmation"
                class="q-mt-md"
                outlined
                placeholder="Type &quot;DELETE&quot; to continue" />
        </p-dialog>

        <q-inner-loading :showing="isLoading" />
    </div>
</template>

<script>
import { normalize } from 'normalizr'
import schemas from '../../store/billingSchemas'
import config from '../../helpers/config'
import { date } from 'quasar'
import PDialog from '../Util/PDialog'

const readablePaymentMethods = {
    CARD: 'Credit Card',
    CHK: 'Check',
    CASH: 'Cash',
    STRP: 'Stripe',
    CDCN: 'Card Connect',
    RMBT: 'Reimbursement',
    OPCC: 'Online Portal'
}

export default {
    components: {
        PDialog
    },
    props: {
        patientId: {
            required: true,
            validator: prop => typeof prop === 'string' || prop === null
        }
    },
    data () {
        return {
            isShowingSettled: false,
            balancePagination: {
                rowsPerPage: 0,
                descending: true,
                sortBy: 'Date'
            },
            paymentPagination: {
                rowsPerPage: 0,
                descending: true,
                sortBy: 'Date'
            },
            deletedPaymentPagination: {
                rowsPerPage: 0,
                descending: true,
                sortBy: 'Date'
            },
            balanceColumns: [{
                name: 'Date',
                label: 'Date',
                field: 'Start',
                align: 'left',
                sortable: true,
                sort: (a, b, rowA, rowB) => {
                    if (rowA.deleted_at === rowB.deleted_at) return a < b ? -1 : 1
                    // force deleted visits to the top of the list regardless of sort direction
                    if (this.balancePagination.descending) return rowA.deleted_at ? 1 : -1
                    return rowA.deleted_at ? -1 : 1
                },
                format: value => {
                    const parts = value.split('/')
                    return parts.slice(1).concat(parts[0].slice(2)).join('/')
                }
            }, {
                name: 'Paid',
                label: 'Paid',
                field: 'Paid',
                sortable: true,
                sort: (a, b, rowA, rowB) => {
                    if (rowA.deleted_at === rowB.deleted_at) return a < b ? -1 : 1
                    // force deleted visits to the top of the list regardless of sort direction
                    if (this.balancePagination.descending) return rowA.deleted_at ? 1 : -1
                    return rowA.deleted_at ? -1 : 1
                },
                format: value => (value !== void 0) ? Number(value).toFixed(2) : null
            }, {
                name: 'Balance',
                label: 'Balance',
                field: 'balance',
                sortable: true,
                sort: (a, b, rowA, rowB) => {
                    if (rowA.deleted_at === rowB.deleted_at) return a < b ? -1 : 1
                    // force deleted visits to the top of the list regardless of sort direction
                    if (this.balancePagination.descending) return rowA.deleted_at ? 1 : -1
                    return rowA.deleted_at ? -1 : 1
                },
                format: value => (value !== void 0) ? Number(value).toFixed(2) : null
            }],
            paymentColumns: [{
                name: 'Date',
                label: 'Payment Date',
                field: 'PaymentDate',
                align: 'left',
                sortable: true,
                format: value => date.formatDate(new Date(value), 'MM/DD/YY')
            }, {
                name: 'Method',
                label: 'Method',
                field: 'PaymentMethod',
                align: 'right',
                sortable: true,
                format: value => readablePaymentMethods[value]
            }, {
                name: 'Amount',
                label: 'Amount',
                field: 'PaymentAmount',
                align: 'right',
                sortable: true,
                format: value => value ? Number(value).toFixed(2) : '0.00'
            }, {
                name: 'Menu'
            }],
            // Challenge Note: the following is filled with an example of the shape of the normalized data.
            raw: {
                payments: {
                    2: {
                        PaymentId: 2,
                        invoice_payments: [
                            5,
                            8
                        ],
                        // ...other fields (assume all referenced fields are defined)
                    },
                    // ...other payments
                },
                invoicePayments: {
                    5: {
                        InvoicePaymentId: 5,
                        PaymentId: 2,
                        InvoiceId: 5,
                        // ...other fields (assume all referenced fields are defined)
                    },
                    8: {
                        InvoicePaymentId: 8,
                        PaymentId: 2,
                        InvoiceId: 6,
                        // ...other fields (assume all referenced fields are defined)
                    },
                    // ...other invoice payments
                },
                invoices: {
                    5: {
                        InvoiceId: 5,
                        // ...other fields (assume all referenced fields are defined)
                    },
                    6: {
                        InvoiceId: 6,
                        // ...other fields (assume all referenced fields are defined)
                    },
                    // ...other invoices
                }
            },
            deletedRaw: {
                payments: {},
                invoicePayments: {}
            },
            isLoading: false,
            modalPaymentId: null,
            isDeletePaymentDialogVisible: false,
            modalConfirmation: null,
            readablePaymentMethods
        }
    },
    computed: {
        availableCredit () {
            // raw: {
            //     payments: {
            //         2: {
            //             PaymentId: 2,
            //                 invoice_payments: [
            //                 5,
            //                 8
            //             ],
            //             // ...other fields (assume all referenced fields are defined)
            //         },
            //         // ...other payments
            //     },{
            //         2: {
            //             PaymentId: 2,
            //                 invoice_payments: [
            //                 5,
            //                 8
            //             ],
            //             // ...other fields (assume all referenced fields are defined)
            //         },
            //         // ...other payments
            //     },
            return Math.round(
                Object.values(this.raw.payments)
                .flatMap(payment => payment.invoice_payments)
                .map(id => this.raw.invoicePayments[id])
                .reduce(
                    (sum, invoicePayment) => {
                        return sum + Number(this.raw.payments[invoicePayment.PaymentId].PaymentAmount) - Number(invoicePayment.Amount)
                    },
                0) * 100
            ) / 100
        },
        totalBalance () {
            return Math.round(Object.values(this.invoicesWithBalances).reduce((sum, invoice) => sum + invoice.balance, 0) * 100) / 100
        },
        invoicesWithBalances () {
            let invoices = []
            Object.values(this.raw.invoices).forEach(i => {
                i.balance = Number(i.ClaimApplied) + Number(i.ServiceApplied) + Number(i.Invoiced) - Number(i.Paid)
                invoices.push(i)
            })

            if (!this.isShowingSettled) {
                invoices = invoices.filter(i => i.balance > 0 || i.deleted_at)
            }
            return invoices
        },
        tableData () {
            return Object.values(this.invoicesWithBalances)
        },
        paymentTableData () {
            return Object.values(this.raw.payments)
        },
        paymentTableData2 () {
            return Object.values(this.deletedRaw.payments)
        },
        modalPaymentApplied () {
            if (!this.modalPaymentId) return []
            // group payment's invoice payments by visit and total them
            return Object.values(this.raw.payments[this.modalPaymentId].invoice_payments.reduce((obj, invoicePaymentId) => {
                const ip = this.raw.invoicePayments[invoicePaymentId]
                const i = this.raw.invoices[ip.InvoiceId]
                obj[ip.InvoiceId] = {
                    ...i,
                    applied: parseFloat(ip.Amount)
                }
                return obj
            }, {}))
        }
    },
    methods: {
        onDeletePaymentClick (paymentId) {
            this.modalPaymentId = paymentId
            this.isDeletePaymentDialogVisible = true
        },
        async deletePayment (paymentId) {
            this.isLoading = true
            await this.$axios.delete(config.apiUrl + '/patientBilling/payment/' + paymentId)
            // console.log(paymentId)
            this.isLoading = false
            this.$q.notify({ type: 'positive', message: 'Successfully deleted payment' })
            this.isDeletePaymentDialogVisible = false
            this.fetchData()
        },
        async fetchData () {
            this.isLoading = true

            // this.invoiceFromBalanceSelected = null
            const { data } = await this.$axios.get(config.apiUrl + '/patients/' + this.patientId + '/balances', { showZeroBalances: true })
            const normalized = normalize(data, {
                Payments: [schemas.payment],
                Invoices: [schemas.invoice]
            })
            const normalizedDeleted = normalize(data, {
                DeletedPayments: [schemas.payment]
            })
            Object.keys(this.raw).forEach(key => this.$set(this.raw, key, normalized.entities[key] || {}))
            Object.keys(this.deletedRaw).forEach(key => this.$set(this.deletedRaw, key, normalizedDeleted.entities[key] || {}))

            this.isLoading = false
        }
    },
    watch: {
        isDeletePaymentDialogVisible (v) {
            if (!v) {
                this.modalPaymentId = null
            }
        },
        patientId: {
            handler (id) {
                id && this.fetchData()
            },
            immediate: true
        }
    }
}
</script>
