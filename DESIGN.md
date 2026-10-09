# Sistema de Diseño y Especificación de UI · Editorial Tucó
**Documento de Gobernanza Frontend (Metodología Refero / DESIGN.md)**  
**Módulo:** Canvas de Redacción Editorial y Editor WYSIWYG (`admin/editar.php`)  
**Versión:** 1.0.0 · Octubre 2026

---

## 1. Arquetipo Visual y Filosofía de Diseño
* **Arquetipo:** *Editorial Warm Paper & Microsoft Word Document Canvas*.
* **Metáfora Conceptual:** El redactor periodístico no interactúa con campos de base de datos dispersos ni formularios genéricos, sino con una **hoja de papel físico sobre un escritorio de redacción**.
* **Objetivos de Experiencia (UX):**
  1. **Inmersión sin distracciones:** Foco absoluto en la escritura, jerarquía tipográfica y legibilidad.
  2. **Metáfora táctil de procesador de texto:** Cinta de herramientas superior anclada (*sticky toolbar*) con feedback visual inmediato, atajos de teclado estándar (`Ctrl+B`, `Ctrl+I`, `Ctrl+U`, `Ctrl+Z`, `Ctrl+Y`) y contador de palabras/tiempo de lectura en pie de página.
  3. **Control editorial periférico:** Metadatos (categoría, bajada periodística, imagen de portada y conmutadores de publicación) organizados en un panel inspector lateral o superior colapsable que no interrumpe el flujo cognitivo de la redacción.
  4. **Modo Claro y Oscuro Nativo:** Escritorio gris perla con hoja blanca nítida en modo claro; escritorio carbón mate con hoja grafito profundo y texto de alto contraste en modo oscuro.

---

## 2. Jerarquía Cromática y Tokens Semánticos

### 2.1. Tokens en Modo Claro (`[data-theme="light"]`)
| Token CSS | Valor | Propósito Semántico |
|---|---|---|
| `--desk-bg` | `#eef0f3` | Escritorio de trabajo neutro perla (fondo envolvente) |
| `--paper-bg` | `#ffffff` | Hoja de papel Word (canvas central de redacción) |
| `--paper-line` | `#dcdfe4` | Borde hairline del contorno de la hoja |
| `--paper-shadow` | `0 1px 3px rgba(0,0,0,0.06), 0 8px 24px rgba(0,0,0,0.05)` | Elevación física tridimensional de la hoja de papel |
| `--toolbar-bg` | `rgba(255, 255, 255, 0.95)` | Cinta de herramientas con efecto translucido (*glassmorphism*) |
| `--toolbar-border` | `#d5d8de` | Delimitador hairline de la cinta de herramientas |
| `--toolbar-btn-hover` | `#f1f3f7` | Estado hover de botones de formato |
| `--toolbar-btn-active` | `#e2e6ed` | Estado activo (botón presionado / formato aplicado) |
| `--doc-ink` | `#1a1a1a` | Tinta de texto del documento (negro editorial óptimo) |
| `--doc-muted` | `#6b7280` | Metadatos, atajos y texto auxiliar |
| `--accent` | `#0b2641` | Acento identitario azul institucional de Editorial Tucó (acciones primarias) |
| `--accent-hover` | `#163e66` | Hover del botón de guardar / publicar |

### 2.2. Tokens en Modo Oscuro (`[data-theme="dark"]`)
| Token CSS | Valor | Propósito Semántico |
|---|---|---|
| `--desk-bg` | `#0d0f12` | Escritorio carbón profundo |
| `--paper-bg` | `#16191f` | Hoja de documento en grafito satinado |
| `--paper-line` | `rgba(255, 255, 255, 0.08)` | Borde hairline de baja reflectancia |
| `--paper-shadow` | `0 4px 20px rgba(0,0,0,0.6), 0 1px 3px rgba(0,0,0,0.4)` | Elevación en penumbra |
| `--toolbar-bg` | `rgba(22, 25, 31, 0.92)` | Cinta de herramientas acrílica oscura |
| `--toolbar-border` | `rgba(255, 255, 255, 0.12)` | Delimitador hairline de barra |
| `--toolbar-btn-hover` | `rgba(255, 255, 255, 0.08)` | Hover de botones de formato |
| `--toolbar-btn-active` | `rgba(255, 255, 255, 0.16)` | Estado activo de formato |
| `--doc-ink` | `#f0ede6` | Tinta del documento en blanco marfil de baja fatiga visual |
| `--doc-muted` | `#9ca3af` | Metadatos y etiquetas secundarias |
| `--accent` | `#58a6ff` | Acento azul luminoso calibrado para alta legibilidad en dark mode |
| `--accent-hover` | `#7cb5f5` | Hover de acción primaria |

