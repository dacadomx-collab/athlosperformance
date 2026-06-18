"use client"

import Link from "next/link"
import type { AnchorHTMLAttributes, MouseEvent as ReactMouseEvent, ReactNode } from "react"
import { useConsentGate } from "@/lib/consentGateContext"

type CtaVariant = "primary" | "secondary"

interface CtaButtonProps extends AnchorHTMLAttributes<HTMLAnchorElement> {
  href: string
  children: ReactNode
  variant?: CtaVariant
}

export function CtaButton({ href, children, variant = "primary", className = "", onClick, ...props }: CtaButtonProps) {
  const { openConsentGate } = useConsentGate()
  const classes = `cta-button cta-button--${variant} ${className}`.trim()

  if (href === "#consent-gate") {
    return (
      <button
        type="button"
        className={classes}
        onClick={(event) => {
          onClick?.(event as unknown as ReactMouseEvent<HTMLAnchorElement>)
          openConsentGate()
        }}
      >
        <span>{children}</span>
      </button>
    )
  }

  return (
    <Link className={classes} href={href} onClick={onClick} {...props}>
      <span>{children}</span>
    </Link>
  )
}
