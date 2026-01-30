// Año automático en el footer
const yearEl = document.getElementById("year");
if (yearEl) yearEl.textContent = new Date().getFullYear();

// Dia automático 
const dayEl = document.getElementById("day");
if (dayEl) dayEl.textContent = new Date().toDateString() ;


// Menú móvil (landing + auth)
const burger = document.querySelector(".burger");
const nav = document.querySelector(".nav");

if (burger && nav) {
  burger.addEventListener("click", () => {
    const isOpen = burger.getAttribute("aria-expanded") === "true";
    burger.setAttribute("aria-expanded", String(!isOpen));

    if (!isOpen) {
      nav.style.display = "flex";
      nav.style.flexDirection = "column";
      nav.style.position = "absolute";
      nav.style.top = "64px";
      nav.style.right = "4%";
      nav.style.background = "rgba(57,63,67,.92)";
      nav.style.border = "1px solid rgba(255,255,255,.14)";
      nav.style.borderRadius = "16px";
      nav.style.padding = "12px";
      nav.style.gap = "12px";
      nav.style.width = "min(260px, 92vw)";
    } else {
      nav.removeAttribute("style");
    }
  });
}

/* =========================
   VALIDACIONES
========================= */

function setError(id, msg) {
  const el = document.getElementById(id);
  if (el) el.textContent = msg || "";
}

function setSuccess(id, msg) {
  const el = document.getElementById(id);
  if (el) el.textContent = msg || "";
}

function isValidUsername(username) {
  // Letras/números/guion/guion bajo, 3-20
  return /^[a-zA-Z0-9_-]{3,20}$/.test(username);
}

function isStrongPassword(pw) {
  // >=8, 1 mayús, 1 minús, 1 número
  return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/.test(pw);
}

function isAdult(dobStr) {
  // dobStr: "YYYY-MM-DD"
  if (!dobStr) return false;
  const dob = new Date(dobStr + "T00:00:00");
  if (Number.isNaN(dob.getTime())) return false;

  const today = new Date();
  let age = today.getFullYear() - dob.getFullYear();
  const m = today.getMonth() - dob.getMonth();
  if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
    age--;
  }
  return age >= 18;
}

/* =========================
   REGISTRO
========================= */
const registerForm = document.getElementById("registerForm");
if (registerForm) {
  registerForm.addEventListener("submit", (e) => {
    e.preventDefault();

    // Reset mensajes
    setSuccess("registerSuccess", "");
    setError("err-username", "");
    setError("err-dob", "");
    setError("err-password", "");
    setError("err-password2", "");

    const username = document.getElementById("username")?.value.trim() || "";
    const dob = document.getElementById("dob")?.value || "";
    const pw = document.getElementById("password")?.value || "";
    const pw2 = document.getElementById("password2")?.value || "";

    let ok = true;

    if (!username) {
      ok = false;
      setError("err-username", "El nombre de usuario es obligatorio.");
    } else if (!isValidUsername(username)) {
      ok = false;
      setError("err-username", "Formato inválido. Usa 3–20 caracteres: letras, números, _ o -.");
    }

    if (!dob) {
      ok = false;
      setError("err-dob", "La fecha de nacimiento es obligatoria.");
    } else if (!isAdult(dob)) {
      ok = false;
      setError("err-dob", "Debes tener 18 años o más para registrarte.");
    }

    if (!pw) {
      ok = false;
      setError("err-password", "La contraseña es obligatoria.");
    } else if (!isStrongPassword(pw)) {
      ok = false;
      setError("err-password", "Contraseña débil. Usa 8+ caracteres con mayúscula, minúscula y número.");
    }

    if (!pw2) {
      ok = false;
      setError("err-password2", "Debes repetir la contraseña.");
    } else if (pw2 !== pw) {
      ok = false;
      setError("err-password2", "Las contraseñas no coinciden.");
    }

    if (!ok) return;

    // Demo (front): mostramos éxito y limpiamos
    setSuccess("registerSuccess", "Registro validado correctamente (demo). En backend se guardará en MySQL.");
    registerForm.reset();
  });
}

/* =========================
   LOGIN
========================= */
const loginForm = document.getElementById("loginForm");
if (loginForm) {
  loginForm.addEventListener("submit", (e) => {
    e.preventDefault();

    setSuccess("loginSuccess", "");
    setError("err-loginUser", "");
    setError("err-loginPass", "");

    const user = document.getElementById("loginUser")?.value.trim() || "";
    const pass = document.getElementById("loginPass")?.value || "";

    let ok = true;

    if (!user) {
      ok = false;
      setError("err-loginUser", "El usuario es obligatorio.");
    }

    if (!pass) {
      ok = false;
      setError("err-loginPass", "La contraseña es obligatoria.");
    }

    if (!ok) return;

    // Demo (front): mostramos éxito
    setSuccess("loginSuccess", "Login validado (demo). En backend se verificará en servidor y se iniciará sesión.");
    // Más adelante: redirigir a dashboard si el login es correcto
    // window.location.href = "dashboard.html";
  });
}

/* =========================
   DASHBOARD (DEMO FRONT)
========================= */

