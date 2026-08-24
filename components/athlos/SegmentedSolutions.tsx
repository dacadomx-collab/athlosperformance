import { SEGMENT_CONTENT, SOCIAL_EVIDENCE_LINKS, type AthlosSegment } from "@/lib/athlosContent"
import { CtaButton } from "@/components/athlos/CtaButton"

interface SegmentedSolutionsProps {
  segment: AthlosSegment
}

export function SegmentedSolutions({ segment }: SegmentedSolutionsProps) {
  const content = SEGMENT_CONTENT[segment]
  const media = SOCIAL_EVIDENCE_LINKS[segment]
  const isLongevidad = segment === "longevidad"

  return (
    <div className="segment-panel" role="tabpanel" id={`panel-${segment}`} aria-labelledby={`tab-${segment}`}>
      <div className="segment-panel__evaluation">
        <p className="segment-panel__eyebrow">{content.eyebrow}</p>
        <h3>{content.evaluationTitle}</h3>
        <p>{content.evaluationBody}</p>
        <ul className="metric-chip-list">
          {content.metrics.map((metric) => (
            <li className="metric-chip" key={metric}>
              {metric}
            </li>
          ))}
        </ul>
        <p className="segment-panel__planning">
          Periodización registrada por {content.planningTerms.join(" → ")}.
        </p>
      </div>

      <div className="segment-panel__solution">
        <h3>{content.solutionTitle}</h3>
        <p>{content.solutionBody}</p>
        <ul className="benefit-list">
          {content.benefits.map((benefit) => (
            <li key={benefit}>{benefit}</li>
          ))}
        </ul>
        {isLongevidad && (
          <p className="segment-panel__disclaimer">
            Cada plan es una prescripción clínica supervisada, no una rutina genérica.
          </p>
        )}
      </div>

      {/* --- CUADRANTE 3: Abajo Izquierda (¡El video en tamaño Pro!) --- */}
      {content.videoUrl ? (
        <div className="media-card" style={{ display: 'flex', alignItems: 'center', gap: '1.5rem', cursor: 'default', textDecoration: 'none', padding: '1.25rem' }}>
          <div style={{ width: '120px', height: '213px', borderRadius: '10px', overflow: 'hidden', flexShrink: 0, border: '1px solid rgba(255,255,255,0.1)', boxShadow: '0 8px 20px rgba(0,0,0,0.4)' }}>
            <video
              src={content.videoUrl}
              autoPlay
              muted
              loop
              playsInline
              style={{ width: '100%', height: '100%', objectFit: 'cover' }}
            />
          </div>
          <span className="media-card__meta">
            <span className="media-card__provider" style={{ color: '#00f2fe', fontSize: '1.1rem', fontWeight: 600 }}>Registro Visual</span>
            <span style={{ fontSize: '0.95rem', color: '#cbd5e1', marginTop: '8px', display: 'block', lineHeight: 1.5 }}>
              Ejecución biomecánica directa en el laboratorio.
            </span>
          </span>
        </div>
      ) : (
        <div /> 
      )}

      <CtaButton className="segment-panel__cta" href="#consent-gate">
        Agenda tu clase muestra
      </CtaButton>
    </div>
  )
}
