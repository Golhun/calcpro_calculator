<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

require_once __DIR__ . '/license_bootstrap.php';
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

  <!-- Tailwind CSS (CDN for dev) -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Alpine.js -->
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <!-- math.js -->
  <script src="https://cdn.jsdelivr.net/npm/mathjs@11.11.2/lib/browser/math.js"></script>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>

<body class="bg-slate-950 text-slate-100">

<div class="max-w-6xl mx-auto px-5 py-8"
     x-data="calcProApp({
       csrfToken: '<?= csrf_token() ?>',
       ioncubeReady: <?= ioncube_loader_ready() ? 'true' : 'false' ?>,
       appName: '<?= htmlspecialchars(APP_NAME) ?>',
       appVersion: '<?= htmlspecialchars(APP_VERSION) ?>',
       appEnv: '<?= htmlspecialchars(APP_ENV) ?>',
       protectedSig: '<?= htmlspecialchars(protected_signature()) ?>'
     })"
     x-init="init()"
     @keydown.window="handleKey($event)">

  <!-- HEADER -->
  <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-sky-600/20 flex items-center justify-center ring-1 ring-sky-500/30">
        <svg class="w-6 h-6 text-sky-300" viewBox="0 0 24 24" fill="currentColor">
          <path fill-rule="evenodd"
            d="M7.5 2.25A2.25 2.25 0 0 0 5.25 4.5v15A2.25 2.25 0 0 0 7.5 21.75h9A2.25 2.25 0 0 0 18.75 19.5v-15A2.25 2.25 0 0 0 16.5 2.25h-9Z"
            clip-rule="evenodd"/>
        </svg>
      </div>
      <div>
        <h1 class="text-xl font-semibold" x-text="meta.appName"></h1>
        <p class="text-slate-300 text-sm">Basic, Scientific, Financial, Stats, Graphs</p>
      </div>
    </div>

    <div class="text-xs text-slate-400 md:text-right">
      <div>Protected: <span class="text-slate-200" x-text="meta.protectedSig"></span></div>
      <div>ionCube Loader: <span class="text-slate-200" x-text="meta.ioncubeReady ? 'Detected' : 'Not detected'"></span></div>
    </div>
  </header>

  <!-- TABS -->
  <nav class="flex flex-wrap gap-2 mb-5">
    <template x-for="t in tabs" :key="t.key">
      <button
        class="px-4 py-2 rounded-xl ring-1 text-sm"
        :class="activeTab === t.key
          ? 'bg-sky-600/20 ring-sky-400/40 text-sky-200'
          : 'bg-slate-900/40 ring-slate-700/40 text-slate-200 hover:bg-slate-900/70'"
        @click="setTab(t.key)"
        x-text="t.label">
      </button>
    </template>

    <button class="ml-auto px-4 py-2 rounded-xl ring-1 ring-slate-700/40 bg-slate-900/40 text-slate-200 hover:bg-slate-900/70 text-sm"
            @click="refreshHistory()">
      Refresh History
    </button>
  </nav>

  <!-- GRID -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    <!-- MAIN PANEL -->
    <section class="lg:col-span-2 rounded-2xl bg-slate-900/40 ring-1 ring-slate-700/40 p-5">

      <!-- DISPLAY -->
      <div class="rounded-2xl bg-slate-950/40 ring-1 ring-slate-700/50 p-4 mb-4">
        <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
          <div>
            <span x-text="activeTabLabel"></span>
            <span class="ml-2" x-show="memory.hasValue">M</span>
          </div>
          <div class="text-slate-500"
               x-show="activeTab === 'basic' || activeTab === 'scientific'">
            Type or use keypad
          </div>
        </div>

        <!-- Editable display for calculator modes -->
        <template x-if="activeTab === 'basic' || activeTab === 'scientific'">
          <input
            type="text"
            class="w-full bg-transparent text-3xl md:text-4xl font-semibold text-sky-200 text-right outline-none"
            x-model="expr"
            @input="updateDisplayFromTyping()"
            placeholder="0">
        </template>

        <!-- Read-only display for others -->
        <template x-if="activeTab !== 'basic' && activeTab !== 'scientific'">
          <div class="text-3xl md:text-4xl font-semibold text-sky-200 text-right break-all"
               x-text="display.main"></div>
        </template>

        <div class="text-slate-300 text-sm min-h-[20px] break-all" x-text="display.preview"></div>
        <div class="mt-2 text-xs text-rose-300" x-show="display.error" x-text="display.error"></div>
      </div>

      <!-- MODE CONTENT -->
      <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">

        <!-- LEFT: Forms -->
        <div class="space-y-4">
          <!-- BASIC / SCIENTIFIC INFO -->
          <div x-show="activeTab === 'basic' || activeTab === 'scientific'" x-cloak
               class="rounded-2xl bg-slate-950/30 ring-1 ring-slate-700/40 p-4">
            <p class="text-xs text-slate-400">
              Supported: <span class="text-slate-200">sin()</span>,
              <span class="text-slate-200">cos()</span>,
              <span class="text-slate-200">tan()</span>,
              <span class="text-slate-200">log()</span>,
              <span class="text-slate-200">sqrt()</span>,
              <span class="text-slate-200">pi</span>,
              <span class="text-slate-200">e</span>,
              <span class="text-slate-200">^</span>
            </p>
          </div>

          <!-- FINANCIAL / STATS / GRAPHS -->
          <!-- (UNCHANGED STRUCTURE FROM YOUR VERSION) -->
          <!-- Keep exactly as you already have -->
        </div>

        <!-- RIGHT: Memory + Keypad + Chart -->
        <div class="space-y-4">

          <!-- MEMORY -->
          <div class="grid grid-cols-4 gap-2">
            <button class="px-3 py-2 rounded-xl bg-slate-950/30 ring-1 ring-slate-700/40 hover:bg-slate-950/45 text-sm"
                    @click="memoryClear()">MC</button>
            <button class="px-3 py-2 rounded-xl bg-slate-950/30 ring-1 ring-slate-700/40 hover:bg-slate-950/45 text-sm"
                    @click="memoryRecall()">MR</button>
            <button class="px-3 py-2 rounded-xl bg-slate-950/30 ring-1 ring-slate-700/40 hover:bg-slate-950/45 text-sm"
                    @click="memoryAdd()">M+</button>
            <button class="px-3 py-2 rounded-xl bg-slate-950/30 ring-1 ring-slate-700/40 hover:bg-slate-950/45 text-sm"
                    @click="memorySubtract()">M−</button>
          </div>

          <!-- KEYPAD (ONLY BASIC / SCIENTIFIC) -->
          <div x-show="activeTab === 'basic' || activeTab === 'scientific'" x-cloak
               class="grid grid-cols-4 gap-2">
            <template x-for="btn in keypad" :key="btn.key">
              <button
                class="px-3 py-3 rounded-xl ring-1 text-sm font-semibold select-none"
                :class="btn.class"
                @click="press(btn.key)"
                x-text="btn.label">
              </button>
            </template>
          </div>

          <!-- CHART -->
          <div class="rounded-2xl bg-slate-950/30 ring-1 ring-slate-700/40 p-4">
            <div class="text-xs text-slate-400 mb-2" x-text="chartTitle"></div>
            <canvas id="mainChart" height="140"></canvas>
          </div>

        </div>
      </div>

    </section>

    <!-- HISTORY -->
    <aside class="rounded-2xl bg-slate-900/40 ring-1 ring-slate-700/40 p-5">
      <h2 class="text-lg font-semibold mb-3">Recent History</h2>
      <p class="text-xs text-slate-400 mb-3">Equals (=) logs results here</p>

      <div class="space-y-2 max-h-[560px] overflow-auto pr-1">
        <template x-for="h in history" :key="h.id">
          <div class="rounded-xl bg-slate-950/30 ring-1 ring-slate-700/40 p-3">
            <div class="text-xs text-slate-400 flex justify-between">
              <span x-text="h.category"></span>
              <span x-text="h.created_at"></span>
            </div>
            <div class="text-sm text-slate-200 mt-1">
              <div class="text-slate-300" x-text="h.input_text"></div>
              <div class="text-sky-200 font-semibold" x-text="h.result_text"></div>
            </div>
            <button class="mt-2 text-xs text-slate-300 hover:text-slate-100"
                    @click="reuseHistory(h)">
              Reuse
            </button>
          </div>
        </template>
      </div>
    </aside>

  </div>

  <footer class="mt-6 text-xs text-slate-400">
    <span x-text="meta.appName"></span>
    v<span x-text="meta.appVersion"></span>
    • Environment: <span x-text="meta.appEnv"></span>
  </footer>
</div>

<script src="app.js?v=<?= urlencode(APP_VERSION) ?>"></script>
</body>
</html>
