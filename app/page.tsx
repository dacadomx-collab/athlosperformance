import { AthlosFooter } from "@/components/athlos/AthlosFooter"
import { AthlosHeader } from "@/components/athlos/AthlosHeader"
import { DifferentiationSection } from "@/components/athlos/DifferentiationSection"
import { EvaluationSplitSection } from "@/components/athlos/EvaluationSplitSection"
import { HeroSection } from "@/components/athlos/HeroSection"
import { MethodologyTimeline } from "@/components/athlos/MethodologyTimeline"
import { ServiceCard } from "@/components/athlos/ServiceCard"
import { TeamSection } from "@/components/athlos/TeamSection"

const serviceCards = [
  {
    eyebrow: "Atletas",
    title: "Rendimiento medible",
    description:
      "Evaluación de composición corporal, fuerza, movilidad y patrones de movimiento para programar cargas con intención.",
    metric: "Somatocarta + biomecánica"
  },
  {
    eyebrow: "Longevidad",
    title: "Autonomía funcional",
    description:
      "Protocolos para adultos mayores basados en Senior Fitness Test, equilibrio dinámico y prevención de caídas.",
    metric: "SFT + TUG cognitivo"
  },
  {
    eyebrow: "Seguimiento",
    title: "Optimización continua",
    description:
      "Cada ciclo se revisa con datos: progreso, respuesta a la carga y ajustes precisos para entrenar mejor.",
    metric: "Datos → decisión"
  }
]

export default function HomePage() {
  return (
    <div className="site-shell">
      <AthlosHeader />
      <main>
        <HeroSection />
        <DifferentiationSection />
        <MethodologyTimeline />
        <TeamSection />
        <EvaluationSplitSection />
        <section className="section" aria-labelledby="base-components-title">
          <div className="section__inner">
            <div className="section-heading">
              <p className="section-kicker">Arquitectura de servicio</p>
              <h2 id="base-components-title">Programas diseñados desde evidencia, no intuición.</h2>
              <p>
                Este primer bloque deja lista la base reusable para desplegar la metodología completa en los siguientes módulos.
              </p>
            </div>
            <div className="service-grid">
              {serviceCards.map((card) => (
                <ServiceCard key={card.title} {...card} />
              ))}
            </div>
          </div>
        </section>
      </main>
      <AthlosFooter />
    </div>
  )
}
