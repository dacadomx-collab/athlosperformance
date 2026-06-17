"use client"

import Image from "next/image"
import Link from "next/link"
import { useEffect, useState } from "react"
import athlosLogo from "@/assets/img/athlos-performance-logotipo-circular.jpg"
import { ATHLOS_NAV_ITEMS } from "@/lib/athlosContent"
import { CtaButton } from "@/components/athlos/CtaButton"
import { ThemeToggle } from "@/components/athlos/ThemeToggle"

export function AthlosHeader() {
  const [isMenuOpen, setIsMenuOpen] = useState(false)

  useEffect(() => {
    document.body.dataset.menu = isMenuOpen ? "open" : "closed"
    return () => {
      document.body.dataset.menu = "closed"
    }
  }, [isMenuOpen])

  function closeMenu() {
    setIsMenuOpen(false)
  }

  return (
    <header className="site-header">
      <div className="site-header__inner">
        <Link className="brand-mark" href="/" aria-label="Athlos Performance BCS">
          <Image
            src={athlosLogo}
            alt=""
            width={48}
            height={48}
            priority
          />
          <span>
            <strong>Athlos</strong>
            <small>Performance BCS</small>
          </span>
        </Link>

        <nav className="desktop-nav" aria-label="Navegación principal">
          {ATHLOS_NAV_ITEMS.map((item) => (
            <Link key={item.href} href={item.href}>
              {item.label}
            </Link>
          ))}
        </nav>

        <div className="site-header__actions">
          <ThemeToggle />
          <CtaButton className="site-header__cta" href="#onboarding" variant="primary">
            Agendar
          </CtaButton>
          <button
            aria-label={isMenuOpen ? "Cerrar menú" : "Abrir menú"}
            aria-controls="mobile-menu"
            aria-expanded={isMenuOpen}
            className="hamburger"
            type="button"
            onClick={() => setIsMenuOpen((open) => !open)}
          >
            <span />
            <span />
            <span />
          </button>
        </div>
      </div>

      <div className="mobile-menu" data-open={isMenuOpen} id="mobile-menu">
        <div className="mobile-menu__panel">
          <nav aria-label="Navegación móvil">
            {ATHLOS_NAV_ITEMS.map((item) => (
              <Link key={item.href} href={item.href} onClick={closeMenu}>
                {item.label}
              </Link>
            ))}
          </nav>
          <CtaButton href="#onboarding" onClick={closeMenu}>
            Agendar Evaluación Inicial
          </CtaButton>
        </div>
      </div>
    </header>
  )
}
