</main>
<footer class="footer">
  <div class="wrap footer-grid">
    <!-- Columna 1: Identidad Editorial y Pie de Imprenta -->
    <div class="footer-col footer-col-brand">
      <div class="footer-brand-title"><?= e(SITE_NAME) ?></div>
      <p class="footer-tagline"><em><?= e(SITE_TAGLINE) ?></em></p>
      <div class="footer-staff-mini">
        <p><strong>Director y Editor Responsable:</strong> Rodolfo Giráldez</p>
        <p><strong>Domicilio Legal:</strong> Las Verbenas 455, Santa Clara del Mar (CP 7609), Pcia. de Buenos Aires, Argentina</p>
        <p><strong>Contacto:</strong> <a href="mailto:editorialtuco@gmail.com">editorialtuco@gmail.com</a></p>
        <p class="muted">Registro DNDA en trámite · Obra amparada por la Ley Nº 11.723 de Propiedad Intelectual.</p>
      </div>
    </div>

    <!-- Columna 2: Secciones Editoriales -->
    <div class="footer-col footer-col-nav">
      <div class="footer-col-title">Secciones</div>
      <ul class="footer-links">
        <li><a href="index.php">Inicio</a></li>
        <li><a href="categoria.php?slug=cultura">Cultura</a></li>
        <li><a href="categoria.php?slug=deportes">Deportes</a></li>
        <li><a href="categoria.php?slug=economia">Economía</a></li>
        <li><a href="categoria.php?slug=politica">Política</a></li>
        <li><a href="categoria.php?slug=tecnologia">Tecnología</a></li>
        <li><a href="buscar.php">Buscador</a></li>
      </ul>
    </div>

    <!-- Columna 3: Marco Regulatorio y Legal Argentino -->
    <div class="footer-col footer-col-legal">
      <div class="footer-col-title">Marco Legal · Argentina</div>
      <ul class="footer-links">
        <li><a href="terminos.php">Términos y Condiciones de Uso</a></li>
        <li><a href="privacidad.php">Política de Privacidad (Ley 25.326)</a></li>
        <li><a href="legales.php">Staff y Pie de Imprenta</a></li>
        <li><a href="https://www.argentina.gob.ar/produccion/defensadelconsumidor/formulario" target="_blank" rel="noopener">Defensa del Consumidor (Ventanilla Única)</a></li>
        <li><a href="admin/" class="footer-admin-link">Acceso Redacción / Administrar</a></li>
      </ul>

      <!-- Botones de Compliance Defensa del Consumidor -->
      <div class="footer-compliance-btns">
        <a href="arrepentimiento.php?tipo=arrepentimiento" class="btn-arrepentimiento" title="Resolución 424/2020 Secretaría de Comercio Interior">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
          Botón de Arrepentimiento
        </a>
        <a href="arrepentimiento.php?tipo=baja" class="btn-baja" title="Resolución 271/2020 Secretaría de Comercio Interior">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          Botón de Baja de Servicio
        </a>
      </div>
    </div>

    <!-- Columna 4: Data Fiscal / AFIP / ARCA -->
    <div class="footer-col footer-col-fiscal">
      <div class="footer-col-title">Identificación Fiscal</div>
      <div class="data-fiscal-badge">
        <div class="data-fiscal-header">AFIP · ARCA</div>
        <div class="data-fiscal-sub">Formulario 960/D</div>
        <div class="data-fiscal-qr">
          <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
            <rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>
            <rect x="6" y="6" width="1" height="1"/><rect x="17" y="6" width="1" height="1"/>
            <rect x="6" y="17" width="1" height="1"/><rect x="17" y="17" width="1" height="1"/>
          </svg>
        </div>
        <div class="data-fiscal-titular">Rodolfo Giráldez</div>
        <a href="legales.php" class="data-fiscal-link">Data Fiscal y Legal</a>
      </div>
    </div>
  </div>

  <!-- Franja Legal Obligatoria de la AAIP (Ley 25.326) -->
  <div class="wrap footer-aaip-strip">
    <div class="aaip-box">
      <p class="aaip-text">
        <strong>Protección de Datos Personales (Ley N° 25.326):</strong> El titular de los datos personales tiene la facultad de ejercer el derecho de acceso a los mismos en forma gratuita a intervalos no inferiores a seis meses, salvo que se acredite un interés legítimo al efecto conforme lo establecido en el artículo 14, inciso 3 de la Ley Nº 25.326. La <strong>AGENCIA DE ACCESO A LA INFORMACIÓN PÚBLICA (AAIP)</strong>, en su carácter de Órgano de Control de la Ley Nº 25.326, tiene la atribución de atender las denuncias y reclamos que se interpongan con relación al incumplimiento de las normas sobre protección de datos personales. 
        <a href="https://www.argentina.gob.ar/aaip/datospersonales" target="_blank" rel="noopener">www.argentina.gob.ar/aaip/datospersonales</a>
      </p>
    </div>
  </div>

  <!-- Barra de Derechos y Crédito LIAA -->
  <div class="wrap footer-bottom-bar">
    <div class="footer-copy">
      © <?= date('Y') ?> <?= e(SITE_NAME) ?>. Todos los derechos reservados. Prohibida su reproducción total o parcial sin autorización expresa (Ley 11.723).
    </div>
    <div class="footer-bottom-right">
      <a href="admin/" class="btn-admin-footer" title="Acceso al panel de redacción">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Administración
      </a>
      <div class="footer-credit">
        <span>Desarrollado por</span>
        <a href="https://liaa.cloud" target="_blank" rel="noopener" aria-label="LIAA · Laboratorio de IA Aplicada">
          <img src="https://liaa.cloud/brand/liaa-logo.png" alt="LIAA">
        </a>
      </div>
    </div>
  </div>
