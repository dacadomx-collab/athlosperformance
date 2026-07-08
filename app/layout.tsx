import type { Metadata, Viewport } from "next"
import "@/styles/athlos-theme.css"
import { BackToTop } from "@/components/athlos/BackToTop"
import { ConsentLeadDialog } from "@/components/athlos/ConsentLeadDialog"
import { ConsentGateProvider } from "@/lib/consentGateContext"
import { ATHLOS_CONTACT } from "@/lib/athlosContent"

const SITE_URL = "https://athlosperformance.tourfindy.com"
const OG_IMAGE = "/media/entrenamiento-fuerza-controlada-laboratorio-athlos-poster.jpg"

export const metadata: Metadata = {
  title: "Athlos Performance BCS | Laboratorio de Ciencias del Deporte y Rendimiento en La Paz",
  description:
    "Centro especializado en evaluación biomecánica, rendimiento deportivo, rehabilitación y longevidad activa para adultos mayores en La Paz, BCS.",
  keywords: [
    "gimnasio La Paz BCS",
    "ciencia del deporte",
    "evaluación biomecánica",
    "entrenamiento adulto mayor",
    "rendimiento físico La Paz"
  ],
  metadataBase: new URL(SITE_URL),
  icons: {
    icon: "/favicon.ico"
  },
  openGraph: {
    type: "website",
    locale: "es_MX",
    url: SITE_URL,
    siteName: "Athlos Performance BCS",
    title: "Athlos Performance BCS | Laboratorio de Ciencias del Deporte y Rendimiento en La Paz",
    description:
      "Centro especializado en evaluación biomecánica, rendimiento deportivo, rehabilitación y longevidad activa para adultos mayores en La Paz, BCS.",
    images: [{ url: OG_IMAGE, width: 1200, height: 630, alt: "Athlos Performance BCS" }]
  },
  twitter: {
    card: "summary_large_image",
    title: "Athlos Performance BCS | Laboratorio de Ciencias del Deporte y Rendimiento en La Paz",
    description:
      "Centro especializado en evaluación biomecánica, rendimiento deportivo, rehabilitación y longevidad activa para adultos mayores en La Paz, BCS.",
    images: [OG_IMAGE]
  }
}

export const viewport: Viewport = {
  width: "device-width",
  initialScale: 1,
  themeColor: "#0E3A5D"
}

const structuredData = {
  "@context": "https://schema.org",
  "@type": "SportsActivityLocation",
  name: "Athlos Performance BCS",
  description:
    "Laboratorio de Ciencias del Deporte especializado en evaluación biomecánica, rendimiento deportivo, rehabilitación y longevidad activa para adultos mayores.",
  url: SITE_URL,
  telephone: `+52${ATHLOS_CONTACT.phone}`,
  email: ATHLOS_CONTACT.email,
  address: {
    "@type": "PostalAddress",
    streetAddress: "Calle Altamirano #2730",
    addressLocality: "La Paz",
    addressRegion: "Baja California Sur",
    postalCode: "23000",
    addressCountry: "MX"
  },
  geo: {
    "@type": "GeoCoordinates",
    latitude: 24.1426,
    longitude: -110.3128
  },
  sameAs: [ATHLOS_CONTACT.instagram, ATHLOS_CONTACT.facebook]
}

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="es" suppressHydrationWarning>
      <body>
        <script
          type="application/ld+json"
          // eslint-disable-next-line react/no-danger
          dangerouslySetInnerHTML={{ __html: JSON.stringify(structuredData) }}
        />
        <ConsentGateProvider>
          {children}
          <ConsentLeadDialog />
        </ConsentGateProvider>
        <BackToTop />
      </body>
    </html>
  )
}
