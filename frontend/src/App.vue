<script setup lang="ts">
</script>

<template>
    <nav style="display: flex; gap: 16px; margin-bottom: 20px;">
        <router-link to="/">Home</router-link>
        <router-link to="/billing">Billing</router-link>
    </nav>

    <router-view />
</template>

<style scoped>
nav a {
    text-decoration: none;
    color: #42b883;
    font-weight: 600;
}
nav a.router-link-active {
    text-decoration: underline;
}
</style>
