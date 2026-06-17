"use client"

import { useState } from "react"
import { SEGMENT_CONTENT, type AthlosSegment } from "@/lib/athlosContent"
import { SegmentedSolutions } from "@/components/athlos/SegmentedSolutions"

const SEGMENTS = Object.keys(SEGMENT_CONTENT) as AthlosSegment[]

export function EvaluationSplitSection() {
  const [activeSegment, setActiveSegment] = useState<AthlosSegment>("atletas")

  return (
    <section className="section section--surface" id="evaluacion" aria-labelledby="evaluation-title">
      <div className="section__inner">
        <div className="section-heading">
          <p className="section-kicker">Segmentación clínica</p>
          <h2 id="evaluation-title">El Punto de Partida: Tu Ficha de Evaluación Clínica</h2>
          <p>
            Nuestro rigor empírico significa que ninguna decisión de carga física se toma por
            intuición. Utilizamos protocolos internacionales estandarizados para mapear tu perfil
            antes de tu primer entrenamiento. Elige tu perfil para ver cómo lo evaluamos.
          </p>
        </div>

        <div className="segment-switcher" role="tablist" aria-label="Seleccionar perfil de evaluación">
          {SEGMENTS.map((segment) => (
            <button
              key={segment}
              role="tab"
              type="button"
              id={`tab-${segment}`}
              aria-selected={activeSegment === segment}
              aria-controls={`panel-${segment}`}
              tabIndex={activeSegment === segment ? 0 : -1}
              className="segment-tab"
              onClick={() => setActiveSegment(segment)}
            >
              {SEGMENT_CONTENT[segment].tabLabel}
            </button>
          ))}
        </div>

        <SegmentedSolutions segment={activeSegment} />
      </div>
    </section>
  )
}
