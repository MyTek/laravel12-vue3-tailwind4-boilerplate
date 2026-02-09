import axios from 'axios'

const baseURL =
    (import.meta as any).env?.VITE_API_BASE_URL ||
    (import.meta as any).env?.VITE_APP_URL ||
    'http://localhost:8080'

export const api = axios.create({
    baseURL,
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
    withCredentials: true,
})

api.interceptors.response.use(
    (res) => res,
    (error) => {
        const res = error?.response
        const data = res?.data
        const firstErrorKey = data?.errors ? Object.keys(data.errors)[0] : null
        const firstError = firstErrorKey ? data.errors[firstErrorKey]?.[0] : null
        const msg = firstError || data?.message || `Request failed (${res?.status ?? 'unknown'})`

        error.message = msg
        return Promise.reject(error)
    }
)

export default api