function fmtDate(iso) {
  // iso: YYYY-MM-DD -> DD/MM/YYYY
  if (!iso) return "—";
  const [y, m, d] = iso.split("-");
  return `${d}/${m}/${y}`;
}

function renderActivities(list, filter = "all") {
  const listEl = document.getElementById("activityList");
  const emptyEl = document.getElementById("emptyState");
  if (!listEl) return;

  listEl.innerHTML = "";

  const filtered = filter === "all" ? list : list.filter(a => a.type === filter);

  if (emptyEl) emptyEl.style.display = filtered.length ? "none" : "block";
  if (!filtered.length) return;

  filtered
    .slice()
    .sort((a, b) => (a.date < b.date ? 1 : -1))
    .forEach((a) => {
      const item = document.createElement("div");
      item.className = "list-item";

      const tag = document.createElement("span");
      tag.className = "tag" + (a.type === "Gym" ? " tag--red" : "");
      tag.textContent = a.type;

      const main = document.createElement("span");
      main.className = "li-main";
      main.textContent = `${a.minutes} min · ${fmtDate(a.date)}`;

      const sub = document.createElement("span");
      sub.className = "li-sub";
      sub.textContent = a.notes ? a.notes : "—";

      item.appendChild(tag);
      item.appendChild(main);
      item.appendChild(sub);

      listEl.appendChild(item);
    });
}

function updateMetrics(list) {
  const countEl = document.getElementById("metricCount");
  const minEl = document.getElementById("metricMinutes");
  const lastEl = document.getElementById("metricLast");

  if (countEl) countEl.textContent = String(list.length);

  const totalMin = list.reduce((acc, a) => acc + (Number(a.minutes) || 0), 0);
  if (minEl) minEl.textContent = String(totalMin);

  if (lastEl) {
    if (!list.length) lastEl.textContent = "—";
    else {
      const last = list.slice().sort((a, b) => (a.date < b.date ? 1 : -1))[0];
      lastEl.textContent = `${last.type} · ${last.minutes} min`;
    }
  }
}

// Estado (demo) — más adelante vendrá de MySQL vía Fetch
const demoActivities = [];

const activityForm = document.getElementById("activityForm");
if (activityForm) {
  // Por defecto: fecha hoy
  const dateEl = document.getElementById("date");
  if (dateEl) {
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, "0");
    const dd = String(today.getDate()).padStart(2, "0");
    dateEl.value = `${yyyy}-${mm}-${dd}`;
  }

  // Render inicial
  renderActivities(demoActivities, "all");
  updateMetrics(demoActivities);

  activityForm.addEventListener("submit", (e) => {
    e.preventDefault();

    // reset mensajes
    setSuccess("activitySuccess", "");
    setError("err-type", "");
    setError("err-minutes", "");
    setError("err-date", "");

    const type = document.getElementById("type")?.value || "";
    const minutes = Number(document.getElementById("minutes")?.value || 0);
    const date = document.getElementById("date")?.value || "";
    const notes = document.getElementById("notes")?.value.trim() || "";

    let ok = true;

    if (!type) {
      ok = false;
      setError("err-type", "Selecciona un tipo de actividad.");
    }
    if (!minutes || minutes < 1 || minutes > 600) {
      ok = false;
      setError("err-minutes", "Introduce una duración válida (1–600 minutos).");
    }
    if (!date) {
      ok = false;
      setError("err-date", "Selecciona una fecha.");
    }

    if (!ok) return;

    demoActivities.push({ type, minutes, date, notes });

    // Render + métricas
    const filter = document.getElementById("filterType")?.value || "all";
    renderActivities(demoActivities, filter);
    updateMetrics(demoActivities);

    setSuccess("activitySuccess", "Actividad añadida (demo). En backend se guardará en MySQL vía Fetch.");
    activityForm.reset();

    // mantener fecha por defecto (hoy)
    if (dateEl) {
      const today = new Date();
      const yyyy = today.getFullYear();
      const mm = String(today.getMonth() + 1).padStart(2, "0");
      const dd = String(today.getDate()).padStart(2, "0");
      dateEl.value = `${yyyy}-${mm}-${dd}`;
    }
  });
}

// Filtro
const filterType = document.getElementById("filterType");
if (filterType) {
  filterType.addEventListener("change", () => {
    renderActivities(demoActivities, filterType.value);
  });
}

// Limpiar (demo)
const clearBtn = document.getElementById("clearBtn");
if (clearBtn) {
  clearBtn.addEventListener("click", () => {
    demoActivities.length = 0;
    renderActivities(demoActivities, "all");
    updateMetrics(demoActivities);
    if (filterType) filterType.value = "all";
  });
}

// Logout (placeholder)
const logoutBtn = document.getElementById("logoutBtn");
if (logoutBtn) {
  logoutBtn.addEventListener("click", () => {
    alert("Demo: cuando exista backend, aquí se cerrará la sesión.");
  });
}

// Nombre de usuario (placeholder)
const dashUser = document.getElementById("dashUser");
if (dashUser) {
  dashUser.textContent = "usuario";
}