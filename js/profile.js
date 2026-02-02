

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
const editBtn = document.getElementById("editProfileBtn");
const cancelBtn = document.getElementById("cancelEditBtn");

// Guardamos valores iniciales para "Cancelar" sin recargar
const initial = {
  full_name: document.getElementById("full_name")?.value ?? "",
  email: document.getElementById("email")?.value ?? "",
  bio: document.getElementById("bio")?.value ?? ""
};

/* ===== Funcion para entrar en modo edicion ===== */

function enterEditMode() {
  if (profileView) profileView.classList.add("is-hidden");
  if (profileForm) profileForm.classList.remove("is-hidden");
  if (editBtn) editBtn.classList.add("is-hidden");
  setOk("profileSuccess", "");
}
/* ===== Funcion para salir del modo edicion ===== */

function exitEditMode(reset = false) {
  if (reset) {
    const fn = document.getElementById("full_name");
    const em = document.getElementById("email");
    const bio = document.getElementById("bio");
    if (fn) fn.value = initial.full_name;
    if (em) em.value = initial.email;
    if (bio) bio.value = initial.bio;
  }

  if (profileForm) profileForm.classList.add("is-hidden");
  if (profileView) profileView.classList.remove("is-hidden");
  if (editBtn) editBtn.classList.remove("is-hidden");

  setErr("err-full_name", "");
  setErr("err-email", "");
  setErr("err-bio", "");
  setOk("profileSuccess", "");
}

if (editBtn) editBtn.addEventListener("click", enterEditMode);
if (cancelBtn) cancelBtn.addEventListener("click", () => exitEditMode(true));

if (profileForm) {
  profileForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    setOk("profileSuccess", "");
    setErr("err-full_name", "");
    setErr("err-email", "");
    setErr("err-bio", "");

    const full_name = document.getElementById("full_name")?.value.trim() || "";
    const email = document.getElementById("email")?.value.trim() || "";
    const bio = document.getElementById("bio")?.value.trim() || "";

    // Validación ligera front
    if (full_name.length > 60) return setErr("err-full_name", "Máx 60 caracteres.");
    if (email.length > 120) return setErr("err-email", "Máx 120 caracteres.");
    if (bio.length > 255) return setErr("err-bio", "Máx 255 caracteres.");

    try {
      setOk("profileSuccess", "Guardando…");

      const res = await fetch("php/profile_update.php", {
        method: "POST",
        headers: { "Content-Type": "application/json", "Accept": "application/json" },
        body: JSON.stringify({ full_name, email, bio })
      });

      const data = await res.json().catch(() => null);
      if (!res.ok || !data || !data.ok) {
        throw new Error((data && data.error) ? data.error : "Error al guardar.");
      }

      // Actualizar tarjeta en vivo (sin recargar)
      const viewRows = profileView?.querySelectorAll(".profile-row .profile-v");
      // Orden: Usuario, Nombre, Email, Bio  -> actualizamos 2,3,4
      if (viewRows && viewRows.length >= 4) {
        viewRows[1].textContent = full_name || "—";
        viewRows[2].textContent = email || "—";
        viewRows[3].textContent = bio || "—";
      }

      // Actualizamos "initial" para que cancelar no vuelva a lo viejo
      initial.full_name = full_name;
      initial.email = email;
      initial.bio = bio;

      setOk("profileSuccess", "Cambios guardados ✅");
      // salir de edición (sin reset)
      exitEditMode(false);
    } catch (err) {
      setOk("profileSuccess", "");
      setErr("err-bio", err.message || "Error");
    }
  });
}