/* app.js
 * Calc Pro frontend logic (Alpine).
 * - Windows-style calculator behavior
 * - Shared keypad for all modes
 * - Keyboard support
 * - Memory functions (MC, MR, M+, M-)
 * - Equals logs to history
 * - Stats plot chart support
 */

function calcProApp(meta) {
	return {
		meta,

		// Tabs
		tabs: [
			{ key: "basic", label: "Basic" },
			{ key: "scientific", label: "Scientific" },
			{ key: "financial", label: "Financial" },
			{ key: "stats", label: "Stats" },
			{ key: "graphs", label: "Graphs" },
		],
		activeTab: "basic",

		// Display
		display: {
			main: "0",
			preview: "",
			error: "",
		},

		// Expression state
		expr: "", // what we evaluate
		lastWasEquals: false, // Windows-like chaining
		activeField: null, // for Financial/Stats/Graphs input typing

		// Memory state
		memory: { value: 0, hasValue: false },

		// History
		history: [],

		// Financial
		fin: {
			type: "simple",
			p: "",
			r: "",
			t: "",
			n: "12",
			m: "12",
			out: null,
			err: "",
		},

		// Stats
		stats: {
			values: "",
			out: null,
			err: "",
		},

		// Graph
		graph: {
			title: "My Graph",
			expression: "sin(x)",
			xMin: "-10",
			xMax: "10",
			step: "0.1",
			err: "",
			msg: "",
		},

		// UI tokens
		activePill: "bg-sky-600/20 ring-sky-400/40 text-sky-200",
		idlePill:
			"bg-slate-950/30 ring-slate-700/40 text-slate-200 hover:bg-slate-950/45",

		// Chart
		chart: null,
		chartTitle: "Chart",

		// Shared keypad: Windows-like mix
		keypad: [
			// Row 1
			{
				key: "C",
				label: "C",
				class:
					"bg-slate-950/30 ring-slate-700/40 text-slate-200 hover:bg-slate-950/45",
			},
			{
				key: "CE",
				label: "CE",
				class:
					"bg-slate-950/30 ring-slate-700/40 text-slate-200 hover:bg-slate-950/45",
			},
			{
				key: "⌫",
				label: "⌫",
				class:
					"bg-slate-950/30 ring-slate-700/40 text-slate-200 hover:bg-slate-950/45",
			},
			{
				key: "/",
				label: "÷",
				class:
					"bg-slate-900/50 ring-slate-700/40 text-slate-200 hover:bg-slate-900/70",
			},

			// Row 2
			{
				key: "7",
				label: "7",
				class:
					"bg-slate-950/40 ring-slate-700/40 text-slate-100 hover:bg-slate-950/55",
			},
			{
				key: "8",
				label: "8",
				class:
					"bg-slate-950/40 ring-slate-700/40 text-slate-100 hover:bg-slate-950/55",
			},
			{
				key: "9",
				label: "9",
				class:
					"bg-slate-950/40 ring-slate-700/40 text-slate-100 hover:bg-slate-950/55",
			},
			{
				key: "*",
				label: "×",
				class:
					"bg-slate-900/50 ring-slate-700/40 text-slate-200 hover:bg-slate-900/70",
			},

			// Row 3
			{
				key: "4",
				label: "4",
				class:
					"bg-slate-950/40 ring-slate-700/40 text-slate-100 hover:bg-slate-950/55",
			},
			{
				key: "5",
				label: "5",
				class:
					"bg-slate-950/40 ring-slate-700/40 text-slate-100 hover:bg-slate-950/55",
			},
			{
				key: "6",
				label: "6",
				class:
					"bg-slate-950/40 ring-slate-700/40 text-slate-100 hover:bg-slate-950/55",
			},
			{
				key: "-",
				label: "−",
				class:
					"bg-slate-900/50 ring-slate-700/40 text-slate-200 hover:bg-slate-900/70",
			},

			// Row 4
			{
				key: "1",
				label: "1",
				class:
					"bg-slate-950/40 ring-slate-700/40 text-slate-100 hover:bg-slate-950/55",
			},
			{
				key: "2",
				label: "2",
				class:
					"bg-slate-950/40 ring-slate-700/40 text-slate-100 hover:bg-slate-950/55",
			},
			{
				key: "3",
				label: "3",
				class:
					"bg-slate-950/40 ring-slate-700/40 text-slate-100 hover:bg-slate-950/55",
			},
			{
				key: "+",
				label: "+",
				class:
					"bg-slate-900/50 ring-slate-700/40 text-slate-200 hover:bg-slate-900/70",
			},

			// Row 5
			{
				key: "±",
				label: "±",
				class:
					"bg-slate-950/30 ring-slate-700/40 text-slate-200 hover:bg-slate-950/45",
			},
			{
				key: "0",
				label: "0",
				class:
					"bg-slate-950/40 ring-slate-700/40 text-slate-100 hover:bg-slate-950/55",
			},
			{
				key: ".",
				label: ".",
				class:
					"bg-slate-950/40 ring-slate-700/40 text-slate-100 hover:bg-slate-950/55",
			},
			{
				key: "=",
				label: "=",
				class: "bg-sky-600/20 ring-sky-400/40 text-sky-200 hover:bg-sky-600/30",
			},
		],

		get activeTabLabel() {
			const t = this.tabs.find((x) => x.key === this.activeTab);
			return t ? t.label : "";
		},

		init() {
			this.refreshHistory();
			this.initChart();
			this.setTab("basic");
		},

		setTab(key) {
			this.activeTab = key;
			this.display.error = "";
			// When switching to non-basic/scientific, keypad types into activeField
			if (key === "financial") this.setActiveField("fin.p");
			if (key === "stats") this.setActiveField("stats.values");
			if (key === "graphs") this.setActiveField("graph.expression");
			if (key === "basic" || key === "scientific") this.activeField = null;
		},

		setActiveField(path) {
			this.activeField = path;
		},

		// ---------- Keyboard support ----------
		handleKey(e) {
			// Avoid hijacking typing inside inputs and textarea
			const tag =
				e.target && e.target.tagName ? e.target.tagName.toLowerCase() : "";
			const isTypingField = ["input", "textarea", "select"].includes(tag);
			// Still allow Enter to trigger compute when on calculator
			if (isTypingField && e.key !== "Enter") return;

			const k = e.key;

			if (k >= "0" && k <= "9") return this.press(k);
			if (k === ".") return this.press(".");
			if (k === "+" || k === "-" || k === "*" || k === "/")
				return this.press(k);
			if (k === "Enter" || k === "=") return this.press("=");
			if (k === "Backspace") return this.press("⌫");
			if (k === "Escape") return this.press("C");

			// Optional: parentheses (useful for scientific)
			if (k === "(" || k === ")") return this.typeIntoTarget(k);
		},

		// ---------- Keypad press handler ----------
		press(key) {
			this.display.error = "";

			// If tab is Financial/Stats/Graphs, keypad types into active field except operator actions and equals.
			if (
				this.activeTab === "financial" ||
				this.activeTab === "stats" ||
				this.activeTab === "graphs"
			) {
				return this.pressNonCalcMode(key);
			}

			// Basic/Scientific mode
			switch (key) {
				case "C":
					return this.clearAll();
				case "CE":
					return this.clearEntry();
				case "⌫":
					return this.backspace();
				case "±":
					return this.toggleSign();
				case "=":
					return this.equals();
				default:
					return this.appendToExpr(key);
			}
		},

		pressNonCalcMode(key) {
			// In non-calc modes, allow:
			// - digits, dot, backspace to edit activeField
			// - '=' triggers the tab's compute action
			// - C clears the activeField (and tab output where relevant)
			if (key === "=") {
				if (this.activeTab === "financial") return this.runFinancial();
				if (this.activeTab === "stats") return this.runStats();
				if (this.activeTab === "graphs") return this.plotFunctionGraph();
				return;
			}

			if (key === "C") {
				if (this.activeTab === "financial") return this.resetFinancial();
				if (this.activeTab === "stats") return this.resetStats();
				if (this.activeTab === "graphs") {
					this.graph.expression = "";
					this.graph.err = "";
					this.graph.msg = "";
					this.setActiveField("graph.expression");
					return;
				}
			}

			if (key === "⌫") return this.backspaceField();
			if (key === "CE") return this.backspaceField();

			// Only allow sensible characters for numeric fields and text fields
			const allowed = "0123456789.";
			const isNumeric = allowed.includes(key);

			// Graph expression and stats values allow broader typing:
			const isWideField =
				this.activeField === "graph.expression" ||
				this.activeField === "stats.values" ||
				this.activeField === "graph.title";

			if (isNumeric || isWideField) {
				return this.typeIntoTarget(key);
			}
		},

		typeIntoTarget(ch) {
			if (!this.activeField) return;

			// Resolve path like "fin.p"
			const [root, prop] = this.activeField.split(".");
			if (!prop || !this[root]) return;

			this[root][prop] = String(this[root][prop] ?? "") + ch;
		},

		backspaceField() {
			if (!this.activeField) return;
			const [root, prop] = this.activeField.split(".");
			if (!prop || !this[root]) return;
			const v = String(this[root][prop] ?? "");
			this[root][prop] = v.length ? v.slice(0, -1) : "";
		},

		// ---------- Calculator behavior ----------
		updateDisplay() {
			this.display.main = this.expr.trim() ? this.expr : "0";
		},

		clearAll() {
			this.expr = "";
			this.display.preview = "";
			this.display.main = "0";
			this.display.error = "";
			this.lastWasEquals = false;
		},

		clearEntry() {
			// Windows-like CE: clear current entry. For our simplified model, clears everything after last operator.
			const s = this.expr;
			const idx = Math.max(
				s.lastIndexOf("+"),
				s.lastIndexOf("-"),
				s.lastIndexOf("*"),
				s.lastIndexOf("/")
			);
			if (idx === -1) {
				this.expr = "";
			} else {
				this.expr = s.slice(0, idx + 1);
			}
			this.updateDisplay();
		},

		backspace() {
			if (!this.expr) return;
			this.expr = this.expr.slice(0, -1);
			this.updateDisplay();
		},

		toggleSign() {
			// Toggle sign of last number segment
			const s = this.expr.trim();
			if (!s) {
				this.expr = "-";
				return this.updateDisplay();
			}

			// Find last number token
			const m = s.match(/(.*?)(-?\d+(\.\d+)?)\s*$/);
			if (!m) return;

			const head = m[1];
			const num = m[2];
			const toggled = num.startsWith("-") ? num.slice(1) : "-" + num;
			this.expr = head + toggled;
			this.updateDisplay();
		},

		appendToExpr(ch) {
			// If last action was equals, starting with a number clears expression; starting with operator continues.
			if (this.lastWasEquals) {
				if ("0123456789.".includes(ch)) {
					this.expr = "";
					this.display.preview = "";
				}
				this.lastWasEquals = false;
			}

			// Prevent invalid sequences
			if (this.expr === "" && ["+", "*", "/", "."].includes(ch)) {
				if (ch === ".") {
					this.expr = "0.";
					return this.updateDisplay();
				}
				return;
			}

			// Map UI operators to math operators
			if (ch === "×") ch = "*";
			if (ch === "÷") ch = "/";

			// Avoid double operators
			const last = this.expr.slice(-1);
			if (
				["+", "-", "*", "/"].includes(last) &&
				["+", "-", "*", "/"].includes(ch)
			) {
				this.expr = this.expr.slice(0, -1) + ch;
			} else {
				this.expr += ch;
			}

			this.updateDisplay();
		},

		equals() {
			if (!this.expr.trim()) return;

			try {
				const val = math.evaluate(this.expr);
				const result = typeof val === "number" && isFinite(val) ? val : val;

				this.display.preview = this.expr + " =";
				this.display.main = String(result);
				this.lastWasEquals = true;

				// Log equals to history
				this.saveHistory(
					this.activeTab === "basic" ? "basic" : "scientific",
					this.expr,
					String(result)
				);

				// Prepare for chaining: set expr to result
				this.expr = String(result);
			} catch (e) {
				this.display.error = e?.message ? e.message : "Invalid expression";
			}
		},

		// ---------- Memory functions ----------
		memoryClear() {
			this.memory.value = 0;
			this.memory.hasValue = false;
		},

		memoryRecall() {
			const v = String(this.memory.value ?? 0);

			// In calculator mode, append to expression
			if (this.activeTab === "basic" || this.activeTab === "scientific") {
				if (this.lastWasEquals) {
					this.expr = "";
					this.lastWasEquals = false;
				}
				this.expr += v;
				this.updateDisplay();
				return;
			}

			// In other modes, type into active field
			if (this.activeField) {
				const [root, prop] = this.activeField.split(".");
				if (this[root] && prop) this[root][prop] = v;
			}
		},

		memoryAdd() {
			const x = this.currentNumericValue();
			if (x === null) return;
			this.memory.value += x;
			this.memory.hasValue = true;
		},

		memorySubtract() {
			const x = this.currentNumericValue();
			if (x === null) return;
			this.memory.value -= x;
			this.memory.hasValue = true;
		},

		currentNumericValue() {
			// Prefer display.main if it's numeric
			const v = Number(this.display.main);
			if (!Number.isFinite(v)) return null;
			return v;
		},

		// ---------- API calls ----------
		async saveHistory(category, input, result) {
			const form = new FormData();
			form.append("csrf_token", this.meta.csrfToken);
			form.append("category", category);
			form.append("input_text", input);
			form.append("result_text", result);

			try {
				await fetch("api.php?action=history_add", {
					method: "POST",
					body: form,
				});
			} catch (_) {
				// Do not block UI for logging issues
			}
			this.refreshHistory();
		},

		async refreshHistory() {
			try {
				const r = await fetch("api.php?action=history_list");
				const j = await r.json();
				if (j.ok) this.history = j.data;
			} catch (_) {}
		},

		reuseHistory(h) {
			// Reuse in calculator mode
			this.setTab(
				h.category === "financial"
					? "financial"
					: h.category === "stats"
					? "stats"
					: "scientific"
			);
			if (h.category === "basic" || h.category === "scientific") {
				this.expr = String(h.input_text || "");
				this.updateDisplay();
			}
		},

		// ---------- Financial ----------
		async runFinancial() {
			this.fin.err = "";
			this.fin.out = null;

			const p = Number(this.fin.p);
			const r = Number(this.fin.r);

			if (!Number.isFinite(p) || p < 0)
				return (this.fin.err = "Principal must be a valid number.");
			if (!Number.isFinite(r))
				return (this.fin.err = "Rate must be a valid number.");

			const form = new FormData();
			form.append("csrf_token", this.meta.csrfToken);

			try {
				if (this.fin.type === "simple") {
					const t = Number(this.fin.t);
					if (!Number.isFinite(t) || t < 0)
						return (this.fin.err = "Time must be a valid number.");
					form.append("type", "simple_interest");
					form.append("principal", p);
					form.append("rate", r);
					form.append("time", t);
				}

				if (this.fin.type === "compound") {
					const t = Number(this.fin.t);
					const n = Number(this.fin.n);
					if (!Number.isFinite(t) || t < 0)
						return (this.fin.err = "Time must be a valid number.");
					if (!Number.isFinite(n) || n <= 0)
						return (this.fin.err = "Compounds/year must be > 0.");
					form.append("type", "compound_interest");
					form.append("principal", p);
					form.append("rate", r);
					form.append("time", t);
					form.append("compounds", Math.floor(n));
				}

				if (this.fin.type === "loan") {
					const m = Number(this.fin.m);
					if (!Number.isFinite(m) || m <= 0)
						return (this.fin.err = "Months must be > 0.");
					form.append("type", "loan_payment");
					form.append("principal", p);
					form.append("rate", r);
					form.append("months", Math.floor(m));
				}

				const res = await fetch("api.php?action=financial", {
					method: "POST",
					body: form,
				});
				const data = await res.json();
				if (!data.ok) return (this.fin.err = data.error || "Financial error");
				this.fin.out = data.data;

				// Log compute as history as well (tied to "=" concept in this mode)
				const input =
					this.fin.type === "loan"
						? `Loan P=${p}, Rate=${r}%, Months=${this.fin.m}`
						: this.fin.type === "compound"
						? `Compound P=${p}, Rate=${r}%, Time=${this.fin.t}y, N=${this.fin.n}`
						: `Simple P=${p}, Rate=${r}%, Time=${this.fin.t}y`;

				const result = this.fin.out.monthly_payment
					? `Monthly=${this.fin.out.monthly_payment}, Total=${this.fin.out.total_payment}`
					: `Amount=${this.fin.out.amount}, Interest=${this.fin.out.interest}`;

				this.saveHistory("financial", input, result);

				this.display.preview = input + " =";
				this.display.main = result;
			} catch (e) {
				this.fin.err = "Failed to compute.";
			}
		},

		resetFinancial() {
			this.fin.p = "";
			this.fin.r = "";
			this.fin.t = "";
			this.fin.n = "12";
			this.fin.m = "12";
			this.fin.out = null;
			this.fin.err = "";
			this.display.preview = "";
			this.display.main = "0";
			this.setActiveField("fin.p");
		},

		// ---------- Stats ----------
		async runStats() {
			this.stats.err = "";
			this.stats.out = null;

			const v = String(this.stats.values || "").trim();
			if (!v) return (this.stats.err = "Please enter some numbers.");

			const form = new FormData();
			form.append("csrf_token", this.meta.csrfToken);
			form.append("values", v);

			try {
				const res = await fetch("api.php?action=stats", {
					method: "POST",
					body: form,
				});
				const data = await res.json();
				if (!data.ok) return (this.stats.err = data.error || "Stats error");
				this.stats.out = data.data;

				const input = `Stats: ${v}`;
				const result = `Mean=${data.data.mean}, StdDev=${data.data.std_dev}`;
				this.saveHistory("stats", input, result);

				this.display.preview = input + " =";
				this.display.main = result;

				// Plot automatically for convenience
				this.plotStats();
			} catch (_) {
				this.stats.err = "Failed to compute stats.";
			}
		},

		resetStats() {
			this.stats.values = "";
			this.stats.out = null;
			this.stats.err = "";
			this.display.preview = "";
			this.display.main = "0";
			this.chartTitle = "Chart";
			this.clearChart();
			this.setActiveField("stats.values");
		},

		// Stats plot: line plot of sorted values
		plotStats() {
			if (!this.stats.out) return;

			const nums = this.parseNumbers(this.stats.values);
			if (!nums.length) return;

			nums.sort((a, b) => a - b);

			const labels = nums.map((_, i) => i + 1);
			const points = nums.map((x) => Number(x.toFixed(6)));

			this.chartTitle = "Stats Plot (Sorted Values)";
			this.setChart(labels, points, "Value");
		},

		parseNumbers(text) {
			const parts = String(text || "")
				.trim()
				.split(/[\s,;]+/)
				.filter(Boolean);
			const nums = [];
			for (const p of parts) {
				const n = Number(p);
				if (Number.isFinite(n)) nums.push(n);
			}
			return nums;
		},

		// ---------- Graphs ----------
		plotFunctionGraph() {
			this.graph.err = "";
			this.graph.msg = "";

			const expr = String(this.graph.expression || "").trim();
			if (!expr) return (this.graph.err = "Expression is required.");

			const xMin = Number(this.graph.xMin);
			const xMax = Number(this.graph.xMax);
			const step = Number(this.graph.step);

			if (!Number.isFinite(xMin) || !Number.isFinite(xMax) || !(xMax > xMin))
				return (this.graph.err = "xMax must be greater than xMin.");
			if (!Number.isFinite(step) || step <= 0)
				return (this.graph.err = "step must be > 0.");

			let compiled;
			try {
				compiled = math.compile(expr);
			} catch (e) {
				return (this.graph.err = e?.message ? e.message : "Invalid expression");
			}

			const xs = [];
			const ys = [];
			for (let x = xMin; x <= xMax; x += step) {
				try {
					const y = compiled.evaluate({ x });
					if (typeof y === "number" && isFinite(y)) {
						xs.push(Number(x.toFixed(6)));
						ys.push(Number(y.toFixed(6)));
					}
				} catch (_) {}
			}

			this.chartTitle = this.graph.title || "Function Plot";
			this.setChart(xs, ys, "y");

			// Show in display too
			this.display.preview = `Graph y=${expr} =`;
			this.display.main = `Points=${ys.length}`;
		},

		async saveGraph() {
			this.graph.err = "";
			this.graph.msg = "";

			const form = new FormData();
			form.append("csrf_token", this.meta.csrfToken);
			form.append("title", this.graph.title);
			form.append("expression", this.graph.expression);
			form.append("x_min", this.graph.xMin);
			form.append("x_max", this.graph.xMax);
			form.append("step", this.graph.step);

			try {
				const res = await fetch("api.php?action=graph_save", {
					method: "POST",
					body: form,
				});
				const data = await res.json();
				if (!data.ok)
					return (this.graph.err = data.error || "Failed to save graph");
				this.graph.msg = "Graph saved.";
				this.saveHistory(
					"scientific",
					`Graph saved: ${this.graph.expression}`,
					`Title=${this.graph.title}`
				);
			} catch (_) {
				this.graph.err = "Failed to save graph.";
			}
		},

		// ---------- Chart helpers ----------
		initChart() {
			const ctx = document.getElementById("mainChart");
			this.chart = new Chart(ctx, {
				type: "line",
				data: {
					labels: [],
					datasets: [
						{
							label: "Data",
							data: [],
							tension: 0.15,
						},
					],
				},
				options: {
					responsive: true,
					plugins: { legend: { display: true } },
					scales: {
						x: { title: { display: true, text: "Index / x" } },
						y: { title: { display: true, text: "Value / y" } },
					},
				},
			});
			this.chartTitle = "Chart";
		},

		clearChart() {
			if (!this.chart) return;
			this.chart.data.labels = [];
			this.chart.data.datasets[0].data = [];
			this.chart.update();
		},

		setChart(labels, data, label) {
			if (!this.chart) return;
			this.chart.data.labels = labels;
			this.chart.data.datasets[0].data = data;
			this.chart.data.datasets[0].label = label || "Data";
			this.chart.update();
		},
	};
}
