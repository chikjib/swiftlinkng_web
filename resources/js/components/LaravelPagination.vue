<template>
  <nav
    v-if="lastPage > 1"
    class="swift-laravel-pagination"
    :class="`swift-pagination--${align}`"
    aria-label="Pagination"
  >
    <button
      type="button"
      :disabled="currentPage <= 1"
      aria-label="Previous page"
      @click="selectPage(currentPage - 1)"
    >
      <span aria-hidden="true">&lsaquo;</span>
    </button>

    <button
      v-for="page in visiblePages"
      :key="page"
      type="button"
      :class="{ active: page === currentPage }"
      :aria-current="page === currentPage ? 'page' : null"
      @click="selectPage(page)"
    >
      {{ page }}
    </button>

    <button
      type="button"
      :disabled="currentPage >= lastPage"
      aria-label="Next page"
      @click="selectPage(currentPage + 1)"
    >
      <span aria-hidden="true">&rsaquo;</span>
    </button>
  </nav>
</template>

<script>
export default {
  name: "LaravelPagination",
  props: {
    data: {
      type: Object,
      default: () => ({}),
    },
    limit: {
      type: Number,
      default: 5,
    },
    align: {
      type: String,
      default: "left",
    },
  },
  emits: ["pagination-change-page"],
  computed: {
    currentPage() {
      return Math.max(1, Number(this.data.current_page || this.data.meta?.current_page || 1));
    },
    lastPage() {
      return Math.max(1, Number(this.data.last_page || this.data.meta?.last_page || 1));
    },
    visiblePages() {
      const size = Math.max(1, Number(this.limit) || 5);
      let start = Math.max(1, this.currentPage - Math.floor(size / 2));
      let end = Math.min(this.lastPage, start + size - 1);
      start = Math.max(1, end - size + 1);
      const pages = [];
      for (let page = start; page <= end; page += 1) pages.push(page);
      return pages;
    },
  },
  methods: {
    selectPage(page) {
      const target = Number(page);
      if (target < 1 || target > this.lastPage || target === this.currentPage) return;
      this.$emit("pagination-change-page", target);
    },
  },
};
</script>

<style scoped>
.swift-laravel-pagination {
  display: flex;
  align-items: center;
  gap: 5px;
  margin: 18px 0;
}
.swift-pagination--center { justify-content: center; }
.swift-pagination--right { justify-content: flex-end; }
.swift-laravel-pagination button {
  min-width: 38px;
  height: 38px;
  padding: 0 9px;
  border: 1px solid #dfe0e5;
  border-radius: 10px;
  background: #fff;
  color: #4f5159;
  font-weight: 750;
}
.swift-laravel-pagination button.active {
  border-color: #dc0714;
  background: #dc0714;
  color: #fff;
}
.swift-laravel-pagination button:disabled {
  cursor: not-allowed;
  opacity: .4;
}
@media (prefers-color-scheme: dark) {
  .swift-laravel-pagination button {
    border-color: #3c3f47;
    background: #1c1e23;
    color: #f4f4f6;
  }
}
</style>
