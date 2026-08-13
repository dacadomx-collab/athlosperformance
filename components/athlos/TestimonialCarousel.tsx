"use client"

import Image from "next/image"
import { useEffect, useState } from "react"
import { fetchTestimoniosPublicos, type TestimonioPublico } from "@/lib/ssos-client"

const AUTO_ADVANCE_MS = 12000

export function TestimonialCarousel() {
  const [testimonios, setTestimonios] = useState<TestimonioPublico[] | null>(null)
  const [activeIndex, setActiveIndex] = useState(0)
  const [isPaused, setIsPaused] = useState(false)

  useEffect(() => {
    let cancelled = false
    fetchTestimoniosPublicos().then((data) => {
      if (!cancelled) setTestimonios(data)
    })
    return () => {
      cancelled = true
    }
  }, [])

  useEffect(() => {
    if (!testimonios || testimonios.length < 2 || isPaused) return
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return

    const interval = setInterval(() => {
      setActiveIndex((current) => (current + 1) % testimonios.length)
    }, AUTO_ADVANCE_MS)

    return () => clearInterval(interval)
  }, [testimonios, isPaused])

  if (!testimonios || testimonios.length === 0) {
    return null
  }

  const activeTestimonio = testimonios[activeIndex]
  const goTo = (index: number) => setActiveIndex(((index % testimonios.length) + testimonios.length) % testimonios.length)

  return (
    <section className="section section--surface" id="testimonios" aria-labelledby="testimonials-title">
      <div className="section__inner">
        <div className="section-heading">
          <p className="section-kicker">Casos de éxito</p>
          <h2 id="testimonials-title">Resultados que hablan por sí mismos</h2>
          <p>Historias reales de quienes ya entrenan con ciencia del deporte en Athlos Performance.</p>
        </div>

        <div
          className="testimonial-carousel"
          onMouseEnter={() => setIsPaused(true)}
          onMouseLeave={() => setIsPaused(false)}
          onFocus={() => setIsPaused(true)}
          onBlur={() => setIsPaused(false)}
        >
          <div
            className="testimonial-carousel__viewport"
            role="group"
            aria-roledescription="carrusel"
            aria-label="Testimonios de clientes"
          >
            <blockquote className="testimonial-card" key={activeTestimonio.idTestimonio} aria-live="polite">
              <div className="testimonial-card__avatar" aria-hidden="true">
                {activeTestimonio.fotoUrl ? (
                  <Image src={activeTestimonio.fotoUrl} alt="" fill sizes="4rem" unoptimized />
                ) : (
                  <span>{activeTestimonio.nombreCliente.charAt(0)}</span>
                )}
              </div>
              <p className="testimonial-card__quote">&ldquo;{activeTestimonio.comentario}&rdquo;</p>
              <footer className="testimonial-card__footer">
                <cite>{activeTestimonio.nombreCliente}</cite>
              </footer>
            </blockquote>
          </div>

          {testimonios.length > 1 && (
            <div className="testimonial-carousel__controls">
              <button
                type="button"
                className="testimonial-carousel__arrow"
                onClick={() => goTo(activeIndex - 1)}
                aria-label="Testimonio anterior"
              >
                ‹
              </button>
              <div className="testimonial-carousel__dots" role="tablist" aria-label="Selecciona un testimonio">
                {testimonios.map((testimonio, index) => (
                  <button
                    key={testimonio.idTestimonio}
                    type="button"
                    role="tab"
                    aria-selected={index === activeIndex}
                    aria-label={`Testimonio de ${testimonio.nombreCliente}`}
                    className={`testimonial-carousel__dot${index === activeIndex ? " is-active" : ""}`}
                    onClick={() => goTo(index)}
                  />
                ))}
              </div>
              <button
                type="button"
                className="testimonial-carousel__arrow"
                onClick={() => goTo(activeIndex + 1)}
                aria-label="Siguiente testimonio"
              >
                ›
              </button>
            </div>
          )}
        </div>
      </div>
    </section>
  )
}
