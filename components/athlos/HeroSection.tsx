import { ATHLOS_HERO_VIDEO } from "@/lib/athlosContent"
import { CtaButton } from "@/components/athlos/CtaButton"
import { HeroBackgroundVideo } from "@/components/athlos/HeroBackgroundVideo"

const heroMetrics = [
  ["Evaluación integral"],
  ["Interpretación de datos"],
  ["Prescripción personalizada"]
] as const

export function HeroSection() {
  return (
    <section className="hero" aria-labelledby="hero-title">
      <div className="hero__inner">
        <div className="hero__content">
          <p className="hero__kicker">Sport Science Lab · La Paz BCS</p>
          <div className="hero__title-group">
            <h1 id="hero-title">El rendimiento se construye con datos,<br /> no con suposiciones.</h1>
            <p className="hero__subtitle">
              Laboratorio de Ciencias del Deporte. Transformamos datos biomecánicos,
              funcionales y clínicos en programas de entrenamiento personalizados para rendimiento deportivo,
              salud y longevidad.
            </p>
          </div>
          <div className="hero__actions">
            <CtaButton href="#consent-gate">Agenda tu clase muestra</CtaButton>
            <CtaButton href="#metodologia" variant="secondary">
              Conoce nuestra metodología
            </CtaButton>
          </div>
        </div>

        <div className="hero-visual" aria-label="Panel visual de análisis Athlos">
          <HeroBackgroundVideo src={ATHLOS_HERO_VIDEO.src} poster={ATHLOS_HERO_VIDEO.poster} />
          <span className="hero-visual__tint" aria-hidden="true" />
          <div className="hero-visual__card hero-visual__card--primary">
            <span>Biomecánica</span>
            <strong>92%</strong>
            <small>patrón estable</small>
          </div>
          <div className="hero-visual__card hero-visual__card--secondary">
            <span>Carga óptima</span>
            <strong>4 fases</strong>
            <small>seguimiento continuo</small>
          </div>
        </div>
      </div>
      <div className="hero__metrics" aria-label="Resumen metodológico">
        {heroMetrics.map(([step, label]) => (
          <div key={step}>
            <span>{step}</span>
            <p>{label}</p>
          </div>
        ))}
      </div>
    </section>
  )
}
