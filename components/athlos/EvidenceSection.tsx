import { CERTIFICATIONS, EVIDENCE_LINKS } from "@/lib/athlosContent"
import { SocialEmbedFacade } from "@/components/athlos/SocialEmbedFacade"

export function EvidenceSection() {
  return (
    <section className="section section--surface" id="autoridad" aria-labelledby="evidence-title">
      <div className="section__inner">
        <div className="section-heading">
          <p className="section-kicker">Autoridad científica</p>
          <h2 id="evidence-title">La ciencia detrás de cada decisión</h2>
          <p>
            Nuestro sistema integra principios de biomecánica, control motor, ciencias del
            envejecimiento saludable y prevención de lesiones, respaldado por una red médica
            local y certificaciones internacionales. Porque mejorar el rendimiento no depende de
            entrenar más. Depende de entrenar mejor.
          </p>
        </div>

        <ul className="certification-row">
          {CERTIFICATIONS.map((cert) => (
            <li className="certification-chip" key={cert.name}>
              <strong>{cert.name}</strong>
              <span>{cert.description}</span>
            </li>
          ))}
        </ul>

        <div className="section-heading">
          <p className="section-kicker">Evidencia en movimiento</p>
          <h3>Así se ve el laboratorio en acción</h3>
        </div>

        <div className="evidence-grid">
          {EVIDENCE_LINKS.map((link) => (
            <SocialEmbedFacade provider={link.provider} label={link.label} href={link.href} key={link.href} />
          ))}
        </div>
      </div>
    </section>
  )
}
