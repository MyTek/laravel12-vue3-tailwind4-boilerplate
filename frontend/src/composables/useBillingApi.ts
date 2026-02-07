import { useApiClient, type Paginated } from './useApiClient'

export type Person = {
    id: number
    first_name: string | null
    last_name: string | null
    created_at?: string
    updated_at?: string
}

export type Invoice = {
    id: number
    person_id: number
    total_amount?: string
    paid_amount?: string
    balance?: string
    settled?: boolean
    created_at?: string
    updated_at?: string
}

export type InvoiceItem = {
    id: number
    invoice_id: number
    amount: string | number | null
    created_at?: string
    updated_at?: string
}

export type Payment = {
    id: number
    person_id: number
    payment_method: string | null
    payment_amount: string | number
    deleted_at?: string | null
    created_at?: string
    updated_at?: string
}

export type InvoicePayment = {
    id: number
    payment_id: number
    invoice_id: number | null
    amount: string | number
    created_at?: string
    updated_at?: string
}

export function useBillingApi() {
    const api = useApiClient()

    return {
        people: {
            list: (page = 1) => api.get<Paginated<Person>>('/api/v1/people', { page }),
            create: (payload: { first_name?: string | null; last_name?: string | null }) =>
                api.post<Person>('/api/v1/people', payload),
            update: (id: number, payload: { first_name?: string | null; last_name?: string | null }) =>
                api.put<Person>(`/api/v1/people/${id}`, payload),
            remove: (id: number) => api.del<void>(`/api/v1/people/${id}`),
        },

        invoices: {
            list: (page = 1) => api.get<Paginated<Invoice>>('/api/v1/invoices', { page }),
            create: (payload: { person_id: number }) => api.post<Invoice>('/api/v1/invoices', payload),
            update: (id: number, payload: { person_id?: number }) =>
                api.put<Invoice>(`/api/v1/invoices/${id}`, payload),
            remove: (id: number) => api.del<void>(`/api/v1/invoices/${id}`),
        },

        invoiceItems: {
            list: (page = 1) => api.get<Paginated<InvoiceItem>>('/api/v1/invoice-items', { page }),
            create: (payload: { invoice_id: number; amount?: number | null }) =>
                api.post<InvoiceItem>('/api/v1/invoice-items', payload),
            update: (id: number, payload: { invoice_id?: number; amount?: number | null }) =>
                api.put<InvoiceItem>(`/api/v1/invoice-items/${id}`, payload),
            remove: (id: number) => api.del<void>(`/api/v1/invoice-items/${id}`),
        },

        payments: {
            list: (page = 1) => api.get<Paginated<Payment>>('/api/v1/payments', { page }),
            create: (payload: { person_id: number; payment_method?: string | null; payment_amount: number }) =>
                api.post<Payment>('/api/v1/payments', payload),
            update: (id: number, payload: { person_id?: number; payment_method?: string | null; payment_amount?: number }) =>
                api.put<Payment>(`/api/v1/payments/${id}`, payload),
            remove: (id: number) => api.del<void>(`/api/v1/payments/${id}`),
        },

        invoicePayments: {
            list: (page = 1) => api.get<Paginated<InvoicePayment>>('/api/v1/invoice-payments', { page }),
            create: (payload: { payment_id: number; invoice_id?: number | null; amount: number }) =>
                api.post<InvoicePayment>('/api/v1/invoice-payments', payload),
            update: (id: number, payload: { payment_id?: number; invoice_id?: number | null; amount?: number }) =>
                api.put<InvoicePayment>(`/api/v1/invoice-payments/${id}`, payload),
            remove: (id: number) => api.del<void>(`/api/v1/invoice-payments/${id}`),
        },
    }
}
