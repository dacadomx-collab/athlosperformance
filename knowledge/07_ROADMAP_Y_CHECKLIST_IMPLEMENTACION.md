# 🧬 07 - ROADMAP Y CHECKLIST DE IMPLEMENTACIÓN COGNITIVA

> **Clasificación:** Documento Operativo de Proyecto / Guía de Ingeniería
> **Origen:** Lógica del Arquitecto (Gemini) + Consultoría Estratégica (ChatGPT)
> **Versión:** 1.0 | 2026-05-27

---

## 🎯 OBJETIVO GENERAL DEL ROADMAP
Este documento establece las fases lógicas de desarrollo, los criterios mínimos de aceptación técnicos y las reglas de piedra conceptuales para guiar la construcción del **Athlos Cognitive Engine v1.0**. Ningún hito de programación se considerará cerrado si no se satisfacen los requerimientos descritos en este checklist.

---

## 📊 CUADRO DE PRIORIZACIÓN DE ARQUITECTURA

| Prioridad | Componente / Requerimiento Conceptual | Descripción Técnica e Impacto |
| :--- | :--- | :--- |
| 🔴 **P0 - Crítico** | **Consent Gate Flujo Conversacional** | Bloqueo obligatorio en mensajería para requerir aceptación explícita de términos antes de almacenar datos de salud en el CRM. El backend debe rechazar cargas sin este token de confirmación. |
| 🔴 **P0 - Crítico** | **Motor de Riesgo Clínico & Filtro Anti-Alucinación** | Estructura RAG en capas cerradas alimentada por PDFs y guías aprobadas. Desvío inmediato a humano ante disparadores críticos (*dolor de pecho, mareos, inflamación severa*). |
| 🔴 **P0 - Crítico** | **Human Handoff Fluido** | Protocolo de escalamiento que transfiere el historial conversacional completo con perfiles detectados al equipo de staff cuando la confianza baja de 0.75. |
| 🟡 **P1 - Alto** | **Scoring Multidimensional de Perfiles** | Abandono de clasificaciones rígidas. Implementación de una huella fisiológica dinámica mediante vectores flotantes (*rendimiento, rehabilitación, control graso, carga psicológica*). |
| 🟡 **P1 - Alto** | **Normalización Ontológica de Ingesta** | Mapeo previo de datos cargados vía Excel para estandarizar abreviaturas o errores ortográficos clínicos hacia un diccionario centralizado antes del procesamiento. |
| 🟡 **P1 - Alto** | **Deduplicación Omnicanal de Identidad** | Algoritmo fuzzy-match para enlazar interacciones de Instagram, Facebook y WhatsApp bajo una única ficha de atleta utilizando identificadores normalizados. |
| 🟢 **P2 - Magia** | **Twin Athlete Engine (Gemelo Digital)** | Simulador matemático predictivo que cruza composición corporal, HRV, calidad de sueño y adherencia para proyectar fatiga o riesgo de lesiones a semanas vista. |
| 🟢 **P2 - Magia** | **Adaptive Motivational Persona** | Módulo conversacional que altera la sintaxis y los disparadores de enganche según el estilo cognitivo detectado (*competitivo, controlado, ejecutivo*). |
| 🟢 **P2 - Magia** | **Invisible Recovery Intelligence** | Análisis longitudinal de sentimientos, latencia en respuestas y cancelaciones de citas para predecir el abandono o el burnout antes de que sea verbalizado. |

---

## 🛠️ CHECKLIST TÉCNICO DE IMPLEMENTACIÓN

### 🛫 Fase A: Capa de Entrada y Seguridad Legal
- [ ] **Despliegue del Webhook Central:** Configurar el punto de entrada lógico capaz de recibir y discriminar los payloads provenientes de canales omnicanal.
- [ ] **Middleware de Consent Gate:** Interceptar el flujo entrante; si el usuario no cuenta con la bandera de autorización legal afirmativa persistida, el sistema congelará la IA libre y solo desplegará el flujo estructurado de aceptación de términos.
- [ ] **Motor de Interceptación de Riesgo Clínico:** Programar un módulo de escaneo rápido de cadenas de texto basado en diccionarios de peligro médico. Ante un positivo, se detiene la inferencia de lenguaje y se activa el protocolo de seguridad con alerta prioritaria al staff.

### 🏋️ Fase B: Ingesta Semántica y Estructuración de CRM
- [ ] **Procesador de Historiales Legacy:** Desarrollar el motor de lectura de archivos masivos para procesar e indexar las estructuras históricas de los atletas actuales provenientes del archivo Excel.
- [ ] **Capa de Normalización Ontológica:** Implementar la rutina de traducción que mapee términos inconsistentes o abreviaciones hacia un vocabulario unificado controlado antes de cruzar los datos.
- [ ] **Estructuración de la Ficha Multidimensional:** Habilitar el almacenamiento dinámico basado en vectores numéricos de afinidad para guardar las "huellas comportamentales y fisiológicas" del atleta sin encasillarlo rígidamente.

### 🛡️ Fase C: Arquitectura Anti-Alucinación y RAG
- [ ] **Segregación Estricta de Memoria Contextual:** Diseñar la separación lógica de contextos en el backend para impedir que datos transaccionales o de marketing contaminen los procesos de inferencia de rendimiento.
- [ ] **Filtro de Confidence Threshold:** Implementar el validador de umbrales numéricos de confianza fijado en 0.75 para cualquier cálculo de proyecciones físicas o de carga.
- [ ] **Bitácora Inmutable de Auditoría Médica:** Crear el repositorio físico de logs para registrar de manera permanente cualquier conversación donde el sistema haya detectado terminología de riesgo o dolor.

### 🚀 Fase D: Módulos Avanzados Predictivos ("Magia")
- [ ] **Simulador Asíncrono del Gemelo Digital:** Programar la lógica matemática del simulador numérico para proyecciones de composición corporal y fatiga, aislándolo del hilo de ejecución web principal para mantener la latencia del servidor baja.
- [ ] **Inyector de Persona Conversacional Adaptativa:** Crear la capa lógica que modifique dinámicamente las variables gramaticales y de tono de las plantillas de mensajería saliente según el perfil dominante.
- [ ] **Telemetría de Alerta Temprana (Burnout/Abandono):** Desarrollar las métricas de monitoreo longitudinal sobre comportamientos del cliente (*latencias de respuesta en chat, cancelaciones de citas*) para disparar notificaciones humanas de reenganche estratégico.

---

## 📐 DIRECTRICES DE INFRAESTRUCTURA (REGLAS DE PIEDRA DEL ARQUITECTO)

1. **Desacoplamiento Absoluto de Prompts:** Queda estrictamente prohibido codificar de forma fija (hardcode) los prompts del sistema dentro de los scripts PHP de backend. Todos los prompts maestros de extracción NLP, clasificación de intenciones y configuraciones de guardrails médicos deben ser consumidos dinámicamente desde archivos de configuración dedicados o desde almacenamiento seguro.
2. **Manejo de Operaciones Pesadas en Segundo Plano:** El procesamiento analítico que involucre comparaciones masivas de datos o proyecciones a semanas vista jamás debe interrumpir el flujo transaccional de los webhooks de mensajería. Estos procesos se ejecutarán fuera del hilo principal mediante llamadas asíncronas o tareas programadas en el servidor local.
3. **Principio de Inferencia Probabilística:** Toda comunicación generada automáticamente por el motor cognitivo ante consultas de carácter físico o funcional se redactará mandatoriamente utilizando un lenguaje de probabilidad, sugerencia calificada y humildad epistemológica, anulando juicios definitivos o deterministas sobre diagnósticos corporales.