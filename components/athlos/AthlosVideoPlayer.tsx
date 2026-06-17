"use client"

import { useRef, useState } from "react"

interface AthlosVideoPlayerProps {
  src: string
  poster: string
  label: string
}

export function AthlosVideoPlayer({ src, poster, label }: AthlosVideoPlayerProps) {
  const videoRef = useRef<HTMLVideoElement>(null)
  const [isPlaying, setIsPlaying] = useState(false)

  function handlePlay() {
    videoRef.current?.play()
    setIsPlaying(true)
  }

  return (
    <div className="video-player">
      <video
        ref={videoRef}
        className="video-player__el"
        poster={poster}
        muted
        playsInline
        preload="none"
        controls={isPlaying}
        onPause={() => setIsPlaying(false)}
        onEnded={() => setIsPlaying(false)}
      >
        <source src={src} type="video/mp4" />
      </video>
      <span className="video-player__tint" aria-hidden="true" />
      {!isPlaying && (
        <>
          <button
            className="video-player__play"
            type="button"
            onClick={handlePlay}
            aria-label={`Reproducir video: ${label}`}
          >
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M8 6v12l10-6-10-6Z" fill="currentColor" />
            </svg>
          </button>
          <span className="video-player__label">{label}</span>
        </>
      )}
    </div>
  )
}
