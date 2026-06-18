"use client"

import { useEffect, useId, useRef, useState, type FormEvent, type MouseEvent } from "react"
import { ATHLOS_CONTACT } from "@/lib/athlosContent"
import { useConsentGate } from "@/lib/consentGateContext"

type DialogStep = "consent" | "form" | "success"

interface LeadFormState {
  nombreCompleto: string
  telefono: string
  objetivoDeclarado: string
}

const EMPTY_FORM: LeadFormState = { nombreCompleto: "", telefono: "", objetivoDeclarado: "" }

const FOCUSABLE_SELECTOR = 'button, a[href], input, select, textarea, [tabindex]:not([tabindex="-1"])'

export function ConsentLeadDialog() {
  const { isOpen, closeConsentGate } = useConsentGate()
  const [step, setStep] = useState<DialogStep>("consent")
  const [consentChecked, setConsentChecked] = useState(false)
  const [formState, setFormState] = useState<LeadFormState>(EMPTY_FORM)
  const titleId = useId()
  const dialogRef = useRef<HTMLDivElement>(null)
  const previousFocusRef = useRef<HTMLElement | null>(null)

  function getFocusableElements(): HTMLElement[] {
    const dialog = dialogRef.current
    if (!dialog) return []
    return Array.from(dialog.querySelectorAll<HTMLElement>(FOCUSABLE_SELECTOR)).filter(
      (el) => !el.hasAttribute("disabled")
    )
  }

  useEffect(() => {
    if (!isOpen) {
      setStep("consent")
      setConsentChecked(false)
      setFormState(EMPTY_FORM)
      return
    }

    previousFocusRef.current = document.activeElement as HTMLElement | null
    document.body.dataset.dialog = "open"

    function handleKeyDown(event: KeyboardEvent) {
      if (event.key === "Escape") {
        closeConsentGate()
        return
      }
      if (event.key !== "Tab") return

      const elements = getFocusableElements()
      if (elements.length === 0) return
      const first = elements[0]
      const last = elements[elements.length - 1]

      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault()
        last.focus()
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault()
        first.focus()
      }
    }

    document.addEventListener("keydown", handleKeyDown)

    return () => {
      document.removeEventListener("keydown", handleKeyDown)
      delete document.body.dataset.dialog
      previousFocusRef.current?.focus()
    }
  }, [isOpen, closeConsentGate])

  useEffect(() => {
    if (!isOpen) return
    getFocusableElements()[0]?.focus()
  }, [isOpen, step])

  if (!isOpen) return null

  function handleBackdropClick(event: MouseEvent<HTMLDivElement>) {
    if (event.target === event.currentTarget) closeConsentGate()
  }

  function handleConsentSubmit(event: FormEvent) {
    event.preventDefault()
    if (!consentChecked) return
    setStep("form")
  }

  function handleLeadSubmit(event: FormEvent) {
    event.preventDefault()
    // Contrato listo para api/webhook_mensajeria.php (ver REPORTE_TECNICO_FINAL.md);
    // el envío real se conecta cuando el backend expone el endpoint correspondiente.
    const payload = {
      nombreCompleto: formState.nombreCompleto.trim(),
      telefono: formState.telefono.trim(),
      objetivoDeclarado: formState.objetivoDeclarado.trim(),
      consentGateStatus: "aceptado"
    }
    void payload
    setStep("success")
  }

  return (
    <div className="consent-gate" role="presentation" onClick={handleBackdropClick}>
      <div
        className="consent-gate__dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby={titleId}
        ref={dialogRef}
      >
        <button type="button" className="consent-gate__close" onClick={closeConsentGate} aria-label="Cerrar">
          ×
        </button>

        {step === "consent" && (
          <form className="consent-gate__panel" onSubmit={handleConsentSubmit}>
            <p className="section-kicker">Aviso de privacidad</p>
            <h2 id={titleId}>Antes de continuar</h2>
            <p className="consent-gate__notice">
              Para agendar tu evaluación inicial necesitamos tratar datos personales y, si los
              compartes, datos relacionados con tu salud (lesiones, condiciones médicas u
              objetivos físicos). Estos datos se usan únicamente para diseñar tu programa y
              contactarte; no se comparten con terceros sin tu autorización. Puedes solicitar su
              eliminación escribiendo a {ATHLOS_CONTACT.email}.
            </p>
            <label className="consent-gate__checkbox">
              <input
                type="checkbox"
                checked={consentChecked}
                onChange={(event) => setConsentChecked(event.target.checked)}
                required
              />
              <span>
                He leído y acepto el tratamiento de mis datos personales y de salud conforme al
                aviso de privacidad.
              </span>
            </label>
            <button type="submit" className="cta-button cta-button--primary" disabled={!consentChecked}>
              <span>Continuar</span>
            </button>
          </form>
        )}

        {step === "form" && (
          <form className="consent-gate__panel" onSubmit={handleLeadSubmit}>
            <p className="section-kicker">Evaluación inicial</p>
            <h2 id={titleId}>Cuéntanos lo esencial</h2>
            <label className="consent-gate__field">
              <span>Nombre completo</span>
              <input
                type="text"
                required
                value={formState.nombreCompleto}
                onChange={(event) =>
                  setFormState((current) => ({ ...current, nombreCompleto: event.target.value }))
                }
              />
            </label>
            <label className="consent-gate__field">
              <span>Teléfono (lada 612)</span>
              <input
                type="tel"
                required
                pattern="[0-9]{10,12}"
                value={formState.telefono}
                onChange={(event) =>
                  setFormState((current) => ({ ...current, telefono: event.target.value }))
                }
              />
            </label>
            <label className="consent-gate__field">
              <span>Objetivo de salud</span>
              <input
                type="text"
                required
                placeholder="Ej. rendimiento deportivo, movilidad, longevidad"
                value={formState.objetivoDeclarado}
                onChange={(event) =>
                  setFormState((current) => ({ ...current, objetivoDeclarado: event.target.value }))
                }
              />
            </label>
            <p className="consent-gate__disclaimer">
              Esta evaluación es profesional, no un diagnóstico en línea.
            </p>
            <button type="submit" className="cta-button cta-button--primary">
              <span>Enviar solicitud</span>
            </button>
          </form>
        )}

        {step === "success" && (
          <div className="consent-gate__panel">
            <p className="section-kicker">Solicitud recibida</p>
            <h2 id={titleId}>Gracias{formState.nombreCompleto ? `, ${formState.nombreCompleto.split(" ")[0]}` : ""}</h2>
            <p>
              Nuestro equipo se pondrá en contacto contigo al {formState.telefono} para confirmar
              tu evaluación inicial.
            </p>
            <button type="button" className="cta-button cta-button--secondary" onClick={closeConsentGate}>
              <span>Cerrar</span>
            </button>
          </div>
        )}
      </div>
    </div>
  )
}
