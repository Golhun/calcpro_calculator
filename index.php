<?php
declare(strict_types=1);

require_once __DIR__ . '/ioncube_check.php';
require_once __DIR__ . '/protected_stub.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Calc Pro</title>

  <!-- Tailwind CSS (CDN for simplicity). For production, build Tailwind locally. -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Alpine.js -->
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <!-- math.js for safe expression parsing and evaluation -->
  <script src="https://cdn.jsdelivr.net/npm/mathjs@11.11.2/lib/browser/math.js"></script>

  <!-- Chart.js for graph plotting -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>

<body class="bg-slate-950 text-slate-100">
  <div class="max-w-6xl mx-auto px-5 py-8" x-data="calcProApp()">

    <header class="flex items-center justify-between gap-3 mb-7">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-sky-600/20 flex items-center justify-center ring-1 ring-sky-500/30">
          <!-- Heroicon: calculator -->
          <svg class="w-6 h-6 text-sky-300" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M7.5 2.25A2.25 2.25 0 0 0 5.25 4.5v15A2.25 2.25 0 0 0 7.5 21.75h9A2.25 2.25 0 0 0 18.75 19.5v-15A2.25 2.25 0 0 0 16.5 2.25h-9ZM7.5 3.75h9a.75.75 0 0 1 .75.75V7.5H6V4.5a.75.75 0 0 1 .75-.75ZM6 9h12v10.5a.75.75 0 0 1-.75.75h-10.5A.75.75 0 0 1 6 19.5V9Zm2.25 2.25a.75.75 0 0 0 0 1.5h1.5a.75.75 0 0 0 0-1.5h-1.5Zm0 3a.75.75 0 0 0 0 1.5h1.5a.75.75 0 0 0 0-1.5h-1.5Zm0 3a.75.75 0 0 0 0 1.5h1.5a.75.75 0 0 0 0-1.5h-1.5Zm4.5-6a.75.75 0 0 0 0 1.5h1.5a.75.75 0 0 0 0-1.5h-1.5Zm0 3a.75.75 0 0 0 0 1.5h1.5a.75.75 0 0 0 0-1.5h-1.5Zm0 3a.75.75 0 0 0 0 1.5h1.5a.75.75 0 0 0 0-1.5h-1.5Zm4.5-6a.75.75 0 0 0 0 1.5h1.5a.75.75 0 0 0 0-1.5h-1.5Zm0 3a.75.75 0 0 0 0 1.5h1.5a.75.75 0 0 0 0-1.5h-1.5Zm0 3a.75.75 0 0 0 0 1.5h1.5a.75.75 0 0 0 0-1.5h-1.5Z" clip-rule="evenodd"/>
          </svg>
        </div>
        <div>
          <h1 class="text-xl font-semibold">Calc Pro</h1>
          <p class="text-slate-300 text-sm">Basic, Scientific, Financial, Stats, Graphs. With MySQL history.</p>
        </div>
      </div>

      <div class="text-xs text-slate-400 text-right">
        <div>Protected module: <span class="text-slate-200"><?= htmlspecialchars(protected_signature()) ?></span></div>
        <div>ionCube Loader: <span class="text-slate-200" x-text="ioncubeReady ? 'Detected' : 'Not detected'"></span></div>
      </div>
    </header>

    <!-- Tabs -->
    <nav class="flex flex-wrap gap-2 mb-6">
      <template x-for="t in tabs" :key="t.key">
        <button
          class="px-4 py-2 rounded-xl ring-1 text-sm"
          :class="activeTab === t.key ? 'bg-sky-600/20 ring-sky-400/40 text-sky-200' : 'bg-slate-900/40 ring-slate-700/40 text-slate-200 hover:bg-slate-900/70'"
          @click="activeTab = t.key"
          x-text="t.label"></button>
      </template>

      <button class="ml-auto px-4 py-2 rounded-xl ring-1 ring-slate-700/40 bg-slate-900/40 text-slate-200 hover:bg-slate-900/70 text-sm"
              @click="refreshHistory()">
        Refresh History
      </button>
    </nav>

    <!-- Content grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
      <!-- Left: active tool -->
      <section class="lg:col-span-2 rounded-2xl bg-slate-900/40 ring-1 ring-slate-700/40 p-5">

        <!-- BASIC + SCIENTIFIC -->
        <div x-show="activeTab === 'basic' || activeTab === 'scientific'" x-cloak>
          <h2 class="text-lg font-semibold mb-3" x-text="activeTab === 'basic' ? 'Basic Calculator' : 'Scientific Calculator'"></h2>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
            <div class="md:col-span-2">
              <label class="text-sm text-slate-300">Expression</label>
              <input x-model="expr"
                     class="w-full mt-1 px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50 focus:ring-4 focus:ring-sky-500/10 focus:border-sky-400 outline-none"
                     placeholder="Example: (2+3)*5, sin(pi/4)^2, log(100,10), sqrt(81)" />
              <p class="text-xs text-slate-400 mt-2">
                Supported via math.js. Try: <span class="text-slate-200">pi</span>, <span class="text-slate-200">e</span>, <span class="text-slate-200">sin(x)</span>, <span class="text-slate-200">cos(x)</span>, <span class="text-slate-200">tan(x)</span>, <span class="text-slate-200">log(a,b)</span>, <span class="text-slate-200">ln(x)</span>, <span class="text-slate-200">sqrt(x)</span>, <span class="text-slate-200">x^2</span>, <span class="text-slate-200">factorial(5)</span>.
              </p>
            </div>

            <div>
              <label class="text-sm text-slate-300">Result</label>
              <div class="mt-1 px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50 min-h-[42px] flex items-center">
                <span class="text-sky-200 font-semibold break-all" x-text="result"></span>
              </div>

              <div class="mt-3 flex gap-2">
                <button class="px-4 py-2 rounded-xl bg-sky-600/20 ring-1 ring-sky-400/40 text-sky-200 hover:bg-sky-600/30 text-sm"
                        @click="evaluateExpr()">
                  Evaluate
                </button>
                <button class="px-4 py-2 rounded-xl bg-slate-900/50 ring-1 ring-slate-700/40 text-slate-200 hover:bg-slate-900/70 text-sm"
                        @click="clearExpr()">
                  Clear
                </button>
              </div>

              <div class="mt-3 text-xs text-slate-400" x-show="error">
                <span class="text-rose-300" x-text="error"></span>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-4 md:grid-cols-6 gap-2">
            <template x-for="b in keypad" :key="b">
              <button class="px-3 py-2 rounded-xl bg-slate-950/30 ring-1 ring-slate-700/40 text-slate-200 hover:bg-slate-950/50 text-sm"
                      @click="append(b)"
                      x-text="b"></button>
            </template>
          </div>
        </div>

        <!-- FINANCIAL -->
        <div x-show="activeTab === 'financial'" x-cloak>
          <h2 class="text-lg font-semibold mb-3">Financial Calculations</h2>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-2xl bg-slate-950/30 ring-1 ring-slate-700/40 p-4">
              <h3 class="font-semibold mb-2">Simple Interest</h3>
              <div class="space-y-2">
                <input x-model.number="fin.simple.p" class="w-full px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50" placeholder="Principal" />
                <input x-model.number="fin.simple.r" class="w-full px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50" placeholder="Rate (%)" />
                <input x-model.number="fin.simple.t" class="w-full px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50" placeholder="Time (years)" />
                <button class="w-full px-4 py-2 rounded-xl bg-sky-600/20 ring-1 ring-sky-400/40 text-sky-200 hover:bg-sky-600/30 text-sm"
                        @click="calcSimpleInterest()">
                  Calculate
                </button>
                <div class="text-sm text-slate-200">
                  Interest: <span class="text-sky-200" x-text="fin.simple.out.interest ?? '-'"></span><br/>
                  Amount: <span class="text-sky-200" x-text="fin.simple.out.amount ?? '-'"></span>
                </div>
              </div>
            </div>

            <div class="rounded-2xl bg-slate-950/30 ring-1 ring-slate-700/40 p-4">
              <h3 class="font-semibold mb-2">Compound Interest</h3>
              <div class="space-y-2">
                <input x-model.number="fin.compound.p" class="w-full px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50" placeholder="Principal" />
                <input x-model.number="fin.compound.r" class="w-full px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50" placeholder="Rate (%)" />
                <input x-model.number="fin.compound.t" class="w-full px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50" placeholder="Time (years)" />
                <input x-model.number="fin.compound.n" class="w-full px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50" placeholder="Compounds/year (e.g. 12)" />
                <button class="w-full px-4 py-2 rounded-xl bg-sky-600/20 ring-1 ring-sky-400/40 text-sky-200 hover:bg-sky-600/30 text-sm"
                        @click="calcCompoundInterest()">
                  Calculate
                </button>
                <div class="text-sm text-slate-200">
                  Interest: <span class="text-sky-200" x-text="fin.compound.out.interest ?? '-'"></span><br/>
                  Amount: <span class="text-sky-200" x-text="fin.compound.out.amount ?? '-'"></span>
                </div>
              </div>
            </div>

            <div class="rounded-2xl bg-slate-950/30 ring-1 ring-slate-700/40 p-4">
              <h3 class="font-semibold mb-2">Loan Payment</h3>
              <div class="space-y-2">
                <input x-model.number="fin.loan.p" class="w-full px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50" placeholder="Principal" />
                <input x-model.number="fin.loan.r" class="w-full px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50" placeholder="Annual rate (%)" />
                <input x-model.number="fin.loan.m" class="w-full px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50" placeholder="Months" />
                <button class="w-full px-4 py-2 rounded-xl bg-sky-600/20 ring-1 ring-sky-400/40 text-sky-200 hover:bg-sky-600/30 text-sm"
                        @click="calcLoanPayment()">
                  Calculate
                </button>
                <div class="text-sm text-slate-200">
                  Monthly: <span class="text-sky-200" x-text="fin.loan.out.monthly_payment ?? '-'"></span><br/>
                  Total: <span class="text-sky-200" x-text="fin.loan.out.total_payment ?? '-'"></span><br/>
                  Interest: <span class="text-sky-200" x-text="fin.loan.out.total_interest ?? '-'"></span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- STATS -->
        <div x-show="activeTab === 'stats'" x-cloak>
          <h2 class="text-lg font-semibold mb-3">Statistical Calculations</h2>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
              <label class="text-sm text-slate-300">Numbers (comma, space, or semicolon separated)</label>
              <textarea x-model="stats.values"
                        class="w-full mt-1 px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50 focus:ring-4 focus:ring-sky-500/10 focus:border-sky-400 outline-none"
                        rows="4"
                        placeholder="Example: 2, 3, 3, 5, 9, 10"></textarea>
              <div class="mt-3 flex gap-2">
                <button class="px-4 py-2 rounded-xl bg-sky-600/20 ring-1 ring-sky-400/40 text-sky-200 hover:bg-sky-600/30 text-sm"
                        @click="calcStats()">
                  Compute
                </button>
                <button class="px-4 py-2 rounded-xl bg-slate-900/50 ring-1 ring-slate-700/40 text-slate-200 hover:bg-slate-900/70 text-sm"
                        @click="stats.values=''; stats.out=null; stats.err=''">
                  Clear
                </button>
              </div>
              <div class="mt-2 text-xs text-rose-300" x-show="stats.err" x-text="stats.err"></div>
            </div>

            <div class="rounded-2xl bg-slate-950/30 ring-1 ring-slate-700/40 p-4">
              <h3 class="font-semibold mb-2">Summary</h3>
              <template x-if="stats.out">
                <div class="text-sm text-slate-200 space-y-1">
                  <div>Count: <span class="text-sky-200" x-text="stats.out.count"></span></div>
                  <div>Sum: <span class="text-sky-200" x-text="stats.out.sum"></span></div>
                  <div>Mean: <span class="text-sky-200" x-text="stats.out.mean"></span></div>
                  <div>Median: <span class="text-sky-200" x-text="stats.out.median"></span></div>
                  <div>Mode: <span class="text-sky-200" x-text="(stats.out.mode && stats.out.mode.length) ? stats.out.mode.join(', ') : 'None'"></span></div>
                  <div>Variance: <span class="text-sky-200" x-text="stats.out.variance"></span></div>
                  <div>Std Dev: <span class="text-sky-200" x-text="stats.out.std_dev"></span></div>
                  <div>Min: <span class="text-sky-200" x-text="stats.out.min"></span></div>
                  <div>Max: <span class="text-sky-200" x-text="stats.out.max"></span></div>
                </div>
              </template>
              <template x-if="!stats.out">
                <div class="text-sm text-slate-400">No results yet.</div>
              </template>
            </div>
          </div>
        </div>

        <!-- GRAPHS -->
        <div x-show="activeTab === 'graphs'" x-cloak>
          <h2 class="text-lg font-semibold mb-3">Graph Drawing</h2>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-1 space-y-2">
              <input x-model="graph.title" class="w-full px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50" placeholder="Graph title" />
              <input x-model="graph.expression" class="w-full px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50" placeholder="Expression in x, e.g. sin(x)+x^2" />

              <div class="grid grid-cols-3 gap-2">
                <input x-model.number="graph.xMin" class="px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50" placeholder="xMin" />
                <input x-model.number="graph.xMax" class="px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50" placeholder="xMax" />
                <input x-model.number="graph.step" class="px-3 py-2 rounded-xl bg-slate-950/40 ring-1 ring-slate-700/50" placeholder="step" />
              </div>

              <button class="w-full px-4 py-2 rounded-xl bg-sky-600/20 ring-1 ring-sky-400/40 text-sky-200 hover:bg-sky-600/30 text-sm"
                      @click="plotGraph()">
                Plot
              </button>

              <button class="w-full px-4 py-2 rounded-xl bg-slate-900/50 ring-1 ring-slate-700/40 text-slate-200 hover:bg-slate-900/70 text-sm"
                      @click="saveGraph()">
                Save Graph
              </button>

              <div class="text-xs text-rose-300" x-show="graph.err" x-text="graph.err"></div>
              <div class="text-xs text-sky-200" x-show="graph.msg" x-text="graph.msg"></div>

              <div class="mt-3">
                <h3 class="font-semibold mb-2">Saved Graphs</h3>
                <div class="space-y-2 max-h-60 overflow-auto pr-1">
                  <template x-for="g in savedGraphs" :key="g.id">
                    <button class="w-full text-left px-3 py-2 rounded-xl bg-slate-950/30 ring-1 ring-slate-700/40 hover:bg-slate-950/45"
                            @click="loadSavedGraph(g)">
                      <div class="text-sm text-slate-200" x-text="g.title"></div>
                      <div class="text-xs text-slate-400" x-text="g.expression"></div>
                    </button>
                  </template>
                </div>
              </div>
            </div>

            <div class="md:col-span-2 rounded-2xl bg-slate-950/30 ring-1 ring-slate-700/40 p-4">
              <canvas id="graphCanvas" height="120"></canvas>
            </div>
          </div>
        </div>

      </section>

      <!-- Right: history -->
      <aside class="rounded-2xl bg-slate-900/40 ring-1 ring-slate-700/40 p-5">
        <h2 class="text-lg font-semibold mb-3">Recent History</h2>
        <p class="text-xs text-slate-400 mb-3">Last 50 calculations saved to MySQL.</p>

        <div class="space-y-2 max-h-[560px] overflow-auto pr-1">
          <template x-for="h in history" :key="h.id">
            <div class="rounded-xl bg-slate-950/30 ring-1 ring-slate-700/40 p-3">
              <div class="flex items-center justify-between text-xs text-slate-400 mb-1">
                <span x-text="h.category"></span>
                <span x-text="h.created_at"></span>
              </div>
              <div class="text-sm text-slate-200 break-words">
                <div class="text-slate-300" x-text="h.input_text"></div>
                <div class="text-sky-200 font-semibold" x-text="h.result_text"></div>
              </div>
              <div class="mt-2">
                <button class="text-xs text-slate-300 hover:text-slate-100"
                        @click="expr = h.input_text; activeTab = (h.category==='financial'?'financial':(h.category==='stats'?'stats':'scientific')); result = h.result_text;">
                  Reuse
                </button>
              </div>
            </div>
          </template>
        </div>
      </aside>
    </div>
  </div>

