# ROADMAP_DESARROLLO.md

Proyecto: Athlos Performance BCS Landing Page
Fecha de diagnóstico: 2026-06-17
Modo de trabajo: Modular, Mobile-First, Fricción Cero

---

## 0. Diagnóstico Ejecutivo

### Objetivo de la landing

Construir una landing page premium para posicionar Athlos Performance BCS como un Sport Science Lab en La Paz, BCS, convirtiendo tráfico orgánico en prospectos de alto valor mediante CTAs de baja fricción conectados al flujo conversacional del Athlos Cognitive Engine.

### Principios obligatorios detectados

- Mobile-First absoluto; sin anchos fijos en contenedores principales.
- Modo día/noche con toggle nativo y contraste WCAG 2.1 mínimo 4.5:1.
- Estética clínica/deportiva: ciencia, datos, precisión, movimiento humano; no clichés de gimnasio.
- Consent Gate obligatorio antes de procesar datos sensibles de salud.
- CTA primario debe abrir flujo conversacional/onboarding y no persistir datos clínicos sin consentimiento explícito.
- Nombres técnicos nuevos deben registrarse en `knowledge/02_SYSTEM_CODEX_REGISTRY.md` al cerrar cada hito.
- Cada hito debe terminar con validación, reporte técnico y documentación viva cuando aplique.

### Inconsistencias o riesgos detectados

- `CLAUDE.md` define Frontend Matrix como Next.js / React + pnpm, mientras `knowledge/00_ADN_DEL_PROYECTO.md` conserva una mención legacy a HTML/CSS/JavaScript nativo. Decisión técnica propuesta: usar Next.js / React por ser la directriz más reciente del manual operativo residente.
- El proyecto contiene archivos locales confidenciales excluidos del flujo de desarrollo. No deben referenciarse desde frontend, commits publicos ni reportes compartibles.
- `assets/img/logo2.png` corresponde a AXON_DCD, no a Athlos Performance. Se conserva como asset técnico, pero no debe usarse como marca principal de la landing comercial.

---

## 1. Inventario de Assets Renombrados

Los archivos de `assets/img` fueron renombrados para SEO, semántica y mantenibilidad.

| Uso sugerido | Nombre actual |
| --- | --- |
| Logo principal / header / footer | `athlos-performance-logotipo-circular.jpg` |
| Marca técnica secundaria, no comercial | `axon-dcd-logotipo-tecnologia.png` |
| Staff / autoridad humana | `arturo-naranjo-coach-athlos-performance.jpg` |
| Staff / autoridad humana | `bernardo-lobo-coach-athlos-performance.jpg` |
| Staff / autoridad humana | `luis-moctezuma-coach-athlos-performance.jpg` |
| Staff / autoridad humana | `roberto-lopez-coach-athlos-performance.jpg` |
| Video hero alternativo / fuerza controlada | `entrenamiento-fuerza-controlada-laboratorio-athlos.mp4` |
| Video segmento atletas / potencia tren inferior | `evaluacion-potencia-tren-inferior-atletas-athlos.mp4` |
| Video metodología / prescripción de carga | `prescripcion-carga-mecanica-entrenamiento-athlos.mp4` |

### Criterio visual de uso

- El hero no debe apoyarse únicamente en máquinas o pesas; se construirá como laboratorio visual con overlays de datos, métricas y micro-interfaces para transformar material físico en narrativa científica.
- Los portraits de coaches se usarán en autoridad humana o footer institucional, no como hero principal.
- Los videos de ejercicio local funcionan mejor como fondos sutiles con capa de análisis visual, no como clips promocionales crudos.

---

## 2. Estrategia de Medios Externos

Fuente: `knowledge/Links_Redes_socailes.txt`

### Enlaces disponibles

- Facebook Reel 1: `https://www.facebook.com/share/r/1FkpXU2h2v/`
- Facebook Reel 2: `https://www.facebook.com/share/r/1D6BnnZR3F/`
- Instagram Reel 1: `https://www.instagram.com/reel/DY1CrjOzZUC/?utm_source=ig_web_button_share_sheet&igsh=MzRlODBiNWFlZA==`
- Instagram Reel 2: `https://www.instagram.com/reel/DYxBlVyN9tl/?utm_source=ig_web_button_share_sheet&igsh=MzRlODBiNWFlZA==`
- Instagram Post: `https://www.instagram.com/p/DX7OY33Ac31/?utm_source=ig_web_button_share_sheet&igsh=MzRlODBiNWFlZA==`
- Instagram Reel 3: `https://www.instagram.com/reel/DXsJZEfkhC3/?utm_source=ig_web_button_share_sheet&igsh=MzRlODBiNWFlZA==`

