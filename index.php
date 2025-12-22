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

  <!-- Tailwind CSS (CDN for dev). For production, build locally. -->
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
     @keydown.window.prevent="handleKey($event)">

  <!-- HEADER -->
  <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-sky-600/20 flex items-center justify-center ring-1 ring-sky-500/30">
        <!-- Heroicon: calculator-ish -->
        <svg class="w-6 h-6 text-sky-300" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M7.5 2.25A2.25 2.25 0 0 0 5.25 4.5v15A2.25 2.25 0 0 0 7.5 21.75h9A2.25 2.25 0 0 0 18.75 19.5v-15A2.25 2.25 0 0 0 16.5 2.25h-9ZM7.5 3.75h9a.75.75 0 0 1 .75.75V7.5H6V4.5a.75.75 0 0 1 .75-.75ZM6 9h12v10.5a.75.75 0 0 1-.75.75h-10.5A.75.75 0 0 1 6 19.5V9Z" clip-rule="evenodd"/>
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

    <!-- LEFT: Calculator panel -->
    <section class="lg:col-span-2 rounded-2xl bg-slate-900/40 ring-1 ring-slate-700/40 p-5">

      <!-- DISPLAY (Windows-like) -->
      <div class="rounded-2xl bg-slate-950/40 ring-1 ring-slate-700/50 p-4 mb-4">
        <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
          <div>
            <span class="text-slate-300" x-text="activeTabLabel"></span>
            <span class="ml-2" x-show="memory.hasValue">M</span>
          </div>
          <div class="text-slate-500">Keyboard: 0-9, + - * /, Enter, Backspace, Esc</div>
        </div>

        <div class="text-slate-300 text-sm min-h-[20px] break-all" x-text="display.preview"></div>
        <div class="text-3xl md:text-4xl font-semibold text-sky-200 text-right break-all" x-text="display.main"></div>

        <div class="mt-3 text-xs text-rose-300" x-show="display.error" x-text="display.error"></div>
      </div>

      <!-- MODE CONTENT -->
      <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <!-- LEFT: Mode forms -->
        <div class="space-y-4">

          <!-- BASIC/SCIENTIFIC mode hints -->
          <div x-show="activeTab === 'basic' || activeTab === 'scientific'" x-cloak
               class="rounded-2xl bg-slate-950/30 ring-1 ring-slate-700/40 p-4">
            <h2 class="font-semibold mb-2" x-text="activeTab === 'basic' ? 'Basic' : 'Scientific'"></h2>
            <p class="text-xs text-slate-400">
              Scientific functions supported via math.js:
              <span class="text-slate-200">sin()</span>,
              <span class="text-slate-200">cos()</span>,
              <span class="text-slate-200">tan()</span>,
              <span class="text-slate-200">sqrt()</span>,
              <span class="text-slate-200">log()</span>,
              <span class="text-slate-200">ln()</span>,
              <span class="text-slate-200">pi</span>,
              <span class="text-slate-200">e</span>,
              <span class="text-slate-200">^</span>.
            </p>
          </div>

          <!-- FINANCIAL -->
          <div x-show="activeTab === 'financial'" x-cloak
               class="rounded-2xl bg-slate-950/30 ring-1 ring-slate-700/40 p-4">
            <h2 class="font-semibold mb-3">Financial</h2>

            <div class="grid grid-cols-2 gap-2 mb-2">
              <button class="px-3 py-2 rounded-xl ring-1 text-sm"
                      :class="fin.type==='simple' ? activePill : idlePill"
                      @click="fin.type='simple'">Simple Interest</button>
              <button class="px-3 py-2 rounded-xl ring-1 text-sm"
                      :class="fin.type==='compound' ? activePill : idlePill"
                      @click="fin.type='compound'">Compound Interest</button>
              <button class="px-3 py-2 rounded-xl ring-1 text-sm col-span-2"
                      :class="fin.type==='loan' ? activePill : idlePill"
                      @click="fin.type='loan'">Loan Payment</button>
            </div>

            <div class="space-y-2">
              <input class="w-full px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50"
                     placeholder="Principal"
                     x-model="fin.p"
                     @focus="setActiveField('fin.p')">

              <input class="w-full px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50"
                     placeholder="Rate (%)"
                     x-model="fin.r"
                     @focus="setActiveField('fin.r')">

              <template x-if="fin.type==='loan'">
                <input class="w-full px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50"
                       placeholder="Months"
                       x-model="fin.m"
                       @focus="setActiveField('fin.m')">
              </template>

              <template x-if="fin.type!=='loan'">
                <input class="w-full px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50"
                       placeholder="Time (years)"
                       x-model="fin.t"
                       @focus="setActiveField('fin.t')">
              </template>

              <template x-if="fin.type==='compound'">
                <input class="w-full px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50"
                       placeholder="Compounds/year (e.g. 12)"
                       x-model="fin.n"
                       @focus="setActiveField('fin.n')">
              </template>

              <div class="flex gap-2 pt-1">
                <button class="px-4 py-2 rounded-xl bg-sky-600/20 ring-1 ring-sky-400/40 text-sky-200 hover:bg-sky-600/30 text-sm"
                        @click="runFinancial()">
                  Compute
                </button>
                <button class="px-4 py-2 rounded-xl bg-slate-900/50 ring-1 ring-slate-700/40 text-slate-200 hover:bg-slate-900/70 text-sm"
                        @click="resetFinancial()">
                  Reset
                </button>
              </div>

              <div class="text-sm text-slate-200 mt-2" x-show="fin.out">
                <template x-if="fin.out.monthly_payment">
                  <div>
                    Monthly: <span class="text-sky-200 font-semibold" x-text="fin.out.monthly_payment"></span><br>
                    Total: <span class="text-sky-200 font-semibold" x-text="fin.out.total_payment"></span><br>
                    Interest: <span class="text-sky-200 font-semibold" x-text="fin.out.total_interest"></span>
                  </div>
                </template>

                <template x-if="fin.out.amount">
                  <div>
                    Amount: <span class="text-sky-200 font-semibold" x-text="fin.out.amount"></span><br>
                    Interest: <span class="text-sky-200 font-semibold" x-text="fin.out.interest"></span>
                  </div>
                </template>
              </div>

              <div class="text-xs text-rose-300" x-show="fin.err" x-text="fin.err"></div>
            </div>
          </div>

          <!-- STATS -->
          <div x-show="activeTab === 'stats'" x-cloak
               class="rounded-2xl bg-slate-950/30 ring-1 ring-slate-700/40 p-4">
            <h2 class="font-semibold mb-2">Stats</h2>

            <label class="text-xs text-slate-400">Numbers (comma / space separated)</label>
            <textarea class="w-full mt-1 px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50"
                      rows="3"
                      placeholder="Example: 2, 3, 3, 5, 9, 10"
                      x-model="stats.values"
                      @focus="setActiveField('stats.values')"></textarea>

            <div class="flex gap-2 pt-2">
              <button class="px-4 py-2 rounded-xl bg-sky-600/20 ring-1 ring-sky-400/40 text-sky-200 hover:bg-sky-600/30 text-sm"
                      @click="runStats()">
                Compute
              </button>
              <button class="px-4 py-2 rounded-xl bg-slate-900/50 ring-1 ring-slate-700/40 text-slate-200 hover:bg-slate-900/70 text-sm"
                      @click="resetStats()">
                Reset
              </button>
              <button class="ml-auto px-4 py-2 rounded-xl bg-slate-900/50 ring-1 ring-slate-700/40 text-slate-200 hover:bg-slate-900/70 text-sm"
                      @click="plotStats()"
                      :disabled="!stats.out">
                Plot
              </button>
            </div>

            <div class="text-xs text-rose-300 mt-2" x-show="stats.err" x-text="stats.err"></div>

            <div class="mt-3 text-sm text-slate-200" x-show="stats.out">
              <div class="grid grid-cols-2 gap-2">
                <div>Mean: <span class="text-sky-200 font-semibold" x-text="stats.out.mean"></span></div>
                <div>Median: <span class="text-sky-200 font-semibold" x-text="stats.out.median"></span></div>
                <div>Std Dev: <span class="text-sky-200 font-semibold" x-text="stats.out.std_dev"></span></div>
                <div>Variance: <span class="text-sky-200 font-semibold" x-text="stats.out.variance"></span></div>
                <div>Min: <span class="text-sky-200 font-semibold" x-text="stats.out.min"></span></div>
                <div>Max: <span class="text-sky-200 font-semibold" x-text="stats.out.max"></span></div>
              </div>
            </div>
          </div>

          <!-- GRAPHS (function plotting) -->
          <div x-show="activeTab === 'graphs'" x-cloak
               class="rounded-2xl bg-slate-950/30 ring-1 ring-slate-700/40 p-4">
            <h2 class="font-semibold mb-2">Graphs</h2>
            <input class="w-full px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50 mb-2"
                   placeholder="Title"
                   x-model="graph.title"
                   @focus="setActiveField('graph.title')">

            <input class="w-full px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50 mb-2"
                   placeholder="Expression in x, e.g. sin(x)+x^2"
                   x-model="graph.expression"
                   @focus="setActiveField('graph.expression')">

            <div class="grid grid-cols-3 gap-2 mb-2">
              <input class="px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50"
                     placeholder="xMin"
                     x-model="graph.xMin"
                     @focus="setActiveField('graph.xMin')">
              <input class="px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50"
                     placeholder="xMax"
                     x-model="graph.xMax"
                     @focus="setActiveField('graph.xMax')">
              <input class="px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50"
                     placeholder="step"
                     x-model="graph.step"
                     @focus="setActiveField('graph.step')">
            </div>

            <div class="flex gap-2">
              <button class="px-4 py-2 rounded-xl bg-sky-600/20 ring-1 ring-sky-400/40 text-sky-200 hover:bg-sky-600/30 text-sm"
                      @click="plotFunctionGraph()">
                Plot
              </button>
              <button class="px-4 py-2 rounded-xl bg-slate-900/50 ring-1 ring-slate-700/40 text-slate-200 hover:bg-slate-900/70 text-sm"
                      @click="saveGraph()">
                Save
              </button>
            </div>

            <div class="text-xs text-rose-300 mt-2" x-show="graph.err" x-text="graph.err"></div>
            <div class="text-xs text-sky-200 mt-2" x-show="graph.msg" x-text="graph.msg"></div>
          </div>

        </div>

        <!-- RIGHT: Keypad + Charts -->
        <div class="space-y-4">

          <!-- MEMORY ROW -->
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

          <!-- KEYPAD (shared across all modes) -->
          <div class="grid grid-cols-4 gap-2">
            <template x-for="btn in keypad" :key="btn.key">
              <button
                class="px-3 py-3 rounded-xl ring-1 text-sm font-semibold select-none"
                :class="btn.class"
                @click="press(btn.key)"
                x-text="btn.label">
              </button>
            </template>
          </div>

          <!-- CHART AREA (Stats/Graphs share same canvas slot) -->
          <div class="rounded-2xl bg-slate-950/30 ring-1 ring-slate-700/40 p-4">
            <div class="text-xs text-slate-400 mb-2" x-text="chartTitle"></div>
            <canvas id="mainChart" height="140"></canvas>
          </div>

        </div>
      </div>

    </section>

    <!-- RIGHT: History -->
    <aside class="rounded-2xl bg-slate-900/40 ring-1 ring-slate-700/40 p-5">
      <h2 class="text-lg font-semibold mb-3">Recent History</h2>
      <p class="text-xs text-slate-400 mb-3">Equals (=) logs results here.</p>

      <div class="space-y-2 max-h-[560px] overflow-auto pr-1">
        <template x-for="h in history" :key="h.id">
          <div class="rounded-xl bg-slate-950/30 ring-1 ring-slate-700/40 p-3">
            <div class="text-xs text-slate-400 flex justify-between">
              <span x-text="h.category"></span>
              <span x-text="h.created_at"></span>
            </div>
            <div class="text-sm text-slate-200 break-words mt-1">
              <div class="text-slate-300" x-text="h.input_text"></div>
              <div class="text-sky-200 font-semibold" x-text="h.result_text"></div>
            </div>
            <div class="mt-2">
              <button class="text-xs text-slate-300 hover:text-slate-100"
                      @click="reuseHistory(h)">
                Reuse
              </button>
            </div>
          </div>
        </template>
      </div>
    </aside>

  </div>

  <footer class="mt-6 text-xs text-slate-400">
    <span x-text="meta.appName"></span> v<span x-text="meta.appVersion"></span> • Environment: <span x-text="meta.appEnv"></span>
  </footer>
</div>

<!-- App logic kept in a separate root file to keep index.php short -->
<script src="app.js?v=<?= urlencode(APP_VERSION) ?>"></script>
</body>
</html>
