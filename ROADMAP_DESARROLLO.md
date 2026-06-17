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

- [ ] Construir `EvidenceMediaSection` con enlaces sociales externos optimizados.
- [ ] Usar lazy links/cards, no embeds pesados en primera carga.
- [ ] Construir `AuthoritySection` con red médica/certificaciones mencionadas en handoff.
- [ ] Integrar staff portraits como prueba humana, sin convertirlos en sección egocéntrica.
- [ ] Todos los enlaces externos deben usar `target="_blank"` y `rel="noopener noreferrer"`.

Punto de control: DETENERSE para validar qué videos/reels representan mejor autoridad real.

### Módulo 6 - CTA Final y Consent Gate UX

- [ ] Construir `FinalCtaSection`.
- [ ] Construir `ConsentLeadDialog` o stub funcional del flujo de consentimiento.
- [ ] Capturar solo variables iniciales permitidas: `nombreCompleto`, `telefono`, `objetivoDeclarado`.
- [ ] No persistir ni enviar datos clínicos sensibles antes de aceptación explícita.
- [ ] Preparar payload alineado con contratos existentes si se integra backend.
- [ ] Mensajes claros: evaluación profesional, no diagnóstico online.

Punto de control: DETENERSE para validar legalidad, copy y comportamiento del CTA.

### Módulo 7 - Performance, Accesibilidad y QA

- [ ] Ejecutar build.
- [ ] Auditar navegación por teclado.
- [ ] Validar contraste light/dark.
- [ ] Validar mobile-first en anchos 360px, 390px, 430px, tablet y desktop.
- [ ] Optimizar videos: preload controlado, poster, lazy-load, `playsInline`, `muted`.
- [ ] Revisar dead code, imports y assets no usados.
- [ ] Generar reporte técnico de hito e indexarlo si existe `/reportes`.

Punto de control: DETENERSE antes de cualquier preparación de deploy.

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

Estado actual: MODULOS 1-3 CERRADOS. MODULO 4 IMPLEMENTADO. MEJORA VISUAL APLICADA A MODULOS 3-4. DETENIDO PARA VALIDACION DE SENSIBILIDAD MEDICA Y CLARIDAD VISUAL ANTES DE MODULO 5.

**AJUSTE DE PULIDO VISUAL Y TECNICO (2026-06-17, segunda pasada):**
- Hero: eliminado el elemento `hero-visual__scanline` ("escáner") y su `@keyframes scan` (dead code). Sustituido por separador tipográfico: línea vertical de 1px `#3F6E8A` (`hero__title-group::before`) conectando H1 y subtítulo.
- Nueva sección `TeamSection` (`#staff`) debajo de `MethodologyTimeline`: grid de 3 coaches (Bernardo Lobo, Luis Moctezuma, Arturo Naranjo), foto cuadrada sin filtro pesado, `border-radius: 8px`, pie de foto limpio + expertise a 2 líneas.
- **Corrección de dato:** el Arquitecto solicitó "Arturo Sánchez"; el asset y registros disponibles corresponden a **Arturo Naranjo** (`arturo-naranjo-coach-athlos-performance.jpg`, confirmado además como `ASESOR` en `Menor_65_02 DATOS ANTROMPOMETRÍA ATHLOS.xlsx`). Se usó el nombre real en vez de inventar una persona no registrada — Mandamiento 4 (anti-alucinación).
- **Dato pendiente de validación humana:** no existe en la base de conocimiento una certificación/especialidad verificada por coach. Se usaron descripciones genéricas y seguras (rol + expertise breve) en vez de atribuir certificaciones específicas (ISAK/McKenzie/Mulligan) sin confirmación, para evitar credenciales no verificadas. Reemplazar con bios reales antes de lanzamiento.
- Legibilidad: pies de foto/video (`.media-portrait__caption`, `.video-player__label`) reforzados con `backdrop-filter: blur(10px)` y mayor opacidad de fondo.
- Gap de despliegue resuelto: excepción `!public/media/*.mp4` en `.gitignore` — los 2 videos de la landing ahora se versionan en Git y viajarán por el pipeline `deploy.yml` existente sin pasos manuales. El filtro azulado sobre el material de gimnasio se mantiene como mitigación visual, pendiente de validación con cliente/regrabación futura.
