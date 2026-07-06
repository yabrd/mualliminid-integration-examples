<template>
  <div
    class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 shadow-sm dark:shadow-none w-full transition-colors duration-200 rounded-sm overflow-hidden"
  >
    <header
      v-if="title || $slots.headerActions"
      class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700/80 shadow-[0_4px_12px_rgba(0,0,0,0.05)] relative z-10 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
    >
      <div class="flex-1">
        <slot name="title">
          <h2
            v-if="title"
            :class="[
              'font-bold text-slate-800 dark:text-slate-200',
              subtitle ? 'text-xl' : 'text-2xl'
            ]"
          >
            {{ title }}
          </h2>
        </slot>
        <p
          v-if="subtitle"
          class="text-xs text-slate-500 dark:text-slate-400 mt-1"
        >
          {{ subtitle }}
        </p>
      </div>
      <div v-if="$slots.headerActions" class="shrink-0">
        <slot name="headerActions" />
      </div>
    </header>

    <div :class="[(title || $slots.headerActions || $slots.footer) ? 'bg-slate-50/50 dark:bg-slate-950/30' : '', bodyClass]">
      <slot />
    </div>

    <footer
      v-if="$slots.footer"
      class="bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700/80 shadow-[0_-4px_12px_rgba(0,0,0,0.05)] relative z-10 p-4 sm:p-5"
    >
      <slot name="footer" />
    </footer>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    default: null,
  },
  subtitle: {
    type: String,
    default: null,
  },
  bodyClass: {
    type: String,
    default: 'p-4 sm:p-5',
  },
});
</script>
