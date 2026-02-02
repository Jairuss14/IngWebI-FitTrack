
/* ===== Funciones de Error ===== */

function setErr(id, msg) {
  const el = document.getElementById(id);
  if (el) el.textContent = msg || "";
}
function setOk(id, msg) {
  const el = document.getElementById(id);
  if (el) el.textContent = msg || "";
}

const profileView = document.getElementById("profileView");
const profileForm = document.getElementById("profileForm");
const passwordForm = document.getElementById("passwordForm");

const editBtn = document.getElementById("editProfileBtn");
const cancelEditBtn = document.getElementById("cancelEditBtn");

const changePasswordBtn = document.getElementById("changePasswordBtn");
const cancelPasswordBtn = document.getElementById("cancelPasswordBtn");

// Inputs perfil
const fullNameEl = document.getElementById("full_name");
const emailEl = document.getElementById("email");
const bioEl = document.getElementById("bio");

// Avatar
const avatarInput = document.getElementById("avatar");
const avatarPreview = document.getElementById("avatarPreview");

// Valores iniciales para cancelar
const initial = {
  full_name: fullNameEl?.value ?? "",
  email: emailEl?.value ?? "",
  bio: bioEl?.value ?? ""
};

// Menú móvil (landing + dashboard + auth)
const header = document.querySelector(".site-header");
const burger = header?.querySelector(".burger");
const nav = header?.querySelector(".nav");

if (burger && nav) {
  burger.addEventListener("click", () => {
    const isOpen = burger.getAttribute("aria-expanded") === "true";
    burger.setAttribute("aria-expanded", String(!isOpen));
    nav.classList.toggle("nav--open", !isOpen);
  });
}

function hideAllEditors() {
  if (profileForm) profileForm.classList.add("is-hidden");
  if (passwordForm) passwordForm.classList.add("is-hidden");
  if (profileView) profileView.classList.remove("is-hidden");
  if (editBtn) editBtn.classList.remove("is-hidden");

  setOk("profileSuccess", "");
  setOk("passwordSuccess", "");
}

function enterEditMode() {
  if (profileView) profileView.classList.add("is-hidden");
  if (passwordForm) passwordForm.classList.add("is-hidden");
  if (profileForm) profileForm.classList.remove("is-hidden");
  if (editBtn) editBtn.classList.add("is-hidden");
  setOk("profileSuccess", "");
}

function exitEditMode(reset = false) {
  if (reset) {
    if (fullNameEl) fullNameEl.value = initial.full_name;
    if (emailEl) emailEl.value = initial.email;
    if (bioEl) bioEl.value = initial.bio;
    if (avatarInput) avatarInput.value = "";
    setErr("err-avatar", "");
  }
  hideAllEditors();
}

function togglePassword() {
  // Si está visible -> ocultar
  const visible = passwordForm && !passwordForm.classList.contains("is-hidden");
  if (visible) {
    hideAllEditors();
  } else {
    if (profileForm) profileForm.classList.add("is-hidden");
    if (profileView) profileView.classList.remove("is-hidden"); // dejamos tarjeta visible de fondo
    if (passwordForm) passwordForm.classList.remove("is-hidden");
    setOk("passwordSuccess", "");
  }
}

// Preview de avatar y validación básica client-side
if (avatarInput && avatarPreview) {
  avatarInput.addEventListener("change", () => {
    setErr("err-avatar", "");
    const f = avatarInput.files && avatarInput.files[0];
    if (!f) return;

    const okTypes = ["image/jpeg","image/png","image/webp"];
    if (!okTypes.includes(f.type)) {
      setErr("err-avatar", "Formato inválido (solo JPG/PNG/WEBP).");
      avatarInput.value = "";
      return;
    }
    if (f.size > 2 * 1024 * 1024) {
      setErr("err-avatar", "Archivo demasiado grande (máx 2MB).");
      avatarInput.value = "";
      return;
    }

    const url = URL.createObjectURL(f);
    avatarPreview.src = url;
  });
}

if (editBtn) editBtn.addEventListener("click", enterEditMode);
if (cancelEditBtn) cancelEditBtn.addEventListener("click", () => exitEditMode(true));
if (changePasswordBtn) changePasswordBtn.addEventListener("click", togglePassword);
if (cancelPasswordBtn) cancelPasswordBtn.addEventListener("click", () => hideAllEditors());

