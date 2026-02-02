
/* ===== Funciones de Error ===== */
function setError(id, msg) {
  const el = document.getElementById(id);
  if (el) el.textContent = msg || "";
}

function setSuccess(id, msg) {
  const el = document.getElementById(id);
  if (el) el.textContent = msg || "";
}
/* ===== Funcion para la fecha ===== */

function fmtDateES(iso) {
  if (!iso) return "—";
  const [y, m, d] = iso.split("-");
  return `${d}/${m}/${y}`;
}


/* ===== Funcion para obtener el objetivo ===== */
async function goalGet() {
  const res = await fetch("php/goal_get.php", { headers: { "Accept": "application/json" } });
  const data = await res.json().catch(() => null);
  if (!res.ok || !data || !data.ok) throw new Error((data && data.error) ? data.error : "No se pudo cargar el objetivo.");
  return data;
}
/* ===== Funcion para guardar el objetivo ===== */

async function goalSave(payload) {
  const res = await fetch("php/goal_save.php", {
    method: "POST",
    headers: { "Content-Type": "application/json", "Accept": "application/json" },
    body: JSON.stringify(payload)
  });
  const data = await res.json().catch(() => null);
  if (!res.ok || !data || !data.ok) throw new Error((data && data.error) ? data.error : "No se pudo guardar el objetivo.");
  return true;
}
/* ===== Funcion para obtener el resumen del objetivo ===== */

function goalSummaryText(goal) {
  const periodTxt = goal.period === "week" ? "esta semana" : goal.period === "month" ? "este mes" : "en total";
  const typeTxt = goal.goal_type === "minutes" ? "minutos" : "actividades";
  const filterTxt = goal.activity_type ? ` · solo ${goal.activity_type}` : "";
  return `Objetivo: ${goal.target_value} ${typeTxt} ${periodTxt}${filterTxt}`;
}
/* ===== Funcion para renderizar el objetivo ===== */

function renderGoal(data) {
  const summaryEl = document.getElementById("goalSummary");
  const wrap = document.getElementById("goalProgressWrap");
  const fill = document.getElementById("goalBarFill");
  const text = document.getElementById("goalProgressText");

  if (!summaryEl || !wrap || !fill || !text) return;

  if (!data.has_goal) {
    summaryEl.textContent = "Aún no tienes un objetivo definido.";
    const validityEl = document.getElementById("goalValidity");
    if (validityEl) validityEl.textContent = "—";
    wrap.classList.add("is-hidden");
    fill.style.width = "0%";
    text.textContent = "";
    return;
  }

  const g = data.goal;
  const statusEl = document.getElementById("goalStatus");

  if (statusEl) {
    statusEl.className = "goal-status"; // reset

    if (g.progress >= g.target_value) {
      statusEl.classList.add("success");
      statusEl.innerHTML = `
      <span class="icon">✔️</span>
      <span>Objetivo cumplido</span>
      <span class="icon">👏</span>
    `;
    } else {
      statusEl.textContent = ""; // aún no cumplido
    }
  }
  summaryEl.textContent = goalSummaryText(g);
  const validityEl = document.getElementById("goalValidity");
  if (validityEl) {
    const fromTxt = fmtDateES(g.starts_from);
    const toTxt = g.ends_at ? fmtDateES(g.ends_at) : "— (sin caducidad)";
    validityEl.textContent = `Válido desde: ${fromTxt} · Hasta: ${toTxt}`;
  }

  const pct = Math.max(0, Math.min(100, Math.round((g.progress / g.target_value) * 100)));
  fill.style.width = `${pct}%`;

  wrap.classList.remove("is-hidden");
  text.textContent = `${g.progress} / ${g.target_value} (${pct}%)`;
}

/* ===== Modal UI ===== */
const openGoalBtn = document.getElementById("openGoalBtn");
const goalModal = document.getElementById("goalModal");
const closeGoalBtn = document.getElementById("closeGoalBtn");
const cancelGoalBtn = document.getElementById("cancelGoalBtn");
const goalForm = document.getElementById("goalForm");

function openGoalModal() {
  if (!goalModal) return;
  goalModal.classList.remove("is-hidden");
}
function closeGoalModal() {
  if (!goalModal) return;
  goalModal.classList.add("is-hidden");
  setSuccess("goalSuccess", "");
  setError("err-goal_type", "");
  setError("err-target_value", "");
  setError("err-period", "");
}

if (openGoalBtn) openGoalBtn.addEventListener("click", openGoalModal);
if (closeGoalBtn) closeGoalBtn.addEventListener("click", closeGoalModal);
if (cancelGoalBtn) cancelGoalBtn.addEventListener("click", closeGoalModal);

if (goalModal) {
  goalModal.addEventListener("click", (e) => {
    if (e.target === goalModal) closeGoalModal();
  });
}

const goalTypeEl = document.getElementById("goal_type");
const goalHintEl = document.getElementById("goalHint");
if (goalTypeEl && goalHintEl) {
  goalTypeEl.addEventListener("change", () => {
    goalHintEl.textContent = goalTypeEl.value === "minutes"
      ? "Ej: 600 minutos este mes"
      : "Ej: 12 actividades este mes";
  });
}

/* ===== Guardar objetivo ===== */
if (goalForm) {
  goalForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    setSuccess("goalSuccess", "");
    setError("err-goal_type", "");
    setError("err-target_value", "");
    setError("err-period", "");

    const goal_type = document.getElementById("goal_type")?.value || "";
    const target_value = Number(document.getElementById("target_value")?.value || 0);
    const period = document.getElementById("period")?.value || "month";
    const activity_type = document.getElementById("activity_type")?.value || "";

    let ok = true;
    if (!goal_type) { ok = false; setError("err-goal_type", "Selecciona un tipo."); }
    if (!target_value || target_value < 1 || target_value > 100000) { ok = false; setError("err-target_value", "Introduce un valor válido."); }
    if (!period) { ok = false; setError("err-period", "Selecciona un periodo."); }
    if (!ok) return;

    try {
      setSuccess("goalSuccess", "Guardando…");
      await goalSave({ goal_type, target_value, period, activity_type });
      const fresh = await goalGet();
      renderGoal(fresh);
      setSuccess("goalSuccess", "Objetivo guardado ✅");
      closeGoalModal();
    } catch (err) {
      setSuccess("goalSuccess", "");
      setError("err-target_value", err.message || "Error");
    }
  });
}

/* ===== Cargar objetivo al entrar en dashboard ===== */
if (document.getElementById("goalCard")) {
  goalGet().then(renderGoal).catch(() => { });
}