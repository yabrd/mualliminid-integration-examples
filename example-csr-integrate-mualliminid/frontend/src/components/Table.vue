<template>
  <table class="w-full text-left border-collapse">
    <thead>
      <tr
        class="bg-slate-100 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-800 text-xs uppercase text-slate-500 dark:text-slate-400 font-bold"
      >
        <th v-for="(header, i) in headers" :key="header" :class="[i === 0 ? 'min-w-[130px] sm:min-w-[160px]' : 'min-w-[80px]', 'sticky top-0 bg-slate-100 dark:bg-slate-800 backdrop-blur-[1px] border-b border-slate-200 dark:border-slate-800 py-2 px-2.5 sm:py-2.5 sm:px-3.5 z-10']">
          {{ header }}
        </th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40 text-sm">
      <tr
        v-for="(row, idx) in rows"
        :key="idx"
        class="bg-white odd:bg-white even:bg-slate-50/40 dark:bg-slate-900 dark:odd:bg-slate-900 dark:even:bg-slate-950/20 hover:bg-indigo-50/30 dark:hover:bg-indigo-950/10 transition-colors duration-150"
      >
        <td v-for="col in columns" :key="col.key" :class="[col.type === 'title' ? 'min-w-[130px] sm:min-w-[160px]' : 'min-w-[80px]', 'py-2 px-2.5 sm:py-2.5 sm:px-3.5']">
          <template v-if="col.type === 'title'">
            <span
              class="font-medium text-slate-800 dark:text-slate-200 text-xs sm:text-[15px]"
              >{{ row[col.key] ? row[col.key] : "-" }}</span
            >
          </template>
          <template v-else-if="col.type === 'badge'">
            <span
              class="bg-slate-100 dark:bg-slate-800 px-2.5 py-0.5 text-xs sm:text-[13px] text-slate-600 dark:text-slate-300 font-medium rounded"
            >
              {{ row[col.key] ? row[col.key] : "-" }}
            </span>
          </template>
          <template v-else-if="col.type === 'code'">
            <span
              class="font-mono text-xs sm:text-[14px] text-slate-600 dark:text-slate-300"
              >{{ row[col.key] ? row[col.key] : "-" }}</span
            >
          </template>
          <template v-else>
            <span class="text-slate-600 dark:text-slate-300 text-xs sm:text-sm">{{
              row[col.key] ? row[col.key] : "-"
            }}</span>
          </template>
        </td>
      </tr>
      <tr v-if="rows.length === 0">
        <td
          :colspan="headers.length"
          class="py-12 text-center text-slate-400 dark:text-slate-500"
        >
          Belum ada data. Silakan lakukan sinkronisasi terlebih dahulu.
        </td>
      </tr>
    </tbody>
  </table>
</template>

<script setup>
defineProps({
  headers: {
    type: Array,
    required: true,
  },
  rows: {
    type: Array,
    required: true,
  },
  columns: {
    type: Array,
    required: true,
  },
});
</script>
