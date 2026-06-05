# 🧬 00 - ADN DEL PROYECTO (DIRECTRIZ MAESTRA)
> **Sistema:** Athlos Cognitive Engine v1.0  
> **Entorno:** XAMPP Local → Producción vía CI/CD  
> **Última actualización:** 2026-05-27

---

## 📌 1. IDENTIDAD DEL PROYECTO

- **Nombre del Sistema:** Athlos Cognitive Engine v1.0
- **Nombre Comercial:** Athlos Performance BCS
- **Ubicación Física:** La Paz, Baja California Sur, México
- **Objetivo Principal:** Alcanzar la **"Fricción Cero Operativa"** — automatizar al 100% la captación omnicanal, el filtrado de perfiles, la atención inicial 24/7 y la gestión de agenda, liberando al staff para enfocarse exclusivamente en ciencia del deporte y entrenamiento de alto rendimiento.
- **Propuesta de Valor Técnica:** Un sistema que extrae inteligencia de cada conversación, normaliza datos clínicos legacy y genera inteligencia predictiva sobre el rendimiento de cada atleta.

---

## 🛠️ 2. STACK TECNOLÓGICO Y ARQUITECTURA

| Capa | Tecnología |
| :--- | :--- |
| **Frontend** | HTML5 / CSS3 / JavaScript ES6+ nativo. Mobile-First. Modo Oscuro nativo. |
| **Backend** | PHP Nativo (>=8.1) con PDO y Prepared Statements. Arquitectura sin framework. |
| **Base de Datos** | MySQL 8.x — conexión centralizada vía `api/conexion.php` (PDO). |
| **Entorno Local** | XAMPP (Apache + MySQL) en `C:\xampp\htdocs\Athlos_Performance` |
| **Motor IA / NLP** | Integración vía API REST con LLM externo (OpenAI / Claude API). RAG local sobre PDFs indexados. |
| **Mensajería** | Meta Business API (WhatsApp Business, Instagram DM, Facebook Messenger) vía Webhooks. |

### Infraestructura CI/CD
- **Flujo:** Desarrollo Local → GitHub (rama `main`) → GitHub Actions (`deploy.yml`) → FTP Auto-Deploy al servidor de producción.
- **Secretos:** Todas las credenciales (DB, FTP, API Keys) residen exclusivamente en `.env` local y en **GitHub Secrets**. Prohibido hardcodear.

---

## 🧩 3. MÓDULOS PRINCIPALES (CORE FEATURES)

### Módulo 1 — AI FrontDesk 24/7 (Red de Captación Omnicanal)
Sistema conversacional multicanal que opera sin intervención humana. Conectado a WhatsApp Business, Instagram DM y Facebook Messenger. El motor NLP extrae entidades (nombre, teléfono, objetivo) de la conversación natural, clasifica el perfil del usuario y llena el CRM automáticamente.
- **Subcomponente:** Clasificador de Intenciones (perfiles: `atleta_competitivo` / `rehabilitacion` / `composicion_corporal`).
- **Subcomponente:** Extractor NLP de Entidades → crea el registro en `leads_prospectos`.
- **Ley Crítica:** El **Consent Gate** debe activarse y ser aceptado antes de persistir cualquier dato de salud.

### Módulo 2 — Backoffice CRM & Sistema de Pacientes
Panel de administración web para la gestión completa de atletas, historiales clínicos y métricas de rendimiento.
- **Subcomponente — Lector Semántico de Historiales:** Pipeline de ingesta de archivos Excel con paso de **Normalización Ontológica** (mapeo de términos clínicos a vocabulario controlado ICD-10) antes de generar embeddings para búsqueda semántica.
- **Subcomponente — Motor de Búsqueda Semántica:** Permite queries en lenguaje natural sobre la base de atletas (ej: *"¿Quiénes tienen LCA y no han venido este mes?"*).
- **Subcomponente — Copiloto de Diagnóstico:** Al ingresar nuevos datos físicos (% grasa, fuerza, VO2Max), cruza con el histórico del atleta y genera un Reporte Automatizado de Rendimiento con gráficas predictivas y percentiles de referencia poblacional.

