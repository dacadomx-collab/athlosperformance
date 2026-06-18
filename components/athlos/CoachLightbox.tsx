"use client"

import Image, { type StaticImageData } from "next/image"
import { useEffect } from "react"

interface CoachLightboxProps {
  photo: StaticImageData
  name: string
  role: string
  onClose: () => void
}

export function CoachLightbox({ photo, name, role, onClose }: CoachLightboxProps) {
  useEffect(() => {
    function handleKeyDown(event: KeyboardEvent) {
      if (event.key === "Escape") onClose()
    }
    document.addEventListener("keydown", handleKeyDown)
    document.body.dataset.dialog = "open"
    return () => {
      document.removeEventListener("keydown", handleKeyDown)
      delete document.body.dataset.dialog
    }
  }, [onClose])

  return (
    <div
      className="coach-lightbox"
      role="presentation"
      onClick={(event) => {
        if (event.target === event.currentTarget) onClose()
      }}
    >
      <div className="coach-lightbox__dialog" role="dialog" aria-modal="true" aria-label={`${name}, ${role}`}>
        <button type="button" className="consent-gate__close" onClick={onClose} aria-label="Cerrar">
          ×
        </button>
        <div className="coach-lightbox__photo">
          <Image src={photo} alt={`${name}, ${role}`} fill sizes="(min-width: 42rem) 26rem, 90vw" />
        </div>
        <div className="coach-lightbox__meta">
          <h3>{name}</h3>
          <p>{role}</p>
        </div>
      </div>
    </div>
  )
}