### Ubicación estratégica propuesta

- Hero: no embeber redes aquí. El hero debe cargar rápido y controlar narrativa; usar video local optimizado o composición CSS.
- Sección "Evidencia en movimiento": insertar cards enlazables a Instagram/Facebook con `target="_blank"` y `rel="noopener noreferrer"`, sin embeds pesados iniciales. Esto mejora performance móvil.
- Sección "Autoridad Científica": usar 1 reel/post como prueba social contextual, presentado como "mira cómo evaluamos/movemos", no como feed genérico.
- CTA final: incluir enlaces sociales compactos para confianza secundaria, sin competir con el CTA principal de onboarding.

### Decisión de performance

No usar embeds nativos de Instagram/Facebook en primera carga. Se recomienda lazy-load bajo interacción o abrir en nueva pestaña. Los embeds sociales suelen penalizar Core Web Vitals en móvil.

---

## 3. Arquitectura Propuesta

### Stack operativo

- Next.js / React
- pnpm
- Componentes Mobile-First
- CSS variables para paleta corporativa
- Theme toggle con persistencia en `localStorage`
- CTA conversacional preparado para conectar con `api/webhook_mensajeria.php` o widget/formulario intermedio

### Estructura modular sugerida

```txt
app/
  layout.tsx
  page.tsx
components/
  athlos/
    AthlosHeader.tsx
    ThemeToggle.tsx
    MobileMenu.tsx
    HeroSection.tsx
    DifferentiationSection.tsx
    MethodologyTimeline.tsx
    EvaluationSplitSection.tsx
    SegmentedSolutions.tsx
    EvidenceMediaSection.tsx
    AuthoritySection.tsx
    FinalCtaSection.tsx
    ConsentLeadDialog.tsx
lib/
  athlosContent.ts
  socialLinks.ts
styles/
  athlos-theme.css
```

### Diseño visual

- Fondo hero: azul profundo `#0E3A5D` con gradientes radiales, líneas de análisis biomecánico y cards flotantes de métricas.
- Acento CTA: turquesa `#00B8C9`.
- Apoyo visual: azul acero `#3F6E8A`, grises clínicos, blanco frío, modo oscuro con contraste controlado.
- Tipografía sugerida: Sora para titulares, Inter para cuerpo. Montserrat puede usarse en micro-labels si ya está cargada.

---

## 4. Checklist Maestro por Módulos

### Módulo 1 - Fundación Frontend y Sistema Visual

- [x] Verificar si existe `package.json`; si no existe, proponer scaffold Next.js sin tocar backend crítico.
- [x] Crear tokens visuales: colores, spacing fluido, radios, sombras, estados light/dark.
- [x] Implementar `ThemeToggle` con persistencia.
- [x] Implementar `AthlosHeader` Mobile-First.
- [x] Implementar menú hamburguesa premium con animación suave, focus trap básico y `aria-expanded`.
- [x] Registrar componentes nuevos en `knowledge/02_SYSTEM_CODEX_REGISTRY.md`.
- [x] Validar que no hay anchos fijos en contenedores principales.

Punto de control: DETENERSE y presentar captura/estructura antes de construir secciones de conversión.

### Módulo 2 - Hero de Alta Intención

- [x] Construir `HeroSection` con H1/H2 exactos del handoff.
- [x] Integrar CTA primario "Agendar Evaluación Inicial".
- [x] Integrar CTA secundario "Conoce Nuestra Metodología" con anclaje interno.
- [x] Usar video local o composición visual científica sin penalizar móvil.
- [x] Añadir micro-métricas visuales: movilidad, fuerza, riesgo, seguimiento.
- [x] Asegurar fallback sin video para usuarios con `prefers-reduced-motion`.

Punto de control: DETENERSE para validar impacto visual, copy y claridad de segmentación.

**CIERRE FORMAL MÓDULO 2 (2026-06-17):** `prefers-reduced-motion: reduce` reforzado en `styles/athlos-theme.css` para congelar explícitamente `.hero-visual__scanline` (además de la regla global ya existente que neutraliza todas las animaciones/transiciones). Build de producción validado sin errores. Módulo 2 cerrado.

### Módulo 3 - Diferenciación y Metodología Científica

