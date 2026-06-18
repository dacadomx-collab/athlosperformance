# REPORTE_TECNICO_FINAL.md

Proyecto: Athlos Performance BCS — Landing Page
Sistema: Athlos Cognitive Engine v1.0
Fecha de cierre: 2026-06-17
Módulos cubiertos: 1-7 (Fundación → QA/Performance/Cierre)

---

## 1. Resumen Ejecutivo

La landing page de Athlos Performance BCS está **estructuralmente completa** y **validada en navegador real** (no solo por inspección de código). Stack: Next.js 16.2.7 (App Router, static export) + React 19.2.7 + TypeScript estricto + CSS nativo con variables corporativas. Sin dependencias de UI externas.

Build de producción (`pnpm build`) compila sin errores ni advertencias al cierre de este reporte.

---

## 2. Estado por Módulo

| Módulo | Estado | Nota |
| :--- | :--- | :--- |
| 1 — Fundación Frontend | ✅ Cerrado | Header, ThemeToggle, menú móvil, sistema de diseño. |
| 2 — Hero de Alta Intención | ✅ Cerrado | Copy oficial, CTAs, panel visual sin "escáner" (rediseñado a separador tipográfico). |
| 3 — Diferenciación y Metodología | ✅ Cerrado | 4 fases con video/foto real por fase, spotlight de evidencia social. |
| 4 — Segmentación Clínica | ✅ Cerrado | Switcher de tabs Atletas/Longevidad, terminología real de fichas clínicas. |
| 5 — Evidencia Social y Autoridad | ✅ Cerrado | 6/6 enlaces sociales distribuidos sin duplicar, certificaciones ISAK/McKenzie/Mulligan. |
| 6 — CTA Final y Consent Gate | ✅ Cerrado | Modal de 3 pasos, REGLA-01 aplicada, foco gestionado. |
| 7 — QA, Performance y Cierre | ✅ Cerrado (este reporte) | Auditoría de accesibilidad, contraste, overflow y dead code. |

---

## 3. Auditoría de Accesibilidad — Verificación en Navegador Real

Se construyó un script de automatización vía **Chrome DevTools Protocol** (headless Chrome + WebSocket nativo de Node 24, sin dependencias nuevas en el proyecto) para verificar comportamiento real, no solo código fuente.

### 3.1 Navegación por teclado del `ConsentLeadDialog`

| Verificación | Resultado |
| :--- | :--- |
| Clic en CTA del Hero abre el modal | ✅ `dialogExists: true` |
| El foco entra al modal automáticamente | ✅ Foco inicial en el botón de cerrar (`×`) |
| Tab cicla solo dentro del modal | ✅ Secuencia `checkbox → cerrar → checkbox → cerrar` (el botón "Continuar" está correctamente excluido del ciclo mientras está `disabled`) |
| Shift+Tab desde el primer elemento salta al último (trap circular) | ✅ Confirmado |
| Checkbox de consentimiento bloquea el avance (REGLA-01) | ✅ `Continuar` con `disabled: true` antes de marcar, `disabled: false` después |
| Avance al formulario expone exactamente 3 campos | ✅ `["text", "tel", "text"]` = `nombreCompleto`, `telefono`, `objetivoDeclarado` |
| Escape cierra el modal | ✅ Confirmado |
| El foco regresa al botón disparador tras cerrar | ✅ Foco final: "Agendar Evaluación Inicial" |

**Implementación:** `components/athlos/ConsentLeadDialog.tsx` — focus trap manual (sin librerías), captura/restauración de foco con `previousFocusRef`, bloqueo de scroll vía `body[data-dialog="open"]`.

### 3.2 Contraste de color (WCAG 2.1, mínimo 4.5:1)

**Hallazgo crítico corregido:** el acento turquesa `#00B8C9` usado como color de texto (kickers, roles del equipo, certificaciones, badges) daba **~2.5:1 en modo claro** contra el fondo casi blanco — por debajo del mínimo. Se introdujo la variable `--athlos-turquoise-text`, igual al turquesa vivo en modo oscuro (contraste real ~6.4:1) pero remapeada a `#00707D` en modo claro (contraste real ~5.5:1, confirmado leyendo `getComputedStyle` en el navegador, no solo calculado a mano).

