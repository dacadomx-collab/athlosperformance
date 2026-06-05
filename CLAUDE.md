# 📘 MANUAL OPERATIVO RESIDENTE: ATHLOS PERFORMANCE ENGINE V1.0

## 🛠️ COMANDOS DE COMPILACIÓN Y ENTORNO LOCAL
- **Iniciar Entorno de Desarrollo (Frontend Layer):** `pnpm dev`
- **Compilar Artefactos para Producción:** `pnpm build`
- **Exportar Vistas Estáticas Adaptativas:** `pnpm out`
- **Limpiar Cachés y Código Muerto:** `pnpm clean`

## 🧠 STACK TECNOLÓGICO DE LA ALIANZA COGNITIVA
- **Backend Architecture:** PHP 8.2+ con Tipado Estricto (`strict_types=1`).
- **Data Layers:** MySQL con motor InnoDB y codificación nativa `utf8mb4_unicode_ci`.
- **Frontend Matrix:** Next.js / React (Mobile-First de diseño fluido responsivo).
- **Package Management:** `pnpm` (Rápido, determinista y eficiente en espacio).

## 📐 REGLAS DE NOMENCLATURA Y CONTRATO DE INTERCAMBIO
- **Backend / Database Registry:** Estricto `snake_case` para nombres de columnas, variables locales, endpoints lógicos y tablas.
- **Frontend UI Framework:** Estricto `camelCase` para variables JS/TS, estados de React, custom hooks y componentes de vista.
- **Contrato de API Estricto:** Transferencia bidireccional exclusivamente mediante JSON codificado en UTF-8.