- [x] Construir `DifferentiationSection` con copy definitivo.
- [x] Construir `MethodologyTimeline` en 4 fases.
- [x] Usar iconografía minimalista, no estética de gimnasio.
- [x] En móvil, timeline vertical; en escritorio, cards o flujo horizontal.
- [x] Añadir animaciones discretas de entrada y estados hover accesibles.

Punto de control: DETENIDO para revisar legibilidad y jerarquía narrativa desde el navegador.

### Módulo 4 - Segmentación Clínica: Menores 65 / Mayores 65

- [x] Construir `EvaluationSplitSection` con switcher de tabs accesible (`role="tablist"`) en lugar de columnas estáticas, por decisión explícita de UX del Arquitecto.
- [x] Columna menores de 65: antropometría, Fórmula de Siri, Rocha, Somatocarta, patrones de movimiento + terminología real de periodización (`Mesociclo`, `Microciclo`, `Sesión`) extraída de `Menor_65_03 Ficha plan de sesion.xlsx`.
- [x] Columna mayores de 65: Senior Fitness Test, Chair Stand, TUG, equilibrio dinámico, independencia + disclaimer "prescripción clínica, no rutina genérica".
- [x] Construir `SegmentedSolutions` con bloques "Alto Rendimiento" y "Longevidad Clínica".
- [x] Evitar promesas médicas absolutas; usar lenguaje de evaluación y seguridad.
- [x] Integrar 2 cards de evidencia social lazy-loaded (Instagram en Atletas, Facebook en Longevidad), `target="_blank"` + `rel="noopener noreferrer"`, sin embeds pesados.
- [x] CTA de cada panel apunta a `#consent-gate` (mismo ancla usada en el Hero, pendiente de materializar como `ConsentLeadDialog` en Módulo 6).

Punto de control: DETENIDO para validar sensibilidad médica y precisión del mensaje desde el navegador.

**Nota técnica:** No existen en `assets/img` fotografías de evaluaciones antropométricas o de Ficha Senior Fitness Test; solo logos, retratos de coaches y 3 videos locales de entrenamiento. Se usó visualización de datos (chips de métricas) en vez de fotografía clínica para mantener coherencia con la estética "Sport Science Lab" ya aprobada en el Hero, evitando fotografía genérica o de stock no autorizada.

### Módulo 5 - Evidencia Social y Autoridad

- [x] Construir `EvidenceSection` (consolida `EvidenceMediaSection` + `AuthoritySection`) con enlaces sociales externos optimizados.
- [x] Usar lazy links/cards, no embeds pesados en primera carga.
- [x] Bloque de certificaciones (ISAK, McKenzie, Mulligan) con copy del handoff.
- [x] Staff portraits ya integrados como prueba humana en `TeamSection` (módulo previo), sin convertirlos en sección egocéntrica.
- [x] Todos los enlaces externos usan `target="_blank"` y `rel="noopener noreferrer"`.

**Nota de datos:** de los 6 enlaces en `Links_Redes_socailes.txt`, 3 ya estaban asignados a Módulos 3-4 (`DIFFERENTIATION_SPOTLIGHT`, `SOCIAL_EVIDENCE_LINKS`). Solo quedaban **3 enlaces disponibles** para este módulo, no 4 — se usaron los 3 reales (`EVIDENCE_LINKS`), agotando el archivo fuente. No se inventó un cuarto enlace.

**Consolidación de flujo confirmada:** Hero → Diferenciación → Metodología → Equipo → Evaluación clínica (Módulo 4) → Evidencia Social y Autoridad (Módulo 5) → Footer. El CTA Final/Consent Gate (Módulo 6) se conecta vía el ancla `#consent-gate` ya usada en todos los CTA existentes; el diálogo en sí aún no se construye.

**Limpieza de dead code:** se retiró de `app/page.tsx` el bloque placeholder "Arquitectura de servicio" (Módulo 1) que duplicaba contenido ya cubierto por `EvaluationSplitSection`/`SegmentedSolutions`. `ServiceCard.tsx` quedó huérfano (sin imports) — el sistema de permisos bloqueó su borrado físico por no haber instrucción explícita; queda pendiente de tu autorización para eliminarlo.

Punto de control: DETENIDO para validar autoridad real, certificaciones y los 3 videos/reels usados.

### Módulo 6 - CTA Final y Consent Gate UX

