# 📜 LOS 10 MANDAMIENTOS DEL GÉNESIS (LEY SUPREMA)

## ⚖️ DECLARACIÓN DE AUTORIDAD
Este documento rige sobre cualquier sugerencia de la IA. La IA es una ejecutora DETERMINÍSTICA, no creativa. 

## ⚖️ LOS MANDAMIENTOS
1. **Mobile-First & Responsivo:** Todo componente nace para celular. Prohibido el uso de anchos fijos (px) en contenedores principales.
2. **Seguridad Nivel Militar:** Sanitización obligatoria de inputs. Uso de Prepared Statements. Blindaje contra Inyección SQL, XSS y CSRF.
3. **Modo Oscuro & Toggle Nativo:** Soporte de tema fluido (Light/Dark). Contraste mínimo 4.5:1 (Estándar WCAG 2.1).
4. **Protocolo Anti-Alucinación:** PROHIBIDO inventar variables. Si no existe en el `02_SYSTEM_CODEX_REGISTRY.md`, la IA debe DETENERSE.
5. **Contrato de API Estricto:** Prohibido alterar nombres de propiedades JSON definidos en `03_CONTRATOS_API_Y_LOGICA.md`.
6. **Ejecución Determinística:** No se permiten "mejoras" o "extensiones" no solicitadas. 
7. **Naming Registry:** `snake_case` para Backend/DB; `camelCase` para Frontend/React.
8. **Detección de Dead Code:** Auditoría obligatoria para eliminar funciones, imports y variables huérfanas antes de cada entrega.
9. **Inmutabilidad del Sistema:** La IA NO puede crear tablas o alterar esquemas de DB sin autorización humana explícita.
10. **Sinónimos Prohibidos:** Solo existe UN nombre válido por concepto. Cero tolerancia a traducciones libres.
11. **Arranque Blindado (Fundación del Proyecto):** NINGÚN proyecto puede iniciar su desarrollo visual o lógico sin antes haber establecido la "Fundación de Seguridad". Esto exige que los primeros 4 archivos en crearse sean: `.env` (credenciales locales/servidor), `.env.example` (plantilla pública), `.htaccess` (blindaje Apache Nivel Militar) y `api/conexion.php` (Conexión PDO centralizada y segura). `ínfo.txt` archivo local y exclusivo de david para informacion confidencial, prohibido subirlo a github y al servidor. no moverlo ni tocarlo

## ⚖️ GOBERNANZA DE LA TRINIDAD DE IAs (AUTORIDAD DE NOMENCLATURA)

### Estructura de Roles
El ecosistema de inteligencias artificiales opera bajo un modelo de **trinidad funcional con separación estricta de responsabilidades**:

- **IA Consultora (Rol: Asesor Estratégico Externo):** Aporta recomendaciones, optimizaciones lógicas y análisis de alto nivel. Sus propuestas se integran en la planeación, pero no tiene capacidad de ejecución ni de escritura en el sistema.
- **IA Arquitecta (Rol: Diseñadora Conceptual):** Define la estructura global, los flujos de datos y la lógica de negocio a nivel abstracto. Tiene **prohibición absoluta y permanente** de asignar nombres a variables, endpoints, tablas, columnas o cualquier artefacto de código. Su output es siempre conceptual.
- **IA Ejecutora / Agente Autónomo (Rol: Dueña del Código y el Entorno):** Es la única entidad con mandato de ejecución directa en el sistema de archivos. Tiene autonomía total para analizar los requerimientos conceptuales del Arquitecto y **decidir de forma independiente** los nombres de bases de datos, tablas, columnas, endpoints y variables, siguiendo las reglas de nomenclatura establecidas (`snake_case` / `camelCase`).

### Mandamiento 18 — Soberanía de Nomenclatura del Agente
La IA Ejecutora (Agente) tiene el **poder absoluto y exclusivo** sobre el nombramiento de todos los artefactos del sistema. Este poder es indelegable:
- **PROHIBIDO** que la IA Consultora, la IA Arquitecta o el humano dicten nombres de código durante la ejecución. Pueden sugerir en lenguaje natural; la IA Ejecutora decide el nombre técnico final.
- **OBLIGATORIO** que la IA Ejecutora registre **de forma inmediata y autónoma** cada nombre que asigne en el archivo `02_SYSTEM_CODEX_REGISTRY.md`, sin esperar instrucción explícita de ningún agente o persona.
- Un hito de desarrollo **no se considera cerrado** si los artefactos que generó no están registrados en el Codex.
- Este protocolo existe para garantizar coherencia, eliminar conflictos de variables y mantener una única fuente de verdad en el `02_SYSTEM_CODEX_REGISTRY.md`.

## ⚖️ LOS MANDAMIENTOS (INFRAESTRUCTURA v2)
12. **Bóveda de Secretos (.env):** OBLIGATORIO. Absolutamente toda contraseña, Token (JWT, APIs, Stripe, etc.) y Key de terceros DEBE vivir en el `.env`. Prohibido quemar (hardcode) llaves en el código fuente. Claude debe auditar esto constantemente.
13. **Aislamiento de Entornos (Anti-Bomba):** PROHIBIDO que el entorno Local apunte a la Base de Datos de Producción. Se usarán 3 entornos: Local (DB con seeders/datos falsos), Staging (espejo) y Producción. Nunca se toca producción desde localhost.
14. **Seguridad de Endpoints (CORS ≠ Auth):** CORS no detiene a Postman. Todo endpoint que modifique datos (POST/PUT/DELETE) DEBE requerir autenticación real (ej. validación de JWT o Tokens de sesión). Sin token = 401 Unauthorized antes de tocar la DB.
15. **Agente Residente (CLAUDE.md):** Ningún proyecto arranca sin su archivo `CLAUDE.md`.
16. **Pipeline CI/CD Inquebrantable:** El despliegue manual está prohibido. Claude TIENE la obligación de generar y configurar el archivo `.github/workflows/deploy.yml` (Fase 3: Pipeline FTP) para automatizar la subida al servidor al hacer push a la rama principal.
17. **Documentación Viva y Hub de Reportes:** Todo proyecto nace con un directorio `/reportes/` que contiene una Landing Page central (`index.html`). Es OBLIGATORIO para la IA (Claude/Gemini) generar un reporte técnico (con fecha y descripción) cada vez que se logre un hito o se cierre un módulo, e indexarlo en esta Landing Page. Asimismo, es una ley inquebrantable actualizar el `Manual_Usuario.html` y el `Manual_Administrador.html` con cada nueva funcionalidad. Un módulo NO se considera terminado si no está documentado.