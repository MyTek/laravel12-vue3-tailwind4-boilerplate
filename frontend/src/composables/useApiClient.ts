import { computed } from 'vue'

export type ApiErrorPayload = {
    message?: string
    errors?: Record<string, string[]>
}

export class ApiError extends Error {
    status: number
    payload: ApiErrorPayload | null

    constructor(message: string, status: number, payload: ApiErrorPayload | null) {
        super(message)
        this.status = status
        this.payload = payload
    }
}

export type PaginationMeta = {
    current_page: number
    from: number | null
    last_page: number
    per_page: number
    to: number | null
    total: number
}

export type Paginated<T> = {
    data: T[]
    meta?: PaginationMeta
}

function buildQuery(params?: Record<string, any>) {
    if (!params) return ''
    const sp = new URLSearchParams()
    Object.entries(params).forEach(([k, v]) => {
        if (v === undefined || v === null || v === '') return
        sp.set(k, String(v))
    })
    const s = sp.toString()
    return s ? `?${s}` : ''
}

async function parseJsonSafe(res: Response) {
    const text = await res.text()
    if (!text) return null
    try {
        return JSON.parse(text)
    } catch {
        return null
    }
}

export function useApiClient() {
    const baseUrl = computed(() => {
        const v = (import.meta as any).env?.VITE_API_BASE_URL
        return (v && String(v).trim()) ? String(v).replace(/\/+$/, '') : ''
    })

    async function request<T>(
        method: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE',
        path: string,
        opts?: { body?: any; query?: Record<string, any> }
    ): Promise<T> {
        const url = `${baseUrl.value}${path}${buildQuery(opts?.query)}`
        const hasBody = method !== 'GET' && method !== 'DELETE' && opts?.body !== undefined

        const res = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: hasBody ? JSON.stringify(opts?.body ?? {}) : undefined,
        })

        if (res.status === 204) {
            return undefined as unknown as T
        }

        const payload = await parseJsonSafe(res)

        if (!res.ok) {
            const message =
                (payload && (payload.message || payload.error)) ||
                `Request failed (${res.status})`
            throw new ApiError(message, res.status, payload)
        }

        return payload as T
    }

    return {
        get: <T>(path: string, query?: Record<string, any>) => request<T>('GET', path, { query }),
        post: <T>(path: string, body?: any) => request<T>('POST', path, { body }),
        put: <T>(path: string, body?: any) => request<T>('PUT', path, { body }),
        patch: <T>(path: string, body?: any) => request<T>('PATCH', path, { body }),
        del: <T>(path: string) => request<T>('DELETE', path),
    }
}