- [x] Construir `FinalCtaSection` (Sección 7 del handoff, fondo fijo `#0E3A5D`, botones `#00B8C9`).
- [x] Construir `ConsentLeadDialog` como stub funcional: modal de 3 pasos (consentimiento → formulario → confirmación).
- [x] Capturar solo variables iniciales permitidas: `nombreCompleto`, `telefono`, `objetivoDeclarado`.
- [x] No persistir ni enviar datos clínicos sensibles antes de aceptación explícita: el paso `form` es inalcanzable sin marcar el checkbox obligatorio de consentimiento (REGLA-01).
- [x] Preparar payload alineado con contratos existentes si se integra backend: `{ nombreCompleto, telefono, objetivoDeclarado, consentGateStatus: "aceptado" }`, JSON UTF-8, listo para `api/webhook_mensajeria.php`.
- [x] Mensajes claros: evaluación profesional, no diagnóstico online (disclaimer en el paso `form`).
- [x] CtaButton: cualquier CTA con `href="#consent-gate"` ahora abre el modal (`ConsentGateProvider` global en `app/layout.tsx`) en vez de navegar — cubre Hero, ambos paneles de `SegmentedSolutions` y `FinalCtaSection` con una sola regla, sin tocar componente por componente.
- [x] CTA secundario de `FinalCtaSection` abre WhatsApp directo del staff médico (`wa.me/52<telefono>`).
- [x] Limpieza: `ServiceCard.tsx` y su CSS (`.service-grid`, `.service-card*`) eliminados — autorizado explícitamente por el Arquitecto.

**Pendiente de decisión humana (fuera de alcance de este módulo):** el payload del lead solo se loguea en consola (`console.log`) como stub — no hay integración real con `api/webhook_mensajeria.php` todavía, ya que este proyecto Next usa `output: "export"` (sin API routes server-side). Conectar el envío real requiere definir el endpoint HTTP que la PHP API expondrá para este formulario.

Punto de control: DETENIDO para validar legalidad, copy y comportamiento del CTA antes de Módulo 7.

### Módulo 7 - Performance, Accesibilidad y QA

- [ ] Ejecutar build.
- [x] Auditar navegación por teclado: verificado en navegador real vía CDP (focus trap, Tab/Shift+Tab circular, Escape, restauración de foco) en `ConsentLeadDialog`.
- [x] Validar contraste light/dark: corregido `--athlos-turquoise-text` (2.5:1 → 5.5:1 en modo claro) + bug de especificidad CSS preexistente que anulaba el color de todos los `.section-kicker`/`.footer-kicker`.
- [x] Validar mobile-first en anchos 360px, 390px, 430px, tablet y desktop: 0 overflow horizontal medido (`scrollWidth === clientWidth`) en los 5 anchos.
- [x] Optimizar videos: `preload="none"`, poster real (`ffmpeg`), `playsInline`, `muted` — ya cumplido desde Módulo 3-4, confirmado sin cambios.
- [x] Revisar dead code, imports y assets no usados: `ServiceCard.tsx`, variant `ghost`, `console.log` de debug eliminados; 0 `TODO`/`FIXME` restantes.
- [x] Generar reporte técnico de hito: `REPORTE_TECNICO_FINAL.md` en la raíz del proyecto.

**Hallazgo fuera de alcance, documentado para decisión humana:** `api/webhook_mensajeria.php` no es un endpoint apto para recibir el payload del `ConsentLeadDialog` (valida firma Meta y espera payload de Meta, no JSON del formulario web). Ver sección 6.2 de `REPORTE_TECNICO_FINAL.md` para las dos rutas de integración propuestas. No se creó código backend nuevo sin autorización (Mandamiento 9).

Punto de control: MÓDULO 7 CERRADO. Build de producción confirmado sin errores. Listo para revisión humana final y despliegue.

---

## 5. Pre-Code Checklist

- [x] Knowledge base leída: handoff, mandamientos, codex, contratos, protocolos, guardrails, estrategia IA, roadmap previo y carpetas segmentadas.
- [x] Assets multimedia escaneados visualmente.
- [x] Assets renombrados con nomenclatura semántica.
- [x] Links sociales analizados y ubicados estratégicamente.
- [x] Inconsistencias documentadas.
- [ ] Validación humana del roadmap antes de iniciar Módulo 1.

---

## 6. Decisión Pendiente de Validación

Recomiendo iniciar con Módulo 1 usando Next.js / React, porque `CLAUDE.md` lo declara como frontend vigente. Si el Arquitecto decide mantener la directriz legacy de HTML/CSS/JS nativo, este roadmap se ajusta antes de escribir componentes.

