import { test, expect } from 'vitest'
import { render, screen } from '@testing-library/vue'
import App from './App.vue'

test('renders vite + vue header', () => {
    render(App)
    expect(screen.getByText('Vite + Vue')).toBeInTheDocument()
})
