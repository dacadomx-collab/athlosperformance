"use client"

import { useState } from "react"
import { getSocialEmbedSrc } from "@/lib/socialEmbed"

interface SocialEmbedFacadeProps {
  provider: "Instagram" | "Facebook"
  label: string
  href: string
}

export function SocialEmbedFacade({ provider, label, href }: SocialEmbedFacadeProps) {
  const [isLoaded, setIsLoaded] = useState(false)

  if (isLoaded) {
    return (
      <div className="social-embed">
        <iframe
          src={getSocialEmbedSrc(provider, href)}
          loading="lazy"
          title="Video de Athlos Performance"
          allow="encrypted-media; picture-in-picture"
          allowFullScreen
        />
      </div>
    )
  }

  return (
    <button
      type="button"
      className="social-embed social-embed--facade"
      onClick={() => setIsLoaded(true)}
      aria-label={`Cargar video de ${provider}: ${label}`}
    >
      <span className="media-card__play" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M8 6v12l10-6-10-6Z" fill="currentColor" />
        </svg>
      </span>
      <span className="media-card__meta">
        <span className="media-card__provider">{provider}</span>
        <span>{label}</span>
      </span>
    </button>
  )
}