**ACTUALIZACIÓN VISUAL (2026-06-17):** Por instrucción directa del Arquitecto, se elevó el nivel visual de Módulos 3 y 4 antes de avanzar a Módulo 5:
- `DifferentiationSection` ahora incluye bloque `evidence-spotlight` con iframe lazy (Instagram Reel 2) de espacio significativo.
- `MethodologyTimeline` reemplaza el icono aislado por media real por fase: `AthlosVideoPlayer` (videos locales de evaluación y prescripción) en fases 01/03, retratos de coach con overlay azul 40% en fases 02/04.
- Nuevo componente `AthlosVideoPlayer` con poster, botón play custom y tratamiento duotono azul.
- **Riesgo detectado y mitigado:** los frames extraídos de los videos locales muestran un gimnasio convencional con señalética genérica (contradice la regla "prohibido imágenes de gimnasio tradicionales" del handoff). Se aplicó `grayscale + saturate + tinte azul #0E3A5D al 40% en mix-blend-mode: color` para abstraer el fondo y alinearlo a la estética de laboratorio. Recomendación pendiente de validación humana: regrabar este material en el entorno real de Athlos cuando sea posible.
- **Gap de despliegue detectado:** `*.mp4` está excluido de Git por `.gitignore`; los videos en `public/media/` no viajarán por el pipeline `deploy.yml` actual. Requiere decisión humana sobre subida manual/FTP directa o ajuste del pipeline.

Estado actual: MODULOS 1-7 COMPLETOS. QA, ACCESIBILIDAD Y PERFORMANCE VALIDADOS EN NAVEGADOR REAL. PENDIENTE UNICAMENTE: decision humana sobre integracion backend real del lead (ver REPORTE_TECNICO_FINAL.md seccion 6.2) y, opcionalmente, `.env.example` + configuracion de ESLint.

---

## 8. Módulo 7.1 — Refinamiento Post-Auditoría Visual Humana (2026-06-17)

La auditoría visual humana detectó 4 problemas que la verificación automatizada del Módulo 7 no cubrió (texto ilegible en zonas específicas, UX de videos sociales, navegación larga sin retorno rápido, enlace de contacto huérfano):

- [x] **Bug crítico de contraste corregido:** el degradado de `body` mezclaba un color de tema variable con `var(--athlos-deep-blue)` fijo, generando zonas oscuras inesperadas en modo claro (footer, certificaciones) donde el texto se volvía casi invisible. Nueva variable `--body-gradient-end`, theme-aware. Verificado antes/después con capturas de pantalla reales.
- [x] **Embebido real de Instagram/Facebook:** `EvidenceSection` ahora usa `SocialEmbedFacade` — póster con botón play que monta un `<iframe loading="lazy">` real solo al hacer clic (patrón facade, cero peso de red hasta interacción).
- [x] **Botón "Volver Arriba":** `BackToTop`, FAB fijo inferior-derecha, aparece tras 600px de scroll, `scrollTo({behavior:"smooth"})`, optimizado para táctil (52px, `env(safe-area-inset-*)`).
- [x] **Sección de Contacto resuelta:** `ContactSection` (`#contacto`) con dirección + enlace a Google Maps + CTA de WhatsApp, antes del Footer. El enlace "Contacto" del footer ahora apunta a `#contacto` (antes `mailto:`, huérfano respecto a la estrategia SPA).

Todo verificado empíricamente en navegador real (Chrome headless vía CDP): overflow en 5 anchos sin regresión, focus trap del modal sin regresión, facade→iframe confirmado por DOM, FAB confirmado funcionalmente, contraste confirmado con capturas antes/después.

**Hallazgo adicional sin corregir (fuera de alcance de esta petición):** el nav item "Programas" (`#programas`) también es un ancla huérfana — no existe ninguna sección con ese id. Mismo patrón que tenía "Contacto". Pendiente de decisión del Arquitecto.

Build de producción confirmado sin errores tras todos los cambios.

