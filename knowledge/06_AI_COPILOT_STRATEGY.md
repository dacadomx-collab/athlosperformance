# 🧠 AI COPILOT STRATEGY — SISTEMA OPERATIVO COGNITIVO

> **Clasificación:** Documento Estratégico de Proyecto  
> **Fuente:** Sesión de Consultoría con IA Consultora  
> **Versión:** 1.0 | 2026-05-27

---

## 🎯 OBJETIVO OPERATIVO

Llevar al laboratorio a **"Fricción Cero Operativa"**: atención inicial, captura de leads y análisis de pacientes 100% automatizados. El staff enfoca su energía exclusivamente en ciencia y entrenamiento de alto rendimiento.

---

## 🏗️ ARQUITECTURA DEL PLAN DE IA (v1)

### 1. Red de Captación Omnicanal
- IA conectada a canales de mensajería y redes sociales (WA, IG, FB).
- Comunicación adaptativa, sin texto plano genérico.
- Identificación de perfil de usuario: atleta competitivo / rehabilitación / composición corporal.
- NLP para extracción de entidades (nombre, teléfono, necesidad) y llenado automático de CRM.

### 2. Backoffice CRM & Sistema de Pacientes
- **Ingesta de Excel:** La IA procesa historiales legacy y construye perfiles dinámicos.
- **Consultas semánticas:** El staff realiza preguntas en lenguaje natural sobre la base de pacientes.
- Ejemplo de query: *"¿Quiénes tienen lesión de ligamento y no han venido este mes?"*

### 3. Copiloto de Diagnóstico
- Al ingresar nuevos datos físicos (grasa corporal, fuerza, VO2Max), la IA cruza con el histórico del paciente.
- Genera un **Reporte Automatizado de Rendimiento** con gráficas y proyecciones predictivas.

---

## ⚠️ VACÍOS Y EDGE CASES CRÍTICOS

### EC-01 — Consentimiento en Canal Conversacional (RIESGO LEGAL)
> **Problema:** La extracción de entidades en WhatsApp procesa datos de salud antes de obtener consentimiento firmado.  
> **Solución:** Implementar un **Consent Gate** explícito al inicio de cada flujo conversacional. El dato no se persiste en el CRM hasta que el usuario acepte activamente. Esto no es opcional si se opera bajo GDPR o LGPD.

### EC-02 — Deduplicación de Identidad Omnicanal
> **Problema:** Un mismo usuario puede contactar desde IG (alias), WA (número) y FB (perfil) generando registros duplicados.  
> **Solución:** Motor de deduplicación por señales: teléfono normalizado + email + fuzzy-match de nombre. Sin esto, el 30% de los leads son fantasmas a los 6 meses.

### EC-03 — Degradación Silenciosa en Consultas Semánticas
> **Problema:** Historiales en Excel con ortografía inconsistente ("lig. cruzado", "LCA", "ligamento ant.") rompen la búsqueda semántica.  
> **Solución:** Paso de **Normalización Ontológica** en el pipeline de ingesta: mapear términos clínicos a vocabulario controlado (ICD-10 o equivalente) antes de generar embeddings.

### EC-04 — Ausencia de Human Handoff Graceful
> **Problema:** Cuando la IA no puede clasificar el perfil en 3 turnos, entra en bucle o responde de forma genérica.  
> **Solución:** Criterio explícito de escalación: el bot transfiere a humano con el historial completo de la conversación incluido en el contexto. Cero pérdida de información al escalar.

### EC-05 — Copiloto sin Baseline Poblacional
> **Problema:** Comparar datos del paciente solo con su propio histórico carece de contexto normativo.  
> **Solución:** Integrar percentiles de referencia por deporte, edad y sexo. Un VO2Max de 52 ml/kg/min tiene significados opuestos según el perfil del atleta.

---

## 💡 IDEAS INNOVADORAS — NIVEL "MAGIA PURA"

### IDEA-01 — "El Espejo Predictivo" (Pre-Session Intelligence Brief)
**Descripción:** 24 horas antes de cada sesión, el sistema genera automáticamente un briefing de una página para el coach/científico con:
- Métricas de sueño/recuperación del atleta (integración API con Oura/Whoop).
- Días desde la última sesión y tipo de carga acumulada.
- Recomendación de carga basada en la curva de recuperación y HRV.

**Percepción del cliente:** El laboratorio los conoce mejor que ellos mismos antes de que crucen la puerta.

