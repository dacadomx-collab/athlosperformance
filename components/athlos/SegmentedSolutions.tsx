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

      <a
        className="media-card"
        href={media.href}
        target="_blank"
        rel="noopener noreferrer"
        aria-label={`Ver evidencia en movimiento en ${media.provider}: ${media.label}`}
      >
        <span className="media-card__play" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M8 6v12l10-6-10-6Z" fill="currentColor" />
          </svg>
        </span>
        <span className="media-card__meta">
          <span className="media-card__provider">{media.provider}</span>
          <span>{media.label}</span>
        </span>
      </a>

      <CtaButton className="segment-panel__cta" href="#consent-gate">
        Agendar Evaluación Inicial
      </CtaButton>
    </div>
  )
}
