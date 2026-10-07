# Editorial Tucó — Portal de noticias digital

Portal de noticias y contenidos periodísticos online, mobile-first, para **Editorial Tucó** (`https://editorialtuco.com`). Desarrollado en PHP + SQLite con compliance normativo completo para la República Argentina.

**Sitio en producción:** https://editorialtuco.com

## Características

- Portada periodística con noticia destacada, secciones por categoría, buscador en vivo y páginas de artículo individuales.
- Panel de administración y redacción en `/admin/` (crear, editar, eliminar noticias y categorías, subir imágenes y gestionar publicaciones).
- SEO técnico: URLs amigables (slugs), meta tags Open Graph, imágenes adaptativas.
- **Compliance normativo para la República Argentina:**
  - Ley N° 25.326 de Protección de los Datos Personales (derechos ARCO y leyenda obligatoria de la Agencia de Acceso a la Información Pública - AAIP).
  - Defensa del Consumidor (Ley N° 24.240, Resolución 424/2020 SCI - Botón de Arrepentimiento, Resolución 271/2020 SCI - Botón de Baja de Servicios).
  - Régimen de Medios Periodísticos y Propiedad Intelectual (Ley N° 11.723, Staff y Pie de Imprenta formal).
  - Identificación Fiscal (AFIP / ARCA - Formulario 960/D Data Fiscal).
  - Aviso de consentimiento de cookies y privacidad con persistencia local.

## Requisitos del Servidor

- PHP 8.0+ con extensiones `pdo_sqlite` y `mbstring`.
- Base de datos SQLite gestionada automáticamente en `data/portal.db`.
- Permisos de escritura para el usuario web en los directorios `data/` y `uploads/`.

## Panel de Administración

- URL: `/admin/` (`https://editorialtuco.com/admin/`)
- Gestión de artículos, categorías y carga de imágenes.
- Las credenciales maestras se configuran en `inc/config.php`.

## Estructura de Archivos

```
index.php            Portada principal
noticia.php          Visualización de noticia individual
categoria.php        Listado de noticias por categoría
buscar.php           Buscador de contenidos
terminos.php         Términos y condiciones de uso (Ley 11.723 / Jurisdicción PBA)
privacidad.php       Política de privacidad y datos personales (Ley 25.326 / AAIP)
arrepentimiento.php  Botón de Arrepentimiento (Res. 424/2020) y Baja (Res. 271/2020)
legales.php          Staff, ficha técnica editorial y pie de imprenta
inc/                 Configuración general, header y footer compartidos
admin/               Panel de redacción y administración
assets/style.css     Hoja de estilos responsiva
assets/logo-editorial-tuco.png  Logo oficial de Editorial Tucó
assets/favicon.png   Favicon institucional
data/                Base de datos SQLite y logs de trámites
uploads/             Imágenes subidas desde el panel
```

## Licencia

Editorial Tucó. Todos los derechos reservados.