<script>
function calcProApp() {
  return {
    tabs: [
      { key: 'basic', label: 'Basic' },
      { key: 'scientific', label: 'Scientific' },
      { key: 'financial', label: 'Financial' },
      { key: 'stats', label: 'Stats' },
      { key: 'graphs', label: 'Graphs' },
    ],
    activeTab: 'basic',
    expr: '',
    result: '',
    error: '',
    ioncubeReady: <?= ioncube_loader_ready() ? 'true' : 'false' ?>,

    keypad: ['7','8','9','/','(',')','4','5','6','*','^','sqrt(','1','2','3','-','pi','e','0','.','+','sin(','cos(','tan('],

    history: [],

    fin: {
      simple: { p: 0, r: 0, t: 0, out: {} },
      compound: { p: 0, r: 0, t: 0, n: 12, out: {} },
      loan: { p: 0, r: 0, m: 12, out: {} },
    },

    stats: { values: '', out: null, err: '' },

    graph: {
      title: 'My Graph',
      expression: 'sin(x)',
      xMin: -10,
      xMax: 10,
      step: 0.1,
      err: '',
      msg: ''
    },
    chart: null,
    savedGraphs: [],

    init() {
      this.refreshHistory();
      this.refreshSavedGraphs();
      this.initChart();
    },

    append(v) {
      this.expr += v;
    },

    clearExpr() {
      this.expr = '';
      this.result = '';
      this.error = '';
    },

    evaluateExpr() {
      this.error = '';
      try {
        if (!this.expr.trim()) {
          this.result = '';
          return;
        }
        // Safe evaluation via math.js (no JS eval)
        const val = math.evaluate(this.expr);
        this.result = String(val);

        this.saveHistory('scientific', this.expr, this.result);

      } catch (e) {
        this.error = e?.message ? e.message : 'Invalid expression';
      }
    },

    async saveHistory(category, input_text, result_text) {
      const form = new FormData();
      form.append('category', category);
      form.append('input_text', input_text);
      form.append('result_text', result_text);

      await fetch('api.php?action=history_add', { method: 'POST', body: form });
      this.refreshHistory();
    },

    async refreshHistory() {
      const res = await fetch('api.php?action=history_list');
      const data = await res.json();
      if (data.ok) this.history = data.data;
    },

    async postFinancial(type, payload) {
      const form = new FormData();
      form.append('type', type);
      Object.keys(payload).forEach(k => form.append(k, payload[k]));
      const res = await fetch('api.php?action=financial', { method: 'POST', body: form });
      return res.json();
    },

    async calcSimpleInterest() {
      const r = await this.postFinancial('simple_interest', {
        principal: this.fin.simple.p,
        rate: this.fin.simple.r,
        time: this.fin.simple.t
      });
      if (r.ok) {
        this.fin.simple.out = r.data;
        this.saveHistory('financial', `SimpleInterest P=${this.fin.simple.p}, R=${this.fin.simple.r}%, T=${this.fin.simple.t}`, `Amount=${r.data.amount}, Interest=${r.data.interest}`);
      }
    },

    async calcCompoundInterest() {
      const r = await this.postFinancial('compound_interest', {
        principal: this.fin.compound.p,
        rate: this.fin.compound.r,
        time: this.fin.compound.t,
        compounds: this.fin.compound.n
      });
      if (r.ok) {
        this.fin.compound.out = r.data;
        this.saveHistory('financial', `CompoundInterest P=${this.fin.compound.p}, R=${this.fin.compound.r}%, T=${this.fin.compound.t}, N=${this.fin.compound.n}`, `Amount=${r.data.amount}, Interest=${r.data.interest}`);
      }
    },

    async calcLoanPayment() {
      const r = await this.postFinancial('loan_payment', {
        principal: this.fin.loan.p,
        rate: this.fin.loan.r,
        months: this.fin.loan.m
      });
      if (r.ok) {
        this.fin.loan.out = r.data;
        this.saveHistory('financial', `Loan P=${this.fin.loan.p}, Rate=${this.fin.loan.r}%, Months=${this.fin.loan.m}`, `Monthly=${r.data.monthly_payment}, Total=${r.data.total_payment}`);
      }
    },

    async calcStats() {
      this.stats.err = '';
      const form = new FormData();
      form.append('values', this.stats.values);

      const res = await fetch('api.php?action=stats', { method: 'POST', body: form });
      const data = await res.json();
      if (!data.ok) {
        this.stats.err = data.error || 'Stats error';
        this.stats.out = null;
        return;
      }
      this.stats.out = data.data;
      this.saveHistory('stats', `Stats: ${this.stats.values}`, `Mean=${data.data.mean}, StdDev=${data.data.std_dev}`);
    },

    initChart() {
      const ctx = document.getElementById('graphCanvas');
      this.chart = new Chart(ctx, {
        type: 'line',
        data: { labels: [], datasets: [{ label: 'y', data: [], tension: 0.15 }] },
        options: {
          responsive: true,
          plugins: { legend: { display: true } },
          scales: {
            x: { title: { display: true, text: 'x' } },
            y: { title: { display: true, text: 'y' } }
          }
        }
      });
    },

    plotGraph() {
      this.graph.err = '';
      this.graph.msg = '';

      const expr = this.graph.expression.trim();
      if (!expr) { this.graph.err = 'Expression is required.'; return; }
      if (!(this.graph.xMax > this.graph.xMin)) { this.graph.err = 'xMax must be greater than xMin.'; return; }
      if (!(this.graph.step > 0)) { this.graph.err = 'Step must be > 0.'; return; }

      // Compile expression safely with math.js
      let compiled;
      try {
        compiled = math.compile(expr);
      } catch (e) {
        this.graph.err = e?.message ? e.message : 'Invalid expression';
        return;
      }

      const xs = [];
      const ys = [];
      for (let x = this.graph.xMin; x <= this.graph.xMax; x += this.graph.step) {
        try {
          const y = compiled.evaluate({ x });
          if (typeof y === 'number' && isFinite(y)) {
            xs.push(Number(x.toFixed(6)));
            ys.push(Number(y.toFixed(6)));
          }
        } catch (_) {
          // skip points that error out (e.g. log of negative)
        }
      }

      this.chart.data.labels = xs;
      this.chart.data.datasets[0].data = ys;
      this.chart.data.datasets[0].label = this.graph.title || 'y';
      this.chart.update();

      this.saveHistory('scientific', `Graph y=${expr} on [${this.graph.xMin}, ${this.graph.xMax}] step ${this.graph.step}`, `Points=${ys.length}`);
    },

    async saveGraph() {
      this.graph.err = '';
      this.graph.msg = '';

      const form = new FormData();
      form.append('title', this.graph.title);
      form.append('expression', this.graph.expression);
      form.append('x_min', this.graph.xMin);
      form.append('x_max', this.graph.xMax);
      form.append('step', this.graph.step);

      const res = await fetch('api.php?action=graph_save', { method: 'POST', body: form });
      const data = await res.json();
      if (!data.ok) {
        this.graph.err = data.error || 'Failed to save graph';
        return;
      }
      this.graph.msg = 'Graph saved.';
      this.refreshSavedGraphs();
    },

    async refreshSavedGraphs() {
      const res = await fetch('api.php?action=graph_list');
      const data = await res.json();
      if (data.ok) this.savedGraphs = data.data;
    },

    loadSavedGraph(g) {
      this.activeTab = 'graphs';
      this.graph.title = g.title;
      this.graph.expression = g.expression;
      this.graph.xMin = Number(g.x_min);
      this.graph.xMax = Number(g.x_max);
      this.graph.step = Number(g.step);
      this.plotGraph();
    }
  }
}
</script>
</body>
</html>
