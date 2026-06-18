import type { Metadata, Viewport } from "next"
import "@/styles/athlos-theme.css"
import { BackToTop } from "@/components/athlos/BackToTop"
import { ConsentLeadDialog } from "@/components/athlos/ConsentLeadDialog"
import { ConsentGateProvider } from "@/lib/consentGateContext"

export const metadata: Metadata = {
  title: "Athlos Performance BCS | Laboratorio de Ciencias del Ejercicio",
  description:
    "Laboratorio de Ciencias del Ejercicio y Movimiento Humano en La Paz, BCS. Evaluación, análisis y entrenamiento basado en evidencia.",
  metadataBase: new URL("https://athlosperformance.tourfindy.com")
}

export const viewport: Viewport = {
  width: "device-width",
  initialScale: 1,
  themeColor: "#0E3A5D"
}

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="es" suppressHydrationWarning>
      <body>
        <ConsentGateProvider>
          {children}
          <ConsentLeadDialog />
        </ConsentGateProvider>
        <BackToTop />
      </body>
    </html>
  )
}
