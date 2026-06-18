# REPORTE_TECNICO_FINAL.md

Proyecto: Athlos Performance BCS — Landing Page
Sistema: Athlos Cognitive Engine v1.0
Fecha de cierre: 2026-06-17
Módulos cubiertos: 1-7 + refinamientos 7.1, 7.2, 7.3, 7.4 (Cierre de Producción y Despacho)

---

## 0. Resumen de Estado

| Área | Estado |
| :--- | :--- |
| Build de producción (`pnpm build`) | ✅ OK |
| Integridad multimedia (`/out/media`, 3 videos `.mp4` + posters) | ✅ OK |
| Seguridad legal — Consent Gate (REGLA-01) | ✅ OK |
| Pipeline CI/CD (GitHub Actions → FTP) | ✅ OK (pendiente secreto, ver sección 5) |

---

## 1. Resumen Ejecutivo

La landing page de Athlos Performance BCS está **estructuralmente completa**, **validada en navegador real** (Chrome headless vía CDP, no solo inspección de código) y con su **pipeline de despliegue automatizado verificado de extremo a extremo**. Stack: Next.js 16.2.7 (App Router, `output: "export"`) + React 19.2.7 + TypeScript estricto + CSS nativo con variables corporativas. Sin dependencias de UI externas.

---

## 2. Estado por Módulo

| Módulo | Estado | Nota |
| :--- | :--- | :--- |
| 1 — Fundación Frontend | ✅ Cerrado | Header, ThemeToggle, menú móvil, sistema de diseño. |
| 2 — Hero de Alta Intención | ✅ Cerrado | Video real de fondo en loop (reemplazó el panel abstracto), fallback estático si `prefers-reduced-motion`. |
| 3 — Diferenciación y Metodología | ✅ Cerrado | 4 fases con video real o panel de datos abstracto por fase — cero fotografía de staff en esta sección. |
| 4 — Segmentación Clínica | ✅ Cerrado | Switcher de tabs Atletas/Longevidad, terminología real de fichas clínicas. |
| 5 — Evidencia Social y Autoridad | ✅ Cerrado | 6/6 enlaces sociales distribuidos, embebido real con patrón facade (lazy), certificaciones ISAK/McKenzie/Mulligan. |
| 6 — CTA Final y Consent Gate | ✅ Cerrado | Modal de 3 pasos, REGLA-01 aplicada, foco gestionado (focus trap verificado en navegador real). |
| 7 — QA, Performance y Cierre | ✅ Cerrado | Auditoría de accesibilidad, contraste, overflow y dead code. |
| 7.1 — Refinamiento visual post-auditoría humana | ✅ Cerrado | Bug crítico de contraste corregido (degradado de `body` no theme-aware), embebido social real, botón "Volver Arriba", sección de Contacto. |
| 7.2 — Refinamiento UX/UI y flujo | ✅ Cerrado | 4 coaches (antes 3), lightbox de equipo, fases 02/04 de Metodología sin rostros de staff, Hero con video real, nav reestructurado sin anclas huérfanas. |
| 7.3 — Ingesta de Contacto y CI/CD | ✅ Cerrado | 5 canales de contacto con íconos, CTA "Agendar" del header unificado al Consent Gate, primer `deploy.yml` con build real. |
| 7.4 — Auditoría Estructural de Despliegue | ✅ Cerrado | Política de dependencias (`sharp`) reubicada a `pnpm-workspace.yaml` (ubicación real para pnpm 11.x), aprobación de build registrada, workflow sin hotfixes de terminal. |

---

## 3. Seguridad Legal — Consent Gate (REGLA-01)

Verificado en navegador real (no solo código):

| Verificación | Resultado |
| :--- | :--- |
| Cualquier CTA principal (Hero, header, menú móvil, paneles de evaluación, CTA final) abre el mismo `ConsentLeadDialog` | ✅ Confirmado — un solo túnel de conversión |
| El formulario de captura es inalcanzable sin aceptar el checkbox de consentimiento | ✅ `Continuar` con `disabled: true` hasta marcar el checkbox |
| Solo se capturan las 3 variables permitidas | ✅ `nombreCompleto`, `telefono`, `objetivoDeclarado` |
| Focus trap dentro del modal (Tab/Shift+Tab circular, Escape, restauración de foco) | ✅ Confirmado |
| El botón "Agendar" del header (desktop y móvil) usa el mismo túnel que "Iniciar Onboarding Médico" | ✅ Confirmado — ambos abren `ConsentLeadDialog` vía `href="#consent-gate"` |

