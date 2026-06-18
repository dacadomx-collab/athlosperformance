export const ATHLOS_NAV_ITEMS = [
  { label: "Metodología", href: "#metodologia" },
  { label: "Evaluación Médica", href: "#evaluacion" },
  { label: "Nuestro Equipo", href: "#staff" },
  { label: "Evidencia", href: "#autoridad" },
  { label: "Contacto", href: "#contacto" }
] as const

export const DIFFERENTIATION_SPOTLIGHT = {
  provider: "Instagram",
  label: "Laboratorio en movimiento",
  href: "https://www.instagram.com/reel/DYxBlVyN9tl/?utm_source=ig_web_button_share_sheet&igsh=MzRlODBiNWFlZA=="
} as const

export const COACHES = [
  {
    slug: "bernardo-lobo",
    name: "Bernardo Lobo",
    role: "Coach Athlos Performance",
    expertise:
      "Fuerza y acondicionamiento físico, con enfoque en progresión de carga segura y prevención de lesiones."
  },
  {
    slug: "luis-moctezuma",
    name: "Luis Moctezuma",
    role: "Coach Athlos Performance",
    expertise:
      "Análisis de movimiento y readaptación funcional para un regreso seguro a la actividad física."
  },
  {
    slug: "arturo-naranjo",
    name: "Arturo Naranjo",
    role: "Asesor Athlos Performance",
    expertise:
      "Asesoría en composición corporal y antropometría; interpretación de datos de cada evaluación."
  },
  {
    slug: "roberto-lopez",
    name: "Roberto López",
    role: "Coach Athlos Performance",
    expertise:
      "Seguimiento de cargas y periodización del entrenamiento para sostener el progreso en el tiempo."
  }
] as const

export const EVIDENCE_LINKS = [
  {
    provider: "Facebook",
    label: "Sesión de fuerza controlada en laboratorio",
    href: "https://www.facebook.com/share/r/1D6BnnZR3F/"
  },
  {
    provider: "Instagram",
    label: "Resultados medibles de un ciclo de entrenamiento",
    href: "https://www.instagram.com/p/DX7OY33Ac31/?utm_source=ig_web_button_share_sheet&igsh=MzRlODBiNWFlZA=="
  },
  {
    provider: "Instagram",
    label: "Detrás de cámaras: evaluación funcional",
    href: "https://www.instagram.com/reel/DXsJZEfkhC3/?utm_source=ig_web_button_share_sheet&igsh=MzRlODBiNWFlZA=="
  }
] as const

export const CERTIFICATIONS = [
  { name: "ISAK", description: "Antropometría y composición corporal" },
  { name: "McKenzie", description: "Diagnóstico y terapia mecánica de columna" },
  { name: "Mulligan", description: "Terapia manual y movilización articular" }
] as const

export const ATHLOS_LOCAL_VIDEOS = {
  evaluacion: {
    src: "/media/evaluacion-potencia-tren-inferior-atletas-athlos.mp4",
    poster: "/media/evaluacion-potencia-tren-inferior-atletas-athlos-poster.jpg",
    label: "Evaluación de potencia en tren inferior"
  },
  prescripcion: {
    src: "/media/prescripcion-carga-mecanica-entrenamiento-athlos.mp4",
    poster: "/media/prescripcion-carga-mecanica-entrenamiento-athlos-poster.jpg",
    label: "Prescripción de carga mecánica"
  },
  seguimiento: {
    src: "/media/entrenamiento-fuerza-controlada-laboratorio-athlos.mp4",
    poster: "/media/entrenamiento-fuerza-controlada-laboratorio-athlos-poster.jpg",
    label: "Seguimiento de fuerza controlada"
  }
} as const

export const ATHLOS_HERO_VIDEO = {
  src: "/media/entrenamiento-fuerza-controlada-laboratorio-athlos.mp4",
  poster: "/media/entrenamiento-fuerza-controlada-laboratorio-athlos-poster.jpg"
} as const

export const DIFFERENTIATION_PILLARS = [
  {
    title: "Limitaciones funcionales",
    description: "Identificamos restricciones de movimiento antes de cargar el sistema."
  },
  {
    title: "Patrones de movimiento",
    description: "Mapeamos compensaciones y riesgo biomecánico con datos, no con apariencia."
  },
  {
    title: "Factores de riesgo",
    description: "Filtramos cada caso clínico para diseñar una intervención segura y eficiente."
  }
] as const

