import { DIFFERENTIATION_PILLARS, DIFFERENTIATION_SPOTLIGHT } from "@/lib/athlosContent"
import { getSocialEmbedSrc } from "@/lib/socialEmbed"

export function DifferentiationSection() {
  const embedSrc = getSocialEmbedSrc(DIFFERENTIATION_SPOTLIGHT.provider, DIFFERENTIATION_SPOTLIGHT.href)

  return (
    <section className="section section--surface" aria-labelledby="differentiation-title">
      <div className="section__inner">
        <div className="section-heading">
          <p className="section-kicker">Diferenciación operativa</p>
          <h2 id="differentiation-title">¿Por qué Athlos no es un gimnasio convencional?</h2>
          <p>
            La mayoría de los programas comienzan con ejercicios. Nosotros comenzamos con
            evidencia. Cada persona es sometida a una evaluación integral que permite identificar
            limitaciones funcionales, patrones de movimiento, capacidades físicas y factores de
            riesgo antes de diseñar cualquier intervención. Esto nos permite crear programas más
            seguros, eficientes y personalizados.
          </p>
        </div>
        <div className="pillar-grid">
          {DIFFERENTIATION_PILLARS.map((pillar, index) => (
            <article className="pillar-card" key={pillar.title} style={{ animationDelay: `${index * 90}ms` }}>
              <span className="pillar-card__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <circle cx="12" cy="12" r="9" stroke="currentColor" strokeWidth="1.4" />
                  <path d="M12 7v5l3.2 1.9" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" />
                </svg>
              </span>
              <h3>{pillar.title}</h3>
              <p>{pillar.description}</p>
            </article>
          ))}
        </div>

        <div className="evidence-spotlight">
          <div className="evidence-spotlight__copy">
            <p className="section-kicker">Evidencia en movimiento</p>
            <h3>Así medimos cada repetición</h3>
            <p>
              Cada evaluación se traduce en datos accionables. Mira cómo capturamos el movimiento
              dentro del laboratorio antes de prescribir cualquier carga.
            </p>
            <a
              className="evidence-spotlight__link"
              href={DIFFERENTIATION_SPOTLIGHT.href}
              target="_blank"
              rel="noopener noreferrer"
            >
              Ver en {DIFFERENTIATION_SPOTLIGHT.provider}
            </a>
          </div>
          <div className="evidence-spotlight__frame">
            <iframe
              src={embedSrc}
              loading="lazy"
              title="Video de Athlos Performance"
              allow="encrypted-media; picture-in-picture"
              allowFullScreen
            />
          </div>
        </div>
      </div>
    </section>
  )
}
