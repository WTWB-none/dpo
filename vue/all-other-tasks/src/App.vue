<script setup lang="ts">
import { RouterLink, RouterView } from "vue-router";
</script>

<template>
  <div class="app-shell">
    <header class="app-header">
      <RouterLink to="/" class="logo">База резюме</RouterLink>
      <nav>
        <RouterLink to="/">Главная</RouterLink>
      </nav>
    </header>
    <main class="app-main">
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
:global(body) {
  font-family: "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  margin: 0;
  background: #f4f6fb;
  color: #111827;
  min-height: 100vh;
}

.app-shell {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.app-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 2rem;
  background: #0f172a;
  color: #fff;
}

.logo {
  font-weight: 700;
  font-size: 1.2rem;
  text-decoration: none;
  color: inherit;
}

nav {
  display: flex;
  gap: 1rem;
}

nav a {
  color: #cbd5f5;
  text-decoration: none;
  font-weight: 600;
}

nav a.router-link-active {
  color: #fff;
}

.app-main {
  flex: 1;
  padding: 2rem;
}
</style>