/* ===== Guardar perfil (datos + foto) ===== */
if (profileForm) {
  profileForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    setOk("profileSuccess", "");
    setErr("err-full_name", "");
    setErr("err-email", "");
    setErr("err-bio", "");
    setErr("err-avatar", "");

    const full_name = fullNameEl?.value.trim() || "";
    const email = emailEl?.value.trim() || "";
    const bio = bioEl?.value.trim() || "";

    // Validación ligera (server valida también)
    if (full_name.length > 60) return setErr("err-full_name", "Máx 60 caracteres.");
    if (email.length > 120) return setErr("err-email", "Máx 120 caracteres.");
    if (bio.length > 255) return setErr("err-bio", "Máx 255 caracteres.");

    try {
      setOk("profileSuccess", "Guardando…");

      const fd = new FormData();
      fd.append("full_name", full_name);
      fd.append("email", email);
      fd.append("bio", bio);

      const f = avatarInput?.files && avatarInput.files[0];
      if (f) fd.append("avatar", f);

      const res = await fetch("php/profile_update.php", {
        method: "POST",
        body: fd
      });

      const data = await res.json().catch(() => null);
      if (!res.ok || !data || !data.ok) {
        throw new Error((data && data.error) ? data.error : "Error al guardar.");
      }

      // Actualiza tarjeta (vista) sin recargar
      const viewRows = profileView?.querySelectorAll(".profile-row .profile-v");
      // Orden: Fecha, Email, Bio (en nuestro markup son 3 filas)
      if (viewRows && viewRows.length >= 3) {
        viewRows[1].textContent = email || "—";
        viewRows[2].textContent = bio || "—";
      }
      const nameH3 = profileView?.querySelector(".profile-name");
      if (nameH3) nameH3.textContent = full_name || "—";

      // Si backend devolvió avatar nuevo, actualizamos imágenes
      if (data.avatar_path) {
        const imgs = document.querySelectorAll("img.avatar");
        imgs.forEach(img => { img.src = data.avatar_path; });
      }

      // Actualiza "initial" para que cancelar no vuelva a lo viejo
      initial.full_name = full_name;
      initial.email = email;
      initial.bio = bio;

      // Limpia input file (opcional)
      if (avatarInput) avatarInput.value = "";

      setOk("profileSuccess", "Cambios guardados ✅");
      exitEditMode(false);
    } catch (err) {
      setOk("profileSuccess", "");
      setErr("err-bio", err.message || "Error");
    }
  });
}

/* ===== Cambiar contraseña ===== */
if (passwordForm) {
  passwordForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    setOk("passwordSuccess", "");
    setErr("err-current_password", "");
    setErr("err-new_password", "");
    setErr("err-new_password2", "");

    const current_password = document.getElementById("current_password")?.value || "";
    const new_password = document.getElementById("new_password")?.value || "";
    const new_password2 = document.getElementById("new_password2")?.value || "";

    let ok = true;
    if (!current_password) { ok = false; setErr("err-current_password", "Obligatoria."); }
    if (!new_password || new_password.length < 8) { ok = false; setErr("err-new_password", "Mínimo 8 caracteres."); }
    if (new_password2 !== new_password) { ok = false; setErr("err-new_password2", "No coincide."); }
    if (!ok) return;

    try {
      setOk("passwordSuccess", "Actualizando…");

      const res = await fetch("php/profile_password.php", {
        method: "POST",
        headers: { "Content-Type": "application/json", "Accept": "application/json" },
        body: JSON.stringify({ current_password, new_password })
      });

      const data = await res.json().catch(() => null);
      if (!res.ok || !data || !data.ok) {
        throw new Error((data && data.error) ? data.error : "Error al actualizar.");
      }

      setOk("passwordSuccess", "Contraseña actualizada ✅");
      passwordForm.reset();
      hideAllEditors();
    } catch (err) {
      setOk("passwordSuccess", "");
      setErr("err-current_password", err.message || "Error");
    }
  });
}


/* ===== Dashboard con Fetch ===== */

// Formateo de fecha para la lista
function fmtDate(iso) {
  if (!iso) return "—";
  const [y, m, d] = iso.split("-");
  return `${d}/${m}/${y}`;
}

function toISODate(d) {
  const yyyy = d.getFullYear();
  const mm = String(d.getMonth() + 1).padStart(2, "0");
  const dd = String(d.getDate()).padStart(2, "0");
  return `${yyyy}-${mm}-${dd}`;
}

