# Contexto Integral de Proyecto · Editorial Tucó
**Dominio:** [https://editorialtuco.com](https://editorialtuco.com)  
**Repositorio GitHub:** [https://github.com/jorgejime/editorialtuco.git](https://github.com/jorgejime/editorialtuco.git)  
**Directorio Local:** `C:\Users\DELL\editorialtuco`  
**Última actualización:** 2026-10-07 · Rama `main` sincronizada

---

## 1. Resumen Ejecutivo y Estado del Proyecto
* **Paso a Producción Oficial:** Finalizado. Se eliminaron todas las leyendas de "demo" o credenciales expuestas en interfaz pública.
* **Despliegue Activo en Hostinger:** Desplegado mediante API TUS a `public_html` del plan Cloud Business.
* **Identidad Editorial:**
  * Nombre: Editorial Tucó
  * Slogan: *Fundar es creer*
  * Director y Editor Responsable: Rodolfo Giráldez
  * Domicilio Legal: Las Verbenas 455, Santa Clara del Mar (CP 7609), Provincia de Buenos Aires, Argentina
  * Contacto General: `info@editorialtuco.com`
  * Asuntos Legales: `legal@editorialtuco.com`
  * Comercial y Pauta: `comercial@editorialtuco.com`

---

## 2. Acceso al Panel de Administración
* **URL:** `https://editorialtuco.com/admin/`
* **Acceso desde la web:** Botón discreto en el pie de página (footer) `[ 🔒 Administración ]` y enlace en la columna legal.
* **Credenciales de Producción:**
  * **Usuario:** `admin`
  * **Contraseña:** `demo2026`
* **Capacidades del panel:** Creación, edición, categorización, eliminación de notas, subida de fotos (JPG, PNG, WEBP, GIF < 5MB) y conmutación de estado destacada/publicada.

---

## 3. Infraestructura y Credenciales de Servidor (Hostinger)
* **Plan:** Business Web Hosting (`order_id: 1006721374`)
* **Usuario del sistema:** `u719647957`
* **Dominio:** `editorialtuco.com` (CDN: `editorialtuco.com.cdn.hstgr.net`, SSL activo)
* **Token de Hostinger API:** `fYY4wU28t4jS80PeHEahBjODWexgxUk6CQ9JH1wtc4837e7f`
* **Servidores MCP configurados:** `hostinger-tuco-hosting`, `hostinger-tuco-dns`, `hostinger-tuco-domains`, `hostinger-tuco-billing`, `hostinger-tuco-mail`, `hostinger-tuco-vps`, `hostinger-tuco-ecommerce`, `hostinger-tuco-reach`.
* **Procedimiento de Despliegue Directo:**
  * Vía script Node.js hacia el endpoint TUS resumable `https://developers.hostinger.com/api/hosting/v1/files/upload-urls`.

---

## 4. Arquitectura de Backend y Datos
* **Lenguaje:** PHP 8.x nativo (orientado a máxima eficiencia, sin frameworks lentos).
* **Base de Datos:** SQLite 3 en disco NVMe local: `data/portal.db`
  * Tablas: `noticias` (id, titulo, slug, resumen, contenido, imagen, categoria_id, destacada, publicada, fecha_pub, creada) y `categorias` (id, nombre, slug).
* **Almacenamiento de Multimedia:**
  * Directorio físico: `uploads/` (ej. `uploads/img_XXXXXXXXXX_XXXX.jpg`).
  * En la base de datos se almacena únicamente el nombre del archivo sanitizado.
  * Si la noticia no tiene foto, `placeholder_img()` genera en memoria un SVG estilizado en base64 según la categoría.
* **Auditoría Legal:**
  * Log de constancias fehacientes en `data/tramites_consumidor.json`.

---

## 5. Suite de Compliance Argentina (Regulatorio Completo)
1. **Protección de Datos Personales (Ley N° 25.326):**
   * Franja reglamentaria de la Agencia de Acceso a la Información Pública (AAIP) en el pie de página con enlace oficial.
   * Política de Privacidad detallada en `privacidad.php` con derechos ARCO (Acceso, Rectificación, Cancelación y Oposición).
2. **Defensa del Consumidor (Resoluciones 424/2020 y 271/2020 SCI & Ley 24.240):**
   * Botón de Arrepentimiento (Res. 424/2020): revocación dentro de 10 días corridos.
   * Botón de Baja de Servicio (Res. 271/2020): rescisión inmediata sin trabas ni penalidades.
   * Formulario interactivo con código de trámite fehaciente `TUCO-ARR-YYYYMMDD-XXXXXX` en `arrepentimiento.php`.
3. **Propiedad Intelectual (Ley N° 11.723):**
   * Registro DNDA en trámite, staff y pie de imprenta legal en `legales.php` y `terminos.php`.
4. **Identificación Fiscal AFIP / ARCA:**
   * Badge del Formulario 960/D en el pie de página y enlace a ficha tributaria de Rodolfo Giráldez.
5. **Aviso de Cookies:**
   * Banner flotante con consentimiento persistente en `localStorage`.

---

## 6. Optimización SEO, GEO Targeting y AOI (Motores de IA)
* **GEO Targeting:** Metadatos `geo.region: AR-B`, `geo.placename: Santa Clara del Mar, Buenos Aires, Argentina`, coordenadas `-37.8083, -57.5083`, idioma `es-AR` y `hreflang`.
* **SEO Técnico:** Open Graph, Twitter Cards (`summary_large_image`), directivas para indexación de Googlebot y Bingbot.
* **AOI (Artificial Intelligence Optimization):**
  * `llms.txt` y `llms-full.txt` en la raíz para indexación estructurada por modelos de lenguaje (ChatGPT, Claude, Gemini, Perplexity).
  * `sitemap.xml` dinámico y feed `rss.xml`.
  * Marcado JSON-LD Schema.org para `NewsMediaOrganization`, `WebSite`, `NewsArticle` y `SpeakableSpecification`.

---

## 7. Arquitectura Frontend Mobile-First y Modo Claro/Oscuro
* **Mobile-First Estricto (`assets/style.css`):**
  * Base 100% fluida (320px–599px) con mejoras progresivas para tablet (600px+) y desktop (900px+).
  * Prevención de scroll horizontal no deseado (*Zero-Overflow*).
  * Barra de navegación con scroll horizontal táctil nativo (`.nav-scroll`) y categoría activa `.active`.
  * Touch targets ergonómicos de 44px mínimo (WCAG).
  * Tipografía fluida con `clamp()`.
  * Hero móvil con tarjetas secundarias en layout compacto de lista periodística horizontal (105×78 px).
* **Modo Claro y Oscuro:**
  * Directiva nativa `color-scheme: light dark;`.
  * Detección automática según el sistema operativo (`prefers-color-scheme`).
  * Botón selector interactivo `.theme-toggle` en topbar y panel de administración, persistido en `localStorage`.
  * Script síncrono anti-FOUC en `<head>`.
  * Logotipo dual nativo:
    * Modo claro: `assets/logo-editorial-tuco.png`
    * Modo oscuro: `assets/logo-editorial-tuco-dark.png` (blanco puro `#FFFFFF` con canal alfa y transparencia suave).

---

## 8. Historial de Commits Clave (GitHub `main`)
* `bb456fc`: fix(logo): implementar variante nativa en blanco puro para modo oscuro (`logo-editorial-tuco-dark.png`)
* `18cad31`: feat(theming): implementar soporte nativo de modo claro y oscuro con color-scheme, anti-fouc y selector accesible
* `9a23cc1`: feat(mobile-first): refactorizar arquitectura css mobile-first con scroll horizontal tactil, fluid typography y touch targets wcag
* `24ad4e3`: feat(seo-geo-aoi): optimización SEO técnico, GEO targeting Argentina y AOI (Schema NewsArticle, llms.txt, sitemap y rss)
* `851d03e`: feat(prod): pasar a producción, retirar leyendas de demo y reubicar acceso admin en el footer
* `74c57ab`: feat(compliance): marco legal argentino (Ley 25.326 AAIP, Ley 11.723, Res 424/2020 y 271/2020 SCI, data fiscal y cookies)