**AJUSTE DE PULIDO VISUAL Y TECNICO (2026-06-17, segunda pasada):**
- Hero: eliminado el elemento `hero-visual__scanline` ("escáner") y su `@keyframes scan` (dead code). Sustituido por separador tipográfico: línea vertical de 1px `#3F6E8A` (`hero__title-group::before`) conectando H1 y subtítulo.
- Nueva sección `TeamSection` (`#staff`) debajo de `MethodologyTimeline`: grid de 3 coaches (Bernardo Lobo, Luis Moctezuma, Arturo Naranjo), foto cuadrada sin filtro pesado, `border-radius: 8px`, pie de foto limpio + expertise a 2 líneas.
- **Corrección de dato:** el Arquitecto solicitó "Arturo Sánchez"; el asset y registros disponibles corresponden a **Arturo Naranjo** (`arturo-naranjo-coach-athlos-performance.jpg`, confirmado además como `ASESOR` en `Menor_65_02 DATOS ANTROMPOMETRÍA ATHLOS.xlsx`). Se usó el nombre real en vez de inventar una persona no registrada — Mandamiento 4 (anti-alucinación).
- **Dato pendiente de validación humana:** no existe en la base de conocimiento una certificación/especialidad verificada por coach. Se usaron descripciones genéricas y seguras (rol + expertise breve) en vez de atribuir certificaciones específicas (ISAK/McKenzie/Mulligan) sin confirmación, para evitar credenciales no verificadas. Reemplazar con bios reales antes de lanzamiento.
- Legibilidad: pies de foto/video (`.media-portrait__caption`, `.video-player__label`) reforzados con `backdrop-filter: blur(10px)` y mayor opacidad de fondo.
- Gap de despliegue resuelto: excepción `!public/media/*.mp4` en `.gitignore` — los 2 videos de la landing ahora se versionan en Git y viajarán por el pipeline `deploy.yml` existente sin pasos manuales. El filtro azulado sobre el material de gimnasio se mantiene como mitigación visual, pendiente de validación con cliente/regrabación futura.

---

## 9. Módulo 7.2 — Refinamiento UX/UI y Auditoría de Flujo (2026-06-17, tercera pasada)

- [x] **Nuestro Equipo:** se agregó el 4to coach (Roberto López, faltaba). Se corrigió el encuadre de fotos (`aspect-ratio: 1/1` → `4/5` + `object-position: center 18%`) para no cortar rostros. Se agregó interactividad: clic en cualquier tarjeta abre `CoachLightbox` con la foto ampliada y el nombre.
- [x] **Metodología:** las fases 02 y 04 mostraban fotos de coaches, rompiendo la narrativa científica. Fase 02 ahora usa un panel abstracto CSS (`phase-data-panel`: grid + barras de datos, sin fotografía humana). Fase 04 ahora usa el 3er video local (`entrenamiento-fuerza-controlada-laboratorio-athlos.mp4`) en `AthlosVideoPlayer`. Verificado: cero fotos de coach en `MethodologyTimeline`.
- [x] **Hero:** el panel abstracto (grid + "mapa corporal" CSS) fue sustituido por un video real de fondo en loop (`HeroBackgroundVideo`), `muted`/`playsInline`, con tinte azul `--athlos-deep-blue` al 50% y los chips flotantes ("Biomecánica 92%", "4 fases") por encima (`z-index: 2`). El video solo se reproduce vía JS si `prefers-reduced-motion` no está activo (fallback estático automático, sin atributo `autoPlay`).
- [x] **Navbar reestructurado:** Metodología → Evaluación Médica → Nuestro Equipo → Evidencia → Contacto. Eliminado el ítem "Programas" (ancla huérfana `#programas` sin sección, detectada en el Módulo 7.1) en vez de inventar una sección falsa para sostenerlo. Los 5 enlaces verificados en navegador: los 5 resuelven a un elemento real en el DOM.

**Reutilización de assets:** el 3er video local (antes sin asignar) ahora se usa en dos contextos distintos — fondo ambiental del Hero y card de la Fase 04 — sin necesidad de grabar/conseguir material nuevo.

Todo verificado empíricamente en navegador real: overflow en 7 anchos (360/390/430/768/992/1100/1440, incluyendo la zona de presión del nuevo nav de 5 ítems) sin regresión, foco del modal de Consent Gate sin regresión, capturas de pantalla confirmando Hero/Equipo/Lightbox/Metodología.

Build de producción confirmado sin errores. Estado: **Módulos 1-7 + refinamientos 7.1 y 7.2 completos.**

---

## 10. Módulo 7.3 — Ingesta de Contacto y Configuración CI/CD (2026-06-17, cuarta pasada)

