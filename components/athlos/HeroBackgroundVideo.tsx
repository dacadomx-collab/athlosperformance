"use client"

import { useEffect, useRef } from "react"

interface HeroBackgroundVideoProps {
  src: string
  poster: string
}

export function HeroBackgroundVideo({ src, poster }: HeroBackgroundVideoProps) {
  const videoRef = useRef<HTMLVideoElement>(null)

  useEffect(() => {
    const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches
    if (!prefersReducedMotion) {
      videoRef.current?.play().catch(() => {})
    }
  }, [])

  return (
    <video
      ref={videoRef}
      className="hero-visual__video"
      poster={poster}
      muted
      loop
      playsInline
      preload="metadata"
      aria-hidden="true"
    >
      <source src={src} type="video/mp4" />
    </video>
  )
}
