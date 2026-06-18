"use client"

import Image from "next/image"
import { useState } from "react"
import arturoNaranjo from "@/assets/img/arturo-naranjo-coach-athlos-performance.jpg"
import bernardoLobo from "@/assets/img/bernardo-lobo-coach-athlos-performance.jpg"
import luisMoctezuma from "@/assets/img/luis-moctezuma-coach-athlos-performance.jpg"
import robertoLopez from "@/assets/img/roberto-lopez-coach-athlos-performance.jpg"
import { COACHES } from "@/lib/athlosContent"
import { CoachLightbox } from "@/components/athlos/CoachLightbox"

const COACH_PHOTOS = {
  "bernardo-lobo": bernardoLobo,
  "luis-moctezuma": luisMoctezuma,
  "arturo-naranjo": arturoNaranjo,
  "roberto-lopez": robertoLopez
} as const

type CoachSlug = keyof typeof COACH_PHOTOS

export function TeamSection() {
  const [activeSlug, setActiveSlug] = useState<CoachSlug | null>(null)
  const activeCoach = COACHES.find((coach) => coach.slug === activeSlug)

  return (
    <section className="section" id="staff" aria-labelledby="team-title">
      <div className="section__inner">
        <div className="section-heading">
          <p className="section-kicker">Equipo</p>
          <h2 id="team-title">Nuestro Equipo</h2>
          <p>Ciencia del deporte respaldada por personas, no solo por datos.</p>
        </div>
        <div className="team-grid">
          {COACHES.map((coach) => (
            <button type="button" className="team-card" key={coach.slug} onClick={() => setActiveSlug(coach.slug)}>
              <div className="team-card__photo">
                <Image
                  src={COACH_PHOTOS[coach.slug]}
                  alt={`${coach.name}, ${coach.role}`}
                  fill
                  sizes="(min-width: 42rem) 30vw, 90vw"
                />
              </div>
              <div className="team-card__meta">
                <h3>{coach.name}</h3>
                <p className="team-card__role">{coach.role}</p>
                <p className="team-card__expertise">{coach.expertise}</p>
              </div>
            </button>
          ))}
        </div>
      </div>

      {activeCoach && (
        <CoachLightbox
          photo={COACH_PHOTOS[activeCoach.slug]}
          name={activeCoach.name}
          role={activeCoach.role}
          onClose={() => setActiveSlug(null)}
        />
      )}
    </section>
  )
}
