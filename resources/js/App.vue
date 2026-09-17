<script setup>
import AppFooter from './components/AppFooter.vue';
import AppHeader from './components/AppHeader.vue';
</script>

<template>
    <div class="min-h-screen overflow-x-hidden bg-sand-50">
        <AppHeader />
        <main>
            <RouterView />
        </main>
        <AppFooter />
    </div>
</template>
