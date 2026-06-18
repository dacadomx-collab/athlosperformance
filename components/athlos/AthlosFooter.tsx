import Link from "next/link"
import { ATHLOS_CONTACT, ATHLOS_WHATSAPP_HREF } from "@/lib/athlosContent"
import { FacebookIcon, InstagramIcon, MailIcon, WhatsappIcon } from "@/components/athlos/ContactIcons"

export function AthlosFooter() {
  return (
    <footer className="site-footer">
      <div className="site-footer__inner">
        <div>
          <p className="footer-kicker">Athlos Performance BCS</p>
          <p>Laboratorio de Ciencias del Ejercicio y Movimiento Humano en La Paz, BCS.</p>
        </div>
        <div className="footer-links">
          <a href={ATHLOS_WHATSAPP_HREF} target="_blank" rel="noopener noreferrer">
            <WhatsappIcon />
            <span>WhatsApp</span>
          </a>
          <a href={`mailto:${ATHLOS_CONTACT.email}`}>
            <MailIcon />
            <span>Email</span>
          </a>
          <a href={ATHLOS_CONTACT.instagram} target="_blank" rel="noopener noreferrer">
            <InstagramIcon />
            <span>Instagram</span>
          </a>
          <a href={ATHLOS_CONTACT.facebook} target="_blank" rel="noopener noreferrer">
            <FacebookIcon />
            <span>Facebook</span>
          </a>
          <Link href="#contacto">Contacto</Link>
        </div>
      </div>
    </footer>
  )
}
