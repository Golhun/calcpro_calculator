<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/ioncube_check.php';
require_once __DIR__ . '/protected_stub.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title><?= APP_NAME ?></title>

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Alpine.js -->
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <!-- math.js -->
  <script src="https://cdn.jsdelivr.net/npm/mathjs@11.11.2/lib/browser/math.js"></script>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>

<body class="bg-slate-950 text-slate-100">

<div class="max-w-6xl mx-auto px-5 py-8" x-data="calcProApp()" x-init="init()">

  <!-- HEADER -->
  <header class="flex items-center justify-between gap-3 mb-7">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-sky-600/20 flex items-center justify-center ring-1 ring-sky-500/30">
        <svg class="w-6 h-6 text-sky-300" viewBox="0 0 24 24" fill="currentColor">
          <path fill-rule="evenodd"
            d="M7.5 2.25A2.25 2.25 0 0 0 5.25 4.5v15A2.25 2.25 0 0 0 7.5 21.75h9A2.25 2.25 0 0 0 18.75 19.5v-15A2.25 2.25 0 0 0 16.5 2.25h-9Z"
            clip-rule="evenodd"/>
        </svg>
      </div>
      <div>
        <h1 class="text-xl font-semibold"><?= APP_NAME ?></h1>
        <p class="text-slate-300 text-sm">Basic, Scientific, Financial, Stats, Graphs</p>
      </div>
    </div>

    <div class="text-xs text-slate-400 text-right">
      <div>Protected module:
        <span class="text-slate-200"><?= htmlspecialchars(protected_signature()) ?></span>
      </div>
      <div>ionCube Loader:
        <span class="text-slate-200" x-text="ioncubeReady ? 'Detected' : 'Not detected'"></span>
      </div>
    </div>
  </header>

  <!-- TABS -->
  <nav class="flex flex-wrap gap-2 mb-6">
    <template x-for="t in tabs" :key="t.key">
      <button
        class="px-4 py-2 rounded-xl ring-1 text-sm"
        :class="activeTab === t.key
          ? 'bg-sky-600/20 ring-sky-400/40 text-sky-200'
          : 'bg-slate-900/40 ring-slate-700/40 text-slate-200 hover:bg-slate-900/70'"
        @click="activeTab = t.key"
        x-text="t.label">
      </button>
    </template>

    <button
      class="ml-auto px-4 py-2 rounded-xl ring-1 ring-slate-700/40 bg-slate-900/40 text-slate-200 hover:bg-slate-900/70 text-sm"
      @click="refreshHistory()">
      Refresh History
    </button>
  </nav>

  <!-- CONTENT GRID -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    <!-- MAIN PANEL -->
    <section class="lg:col-span-2 rounded-2xl bg-slate-900/40 ring-1 ring-slate-700/40 p-5">

      <!-- BASIC / SCIENTIFIC -->
      <div x-show="activeTab === 'basic' || activeTab === 'scientific'" x-cloak>
        <h2 class="text-lg font-semibold mb-3"
            x-text="activeTab === 'basic' ? 'Basic Calculator' : 'Scientific Calculator'"></h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
          <div class="md:col-span-2">
            <label class="text-sm text-slate-300">Expression</label>
            <input x-model="expr"
                   class="w-full mt-1 px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50"
                   placeholder="e.g. (2+3)*5, sin(pi/4)^2">
          </div>

          <div>
            <label class="text-sm text-slate-300">Result</label>
            <div class="mt-1 px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50 min-h-[42px]">
              <span class="text-sky-200 font-semibold break-all" x-text="result"></span>
            </div>

            <div class="mt-3 flex gap-2">
              <button class="px-4 py-2 rounded-xl bg-sky-600/20 ring-1 ring-sky-400/40 text-sm"
                      @click="evaluateExpr()">Evaluate</button>
              <button class="px-4 py-2 rounded-xl bg-slate-900/50 ring-1 ring-slate-700/40 text-sm"
                      @click="clearExpr()">Clear</button>
            </div>

            <div class="mt-2 text-xs text-rose-300" x-show="error" x-text="error"></div>
          </div>
        </div>
      </div>

      <!-- FINANCIAL / STATS / GRAPHS -->
      <!-- (UNCHANGED UI STRUCTURE – trimmed for clarity in explanation) -->
      <!-- Your financial, stats, and graph sections remain exactly as-is -->

    </section>

    <!-- HISTORY -->
    <aside class="rounded-2xl bg-slate-900/40 ring-1 ring-slate-700/40 p-5">
      <h2 class="text-lg font-semibold mb-3">Recent History</h2>

      <div class="space-y-2 max-h-[560px] overflow-auto pr-1">
        <template x-for="h in history" :key="h.id">
          <div class="rounded-xl bg-slate-950/30 ring-1 ring-slate-700/40 p-3">
            <div class="text-xs text-slate-400 flex justify-between">
              <span x-text="h.category"></span>
              <span x-text="h.created_at"></span>
            </div>
            <div class="text-sm text-slate-200 break-words">
              <div class="text-slate-300" x-text="h.input_text"></div>
              <div class="text-sky-200 font-semibold" x-text="h.result_text"></div>
            </div>
          </div>
        </template>
      </div>
    </aside>

  </div>

  <!-- FOOTER -->
  <footer class="mt-6 text-xs text-slate-400">
    <?= APP_NAME ?> v<?= APP_VERSION ?> • Environment: <?= APP_ENV ?>
  </footer>

</div>

<!-- APP SCRIPT -->
<script>
function calcProApp() {
  return {
    csrfToken: '<?= csrf_token() ?>',
    ioncubeReady: <?= ioncube_loader_ready() ? 'true' : 'false' ?>,
    tabs: [
      { key: 'basic', label: 'Basic' },
      { key: 'scientific', label: 'Scientific' },
      { key: 'financial', label: 'Financial' },
      { key: 'stats', label: 'Stats' },
      { key: 'graphs', label: 'Graphs' }
    ],
    activeTab: 'basic',
    expr: '',
    result: '',
    error: '',
    history: [],

    init() {
      this.refreshHistory();
    },

    clearExpr() {
      this.expr = '';
      this.result = '';
      this.error = '';
    },

    evaluateExpr() {
      try {
        this.result = String(math.evaluate(this.expr));
        this.saveHistory('scientific', this.expr, this.result);
      } catch (e) {
        this.error = e.message || 'Invalid expression';
      }
    },

    async saveHistory(category, input, result) {
      const form = new FormData();
      form.append('csrf_token', this.csrfToken);
      form.append('category', category);
      form.append('input_text', input);
      form.append('result_text', result);

      await fetch('api.php?action=history_add', { method: 'POST', body: form });
      this.refreshHistory();
    },

    async refreshHistory() {
      const r = await fetch('api.php?action=history_list');
      const j = await r.json();
      if (j.ok) this.history = j.data;
    }
  }
}
</script>

</body>
</html>