### Módulo 3 — Sistema Autónomo de Agenda & Control
Motor de gestión de citas que opera de forma autónoma a través de los canales de mensajería.
- Consulta disponibilidad real de la tabla `disponibilidad_agenda` en tiempo real.
- Reserva, confirma y envía recordatorios automáticos por WhatsApp 24h antes de la cita.
- Control de cupo máximo por franja horaria en el laboratorio.
- Panel admin para gestión de horarios del staff.

---

## 🔌 4. INTEGRACIONES Y TERCEROS (APIs)

| Servicio | Propósito | Variable .env |
| :--- | :--- | :--- |
| Meta Business API | Webhooks de WA/IG/FB y envío de mensajes salientes | `META_VERIFY_TOKEN`, `META_PAGE_TOKEN` |
| LLM API (OpenAI/Claude) | Motor NLP, clasificación de intenciones, generación de reportes, RAG | `LLM_API_KEY`, `LLM_MODEL` |
| SendGrid (Fase 2) | Notificaciones por email al staff | `SENDGRID_API_KEY` |
| XAMPP MySQL Local | DB de desarrollo | `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` |

---

## ⚠️ 5. REGLAS ESPECÍFICAS DEL PROYECTO (LEYES INMUTABLES)

### REGLA-01 — Consent Gate Obligatorio (Ley de Privacidad)
**NINGÚN** dato de salud, lesión, condición física o diagnóstico puede persistirse en la base de datos sin que el usuario haya enviado una confirmación explícita de consentimiento en el canal de mensajería. El campo `consent_gate_status` en `leads_prospectos` debe ser `'aceptado'` antes de permitir cualquier `INSERT` en tablas con datos sensibles.

### REGLA-02 — Normalización Ontológica en Ingesta de Excel
Todo archivo Excel cargado al sistema pasa por un pipeline de normalización antes de insertarse en la DB. Los términos clínicos ambiguos (ej: `"lig. cruzado"`, `"LCA"`, `"ligamento ant."`, `"cruzado anterior"`) se mapean a un vocabulario controlado unificado (referencia ICD-10/terminología deportiva estándar) y se persisten en la columna `antecedentes_lesion_normalizado` (tipo JSON). La columna de texto raw se preserva en `antecedentes_lesion` para auditoría.

### REGLA-03 — Capa Anti-Alucinación Médica de 5 Niveles (Mandatory en todos los flujos conversacionales)
Toda respuesta de la IA que toque terminología médica o deportiva clínica debe pasar por el siguiente pipeline:
1. **Constitución en System Prompt:** La IA tiene prohibido diagnosticar. Ante síntomas neurológicos, dolor agudo o fracturas → escalación inmediata.
2. **RAG Local:** Las respuestas sobre protocolos o lesiones se recuperan ÚNICAMENTE desde el directorio `/rag_base/` (PDFs y guías validadas por el staff médico). Sin chunk relevante = respuesta de escalación.
3. **Confidence Gating:** Umbral mínimo de confianza `0.75`. Respuestas por debajo del umbral → mensaje de escalación automático.
4. **Disclaimer Contextual + CTA:** Disclaimer específico al contexto del usuario (no genérico) que funciona como call-to-action de conversión.
5. **Audit Log:** Toda conversación que active los niveles 1-4 se registra en `audit_log_medico` con `requiere_revision = 1` para revisión del staff.

### REGLA-04 — Deduplicación de Identidad Omnicanal
Antes de crear un nuevo registro en `leads_prospectos`, el sistema debe consultar por `telefono` normalizado (sin espacios, sin guiones, con código de país). Si existe un registro previo del mismo teléfono en cualquier canal, se actualiza el existente en lugar de crear un duplicado.

### REGLA-05 — Percentiles de Referencia en Copiloto de Diagnóstico
El Copiloto de Diagnóstico NUNCA puede entregar una interpretación de métricas sin cruzarlas con los percentiles de referencia correspondientes al deporte, edad y sexo del atleta. Un dato sin contexto normativo es un dato sin valor clínico.