---

## 3. Tipografía y Métricas del Canvas

* **Titular del Documento (`h1` / Titular periodístico):**
  * Fuente: `Georgia, "Times New Roman", Times, serif`
  * Tamaño: `clamp(26px, 3.2vw, 36px)`
  * Peso: `700`
  * Line-height: `1.25`
  * Espaciado inferior: `16px`
* **Cuerpo del Documento (`contenteditable` / Hoja Word):**
  * Fuente: `Georgia, Cambria, "Times New Roman", serif`
  * Tamaño: `18px`
  * Line-height: `1.72` (optimizado para ritmo de lectura periodístico continuo)
  * Párrafos: margen inferior `1.2em`
* **Subtítulos y Jerarquías Internas:**
  * `H2`: `24px`, negrita, margen superior `1.8em`, margen inferior `0.6em`.
  * `H3`: `20px`, negrita, margen superior `1.4em`, margen inferior `0.5em`.
  * `Blockquote`: Borde izquierdo `3px solid var(--accent)`, padding `12px 20px`, estilo itálico, fondo sutil.
  * `Listas`: Sangría estándar de procesador de texto (`padding-left: 28px`), viñetas o numeración limpia.
* **Interfaz de Control e Inspector (UI):**
  * Fuente: `system-ui, -apple-system, "Segoe UI", Roboto, sans-serif`
  * Tamaño: `13px` a `14px`
  * Peso: `500` / `600`

---

## 4. Arquitectura de Componentes de la Interfaz

```
+-------------------------------------------------------------------------------+
| TOPBAR ADMIN: Editorial Tucó · Panel  |  Noticias  |  Categorías  |  Modo  | Salir |
+-------------------------------------------------------------------------------+
| BARRA DE ACCIÓN Y ESTADO (Sticky Ribbon):                                     |
| [ Deshacer | Rehacer ] [ B | I | U ] [ H2 | H3 ] [ • | 1. ] [ “ ” ] [ 🔗 ]    |
| [ Guardar Noticia ] [ Cancelar ]                                              |
+-------------------------------------------------------------------------------+
|                                                                               |
|  ESCRITORIO VIRTUAL (--desk-bg)                                                |
|                                                                               |
|      +----------------------- HOJA DE WORD -----------------------+           |
|      |                                                            |           |
|      |  Kicker / Categoría: [ Política                  v ]       |           |
|      |                                                            |           |
|      |  [ Título principal de la noticia (H1)                   ] |           |
|      |                                                            |           |
|      |  [ Bajada / Resumen periodístico introductorio...        ] |           |
|      |  --------------------------------------------------------- |           |
|      |                                                            |           |
|      |  CUERPO DEL ARTÍCULO (WYSIWYG Canvas):                     |           |
|      |  Aquí el redactor escribe con total libertad de formato.   |           |
|      |  Puede aplicar **negrillas**, *cursivas*, subrayados,      |           |
|      |  subtítulos H2/H3, viñetas y citas textuales.              |           |
|      |                                                            |           |
|      |                                                            |           |
|      +------------------------------------------------------------+           |
|      | STATUS BAR: 482 palabras · 2 min de lectura · Borrador     |           |
|      +------------------------------------------------------------+           |
|                                                                               |
|      +------------------- PANEL DE PUBLICACIÓN -------------------+           |
|      |  Foto de portada: [ Seleccionar archivo ] [ Quitar foto ]   |           |
|      |  [✓] Noticia destacada en portada   [✓] Publicada en vivo  |           |
|      +------------------------------------------------------------+           |
|                                                                               |
+-------------------------------------------------------------------------------+
```

---

## 5. Protocolo de Seguridad y Renderizado (XSS Prevention)
El contenido generado por el canvas contiene HTML semántico limpio. La función de sanitización en el backend (`sanitizar_html_noticia()`):
* **Etiquetas permitidas:** `<p>`, `<strong>`, `<b>`, `<em>`, `<i>`, `<u>`, `<h3>`, `<h4>`, `<ul>`, `<ol>`, `<li>`, `<blockquote>`, `<a>`, `<hr>`, `<br>`.
* **Atributos permitidos:** `<a>` únicamente `href` (validando esquemas `http://`, `https://`, `mailto:`), `target="_blank"`, `rel="noopener noreferrer"`.
* **Etiquetas desinfectadas/bloqueadas:** `<script>`, `<iframe>`, `<object>`, `<embed>`, `<form>`, `<style>`, atributos `on*` (onclick, onerror, onload, etc.).
* **Compatibilidad retroactiva:** Las notas existentes que fueron guardadas como texto plano sin etiquetas HTML se siguen renderizando con párrafos automáticos `nl2br(e($par))`.