**Segundo hallazgo (detectado solo por la verificación en navegador, no por lectura de código):** una colisión de especificidad CSS preexistente (`.section-heading p` / `.site-footer p`, specificity igual y declarada después en el archivo) anulaba silenciosamente el color turquesa de **todos** los kickers de sección y del footer-kicker desde su creación — siempre se renderizaban en `--text-secondary`, nunca en turquesa, en ambos temas. Corregido con `:not(.section-kicker)` / `:not(.footer-kicker)` en esas reglas. Confirmado en vivo: `kickerColor` pasó de `rgb(54,85,108)` a `rgb(0,112,125)` tras el fix.

**Pendiente de bajo riesgo (no corregido, documentado):** `accent-color` del checkbox y el `outline` de foco usan tonos turquesa que en casos extremos rozan el umbral 3:1 de componentes no textuales; el checkbox nativo añade su propio check blanco que compensa visualmente. No se considera bloqueante.

### 3.3 Optimización de video

`AthlosVideoPlayer` ya cumplía los 4 requisitos antes de esta auditoría: `muted`, `playsInline`, `preload="none"` (cero descarga hasta que el usuario presiona play) y `poster` (frame real extraído con `ffmpeg`, no un placeholder genérico). Sin cambios necesarios.

---

## 4. Validación Multi-dispositivo (Overflow)

Medido en navegador real (no estimado), `document.documentElement.scrollWidth` vs `clientWidth`:

| Viewport | scrollWidth | clientWidth | Resultado |
| :--- | :--- | :--- | :--- |
| 360px | 360 | 360 | ✅ Sin overflow |
| 390px | 390 | 390 | ✅ Sin overflow |
| 430px | 430 | 430 | ✅ Sin overflow |
| 768px (tablet) | 753* | 753* | ✅ Sin overflow |
| 1440px (desktop) | 1425* | 1425* | ✅ Sin overflow |

\* La diferencia entre el ancho nominal y el medido corresponde al scrollbar vertical del navegador (no es un defecto del layout); en ambos casos `scrollWidth === clientWidth`, que es la condición real de "sin overflow horizontal".

Se prestó atención especial a `.hero-visual__card` (posicionadas en esquinas), `.segment-switcher`, `.media-card`, `.evidence-spotlight__frame` y `.team-grid` — ninguno generó desbordamiento en ningún ancho probado.

---

## 5. Limpieza de Dead Code

| Acción | Detalle |
| :--- | :--- |
| `ServiceCard.tsx` eliminado | Huérfano desde el Módulo 5; autorizado explícitamente y removido junto a su CSS (`.service-grid`, `.service-card*`). |
| Variant `"ghost"` de `CtaButton` eliminado | Nunca se usó en ningún componente; se eliminó del tipo `CtaVariant` y de `.cta-button--ghost`. |
| `console.log` de depuración eliminado | `ConsentLeadDialog` ya no expone el payload por consola; el contrato queda documentado en este reporte (sección 6.2) en vez de logueado. |
| Búsqueda de `TODO`/`FIXME`/`console.*` | Cero resultados en `app/`, `components/`, `lib/`. |
| Contenido de `lib/athlosContent.ts` | Los 12 registros exportados (`ATHLOS_NAV_ITEMS`, `SEGMENT_CONTENT`, `COACHES`, etc.) están todos referenciados — ninguno es código muerto. |

### Hallazgo no corregido (decisión pendiente del Arquitecto)

- **`pnpm lint` no funciona**: no hay `eslint` ni `eslint-config-next` instalados (`package.json` solo lista `next`, `react`, `react-dom`, `typescript`). El script `lint` en `package.json` está roto desde que se generó. No se instaló nada nuevo sin autorización — se deja documentado para que decidas si se agrega.
- **Falta `.env.example`**: el Mandamiento 11 exige `.env`, `.env.example`, `.htaccess` y `api/conexion.php` como los primeros 4 archivos del proyecto. `.htaccess` y `api/conexion.php` existen; `.env.example` no se encontró en la raíz.

---

## 6. Instrucciones de Despliegue

### 6.1 Pipeline CI/CD (`.github/workflows/deploy.yml`)

Ya configurado y funcional: en cada `push` a `main`, `actions/checkout` clona el repo y `SamKirkland/FTP-Deploy-Action` sincroniza vía FTP, excluyendo `.git*`, `.github/`, `knowledge/`, `node_modules/`, `.next/`, `*.md` y `CLAUDE.md`.

**Importante (decisión ya aplicada en Módulo 6):** el pipeline despliega el repositorio fuente directamente (no corre `pnpm build` ni sube el directorio `out/`). Los 2 videos de `public/media/*.mp4` viajan porque se agregó una excepción explícita en `.gitignore`:
```
!public/media/*.mp4
```
Antes de tu primer `git push` con estos cambios, confirma con `git add -n public/media/` que los archivos aparecen como "would add" (ya verificado en este proyecto).

