interface ServiceCardProps {
  eyebrow: string
  title: string
  description: string
  metric: string
}

export function ServiceCard({ eyebrow, title, description, metric }: ServiceCardProps) {
  return (
    <article className="service-card">
      <p className="service-card__eyebrow">{eyebrow}</p>
      <h3>{title}</h3>
      <p>{description}</p>
      <div className="service-card__metric">{metric}</div>
    </article>
  )
}