</footer>

<!-- Banner de Cookies conforme a normativas de privacidad -->
<aside id="cookie-banner" class="cookie-banner" aria-label="Aviso de Cookies y Privacidad" style="display: none;">
  <div class="wrap cookie-in">
    <div class="cookie-text">
      <strong>Aviso de Privacidad y Cookies:</strong> Este sitio utiliza cookies técnicas necesarias para la navegación y herramientas de análisis anónimo conforme a nuestra <a href="privacidad.php">Política de Privacidad</a> y la Ley N° 25.326 de la República Argentina.
    </div>
    <div class="cookie-actions">
      <button type="button" id="cookie-accept-btn" class="btn btn-sm">Aceptar y Continuar</button>
    </div>
  </div>
</aside>

<script>
(function() {
  // Manejo de Cookies
  if (!localStorage.getItem('editorialtuco_cookies_consent')) {
    var banner = document.getElementById('cookie-banner');
    if (banner) banner.style.display = 'block';
  }
  var cookieBtn = document.getElementById('cookie-accept-btn');
  if (cookieBtn) {
    cookieBtn.addEventListener('click', function() {
      localStorage.setItem('editorialtuco_cookies_consent', 'accepted_' + new Date().toISOString());
      var banner = document.getElementById('cookie-banner');
      if (banner) banner.style.display = 'none';
    });
  }

  // Controlador de Modo Claro / Modo Oscuro
  function getEffectiveTheme() {
    var saved = localStorage.getItem('editorialtuco_theme');
    if (saved === 'dark' || saved === 'light') return saved;
    return (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
  }

  function applyTheme(theme, persist) {
    document.documentElement.setAttribute('data-theme', theme);
    if (persist) {
      try { localStorage.setItem('editorialtuco_theme', theme); } catch(e) {}
    }
    var metaScheme = document.querySelector('meta[name="color-scheme"]');
    if (metaScheme) metaScheme.content = theme;
    var metaThemeColor = document.getElementById('meta-theme-color') || document.querySelector('meta[name="theme-color"]');
    if (metaThemeColor) {
      metaThemeColor.content = theme === 'dark' ? '#121212' : '#ffffff';
    }
    var toggles = document.querySelectorAll('.theme-toggle');
    toggles.forEach(function(btn) {
      var label = btn.querySelector('.theme-toggle-label');
      if (label) label.textContent = theme === 'dark' ? 'Claro' : 'Oscuro';
      btn.setAttribute('aria-label', theme === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
    });
  }

  var currentTheme = getEffectiveTheme();
  applyTheme(currentTheme, false);

  document.addEventListener('click', function(e) {
    var btn = e.target.closest('.theme-toggle');
    if (!btn) return;
    var now = document.documentElement.getAttribute('data-theme') || getEffectiveTheme();
    var next = now === 'dark' ? 'light' : 'dark';
    applyTheme(next, true);
  });

  if (window.matchMedia) {
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
      if (!localStorage.getItem('editorialtuco_theme')) {
        applyTheme(e.matches ? 'dark' : 'light', false);
      }
    });
  }
})();
</script>
</body>
</html>
