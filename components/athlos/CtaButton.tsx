import Link from "next/link"
import type { AnchorHTMLAttributes, ReactNode } from "react"

type CtaVariant = "primary" | "secondary" | "ghost"

interface CtaButtonProps extends AnchorHTMLAttributes<HTMLAnchorElement> {
  href: string
  children: ReactNode
  variant?: CtaVariant
}

export function CtaButton({ href, children, variant = "primary", className = "", ...props }: CtaButtonProps) {
  return (
    <Link className={`cta-button cta-button--${variant} ${className}`.trim()} href={href} {...props}>
      <span>{children}</span>
    </Link>
  )
}
