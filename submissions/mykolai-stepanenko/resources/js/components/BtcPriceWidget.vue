<script setup>
import { onMounted, ref } from 'vue';

const uahFormatter = new Intl.NumberFormat('uk-UA', { style: 'currency', currency: 'UAH' });

const isLoading = ref(true);
const hasError = ref(false);
const price = ref(null);

async function loadPrice() {
    isLoading.value = true;
    hasError.value = false;

    try {
        const response = await fetch('/api/btc-price', { headers: { Accept: 'application/json' } });

        if (! response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        price.value = await response.json();
    } catch {
        price.value = null;
        hasError.value = true;
    } finally {
        isLoading.value = false;
    }
}

onMounted(loadPrice);
</script>

<template>
    <section class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-sm ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800">
        <h1 class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Bitcoin (BTC) / UAH</h1>

        <p v-if="isLoading" class="mt-3 animate-pulse text-3xl font-semibold text-zinc-400" aria-live="polite">
            Завантаження…
        </p>

        <p v-else-if="hasError" class="mt-3 text-lg font-medium text-red-600 dark:text-red-400" role="alert">
            Ціна тимчасово недоступна
        </p>

        <template v-else>
            <p class="mt-3 text-3xl font-semibold tabular-nums">{{ uahFormatter.format(price.price) }}</p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Джерело: {{ price.provider }}</p>
        </template>

        <button
            type="button"
            class="mt-6 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300"
            :disabled="isLoading"
            @click="loadPrice"
        >
            Оновити
        </button>
    </section>
</template>