function computeDateRange() {
  const sel = document.getElementById("timeRange")?.value || "today";
  const now = new Date();

  if (sel === "custom") {
    const from = document.getElementById("fromDate")?.value || "";
    const to = document.getElementById("toDate")?.value || "";
    return { from, to };
  }

  const to = toISODate(now);

  if (sel === "today") {
    return { from: to, to };
  }
  if (sel === "3d") {
    const d = new Date(now);
    d.setDate(d.getDate() - 2); // hoy + 2 días previos = 3 días
    return { from: toISODate(d), to };
  }
  if (sel === "7d") {
    const d = new Date(now);
    d.setDate(d.getDate() - 6); // hoy + 6 días previos = 7 días
    return { from: toISODate(d), to };
  }

  // fallback
  return { from: to, to };
}
function setText(id, value) {
  const el = document.getElementById(id);
  if (el) el.textContent = value;
}

/* ===== Funcion para renderizar actividades ===== */
function renderActivities(list) {
  const listEl = document.getElementById("activityList");
  const emptyEl = document.getElementById("emptyState");
  if (!listEl) return;

  listEl.innerHTML = "";

  if (emptyEl) emptyEl.style.display = list.length ? "none" : "block";
  if (!list.length) return;

  list.forEach((a) => {
    const item = document.createElement("div");
    item.className = "list-item";

    const tag = document.createElement("span");
    tag.className = "tag" + (a.type === "Gym" ? " tag--red" : "");
    tag.textContent = a.type;

    const main = document.createElement("span");
    main.className = "li-main";
    main.textContent = `${a.minutes} min · ${fmtDate(a.date)}`;

    const right = document.createElement("span");
    right.className = "li-sub";

    const notesLine = document.createElement("div");
    notesLine.textContent = a.notes ? a.notes : "—";

    const actions = document.createElement("div");
    actions.className = "li-actions";

    const delBtn = document.createElement("button");
    delBtn.type = "button";
    delBtn.className = "icon-btn icon-btn--danger";
    delBtn.title = "Borrar actividad";
    delBtn.setAttribute("aria-label", "Borrar actividad");
    delBtn.textContent = "Borrar";

    delBtn.addEventListener("click", async () => {
      const ok = confirm("¿Borrar esta actividad?");
      if (!ok) return;

      try {
        delBtn.disabled = true;
        await deleteActivity(a.id);
        await refreshList();
        // si tienes objetivos, refresca (no rompe si no existe)
        if (typeof goalGet === "function" && typeof renderGoal === "function") {
          goalGet().then(renderGoal).catch(() => {});
        }
      } catch (err) {
        alert(err.message || "No se pudo borrar.");
      } finally {
        delBtn.disabled = false;
      }
    });

    actions.appendChild(delBtn);
    right.appendChild(notesLine);
    right.appendChild(actions);

    item.appendChild(tag);
    item.appendChild(main);
    item.appendChild(right);

    listEl.appendChild(item);
  });
}

function updateMetrics(list) {
  setText("metricCount", String(list.length));

  const totalMin = list.reduce((acc, a) => acc + (Number(a.minutes) || 0), 0);
  setText("metricMinutes", String(totalMin));

  if (!list.length) setText("metricLast", "—");
  else setText("metricLast", `${list[0].type} · ${list[0].minutes} min`);
}

// Estado del dashboard (reflejo de BD)
let activitiesState = [];


/* ===== Funcion para obtener actividades ===== */
async function fetchActivities(filter = "all") {
  const qs = new URLSearchParams();
  if (filter && filter !== "all") qs.set("type", filter);
  const { from, to } = computeDateRange();
  if (from) qs.set("from", from);
  if (to) qs.set("to", to);
  
  const res = await fetch(`php/activities_list.php?${qs.toString()}`, {
    headers: { "Accept": "application/json" }
  });

  const data = await res.json().catch(() => null);
  if (!res.ok || !data || !data.ok) {
    const msg = (data && data.error) ? data.error : "No se pudo cargar el historial.";
    throw new Error(msg);
  }
  // Esperamos que el backend devuelva date (activity_date AS date)
  return (data.activities || []).slice().sort((a, b) => (a.date < b.date ? 1 : -1));
}

/* ===== Funcion para añadir actividad ===== */
async function addActivity(payload) {
  const res = await fetch("php/activities_add.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "Accept": "application/json"
    },
    body: JSON.stringify(payload)
  });

  const data = await res.json().catch(() => null);
  if (!res.ok || !data || !data.ok) {
    const msg = (data && data.error) ? data.error : "No se pudo guardar la actividad.";
    throw new Error(msg);
  }
  return data;
}

