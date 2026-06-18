import { ATHLOS_WHATSAPP_HREF } from "@/lib/athlosContent"
import { CtaButton } from "@/components/athlos/CtaButton"

export function FinalCtaSection() {
  return (
    <section className="final-cta" aria-labelledby="final-cta-title">
      <div className="section__inner final-cta__inner">
        <h2 id="final-cta-title">Conoce cómo se mueve tu cuerpo antes de decidir cómo entrenarlo.</h2>
        <p>
          Agenda una evaluación inicial y recibe un análisis profesional para construir un
          programa basado en evidencia científica y objetivos reales.
        </p>
        <div className="final-cta__actions">
          <CtaButton href="#consent-gate">Iniciar Onboarding Médico</CtaButton>
          <CtaButton href={ATHLOS_WHATSAPP_HREF} variant="secondary" target="_blank" rel="noopener noreferrer">
            Hablar con el Staff Médico
          </CtaButton>
        </div>
        <p className="final-cta__note">
          ¿Presenta hernias o desgaste articular? Permita que nuestro equipo valide su caso.
        </p>
      </div>
    </section>
  )
}
