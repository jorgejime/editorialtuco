# Mi Diario — Portal de noticias autoadministrable (demo)

Portal de noticias online, mobile-first, estilo economist.com. PHP + SQLite, sin frameworks ni base de datos externa: se instala copiando los archivos a cualquier hosting con PHP.

**Demo en vivo:** https://caro.liaa.cloud

## Características

- Portada con noticia destacada, secciones por categoría, buscador y página de noticia individual.
- Panel de administración en `/admin/` (crear, editar, eliminar noticias y categorías, subir imágenes, destacar portada).
- SEO básico: URLs amigables (slugs), meta tags Open Graph, sitemap implícito.
- Imágenes de reemplazo automáticas (SVG) cuando la noticia no tiene foto.
- Todo el contenido de ejemplo está en español rioplatense (voseo), listo para reemplazar.

## Requisitos

- PHP 8.0+ con extensiones `pdo_sqlite` y `mbstring`.
- La base de datos SQLite se crea sola en `data/portal.db` en la primera visita (incluye 7 noticias de ejemplo).

## Instalación

1. Copia todos los archivos a la raíz pública del hosting (ej. `public_html/caro/`).
2. Asegúrate de que el servidor web pueda escribir en `data/` y `uploads/`.
3. Abre el sitio. Listo.

## Panel de administración

- URL: `/admin/` (ej. `https://tu-dominio.com/admin/`)
- Usuario demo: `admin`
- Clave demo: `demo2026`
- **En producción cambia estas credenciales en `inc/config.php`.**

## Estructura

```
index.php            Portada
noticia.php          Noticia individual
categoria.php        Listado por categoría
buscar.php           Buscador
inc/                 Configuración, header y footer compartidos
admin/               Panel de administración
assets/style.css     Estilos (mobile-first)
data/                Base SQLite (se genera sola) + .htaccess de protección
uploads/             Imágenes subidas desde el panel
subir.py             Script de despliegue por TUS a Hostinger (uso interno)
```

## Licencia

Código de demostración preparado como propuesta comercial. Todos los derechos reservados.
