<?php
declare(strict_types=1);
require_once __DIR__ . "/php/auth.php";
require_login();
$avatar = (string)($_SESSION["avatar_path"] ?? "");
if ($avatar === "") $avatar = "assets/avatar-default.svg";





?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard · FitTrack</title>
  <meta name="description" content="Dashboard de FitTrack: registra y consulta tus entrenamientos." />
  <link rel="stylesheet" href="css/styles.css" />
</head>

<body>
  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="index.html" aria-label="FitTrack inicio">
        <span class="brand-mark">FT</span>
        <span class="brand-name">FitTrack</span>
      </a>

      <nav class="nav" aria-label="Navegación principal">
        <a href="profile.php">Mi Perfil</a>
        <a href=""></a>
        <a class="btn btn--outline" href="php/logout.php">Cerrar sesión</a>
      </nav>

      <button class="burger" type="button" aria-label="Abrir menú" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
    </div>
  </header>

  <main class="dash">
    <div class="container">
      <section class="dash-hero" aria-label="Resumen">
        <div class="dash-welcome">
          <img class="avatar" src="<?php echo htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar" width="56" height="56">
          <div>
            <h1 class="dash-title">
              Hola, <span id="dashUser"><?php echo htmlspecialchars((string)$_SESSION["username"], ENT_QUOTES, "UTF-8"); ?></span> 👋
            </h1>
            <p class="dash-sub">Aquí puedes registrar entrenamientos y consultar tu historial.</p>
          </div>
        </div>
        
        
          <div class="dash-side">
             <div>
                <section class="panel" id="goalCard" aria-label="Objetivo" style="margin-top:16px;">
                    <header class="panel-head panel-head--row">
                      <div>
                        <h2>Objetivo</h2>
                        <p class="panel-lead" id="goalSummary">Aún no tienes un objetivo definido.</p>
                        <p class="hint" id="goalValidity">—</p>
                        <div id="goalStatus" class="goal-status"></div>
                      </div>
                      <button class="btn btn--ghost" type="button" id="openGoalBtn">Definir objetivo</button>
                      
                    </header>

                    <div class="goal-progress is-hidden" id="goalProgressWrap">
                      <div class="goal-bar">
                        <div class="goal-bar__fill" id="goalBarFill" style="width:0%"></div>
                      </div>
                      <p class="hint" id="goalProgressText">0 / 0</p>
                    </div>
                  </section>
            </div>
            
               <div class="dash-metrics" aria-label="Indicadores">
                  <article class="metric">
                    <p class="metric-k">Actividades</p>
                    <p class="metric-v" id="metricCount">0</p>
                  </article>
                  <article class="metric">
                    <p class="metric-k">Minutos totales</p>
                    <p class="metric-v" id="metricMinutes">0</p>
                  </article>
                  <article class="metric">
                    <p class="metric-k">Última actividad</p>
                    <p class="metric-v" id="metricLast">—</p>
                  </article>
              </div>
        </div>

      </section>

      <section class="dash-grid">
        <!-- ADD -->
        <section id="add" class="panel" aria-label="Añadir actividad">
          <header class="panel-head">
            <h2>Añadir actividad</h2>
          </header>

          <form id="activityForm" class="form" novalidate>
            <div class="field">
              <label for="type">Tipo</label>
              <select id="type" name="type" required>
                <option value="">Selecciona…</option>
                <option value="Running">Running</option>
                <option value="Gym">Gym</option>
                <option value="Bici">Bici</option>
                <option value="Natación">Natación</option>
                <option value="Caminata">Caminata</option>
              </select>
              <p class="error" id="err-type" aria-live="polite"></p>
            </div>

            <div class="field">
              <label for="minutes">Duración (min)</label>
              <input id="minutes" name="minutes" type="number" min="1" max="600" placeholder="Ej: 45" required />
              <p class="error" id="err-minutes" aria-live="polite"></p>
            </div>

            <div class="field">
              <label for="date">Fecha</label>
              <input id="date" name="date" type="date" required />
              <p class="error" id="err-date" aria-live="polite"></p>
            </div>

            <div class="field">
              <label for="notes">Notas (opcional)</label>
              <textarea id="notes" name="notes" rows="3" placeholder="Ej: Tempo suave, RPE 6"></textarea>
            </div>

            <button class="btn btn--primary btn--full" type="submit">Guardar actividad</button>
            <p class="form-success" id="activitySuccess" aria-live="polite"></p>
          </form>
        </section>

        <!-- HISTORY -->
        <section id="history" class="panel" aria-label="Historial de actividades">
          <header class="panel-head panel-head--row">
            <div>
              <h2>Historial</h2>
              <p class="panel-lead">Tus últimas actividades aparecerán aquí.</p>
            </div>

            <div class="filters" aria-label="Filtros">
              <label class="sr-only" for="filterType">Filtrar por tipo</label>
              <select id="filterType">
                <option value="all">Todos</option>
                <option value="Running">Running</option>
                <option value="Gym">Gym</option>
                <option value="Bici">Bici</option>
                <option value="Natación">Natación</option>
                <option value="Caminata">Caminata</option>
              </select>
              <label class="sr-only" for="timeRange">Rango temporal</label>
              <select id="timeRange">
                <option value="today" selected>Hoy</option>
                <option value="3d">Últimos 3 días</option>
                <option value="7d">Última semana</option>
                <option value="custom">Personalizado</option>
              </select>

              <div class="date-range" id="customRange" style="display:none;">
                <label class="sr-only" for="fromDate">Desde</label>
                <input id="fromDate" type="date" />

                <label class="sr-only" for="toDate">Hasta</label>
                <input id="toDate" type="date" />
              </div>
            </div>
          </header>

          <div class="list" id="activityList" aria-label="Lista de actividades"></div>
          <p class="hint" id="emptyState">Aún no hay actividades. Añade la primera 👆</p>
        </section>
      </section>
    </div>
    <div class="modal is-hidden" id="goalModal" role="dialog" aria-modal="true" aria-label="Definir objetivo">
  <div class="modal-card">
    <header class="modal-head">
      <h3>Definir objetivo</h3>
      <button class="icon-btn" type="button" id="closeGoalBtn" aria-label="Cerrar">✕</button>
    </header>

    <form id="goalForm" class="form" novalidate>
      <div class="field">
        <label for="goal_type">Tipo de objetivo</label>
        <select id="goal_type" required>
          <option value="minutes">Minutos totales</option>
          <option value="activities">Número de actividades</option>
        </select>
        <p class="error" id="err-goal_type"></p>
      </div>

      <div class="field">
        <label for="target_value">Objetivo</label>
        <input id="target_value" type="number" min="1" max="100000" placeholder="Ej: 600" required />
        <p class="hint" id="goalHint">Ej: 600 minutos este mes</p>
        <p class="error" id="err-target_value"></p>
      </div>

      <div class="field">
        <label for="period">Periodo</label>
        <select id="period" required>
          <option value="week">Semana</option>
          <option value="month" selected>Mes</option>
          <option value="all">Total (sin periodo)</option>
        </select>
        <p class="error" id="err-period"></p>
      </div>

      <div class="field">
        <label for="activity_type">Filtrar por tipo (opcional)</label>
        <select id="activity_type">
          <option value="">Todos</option>
          <option value="Running">Running</option>
          <option value="Gym">Gym</option>
          <option value="Bici">Bici</option>
          <option value="Natación">Natación</option>
          <option value="Caminata">Caminata</option>
        </select>
      </div>

      <div class="form-actions">
        <button class="btn btn--primary" type="submit">Guardar objetivo</button>
        <button class="btn btn--outline" type="button" id="cancelGoalBtn">Cancelar</button>
      </div>

      <p class="form-success" id="goalSuccess" aria-live="polite"></p>
    </form>
  </div>
</div>
  </main>

  <footer class="site-footer">
    <div class="container footer-inner">
      <p>© <span id="year"></span> FitTrack · UAX - Ingeniería Web 1</p>
      <p> Jairo Hernández</p>
      <p class="footer-links">
        <a href="index.html">Home</a>
      </p>
    </div>
  </footer>

<script src="js/script.js" defer></script>
<script src="js/goals.js" defer></script>
</body>
</html>