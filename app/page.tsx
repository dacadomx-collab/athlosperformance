import { AthlosFooter } from "@/components/athlos/AthlosFooter"
import { AthlosHeader } from "@/components/athlos/AthlosHeader"
import { ContactSection } from "@/components/athlos/ContactSection"
import { DifferentiationSection } from "@/components/athlos/DifferentiationSection"
import { EvaluationSplitSection } from "@/components/athlos/EvaluationSplitSection"
import { EvidenceSection } from "@/components/athlos/EvidenceSection"
import { FinalCtaSection } from "@/components/athlos/FinalCtaSection"
import { HeroSection } from "@/components/athlos/HeroSection"
import { MethodologyTimeline } from "@/components/athlos/MethodologyTimeline"
import { TeamSection } from "@/components/athlos/TeamSection"
import { TestimonialCarousel } from "@/components/athlos/TestimonialCarousel"

export default function HomePage() {
  return (
    <div className="site-shell">
      <AthlosHeader />
      <main>
        <HeroSection />
        <DifferentiationSection />
        <MethodologyTimeline />
        <EvaluationSplitSection />
        <TeamSection />
        <EvidenceSection />
        <TestimonialCarousel />
        <FinalCtaSection />
        <ContactSection />
      </main>
      <AthlosFooter />
    </div>
  )
}