**Pendiente de decisión:** si en algún momento quieres que el servidor reciba el sitio ya compilado (`out/`) en vez del código fuente, el workflow necesitará un paso adicional `pnpm install && pnpm build` antes del FTP, y cambiar `server-dir`/el contenido a desplegar. Esto es un cambio de arquitectura de despliegue, no de este módulo — requiere tu aprobación explícita antes de tocar `deploy.yml` (Mandamiento 16).

### 6.2 Integración real del `ConsentLeadDialog` con el backend

**Corrección importante a un reporte previo:** al inspeccionar `api/webhook_mensajeria.php` para este cierre, confirmé que **no es un endpoint genérico para este formulario**. Esa ruta:
- Solo acepta `GET` (verificación de Meta) y `POST` con la firma `X-Hub-Signature-256` de Meta Business API.
- Espera el payload específico de WhatsApp/Instagram/Facebook (`extract_message_data()`), no `{ nombreCompleto, telefono, objetivoDeclarado }`.

Enviar el payload del formulario web directamente a esa URL **fallaría** la validación de firma o el parseo del payload.

**Payload actual del formulario** (construido en `ConsentLeadDialog.tsx`, listo en memoria pero sin destino de red configurado):
```json
{
  "nombreCompleto": "string",
  "telefono": "string",
  "objetivoDeclarado": "string",
  "consentGateStatus": "aceptado"
}
```

**Dos caminos posibles para conectar el envío real (decisión del Arquitecto, no ejecutada en este módulo):**
1. Crear un endpoint nuevo y dedicado (p. ej. `api/lead_capture.php`) que reutilice `conexion.php` y la lógica de `upsert_lead()` / Consent Gate de `webhook_mensajeria.php`, pero sin la validación de firma Meta — pensado para tráfico del sitio web, no de Meta.
2. Mantener el formulario como captura informativa y reenviar manualmente al flujo conversacional (p. ej. abrir WhatsApp con los datos prellenados) en vez de persistir directo a la base de datos.

No se creó ningún archivo PHP nuevo ni se alteró el esquema de base de datos en este módulo (Mandamiento 9: prohibido sin autorización explícita).

---

## 7. Confirmación de Build de Producción

```
$ pnpm build
✓ Compiled successfully
✓ Running TypeScript — sin errores
✓ Generating static pages (3/3)
```

El repositorio queda limpio: sin `console.log` de depuración, sin componentes huérfanos, sin variantes de CTA sin usar, sin CSS muerto. El sitio está listo para revisión final humana y despliegue.

---

## 8. Addendum — Módulo 7.1: Refinamiento Post-Auditoría Visual Humana

La revisión automatizada del Módulo 7 (overflow, foco, contraste calculado matemáticamente) no detectó un bug real que la auditoría visual humana sí encontró: capturas de pantalla mostraban texto "casi invisible" en el footer y las cards de certificaciones en ciertas condiciones.

**Causa raíz identificada y confirmada con capturas de pantalla antes/después:** el fondo del `<body>` usa un degradado `linear-gradient(135deg, var(--page-bg), var(--athlos-deep-blue))`. `--page-bg` cambia con el tema, pero `--athlos-deep-blue` es **fijo** (siempre `#0E3A5D`). En una página larga, hacia el final del degradado (footer, certificaciones) el color se acerca al extremo oscuro fijo — incluso en modo claro. El texto en modo claro usa colores oscuros (diseñados para fondo claro), resultando en texto oscuro sobre fondo oscuro exactamente donde el degradado se oscurece.

**Fix:** nueva variable `--body-gradient-end`, igual a `--athlos-deep-blue` en modo oscuro (sin cambio visual) y `#E7EFF4` (claro) en modo claro. Verificado: captura de pantalla del footer antes (texto ilegible) y después (texto perfectamente legible) del fix.

**Las otras 3 peticiones de este refinamiento:**
- Videos sociales ahora se embeben de verdad (Instagram `/embed`, Facebook Video Plugin) detrás de un patrón facade — cero descarga de red hasta que el usuario hace clic.
- Botón flotante "Volver Arriba" añadido y verificado funcionalmente (aparece tras 600px de scroll, regresa a `scrollY: 0` con animación suave).
- Sección de Contacto nueva antes del Footer (dirección + Google Maps + WhatsApp); el enlace "Contacto" del footer ya no es huérfano.

Build de producción re-confirmado sin errores tras este refinamiento.