/* ===== Funcion para borrar actividad ===== */
async function deleteActivity(id) {
  const res = await fetch("php/activities_delete.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "Accept": "application/json"
    },
    body: JSON.stringify({ id })
  });

  const data = await res.json().catch(() => null);
  if (!res.ok || !data || !data.ok) {
    const msg = (data && data.error) ? data.error : "No se pudo borrar la actividad.";
    throw new Error(msg);
  }
  return data;
}

/* ===== Funcion para refrescar la lista ===== */
async function refreshList() {
  const filter = document.getElementById("filterType")?.value || "all";
  activitiesState = await fetchActivities(filter);
  renderActivities(activitiesState);
  updateMetrics(activitiesState);
}

const activityForm = document.getElementById("activityForm");
if (activityForm) {
  // fecha por defecto hoy
  const dateEl = document.getElementById("date");
  if (dateEl && !dateEl.value) {
    const t = new Date();
    const yyyy = t.getFullYear();
    const mm = String(t.getMonth() + 1).padStart(2, "0");
    const dd = String(t.getDate()).padStart(2, "0");
    dateEl.value = `${yyyy}-${mm}-${dd}`;
  }

  const timeRange = document.getElementById("timeRange");
const customRange = document.getElementById("customRange");

function toggleCustomRangeUI() {
  if (!timeRange || !customRange) return;
  const isCustom = timeRange.value === "custom";
  customRange.style.display = isCustom ? "flex" : "none";
}

if (timeRange) {
  toggleCustomRangeUI();

  timeRange.addEventListener("change", () => {
    toggleCustomRangeUI();
    refreshList().catch(() => {});
  });
}

// Si es personalizado, al cambiar fechas refresca también
const fromDateEl = document.getElementById("fromDate");
const toDateEl = document.getElementById("toDate");

if (fromDateEl) fromDateEl.addEventListener("change", () => {
  if (timeRange?.value === "custom") refreshList().catch(() => {});
});
if (toDateEl) toDateEl.addEventListener("change", () => {
  if (timeRange?.value === "custom") refreshList().catch(() => {});
});

  // carga inicial
  refreshList().catch(() => {});

  activityForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    setSuccess("activitySuccess", "");
    setError("err-type", "");
    setError("err-minutes", "");
    setError("err-date", "");

    const type = document.getElementById("type")?.value || "";
    const minutes = Number(document.getElementById("minutes")?.value || 0);
    const date = document.getElementById("date")?.value || "";
    const notes = document.getElementById("notes")?.value.trim() || "";

    let ok = true;
    if (!type) { ok = false; setError("err-type", "Selecciona un tipo de actividad."); }
    if (!minutes || minutes < 1 || minutes > 600) { ok = false; setError("err-minutes", "Duración inválida (1–600)."); }
    if (!date) { ok = false; setError("err-date", "Selecciona una fecha."); }
    if (!ok) return;

    try {
      setSuccess("activitySuccess", "Guardando…");
      await addActivity({ type, minutes, date, notes });

      await refreshList();

      // refrescar objetivo si existe
      if (typeof goalGet === "function" && typeof renderGoal === "function") {
        goalGet().then(renderGoal).catch(() => {});
      }

      setSuccess("activitySuccess", "Actividad guardada ✅");
      activityForm.reset();

      // mantener fecha hoy
      if (dateEl) {
        const t = new Date();
        const yyyy = t.getFullYear();
        const mm = String(t.getMonth() + 1).padStart(2, "0");
        const dd = String(t.getDate()).padStart(2, "0");
        dateEl.value = `${yyyy}-${mm}-${dd}`;
      }
    } catch (err) {
      setSuccess("activitySuccess", "");
      setError("err-date", err.message || "Error al guardar.");
    }
  });

  // filtro
  const filterType = document.getElementById("filterType");
  if (filterType) {
    filterType.addEventListener("change", () => {
      refreshList().catch(() => {});
      if (typeof goalGet === "function" && typeof renderGoal === "function") {
        goalGet().then(renderGoal).catch(() => {});
      }
    });
  }

  // limpiar filtro (no borra BD)
  const clearBtn = document.getElementById("clearBtn");
  if (clearBtn) {
    clearBtn.addEventListener("click", () => {
      if (filterType) filterType.value = "all";
      refreshList().catch(() => {});
      if (typeof goalGet === "function" && typeof renderGoal === "function") {
        goalGet().then(renderGoal).catch(() => {});
      }
    });
  }
}