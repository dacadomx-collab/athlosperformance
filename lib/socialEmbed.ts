type SocialProvider = "Instagram" | "Facebook"

export function getSocialEmbedSrc(provider: SocialProvider, href: string): string {
  if (provider === "Facebook") {
    return `https://www.facebook.com/plugins/video.php?href=${encodeURIComponent(href)}&show_text=false`
  }

  const cleanUrl = href.split("?")[0]
  const withTrailingSlash = cleanUrl.endsWith("/") ? cleanUrl : `${cleanUrl}/`
  return `${withTrailingSlash}embed`
}
