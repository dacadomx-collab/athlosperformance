import { ATHLOS_LOCAL_VIDEOS, METHODOLOGY_PHASES } from "@/lib/athlosContent"
import { AthlosVideoPlayer } from "@/components/athlos/AthlosVideoPlayer"

type PhaseIcon = (typeof METHODOLOGY_PHASES)[number]["icon"]

function PhaseIconGlyph({ icon }: { icon: PhaseIcon }) {
  switch (icon) {
    case "clipboard":
      return (
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="6" y="4" width="12" height="17" rx="2" stroke="currentColor" strokeWidth="1.4" />
          <path d="M9 4V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1" stroke="currentColor" strokeWidth="1.4" />
          <path d="M9 11h6M9 15h4" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" />
        </svg>
      )
    case "dashboard":
      return (
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="3" y="4" width="7" height="7" rx="1.2" stroke="currentColor" strokeWidth="1.4" />
          <rect x="14" y="4" width="7" height="11" rx="1.2" stroke="currentColor" strokeWidth="1.4" />
          <rect x="3" y="14" width="7" height="7" rx="1.2" stroke="currentColor" strokeWidth="1.4" />
        </svg>
      )
    case "molecule":
      return (
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="6" cy="7" r="2.2" stroke="currentColor" strokeWidth="1.4" />
          <circle cx="18" cy="7" r="2.2" stroke="currentColor" strokeWidth="1.4" />
          <circle cx="12" cy="18" r="2.2" stroke="currentColor" strokeWidth="1.4" />
          <path d="M8 8.2 10.2 16M16 8.2 13.8 16M8.2 7h7.6" stroke="currentColor" strokeWidth="1.4" />
        </svg>
      )
    case "graph":
      return (
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M4 19V9M10 19V5M16 19v-7M22 19H2" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" />
          <path d="M4 12 9 7l4 3 6-6" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
      )
  }
}

function PhaseMedia({ step }: { step: string }) {
  switch (step) {
    case "01":
      return (
        <AthlosVideoPlayer
          src={ATHLOS_LOCAL_VIDEOS.evaluacion.src}
          poster={ATHLOS_LOCAL_VIDEOS.evaluacion.poster}
          label={ATHLOS_LOCAL_VIDEOS.evaluacion.label}
        />
      )
    case "02":
      return (
        <div className="phase-data-panel">
          <span className="phase-data-panel__grid" aria-hidden="true" />
          <div className="phase-data-panel__bars" aria-hidden="true">
            <span />
            <span />
            <span />
            <span />
          </div>
          <span className="phase-data-panel__label">Procesamiento de datos</span>
        </div>
      )
    case "03":
      return (
        <AthlosVideoPlayer
          src={ATHLOS_LOCAL_VIDEOS.prescripcion.src}
          poster={ATHLOS_LOCAL_VIDEOS.prescripcion.poster}
          label={ATHLOS_LOCAL_VIDEOS.prescripcion.label}
        />
      )
    case "04":
      return (
        <AthlosVideoPlayer
          src={ATHLOS_LOCAL_VIDEOS.seguimiento.src}
          poster={ATHLOS_LOCAL_VIDEOS.seguimiento.poster}
          label={ATHLOS_LOCAL_VIDEOS.seguimiento.label}
        />
      )
    default:
      return null
  }
}

export function MethodologyTimeline() {
  return (
    <section className="section" id="metodologia" aria-labelledby="methodology-title">
      <div className="section__inner">
        <div className="section-heading">
          <p className="section-kicker">Metodología</p>
          <h2 id="methodology-title">Nuestra Metodología Científica</h2>
          <p>Un proceso estructurado basado en evaluación, análisis y seguimiento continuo.</p>
        </div>
        <ol className="timeline">
          {METHODOLOGY_PHASES.map((phase, index) => (
            <li className="timeline__phase" key={phase.step} style={{ animationDelay: `${index * 110}ms` }}>
              <div className="timeline__phase-media">
                <PhaseMedia step={phase.step} />
                <span className="timeline__phase-icon">
                  <PhaseIconGlyph icon={phase.icon} />
                </span>
              </div>
              <span className="timeline__phase-step">{phase.step}</span>
              <h3>{phase.title}</h3>
              <p>{phase.description}</p>
            </li>
          ))}
        </ol>
      </div>
    </section>
  )
}