---

### IDEA-02 — "Voz del Cuerpo" (Longitudinal Narrative Report)
**Descripción:** Al completar un ciclo de evaluación, el sistema genera un **reporte narrado en primera persona** desde la perspectiva del atleta. El reporte es visualmente compartible (formato Story de IG):
> *"En los últimos 90 días tu potencia en sentadilla creció 12%, mientras que tu porcentaje graso bajó 1.8 puntos. Tus datos sitúan tu metabolismo en el percentil 78 para hombres de tu deporte y edad..."*

**Percepción del cliente:** El atleta se convierte en embajador orgánico del laboratorio.

---

### IDEA-03 — "Radar de Riesgo de Abandono" (Churn Prediction Engine)
**Descripción:** Modelo predictivo (XGBoost o similar) entrenado sobre patrones de comportamiento:
- Días desde última visita.
- Frecuencia de respuesta a mensajes del lab.
- Reducción en reservas de sesiones.
- Cambios en adherencia a métricas.

El CRM etiqueta clientes en semáforo (verde/amarillo/rojo). Al entrar en rojo, dispara secuencia de reenganche personalizada referenciando el último logro específico del atleta — no un mensaje genérico.

**Impacto de negocio:** Reducción del churn antes de que el cliente consciencie que estaba por irse.

---

## 🛡️ PROTOCOLO ANTI-ALUCINACIÓN (CONTEXTO MÉDICO-DEPORTIVO)

### Arquitectura en 5 Capas — Obligatoria para canales conversacionales

#### Capa 1 — Constitución del System Prompt
Restricción de dominio explícita en el prompt de sistema:
- **Prohibido:** Diagnosticar condiciones médicas.
- **Prohibido:** Recomendar protocolos de rehabilitación sin intervención de profesional.
- **Obligatorio:** Ante síntomas de dolor agudo, fractura o síntomas neurológicos → escalación inmediata a humano.

#### Capa 2 — RAG sobre Conocimiento Verificado
La IA **no genera** respuestas sobre lesiones o protocolos desde conocimiento paramétrico (fuente de alucinaciones). Recupera respuestas desde una base de conocimiento propia y validada por el equipo médico del lab (documentos PDF, guías clínicas internas).  
> Si no existe chunk relevante → respuesta de escalación. Sin excepciones.

#### Capa 3 — Confidence Gating
Scoring de confianza por respuesta. Umbral sugerido: **0.75**.  
Cualquier respuesta sobre síntomas, lesiones o medicación por debajo del umbral se reemplaza automáticamente con mensaje de escalación.  
Frameworks de referencia: LlamaIndex, LangChain Guardrails.

#### Capa 4 — Disclaimers Contextuales (No Genéricos)
Evitar el disclaimer boilerplate que nadie lee. Cuando la IA toque temas de salud, insertar un disclaimer específico al contexto del usuario que funcione además como CTA:
> *"Esta información es orientativa. Para tu caso específico, nuestro equipo puede darte una evaluación funcional completa."*

#### Capa 5 — Audit Log para Supervisión y Mejora Continua
Toda conversación donde la IA toque terminología médica queda flaggeada en el CRM para revisión posterior. Objetivo: detectar patrones de error, mejorar el RAG y el prompt de forma iterativa.

---

## 📊 PRIORIDAD DE IMPLEMENTACIÓN RECOMENDADA

| Prioridad | Componente | Motivo |
|-----------|------------|--------|
| 🔴 P0 | Consent Gate | Riesgo legal inmediato |
| 🔴 P0 | RAG Médico (Capa 2) | Un error aquí destruye el posicionamiento premium |
| 🔴 P0 | Human Handoff Graceful | Experiencia premium no negociable |
| 🟡 P1 | Deduplicación de Identidad | Integridad del CRM |
| 🟡 P1 | Normalización Ontológica | Calidad de consultas semánticas |
| 🟢 P2 | Pre-Session Brief | Diferenciación competitiva |
| 🟢 P2 | Narrative Report | Viralizabilidad orgánica |
| 🟢 P2 | Churn Radar | Retención de cartera |

> **Regla de oro:** Las ideas de "magia" se implementan **después** de que los cimientos sean sólidos. Un sistema que alucina sobre una lesión una sola vez destruye años de posicionamiento premium.

---

*Documento generado en sesión de consultoría estratégica. Requiere validación del Arquitecto antes de trasladar items a épicas de desarrollo.*
