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
            <iframe
              key={link.href}
              src={link.href}
              loading="lazy"
              title={link.label}
              allow="encrypted-media; picture-in-picture"
              allowFullScreen
              style={{
                width: '100%',
                height: '660px', // Mantenemos la altura para que la cuadrícula no se rompa
                border: 'none',
                borderRadius: '12px',
                backgroundColor: 'white' // Fondo blanco nativo para que luzca como la publicación real
              }}
            />
          ))}
        </div>
      </div>
    </section>
  )
}
