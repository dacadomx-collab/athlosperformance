import Image from "next/image"
import arturoNaranjo from "@/assets/img/arturo-naranjo-coach-athlos-performance.jpg"
import bernardoLobo from "@/assets/img/bernardo-lobo-coach-athlos-performance.jpg"
import luisMoctezuma from "@/assets/img/luis-moctezuma-coach-athlos-performance.jpg"
import { COACHES } from "@/lib/athlosContent"

const COACH_PHOTOS = {
  "bernardo-lobo": bernardoLobo,
  "luis-moctezuma": luisMoctezuma,
  "arturo-naranjo": arturoNaranjo
} as const

export function TeamSection() {
  return (
    <section className="section" id="staff" aria-labelledby="team-title">
      <div className="section__inner">
        <div className="section-heading">
          <p className="section-kicker">Equipo</p>
          <h2 id="team-title">El staff detrás de cada evaluación</h2>
          <p>Ciencia del deporte respaldada por personas, no solo por datos.</p>
        </div>
        <div className="team-grid">
          {COACHES.map((coach) => (
            <article className="team-card" key={coach.slug}>
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
            </article>
          ))}
        </div>
      </div>
    </section>
  )
}
