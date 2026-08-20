import { RECOMENDACIONES } from "@/lib/athlosContent"
import { SocialEmbedFacade } from "@/components/athlos/SocialEmbedFacade"

export function RecomendacionesSection() {
  return (
    <section className="section section--surface" id="recomendaciones" aria-labelledby="recomendaciones-title">
      <div className="section__inner">
        <div className="section-heading">
          <p className="section-kicker">Recomendaciones</p>
          <h2 id="recomendaciones-title">Transformaciones respaldadas por datos</h2>
          <p>
            Atletas y pacientes que llegaron con un objetivo clínico o deportivo específico y hoy lo
            sostienen con evidencia medible, no con apariencia.
          </p>
        </div>

        <ul className="recommendation-grid">
          {RECOMENDACIONES.map((item) => (
            <li className="recommendation-card" key={item.slug}>
              <div className="recommendation-card__header">
                <span className="recommendation-card__avatar" aria-hidden="true">
                  {item.name.charAt(0)}
                </span>
                <div className="recommendation-card__identity">
                  <cite>{item.name}</cite>
                  <span className="recommendation-card__badge">{item.discipline}</span>
                </div>
              </div>

              <blockquote className="recommendation-card__quote">
                <p>&ldquo;{item.quote}&rdquo;</p>
              </blockquote>

              {item.metrics.length > 0 && (
                <ul className="recommendation-card__metrics">
                  {item.metrics.map((metric) => (
                    <li key={metric.label}>
                      <span>{metric.label}</span>
                      <strong>{metric.value}</strong>
                    </li>
                  ))}
                </ul>
              )}

              {item.social && (
                <SocialEmbedFacade
                  provider={item.social.provider}
                  label={item.social.label}
                  href={item.social.href}
                  thumbnail={item.social.thumbnail}
                />
              )}
            </li>
          ))}
        </ul>
      </div>
    </section>
  )
}