export const METHODOLOGY_PHASES = [
  {
    step: "01",
    icon: "clipboard",
    title: "Evaluación Integral",
    description:
      "Historial clínico, composición corporal, análisis biomecánico y evaluación funcional."
  },
  {
    step: "02",
    icon: "dashboard",
    title: "Interpretación de Datos",
    description:
      "Analizamos la información obtenida para identificar fortalezas, limitaciones y oportunidades de mejora clínica."
  },
  {
    step: "03",
    icon: "molecule",
    title: "Prescripción Personalizada",
    description:
      "Diseñamos un programa de cargas mecánicas específico basado en evidencia científica y objetivos individuales."
  },
  {
    step: "04",
    icon: "graph",
    title: "Seguimiento y Optimización",
    description:
      "Medimos el progreso periódicamente para ajustar cada variable del programa con precisión matemática."
  }
] as const

export const SOCIAL_EVIDENCE_LINKS = {
  atletas: {
    provider: "Instagram",
    label: "Evaluación de potencia en tren inferior",
    href: "https://www.instagram.com/reel/DY1CrjOzZUC/?utm_source=ig_web_button_share_sheet&igsh=MzRlODBiNWFlZA=="
  },
  longevidad: {
    provider: "Facebook",
    label: "Sesión de movilidad y equilibrio dinámico",
    href: "https://www.facebook.com/share/r/1FkpXU2h2v/"
  }
} as const

export const SEGMENT_CONTENT = {
  atletas: {
    tabLabel: "Menores de 65",
    eyebrow: "Alto Rendimiento",
    evaluationTitle: "Tu Ficha de Evaluación Clínica",
    evaluationBody:
      "Analizamos tu estructura desde el interior. Calculamos tu densidad corporal, porcentaje de grasa exacta (Fórmula de Siri), masa ósea (Fórmula de Rocha) y te ubicamos en una Somatocarta matemática. Adicionalmente, evaluamos tus patrones de movimiento (como la compensación de rodillas en sentadilla profunda) para asegurar un entrenamiento libre de fricción articular.",
    metrics: ["Somatocarta", "Fórmula de Siri", "Fórmula de Rocha", "Patrones de movimiento"],
    planningTerms: ["Mesociclo", "Microciclo", "Sesión"],
    solutionTitle: "Datos para competir al máximo nivel",
    solutionBody:
      "Ayudamos a atletas y personas activas a optimizar su rendimiento mediante evaluaciones objetivas que permiten mejorar la fuerza, potencia, movilidad y prevención de lesiones.",
    benefits: ["Mayor eficiencia biomecánica", "Programación basada en evidencia", "Reducción de riesgo de lesión"]
  },
  longevidad: {
    tabLabel: "Mayores de 65",
    eyebrow: "Longevidad Clínica",
    evaluationTitle: "Tu Ficha de Evaluación Clínica",
    evaluationBody:
      "Evaluamos los biomarcadores que garantizan tu independencia. Aplicamos el Senior Fitness Test, midiendo fuerza del tren inferior (Chair Stand), agilidad, equilibrio dinámico y riesgo de caída mediante la prueba Timed Up & Go (TUG) con carga cognitiva. Un segundo de mejora en estas pruebas transforma tu seguridad y calidad de vida.",
    metrics: ["Senior Fitness Test", "Chair Stand", "Equilibrio dinámico", "TUG cognitivo"],
    planningTerms: ["Mesociclo", "Microciclo", "Sesión"],
    solutionTitle: "Más años de vida. Más vida en esos años.",
    solutionBody:
      "Programas especializados para adultos mayores, diseñados a partir de criterios geriátricos que priorizan la seguridad, la preservación de la masa ósea (mitigando la osteopenia) y la autonomía.",
    benefits: ["Prevención de sarcopenia y caídas", "Mejora del equilibrio dinámico", "Entorno médico supervisado"]
  }
} as const

export type AthlosSegment = keyof typeof SEGMENT_CONTENT

export const ATHLOS_CONTACT = {
  instagram: "https://www.instagram.com/athlosperformance.bcs",
  facebook: "https://www.facebook.com/profile.php?id=61577545693430",
  email: "athlos.performance01@gmail.com",
  phone: "6122047708",
  address: "Calle Altamirano #2730, La Paz, Baja California Sur 23000"
} as const

export const ATHLOS_WHATSAPP_HREF = `https://wa.me/52${ATHLOS_CONTACT.phone}`

export const ATHLOS_MAPS_HREF = "https://maps.app.goo.gl/jxgaXbnfth5a8xmc9"
