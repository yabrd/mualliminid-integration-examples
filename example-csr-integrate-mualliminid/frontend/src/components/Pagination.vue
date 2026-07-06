<template>
  <div v-if="total > 0" class="w-full">
    <!-- Desktop Layout -->
    <div class="hidden sm:flex items-center justify-between gap-3 text-xs text-slate-600 dark:text-slate-400 w-full transition-colors duration-200">
      <div>
        Menampilkan <span class="font-semibold text-slate-800 dark:text-slate-200">{{ firstItem }}</span> 
        sampai <span class="font-semibold text-slate-800 dark:text-slate-200">{{ lastItem }}</span> 
        dari <span class="font-semibold text-slate-800 dark:text-slate-200">{{ total }}</span> data
      </div>
      <div class="flex items-center gap-1.5">
        <template v-if="currentPage === 1">
          <span class="w-9 h-9 flex items-center justify-center bg-slate-100 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800/50 text-slate-400 dark:text-slate-600 cursor-not-allowed shadow-sm dark:shadow-none rounded-sm font-bold select-none">&larr;</span>
        </template>
        <template v-else>
          <button @click="$emit('changePage', currentPage - 1)" class="w-9 h-9 flex items-center justify-center bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800/80 text-slate-800 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-sm dark:shadow-none rounded-sm font-bold cursor-pointer select-none">&larr;</button>
        </template>

        <template v-for="page in pages" :key="page">
          <span v-if="page === currentPage" class="w-9 h-9 flex items-center justify-center bg-indigo-600 border border-indigo-700 text-white font-semibold rounded-sm shadow-sm dark:shadow-none select-none">{{ page }}</span>
          <button v-else @click="$emit('changePage', page)" class="w-9 h-9 flex items-center justify-center bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800/80 text-slate-800 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-sm dark:shadow-none rounded-sm font-semibold cursor-pointer select-none">{{ page }}</button>
        </template>

        <template v-if="currentPage === lastPage">
          <span class="w-9 h-9 flex items-center justify-center bg-slate-100 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800/50 text-slate-400 dark:text-slate-600 cursor-not-allowed shadow-sm dark:shadow-none rounded-sm font-bold select-none">&rarr;</span>
        </template>
        <template v-else>
          <button @click="$emit('changePage', currentPage + 1)" class="w-9 h-9 flex items-center justify-center bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800/80 text-slate-800 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-sm dark:shadow-none rounded-sm font-bold cursor-pointer select-none">&rarr;</button>
        </template>
      </div>
    </div>

    <!-- Mobile Layout -->
    <div class="flex sm:hidden items-center justify-between w-full text-xs text-slate-600 dark:text-slate-400 transition-colors duration-200">
      <template v-if="currentPage === 1">
        <span class="w-28 h-9 flex items-center justify-center bg-slate-100 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800/50 text-slate-400 dark:text-slate-600 cursor-not-allowed shadow-sm dark:shadow-none rounded-sm font-bold select-none">&larr;</span>
      </template>
      <template v-else>
        <button @click="$emit('changePage', currentPage - 1)" class="w-28 h-9 flex items-center justify-center bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800/80 text-slate-800 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-sm dark:shadow-none rounded-sm font-bold cursor-pointer select-none">&larr;</button>
      </template>

      <div class="font-semibold text-slate-800 dark:text-slate-200">
        {{ currentPage }} / {{ lastPage }}
      </div>

      <template v-if="currentPage === lastPage">
        <span class="w-28 h-9 flex items-center justify-center bg-slate-100 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800/50 text-slate-400 dark:text-slate-600 cursor-not-allowed shadow-sm dark:shadow-none rounded-sm font-bold select-none">&rarr;</span>
      </template>
      <template v-else>
        <button @click="$emit('changePage', currentPage + 1)" class="w-28 h-9 flex items-center justify-center bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800/80 text-slate-800 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-sm dark:shadow-none rounded-sm font-bold cursor-pointer select-none">&rarr;</button>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  firstItem: { type: Number, required: true },
  lastItem: { type: Number, required: true },
  total: { type: Number, required: true },
  currentPage: { type: Number, required: true },
  lastPage: { type: Number, required: true }
});

defineEmits(['changePage']);

const pages = computed(() => {
  const list = [];
  const start = Math.max(1, props.currentPage - 1);
  const end = Math.min(props.lastPage, props.currentPage + 1);

  let realStart = start;
  let realEnd = end;

  if (props.lastPage <= 3) {
    realStart = 1;
    realEnd = props.lastPage;
  } else {
    if (props.currentPage === 1) {
      realStart = 1;
      realEnd = 3;
    } else if (props.currentPage === props.lastPage) {
      realStart = props.lastPage - 2;
      realEnd = props.lastPage;
    }
  }

  for (let i = realStart; i <= realEnd; i++) {
    list.push(i);
  }
  return list;
});
</script>
