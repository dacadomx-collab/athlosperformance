# Mapa de Base de Datos — tourfindycom_athlosp_db

Generado automaticamente a partir de knowledge/sql/tourfindycom_athlosp_db.sql (volcado real de produccion, ESTRUCTURA UNICAMENTE -- nunca se incluyen filas/datos reales en este indice). Regenerar cada vez que el Comandante actualice ese volcado.

Total de tablas: 24

## Índice

- [`acadep_vocacional_leads`](#acadep_vocacional_leads)
- [`alertas_renovacion`](#alertas_renovacion)
- [`asistencias`](#asistencias)
- [`atletas`](#atletas)
- [`audit_log_medico`](#audit_log_medico)
- [`catalogo_servicios`](#catalogo_servicios)
- [`detalles_ejercicio`](#detalles_ejercicio)
- [`disponibilidad_agenda`](#disponibilidad_agenda)
- [`evaluaciones_antropometria`](#evaluaciones_antropometria)
- [`evaluaciones_biomecanica`](#evaluaciones_biomecanica)
- [`evaluaciones_sft`](#evaluaciones_sft)
- [`historial_clinico`](#historial_clinico)
- [`leads_prospectos`](#leads_prospectos)
- [`membresias`](#membresias)
- [`pagos_asistencia`](#pagos_asistencia)
- [`percentiles_sft_referencia`](#percentiles_sft_referencia)
- [`permisos`](#permisos)
- [`planes_macrociclo`](#planes_macrociclo)
- [`roles`](#roles)
- [`rol_permisos`](#rol_permisos)
- [`sesiones_entrenamiento`](#sesiones_entrenamiento)
- [`sesiones_log`](#sesiones_log)
- [`staff`](#staff)
- [`usuarios`](#usuarios)

## `acadep_vocacional_leads`

| Columna | Tipo | Nulo | Default/Extra |
|---|---|---|---|
| id | int(11) | NO | - |
| nombre | varchar(150) | NO | - |
| email | varchar(150) | NO | - |
| whatsapp | varchar(20) | NO | - |
| score_tecnico | int(11) | NO | DEFAULT 0 |
| score_comercial | int(11) | NO | DEFAULT 0 |
| score_creativo | int(11) | NO | DEFAULT 0 |
| score_humano | int(11) | NO | DEFAULT 0 |
| perfil_resultado | varchar(100) | NO | - |
| tiempo_total_seg | float | NO | - |
| pregunta_mas_rapida | int(11) | NO | - |
| tiempo_mas_rapido_seg | float | NO | - |
| pregunta_mas_lenta | int(11) | NO | - |
| tiempo_mas_lento_seg | float | NO | - |
| telemetria_json | text | NO | - |
| ai_insight_manifesto | text | SI | - |
| created_at | timestamp | SI | NULL DEFAULT current_timestamp() |

## `alertas_renovacion`

| Columna | Tipo | Nulo | Default/Extra |
|---|---|---|---|
| id_alerta | int(10) UNSIGNED | NO | - |
| id_atleta | int(10) UNSIGNED | NO | - |
| id_membresia | int(10) UNSIGNED | NO | - |
| tipo_alerta | enum('amarillo','rojo') | NO | COMMENT 'amarillo = quedan 2 sesiones, rojo = 0 sesiones (sin sesiones)' |
| sesiones_restantes_momento | smallint(5) UNSIGNED | NO | - |
| atendida | tinyint(1) | NO | DEFAULT 0 |
| atendida_por | int(10) UNSIGNED | SI | - |
| fecha_atendida | datetime | SI | - |
| created_at | datetime | NO | DEFAULT current_timestamp() |
| updated_at | datetime | SI | ON UPDATE current_timestamp() |

## `asistencias`

| Columna | Tipo | Nulo | Default/Extra |
|---|---|---|---|
| id_asistencia | int(10) UNSIGNED | NO | - |
| id_atleta | int(10) UNSIGNED | NO | - |
| id_cita | int(10) UNSIGNED | SI | COMMENT 'FK lógica -> disponibilidad_agenda.id_cita (definida en 04_schema_agenda_sesiones.sql)' |
| id_membresia | int(10) UNSIGNED | SI | - |
| fecha_hora_checkin | datetime | NO | DEFAULT current_timestamp() |
| registrado_por | int(10) UNSIGNED | SI | - |
| created_at | datetime | NO | DEFAULT current_timestamp() |

## `atletas`

| Columna | Tipo | Nulo | Default/Extra |
|---|---|---|---|
| id_atleta | int(10) UNSIGNED | NO | - |
| id_lead | int(10) UNSIGNED | SI | - |
| nombre_completo | varchar(150) | NO | - |
| fecha_nacimiento | date | SI | - |
| sexo | enum('masculino','femenino','no_especificado') | NO | DEFAULT 'no_especificado' |
| telefono | varchar(20) | NO | - |
| email | varchar(150) | SI | - |
| deporte_principal | varchar(100) | SI | - |
| tipo_membresia | enum('sesion_unica','mensual','trimestral','semestral','anual') | NO | - |
| estatus | enum('activo','inactivo','suspendido') | NO | DEFAULT 'activo' |
| antecedentes_lesion | text | SI | - |
| antecedentes_lesion_normalizado | longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin | SI | CHECK (json_valid(`antecedentes_lesion_normalizado`)) |
| fuente_historial | enum('nuevo','migracion_excel','manual') | NO | DEFAULT 'nuevo' |
| fecha_ingreso | date | NO | - |
| fecha_ultimo_contacto | date | SI | - |
| created_at | datetime | NO | DEFAULT current_timestamp() |
| updated_at | datetime | SI | ON UPDATE current_timestamp() |

## `audit_log_medico`

| Columna | Tipo | Nulo | Default/Extra |
|---|---|---|---|
| id_log | int(10) UNSIGNED | NO | - |
| id_lead | int(10) UNSIGNED | SI | - |
| id_atleta | int(10) UNSIGNED | SI | - |
| canal | enum('whatsapp','instagram','facebook') | NO | - |
| fragmento_conversacion | text | NO | - |
| terminos_medicos_detectados | longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin | NO | CHECK (json_valid(`terminos_medicos_detectados`)) |
| nivel_confianza | decimal(3,2) | NO | - |
| capa_activada | enum('constitution','rag','confidence_gate','disclaimer','escalation') | NO | - |
| requiere_revision | tinyint(1) | NO | DEFAULT 1 |
| revisado_por | varchar(100) | SI | - |
| fecha_revision | datetime | SI | - |
| created_at | datetime | NO | DEFAULT current_timestamp() |

## `catalogo_servicios`

| Columna | Tipo | Nulo | Default/Extra |
|---|---|---|---|
| id_servicio | int(10) UNSIGNED | NO | - |
| nombre_servicio | varchar(200) | NO | - |
| descripcion_tecnica | text | NO | - |
| precio_base | decimal(10,2) | NO | - |
| duracion_minutos | int(10) UNSIGNED | NO | - |
| tipo_servicio | enum('evaluacion_inicial','entrenamiento','rehabilitacion','nutricion','paquete','asesoría') | NO | - |
| numero_sesiones_incluidas | smallint(5) UNSIGNED | SI | COMMENT 'Aplica cuando tipo_servicio = paquete (ej. "Performance 12 sesiones" -> 12)' |
| activo | tinyint(1) | NO | DEFAULT 1 |
| created_at | datetime | NO | DEFAULT current_timestamp() |
| updated_at | datetime | SI | ON UPDATE current_timestamp() |

## `detalles_ejercicio`

| Columna | Tipo | Nulo | Default/Extra |
|---|---|---|---|
| id_detalle | int(10) UNSIGNED | NO | - |
| id_sesion | int(10) UNSIGNED | NO | - |
| bloque | enum('masaje','movilidad','activacion','calentamiento','activacion_cadera','estiramiento_dinamico','integracion_movimiento','activacion_cognitiva','pliometria','parte_medular','vuelta_calma') | NO | - |
| orden | smallint(5) UNSIGNED | NO | DEFAULT 0 |
| nombre_ejercicio | varchar(200) | NO | - |
| sets | varchar(20) | SI | COMMENT 'Texto libre para admitir rangos (ej. "2-4")' |
| reps | varchar(20) | SI | - |
| intensidad | varchar(50) | SI | - |
| descanso | varchar(50) | SI | - |
| notas | varchar(255) | SI | - |

## `disponibilidad_agenda`

| Columna | Tipo | Nulo | Default/Extra |
|---|---|---|---|
| id_cita | int(10) UNSIGNED | NO | - |
| id_atleta | int(10) UNSIGNED | SI | - |
| id_lead | int(10) UNSIGNED | SI | - |
| id_staff | int(10) UNSIGNED | NO | - |
| id_servicio | int(10) UNSIGNED | NO | - |
| fecha_cita | date | NO | - |
| hora_inicio | time | NO | - |
| hora_fin | time | NO | - |
| cupo_maximo_hora | int(10) UNSIGNED | NO | DEFAULT 4 |
| estatus_cita | enum('disponible','reservada','confirmada','cancelada','completada','no_show') | NO | DEFAULT 'disponible' |
| notas_previas | text | SI | - |
| confirmacion_enviada | tinyint(1) | NO | DEFAULT 0 |
| recordatorio_enviado | tinyint(1) | NO | DEFAULT 0 |
| created_at | datetime | NO | DEFAULT current_timestamp() |
| updated_at | datetime | SI | ON UPDATE current_timestamp() |

## `evaluaciones_antropometria`

| Columna | Tipo | Nulo | Default/Extra |
|---|---|---|---|
| id_evaluacion | int(10) UNSIGNED | NO | - |
| id_atleta | int(10) UNSIGNED | NO | - |
| fecha_antropometria | date | NO | - |
| asesor | varchar(150) | SI | - |
| edad_evaluacion | tinyint(3) UNSIGNED | SI | - |
| peso_kg | decimal(5,2) | NO | - |
| estatura_cm | decimal(5,2) | NO | - |
| imc | decimal(5,2) | SI | - |
| clasificacion_imc | enum('bajo_peso','normal','sobrepeso','obesidad','obesidad_severa','obesidad_morbida') | SI | - |
| indice_ponderal | decimal(6,3) | SI | - |
| pliegue_tricipital | decimal(5,2) | SI | - |
| pliegue_bicipital | decimal(5,2) | SI | - |
| pliegue_subescapular | decimal(5,2) | SI | - |
| pliegue_abdominal | decimal(5,2) | SI | - |
| pliegue_ileocrestal | decimal(5,2) | SI | - |
| pliegue_supraespinal | decimal(5,2) | SI | - |
| pliegue_muslo | decimal(5,2) | SI | - |
| pliegue_pierna | decimal(5,2) | SI | - |
| sumatoria_pliegues | decimal(6,2) | SI | - |
| perimetro_brazo_relajado_der | decimal(5,2) | SI | - |
| perimetro_brazo_relajado_izq | decimal(5,2) | SI | - |
| perimetro_brazo_contraido_der | decimal(5,2) | SI | - |
| perimetro_brazo_contraido_izq | decimal(5,2) | SI | - |
| perimetro_muneca_der | decimal(5,2) | SI | - |
| perimetro_muneca_izq | decimal(5,2) | SI | - |
| perimetro_cintura_minima | decimal(5,2) | SI | - |
| perimetro_cadera_maxima | decimal(5,2) | SI | - |
| perimetro_muslo_der | decimal(5,2) | SI | - |
| perimetro_muslo_izq | decimal(5,2) | SI | - |
| perimetro_pierna_relajada_der | decimal(5,2) | SI | - |
| perimetro_pierna_relajada_izq | decimal(5,2) | SI | - |
| perimetro_pierna_contraida_der | decimal(5,2) | SI | - |
| perimetro_pierna_contraida_izq | decimal(5,2) | SI | - |
| diametro_humeral | decimal(5,2) | SI | - |
| diametro_femoral | decimal(5,2) | SI | - |
| diametro_estiloideo | decimal(5,2) | SI | - |
| diametro_biacromial | decimal(5,2) | SI | - |
| diametro_biiliocrestal | decimal(5,2) | SI | - |
| densidad_corporal | decimal(6,4) | SI | - |
| porcentaje_grasa_siri | decimal(5,2) | SI | - |
| masa_grasa_siri_kg | decimal(5,2) | SI | - |
| porcentaje_grasa_rocha | decimal(5,2) | SI | - |
| masa_osea_rocha_kg | decimal(5,2) | SI | - |
| masa_muscular_matiegka_kg | decimal(5,2) | SI | - |
| masa_residual_wurch_kg | decimal(5,2) | SI | - |
| clasificacion_grasa | enum('grasa_esencial','atletas','fitness','aceptable','sobregraso_moderado','sobregraso_riesgo','obeso','obeso_riesgo','obeso_morbido') | SI | - |
| endomorfia | decimal(4,2) | SI | - |
| mesomorfia | decimal(4,2) | SI | - |
| ectomorfia | decimal(4,2) | SI | - |
| indice_cintura_cadera | decimal(4,3) | SI | - |
| clasificacion_riesgo_cintura | enum('sin_riesgo','sin_peligro','peligro_metabolico') | SI | - |
| actividad_ejercicio_actual | varchar(255) | SI | - |
| frecuencia_ejercicio | varchar(100) | SI | - |
| duracion_por_sesion | varchar(100) | SI | - |
| intensidad_ejercicio | varchar(100) | SI | - |
| capturado_por | int(10) UNSIGNED | SI | - |
| created_at | datetime | NO | DEFAULT current_timestamp() |

## `evaluaciones_biomecanica`

| Columna | Tipo | Nulo | Default/Extra |
|---|---|---|---|
| id_evaluacion_biomecanica | int(10) UNSIGNED | NO | - |
| id_atleta | int(10) UNSIGNED | NO | - |
| fecha_evaluacion | date | NO | - |
| feet_flatten | tinyint(1) | NO | DEFAULT 0 COMMENT 'El arco del pie se aplana y prona' |
| feet_turn_out | tinyint(1) | NO | DEFAULT 0 COMMENT 'Sentadilla con pies rotados externamente' |
| heel_rises | tinyint(1) | NO | DEFAULT 0 COMMENT 'El peso se desplaza adelante y el talón se levanta' |
| knees_move_inward | tinyint(1) | NO | DEFAULT 0 COMMENT 'Valgo de rodilla' |
| excessive_forward_lean | tinyint(1) | NO | DEFAULT 0 COMMENT 'El tronco cae hacia adelante' |
| lower_back_arches | tinyint(1) | NO | DEFAULT 0 COMMENT 'Hiperextensión lumbar' |
| lower_back_rounds | tinyint(1) | NO | DEFAULT 0 COMMENT 'Flexión lumbar / retroversión pélvica' |
| arms_fall_forward | tinyint(1) | NO | DEFAULT 0 COMMENT 'Los brazos caen delante de las orejas' |
| observaciones | text | SI | - |
| evaluado_por | int(10) UNSIGNED | SI | - |
| created_at | datetime | NO | DEFAULT current_timestamp() |

## `evaluaciones_sft`

| Columna | Tipo | Nulo | Default/Extra |
|---|---|---|---|
| id_evaluacion_sft | int(10) UNSIGNED | NO | - |
| id_atleta | int(10) UNSIGNED | NO | - |
| fecha_evaluacion | date | NO | - |
| edad_evaluacion | tinyint(3) UNSIGNED | NO | - |
| sexo | enum('masculino','femenino') | NO | - |
| chair_sit_reach_cm | decimal(5,2) | SI | - |
| back_scratch_cm | decimal(5,2) | SI | - |
| functional_reach_cm | decimal(5,2) | SI | - |
| chair_stand_reps | tinyint(3) UNSIGNED | SI | - |
| arm_curl_reps | tinyint(3) UNSIGNED | SI | - |
| time_up_go_seg | decimal(4,2) | SI | - |
| time_up_go_cognitivo_seg | decimal(4,2) | SI | - |
| two_min_step_pasos | smallint(5) UNSIGNED | SI | - |
| semaforo_chair_sit_reach | enum('verde','amarillo','rojo') | SI | - |
| semaforo_back_scratch | enum('verde','amarillo','rojo') | SI | - |
| semaforo_chair_stand | enum('verde','amarillo','rojo') | SI | - |
| semaforo_arm_curl | enum('verde','amarillo','rojo') | SI | - |
| semaforo_time_up_go | enum('verde','amarillo','rojo') | SI | - |
| semaforo_two_min_step | enum('verde','amarillo','rojo') | SI | - |
| semaforo_general | enum('verde','amarillo','rojo') | SI | COMMENT 'Peor semáforo individual (regla de agregación conservadora)' |
| observaciones | text | SI | - |
| evaluado_por | int(10) UNSIGNED | SI | - |
| created_at | datetime | NO | DEFAULT current_timestamp() |

## `historial_clinico`

| Columna | Tipo | Nulo | Default/Extra |
|---|---|---|---|
| id_historial | int(10) UNSIGNED | NO | - |
| id_atleta | int(10) UNSIGNED | NO | - |
| tipo_historial | enum('mayor_65','menor_65') | NO | - |
| actividades_ejercicio_actual | text | SI | - |
| dias_ejercicio_moderado_semana | tinyint(3) UNSIGNED | SI | - |
| objetivo_perdida_peso | tinyint(3) UNSIGNED | SI | COMMENT 'Escala 0-10 (mayor_65) / 0-5 (menor_65), ver REGLA de escala en capa PHP' |
| objetivo_masa_muscular | tinyint(3) UNSIGNED | SI | - |
| objetivo_rendimiento_deportivo | tinyint(3) UNSIGNED | SI | - |
| objetivo_mejorar_salud | tinyint(3) UNSIGNED | SI | - |
| dieta_saludable_score | tinyint(3) UNSIGNED | SI | COMMENT 'Escala 0-10' |
| sigue_dieta_actual | text | SI | - |
| consumo_sal | enum('bajo','medio','alto') | SI | - |
| consumo_azucar | enum('bajo','medio','alto') | SI | - |
| consumo_grasas | enum('bajo','medio','alto') | SI | - |
| control_antojos_score | tinyint(3) UNSIGNED | SI | COMMENT 'Escala 0-10, sólo mayor_65' |
| bebidas_alcoholicas_semana | smallint(5) UNSIGNED | SI | - |
| consumo_cafeina | text | SI | COMMENT 'Sólo mayor_65' |
| sueno_adecuado | text | SI | - |
| nivel_estres_score | tinyint(3) UNSIGNED | SI | COMMENT 'Escala 0-10, sólo mayor_65' |
| tecnicas_manejo_estres | text | SI | COMMENT 'Sólo mayor_65' |
| fuma_o_vapea | text | SI | - |
| ocupacion | varchar(150) | SI | - |
| trabajo_sedentario_detalle | text | SI | - |
| trabajo_movimientos_repetitivos_detalle | text | SI | - |
| trabajo_calzado_tacon | tinyint(1) | SI | - |
| actividad_recreativa_detalle | text | SI | - |
| otro_pasatiempo_detalle | text | SI | - |
| cirugias_previas | text | SI | - |
| rehabilitacion_adecuada_autorizacion | text | SI | - |
| condicion_cronica | text | SI | - |
| medicamentos_actuales | text | SI | - |
| autorizacion_medica_ejercicio | tinyint(1) | SI | - |
| nombre_medico | varchar(150) | SI | - |
| telefono_medico | varchar(20) | SI | - |
| contacto_emergencia_nombre | varchar(150) | SI | - |
| contacto_emergencia_telefono | varchar(20) | SI | - |
| telefono_personal | varchar(20) | SI | - |
| correo_electronico | varchar(150) | SI | - |
| notas_adicionales | text | SI | - |
| fecha_captura | date | NO | - |
| capturado_por | int(10) UNSIGNED | SI | - |
| created_at | datetime | NO | DEFAULT current_timestamp() |
| updated_at | datetime | SI | ON UPDATE current_timestamp() |

## `leads_prospectos`

| Columna | Tipo | Nulo | Default/Extra |
|---|---|---|---|
| id_lead | int(10) UNSIGNED | NO | - |
| nombre_completo | varchar(150) | NO | - |
| telefono | varchar(20) | NO | - |
| email | varchar(150) | SI | - |
| canal_origen | enum('whatsapp','instagram','facebook') | NO | - |
| perfil_detectado | enum('atleta_competitivo','rehabilitacion','composicion_corporal','sin_clasificar') | NO | DEFAULT 'sin_clasificar' |
| objetivo_declarado | text | SI | - |
| consent_gate_status | enum('pendiente','aceptado','rechazado') | NO | DEFAULT 'pendiente' |
| consent_timestamp | datetime | SI | - |
| nlp_entidades_json | longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin | SI | CHECK (json_valid(`nlp_entidades_json`)) |
| confianza_nlp | decimal(3,2) | SI | - |
| estatus_lead | enum('nuevo','en_conversacion','agendado','convertido','descartado') | NO | DEFAULT 'nuevo' |
| churn_score | decimal(3,2) | SI | - |
| fecha_captura | datetime | NO | DEFAULT current_timestamp() |
| updated_at | datetime | SI | ON UPDATE current_timestamp() |

## `membresias`

| Columna | Tipo | Nulo | Default/Extra |
|---|---|---|---|
| id_membresia | int(10) UNSIGNED | NO | - |
| id_atleta | int(10) UNSIGNED | NO | - |
| id_servicio | int(10) UNSIGNED | NO | - |
| fecha_inicio | date | NO | - |
| fecha_fin | date | SI | COMMENT 'NULL para membresías tipo sesion_unica sin vigencia calendario' |
| sesiones_totales | smallint(5) UNSIGNED | NO | DEFAULT 0 |
| sesiones_restantes | smallint(5) UNSIGNED | NO | DEFAULT 0 |
| precio_pagado | decimal(10,2) | NO | - |
| estatus | enum('activa','agotada','vencida','cancelada') | NO | DEFAULT 'activa' |
| notas | varchar(255) | SI | - |
| created_at | datetime | NO | DEFAULT current_timestamp() |
| updated_at | datetime | SI | ON UPDATE current_timestamp() |

## `pagos_asistencia`

| Columna | Tipo | Nulo | Default/Extra |
|---|---|---|---|
| id_pago | int(10) UNSIGNED | NO | - |
| id_atleta | int(10) UNSIGNED | NO | - |
| id_membresia | int(10) UNSIGNED | SI | - |
| concepto_pago | varchar(200) | NO | COMMENT 'Ej. "Promo performance 12 sesiones" (columna Programa del Excel legacy)' |
| monto | decimal(10,2) | NO | - |
| metodo_pago | enum('efectivo','tarjeta','transferencia','otro') | NO | DEFAULT 'efectivo' |
| fecha_pago | date | NO | - |
| registrado_por | int(10) UNSIGNED | SI | - |
| created_at | datetime | NO | DEFAULT current_timestamp() |

## `percentiles_sft_referencia`

| Columna | Tipo | Nulo | Default/Extra |
|---|---|---|---|
| id_percentil | smallint(5) UNSIGNED | NO | - |
| sexo | enum('masculino','femenino') | NO | - |
| edad_min | tinyint(3) UNSIGNED | NO | - |
| edad_max | tinyint(3) UNSIGNED | NO | - |
| variable | enum('chair_sit_reach','back_scratch','chair_stand','arm_curl','time_up_go','two_min_step') | NO | - |
| valor_min | decimal(6,2) | NO | - |
| valor_max | decimal(6,2) | NO | - |
| unidad | varchar(20) | NO | COMMENT 'cm, reps, segundos, pasos' |

## `permisos`

| Columna | Tipo | Nulo | Default/Extra |
|---|---|---|---|
| id_permiso | smallint(5) UNSIGNED | NO | - |
| clave_permiso | varchar(100) | NO | - |
| modulo | varchar(50) | NO | - |
| descripcion | varchar(255) | NO | - |

## `planes_macrociclo`

| Columna | Tipo | Nulo | Default/Extra |
|---|---|---|---|
| id_macro | int(10) UNSIGNED | NO | - |
| id_atleta | int(10) UNSIGNED | NO | - |
| temporada | varchar(100) | SI | - |
| mesociclo | enum('prep_general','prep_especifica','competitiva','transitorio') | NO | - |
| mes | enum('enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre') | NO | - |
| tipo_microciclo | enum('ajuste','activacion','carga','competicion','impacto','recuperacion') | SI | - |
| volumen | tinyint(3) UNSIGNED | SI | COMMENT 'Escala 0-10 de énfasis del atributo en el periodo' |
| velocidad | tinyint(3) UNSIGNED | SI | - |
| fuerza | tinyint(3) UNSIGNED | SI | - |
| resistencia | tinyint(3) UNSIGNED | SI | - |
| flexibilidad | tinyint(3) UNSIGNED | SI | - |
| tecnica | tinyint(3) UNSIGNED | SI | - |
| agilidad | tinyint(3) UNSIGNED | SI | - |
| total_horas | decimal(5,2) | SI | - |
| dias_microciclo | tinyint(3) UNSIGNED | SI | - |
| creado_por | int(10) UNSIGNED | SI | - |
| created_at | datetime | NO | DEFAULT current_timestamp() |
| updated_at | datetime | SI | ON UPDATE current_timestamp() |

## `roles`

| Columna | Tipo | Nulo | Default/Extra |
|---|---|---|---|
| id_rol | tinyint(3) UNSIGNED | NO | - |
| clave_rol | enum('super_admin','admin','coach') | NO | - |
| nombre_rol | varchar(100) | NO | - |
| descripcion | text | SI | - |
| activo | tinyint(1) | NO | DEFAULT 1 |
| created_at | datetime | NO | DEFAULT current_timestamp() |

## `rol_permisos`

| Columna | Tipo | Nulo | Default/Extra |
|---|---|---|---|
| id_rol | tinyint(3) UNSIGNED | NO | - |
| id_permiso | smallint(5) UNSIGNED | NO | - |

## `sesiones_entrenamiento`

| Columna | Tipo | Nulo | Default/Extra |
|---|---|---|---|
| id_sesion | int(10) UNSIGNED | NO | - |
| id_atleta | int(10) UNSIGNED | NO | - |
| id_cita | int(10) UNSIGNED | SI | - |
| id_staff | int(10) UNSIGNED | NO | - |
| id_macro | int(10) UNSIGNED | SI | - |
| fecha_sesion | date | NO | - |
| numero_sesion | smallint(5) UNSIGNED | SI | COMMENT 'Consecutivo de sesión dentro del microciclo/paquete' |
| enfoque | varchar(150) | SI | - |
| fase | enum('prep_general','prep_especifica','competitiva','transitorio') | SI | - |
| rpe_sesion | decimal(3,1) UNSIGNED | SI | COMMENT 'Escala 1-10 (Pie de Cancha slider)' |
| notas_entrenador | text | SI | - |
| created_by | int(10) UNSIGNED | SI | - |
| created_at | datetime | NO | DEFAULT current_timestamp() |

## `sesiones_log`

| Columna | Tipo | Nulo | Default/Extra |
|---|---|---|---|
| id_log_sesion | bigint(20) UNSIGNED | NO | - |
| id_usuario | int(10) UNSIGNED | SI | COMMENT 'NULL si el intento de login falló antes de resolver el usuario (email inexistente)' |
| email_intento | varchar(150) | NO | - |
| tipo_evento | enum('login_exitoso','login_fallido','logout','bloqueo_temporal','cambio_password','token_csrf_invalido') | NO | - |
| ip_origen | varchar(45) | NO | COMMENT 'Soporta IPv4 e IPv6' |
| user_agent | varchar(255) | SI | - |
| created_at | datetime | NO | DEFAULT current_timestamp() |

## `staff`

| Columna | Tipo | Nulo | Default/Extra |
|---|---|---|---|
| id_staff | int(10) UNSIGNED | NO | - |
| nombre_completo | varchar(150) | NO | - |
| especialidad | varchar(100) | NO | - |
| telefono | varchar(20) | SI | - |
| email | varchar(150) | NO | - |
| activo | tinyint(1) | NO | DEFAULT 1 |
| created_at | datetime | NO | DEFAULT current_timestamp() |

## `usuarios`

| Columna | Tipo | Nulo | Default/Extra |
|---|---|---|---|
| id_usuario | int(10) UNSIGNED | NO | - |
| id_rol | tinyint(3) UNSIGNED | NO | - |
| id_staff | int(10) UNSIGNED | SI | COMMENT 'FK lógica -> staff.id_staff (staff se crea en 04_schema_agenda_sesiones.sql; FK se agrega en ese script)' |
| nombre_completo | varchar(150) | NO | - |
| email | varchar(150) | NO | - |
| password_hash | varchar(255) | NO | COMMENT 'Generado con password_hash() PHP (bcrypt/argon2), nunca texto plano' |
| activo | tinyint(1) | NO | DEFAULT 1 |
| requiere_cambio_password | tinyint(1) | NO | DEFAULT 1 |
| ultimo_login | datetime | SI | - |
| intentos_fallidos | tinyint(3) UNSIGNED | NO | DEFAULT 0 |
| bloqueado_hasta | datetime | SI | COMMENT 'Bloqueo temporal tras exceder intentos fallidos' |
| created_at | datetime | NO | DEFAULT current_timestamp() |
| updated_at | datetime | SI | ON UPDATE current_timestamp() |

