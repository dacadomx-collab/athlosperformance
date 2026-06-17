"use client"

import { useEffect, useState } from "react"

const THEME_KEY = "athlos_theme"

type ThemeMode = "light" | "dark"

function getPreferredTheme(): ThemeMode {
  if (typeof window === "undefined") return "dark"
  const stored = window.localStorage.getItem(THEME_KEY)
  if (stored === "light" || stored === "dark") return stored
  return window.matchMedia("(prefers-color-scheme: light)").matches ? "light" : "dark"
}

export function ThemeToggle() {
  const [theme, setTheme] = useState<ThemeMode>("dark")

  useEffect(() => {
    const preferred = getPreferredTheme()
    setTheme(preferred)
    document.documentElement.dataset.theme = preferred
  }, [])

  function toggleTheme() {
    const nextTheme = theme === "dark" ? "light" : "dark"
    setTheme(nextTheme)
    document.documentElement.dataset.theme = nextTheme
    window.localStorage.setItem(THEME_KEY, nextTheme)
  }

  return (
    <button
      aria-label={`Cambiar a modo ${theme === "dark" ? "día" : "noche"}`}
      aria-pressed={theme === "dark"}
      className="theme-toggle"
      type="button"
      onClick={toggleTheme}
    >
      <span className="theme-toggle__track">
        <span className="theme-toggle__orb" />
      </span>
      <span className="theme-toggle__label">{theme === "dark" ? "Noche" : "Día"}</span>
    </button>
  )
}