- [x] **Contacto enriquecido:** `ContactSection` y `AthlosFooter` ahora muestran los 5 canales oficiales (dirección con enlace corto de Google Maps, WhatsApp, email, Instagram, Facebook) con íconos SVG minimalistas (`ContactIcons.tsx`). Colores verificados en navegador en ambos temas usando las variables ya auditadas en Módulo 7 (`--text-secondary` / `--athlos-turquoise-text`).
- [x] **Botón "Agendar" del header unificado:** desktop y móvil ahora usan `href="#consent-gate"`, igual que "Iniciar Onboarding Médico". `CtaButton` extendido para reenviar el `onClick` del caller (necesario para que el botón del menú móvil cierre el menú Y abra el modal en el mismo clic). Verificado: el botón se renderiza como `<button>`, no `<a>`, y abre el modal en ambos contextos.
- [x] **Pipeline CI/CD reescrito:** `.github/workflows/deploy.yml` ahora instala dependencias, ejecuta `pnpm build` (genera `/out` por `output: "export"`) y despliega esa carpeta vía FTP a `ftp.tourfindy.com` con las credenciales indicadas. Contraseña vía `${{ secrets.FTP_PASSWORD }}` — nunca hardcodeada.

**Acción pendiente del Arquitecto, obligatoria antes del primer push con este workflow:** crear el secreto `FTP_PASSWORD` en GitHub (Settings → Secrets and variables → Actions). Sin él, el paso de FTP fallará aunque el build se ejecute correctamente.

Build de producción confirmado sin errores, `/out` verificado localmente con `index.html`, `_next/` y los 3 videos en `media/`. Estado: **Módulos 1-7 + refinamientos 7.1, 7.2 y 7.3 completos. Listo para commit de producción**, sujeto a la creación del secreto `FTP_PASSWORD`.

---

## 11. Módulo 7.4 — Auditoría Estructural de Despliegue (2026-06-17, quinta pasada)

- [x] **Hotfix de terminal eliminado del workflow:** `pnpm config set only-built-dependencies sharp` ya no existe en `deploy.yml`; el paso de instalación quedó reducido a `pnpm install --frozen-lockfile`.
- [x] **Política de dependencias declarada de forma nativa** — con una corrección de ubicación real: la instrucción original pedía declarar `pnpm.onlyBuiltDependencies` dentro de `package.json`, pero `pnpm 11.5.1` advierte explícitamente que ese campo **ya no se lee** ahí. La ubicación funcional correcta es `pnpm-workspace.yaml` (nuevo archivo). Se removió el campo muerto de `package.json`.
- [x] **Aprobación real del build de `sharp`:** declarar `onlyBuiltDependencies` no fue suficiente por sí solo — se requirió `pnpm approve-builds sharp` (no interactivo) para que pnpm registrara la aprobación. Verificado con reinstalación 100% limpia (`rm -rf node_modules`): cero advertencias, binario nativo `@img/sharp-win32-x64` presente y funcional.
- [x] **Dry-run de `pnpm build` ejecutado:** `/out` generado con integridad total — `index.html`, `_next/`, y los 3 videos `.mp4` + posters en `media/`.

**Nota de transparencia:** la instrucción original (campo `pnpm` en `package.json`) no habría funcionado tal cual con la versión de pnpm pineada en este proyecto. Se corrigió a la ubicación real (`pnpm-workspace.yaml`) para que la política de seguridad blinde de verdad el pipeline, en vez de quedar como configuración silenciosamente ignorada.

Estado final: **arquitectura de dependencias blindada localmente y verificada de extremo a extremo. Lista para el push de producción**, sujeto únicamente a la creación del secreto `FTP_PASSWORD` en GitHub.

---

## 12. Auditoría Estructural Final de Despliegue (2026-06-17, sexta pasada)

- [x] **Conflicto de versiones P0 resuelto:** se removió `with: version: 11` del paso `pnpm/action-setup@v4` en `deploy.yml`. El instalador ahora lee automáticamente `pnpm@11.5.1` desde el campo `packageManager` de `package.json` — una sola fuente de verdad para la versión de pnpm en todo el pipeline, sin duplicación ni riesgo de desincronización.
- [x] **Auditoría holística:** revisados `deploy.yml`, `package.json` y `pnpm-workspace.yaml` en conjunto — sin discrepancias de versiones de Node, políticas de build scripts, ni configuración de export estático.
- [x] **Dry-run completo ejecutado:** `rm -rf .next out node_modules && pnpm install && pnpm build` — cero advertencias, cero errores, `sharp` con binario nativo intacto, `/out` generado con `index.html`, `_next/` y los 3 videos en `media/`.
- [x] **Commit y push ejecutados** bajo autorización explícita del Arquitecto.

