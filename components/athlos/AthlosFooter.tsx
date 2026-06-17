import Link from "next/link"
import { ATHLOS_CONTACT } from "@/lib/athlosContent"

export function AthlosFooter() {
  return (
    <footer className="site-footer">
      <div className="site-footer__inner">
        <div>
          <p className="footer-kicker">Athlos Performance BCS</p>
          <p>Laboratorio de Ciencias del Ejercicio y Movimiento Humano en La Paz, BCS.</p>
        </div>
        <div className="footer-links">
          <Link href={ATHLOS_CONTACT.instagram} target="_blank" rel="noopener noreferrer">
            Instagram
          </Link>
          <Link href={ATHLOS_CONTACT.facebook} target="_blank" rel="noopener noreferrer">
            Facebook
          </Link>
          <a href={`mailto:${ATHLOS_CONTACT.email}`}>Contacto</a>
        </div>
      </div>
    </footer>
  )
}