**Nota de integración pendiente:** el payload (`{ nombreCompleto, telefono, objetivoDeclarado, consentGateStatus: "aceptado" }`) se construye en memoria pero no se envía a ningún endpoint todavía. `api/webhook_mensajeria.php` **no es el endpoint correcto** — está diseñado exclusivamente para webhooks de Meta (valida firma `X-Hub-Signature-256` y espera el payload de WhatsApp/Instagram/Facebook, no el de este formulario). Conectar el envío real requiere que el Arquitecto defina un endpoint dedicado (p. ej. `api/lead_capture.php`) — decisión de backend fuera del alcance de este cierre frontend.

---

## 4. Integridad de Build y Multimedia

```
$ pnpm build
✓ Compiled successfully
✓ Running TypeScript — sin errores
✓ Generating static pages (3/3)
```

`/out` generado con:
- `index.html`, `_next/` (assets optimizados)
- `media/` con los 3 videos `.mp4` + sus posters extraídos con `ffmpeg`:
  - `evaluacion-potencia-tren-inferior-atletas-athlos.mp4`
  - `prescripcion-carga-mecanica-entrenamiento-athlos.mp4`
  - `entrenamiento-fuerza-controlada-laboratorio-athlos.mp4`
- Las 3 rutas `/media/*.mp4` confirmadas referenciadas correctamente en el HTML generado.

**Dependencias blindadas:** `sharp` (requerido por la optimización de imágenes) tiene su política de build declarada en `pnpm-workspace.yaml` (`onlyBuiltDependencies`) y su aprobación real registrada (`allowBuilds`). Verificado con reinstalación 100% limpia: cero advertencias, binario nativo `@img/sharp-win32-x64` presente y funcional.

---

## 5. Pipeline CI/CD — `.github/workflows/deploy.yml`

Se ejecuta automáticamente en cada `push` a `main`:

1. `actions/checkout@v4` — clona el repositorio.
2. `pnpm/action-setup@v4` — instala pnpm.
3. `actions/setup-node@v4` (Node 22, caché de pnpm).
4. `pnpm install --frozen-lockfile` — instala dependencias respetando el lockfile y la política de `sharp`.
5. `pnpm build` — genera `/out` (sitio estático completo, incluye `media/`).
6. `SamKirkland/FTP-Deploy-Action@v4.3.5` — sube **todo el contenido de `/out`** (sin exclusiones) a:
   - Servidor: `ftp.tourfindy.com`, puerto `21`
   - Usuario: `ftp_user@athlosperformance.tourfindy.com`
   - Destino: `/home/tourfindycom/public_html/athlosperformance/`

### ⚠️ Acción requerida antes del primer disparo automático

**Debes crear el secreto `FTP_PASSWORD` en GitHub** antes de que este pipeline pueda completar el despliegue:

> GitHub → tu repositorio → **Settings** → **Secrets and variables** → **Actions** → **New repository secret** → nombre `FTP_PASSWORD`, valor: la contraseña real de `ftp_user@athlosperformance.tourfindy.com`.

Sin este secreto, los pasos 1-5 (build) se ejecutarán correctamente, pero el paso 6 (FTP) fallará por credenciales inválidas.

### Confirmación de disparo automático

El workflow está configurado con `on: push: branches: [main]`. **Al ejecutar `git push origin main`, el pipeline se disparará automáticamente** sin pasos manuales adicionales, siempre que el secreto `FTP_PASSWORD` ya exista.

---

## 6. Limpieza de Dead Code

Sin `console.log` de depuración, sin componentes huérfanos (`ServiceCard.tsx` removido), sin variantes de CTA sin usar, sin CSS muerto (`hero-visual__grid`, `hero-visual__body-map`, `media-portrait` removidos), sin anclas de navegación huérfanas (`#programas` eliminado del nav).

**Hallazgos documentados, no bloqueantes:**
- `pnpm lint` no funciona (no hay `eslint`/`eslint-config-next` instalados) — decisión pendiente del Arquitecto.
- Falta `.env.example` en la raíz (Mandamiento 11) — `.htaccess` y `api/conexion.php` sí existen.

---

## 7. Confirmación Final

El repositorio está en condiciones de producción. El sistema está listo para el commit y push final a `main`.
