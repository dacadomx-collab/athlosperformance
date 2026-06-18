import { ATHLOS_CONTACT, ATHLOS_MAPS_HREF, ATHLOS_WHATSAPP_HREF } from "@/lib/athlosContent"
import { CtaButton } from "@/components/athlos/CtaButton"
import { FacebookIcon, InstagramIcon, MailIcon, PinIcon, WhatsappIcon } from "@/components/athlos/ContactIcons"

export function ContactSection() {
  return (
    <section className="section section--surface contact-section" id="contacto" aria-labelledby="contact-title">
      <div className="section__inner contact-section__inner">
        <div className="contact-section__info">
          <p className="section-kicker">Contacto</p>
          <h2 id="contact-title">Visítanos o escríbenos directamente</h2>
          <ul className="contact-channels">
            <li>
              <a href={ATHLOS_MAPS_HREF} target="_blank" rel="noopener noreferrer">
                <PinIcon />
                <span>{ATHLOS_CONTACT.address}</span>
              </a>
            </li>
            <li>
              <a href={ATHLOS_WHATSAPP_HREF} target="_blank" rel="noopener noreferrer">
                <WhatsappIcon />
                <span>WhatsApp · {ATHLOS_CONTACT.phone}</span>
              </a>
            </li>
            <li>
              <a href={`mailto:${ATHLOS_CONTACT.email}`}>
                <MailIcon />
                <span>{ATHLOS_CONTACT.email}</span>
              </a>
            </li>
            <li>
              <a href={ATHLOS_CONTACT.instagram} target="_blank" rel="noopener noreferrer">
                <InstagramIcon />
                <span>Instagram</span>
              </a>
            </li>
            <li>
              <a href={ATHLOS_CONTACT.facebook} target="_blank" rel="noopener noreferrer">
                <FacebookIcon />
                <span>Facebook</span>
              </a>
            </li>
          </ul>
        </div>
        <CtaButton href={ATHLOS_WHATSAPP_HREF} target="_blank" rel="noopener noreferrer">
          Escríbenos por WhatsApp
        </CtaButton>
      </div>
    </section>
  )
}