Estado final: **pipeline corregido, auditado y verificado de extremo a extremo. Push a `main` ejecutado — el despliegue automático a `athlosperformance.tourfindy.com` está en curso**, sujeto a que el secreto `FTP_PASSWORD` ya esté configurado en GitHub.

---

## 13. Auditoría de Logotipo y .htaccess (2026-06-18)

- [x] **Logotipo — código verificado, sin bug.** `AthlosHeader.tsx` ya importaba el archivo correcto (`athlos-performance-logotipo-circular.jpg`, vía `next/image` con import estático). Build local confirmó: el archivo se genera en `out/_next/static/media/`, y el `src` del HTML apunta exactamente a esa ruta — sin rutas rotas, sin `/_next/image?url=` (gracias a `images.unoptimized: true` ya configurado desde el Módulo 1). **El bug no estaba en el código del componente.**
- [x] **Causa raíz real encontrada: `.htaccess` nunca llegaba al servidor.** El archivo vivía en la raíz del repo, no en `public/`, así que `next build` nunca lo copiaba a `/out/`. El pipeline (que solo sube `/out/`) jamás lo desplegó — cualquier `.htaccess` visto en producción fue configurado manualmente, desincronizado del repositorio.
- [x] **Además, el `.htaccess` apuntaba a `index.php`** (`DirectoryIndex index.php` + rewrite a `index.php`), una reliquia de la arquitectura PHP original — exactamente lo contrario del objetivo declarado ("`index.html` como fuente de verdad"). Si ese archivo era el que estaba activo en el servidor, **esta es la explicación más probable del fallo de renderizado** (un `DirectoryIndex` apuntando a un archivo que no existe en el export estático puede romper la carga del documento raíz, arrastrando consigo todos sus recursos referenciados, incluido el logo).
- [x] **Fix:** nuevo `public/.htaccess` con `DirectoryIndex index.html`, rewrite a `index.html`, `ErrorDocument 404 /404.html` (apuntando al 404 real que genera Next), y las mismas reglas de seguridad (bloqueo de `knowledge/`, `.git/`, extensiones sensibles, headers OWASP). Verificado: viaja correctamente a `out/.htaccess` en cada build.
- [x] **`local-dir` del workflow revisado:** ya estaba correctamente configurado en `./out/` desde el Módulo 7.3 — no requería cambios. Se evaluó agregar `dangerous-clean-slate: true` para garantizar que ningún archivo de despliegues anteriores persista en el servidor, pero **se descartó**: es una acción destructiva sobre infraestructura remota compartida que el Arquitecto no solicitó explícitamente.
- [x] **`.htaccess` de la raíz del repo, sin tocar:** posiblemente necesario para el entorno local de XAMPP/backend PHP (`api/conexion.php`). No se eliminó sin autorización explícita.

Build de producción re-confirmado sin errores tras este fix.

---

## 14. Corrección de Destino FTP y Diagnóstico de Localhost (2026-06-18)

- [x] **`server-dir` corregido:** la cuenta FTP está enjaulada en la raíz del subdominio (`athlosperformance.tourfindy.com`), por lo que la ruta absoluta de cPanel (`/home/tourfindycom/public_html/athlosperformance/`) creaba subcarpetas anidadas dentro de esa misma raíz en vez de depositar ahí directamente. Cambiado a `server-dir: ./` — el contenido de `/out` se deposita ahora en la raíz real de la sesión FTP.
- [x] **Diagnóstico de "localhost colgado":** revisé `ConsentGateProvider` y `ConsentLeadDialog` — sin loops de estado, sin dependencias inestables en los `useEffect` (`closeConsentGate` viene de `useCallback` con `[]`). La causa real era un proceso de `next dev` con varias horas de antigüedad (acumulado de turnos anteriores en esta misma sesión), no un bug de código.
- [x] **Purga ejecutada:** `rm -rf .next out node_modules/.cache` + proceso viejo terminado + `pnpm dev` limpio. Medido empíricamente: primera petición 4.0s (compilación inicial normal de Turbopack), peticiones subsecuentes **150-170ms** — muy por debajo del umbral de 500ms.
- [x] **Verificación final de `/out`:** `.htaccess` y el logotipo (`athlos-performance-logotipo-circular.jpg` → `_next/static/media/...jpg`) confirmados presentes, con rutas raíz-relativas (`/_next/...`) coherentes con el nuevo `server-dir: ./`.

Estado final: **pipeline corregido para la jaula FTP real, entorno local saneado y verificado por debajo de 500ms, build de producción íntegro. Listo para el push definitivo.**
