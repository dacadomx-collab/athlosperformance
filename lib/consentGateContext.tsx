"use client"

import { createContext, useCallback, useContext, useState, type ReactNode } from "react"

interface ConsentGateContextValue {
  isOpen: boolean
  openConsentGate: () => void
  closeConsentGate: () => void
}

const ConsentGateContext = createContext<ConsentGateContextValue | null>(null)

export function ConsentGateProvider({ children }: { children: ReactNode }) {
  const [isOpen, setIsOpen] = useState(false)
  const openConsentGate = useCallback(() => setIsOpen(true), [])
  const closeConsentGate = useCallback(() => setIsOpen(false), [])

  return (
    <ConsentGateContext.Provider value={{ isOpen, openConsentGate, closeConsentGate }}>
      {children}
    </ConsentGateContext.Provider>
  )
}

export function useConsentGate() {
  const context = useContext(ConsentGateContext)
  if (!context) {
    throw new Error("useConsentGate debe usarse dentro de un ConsentGateProvider")
  }
  return context
}
