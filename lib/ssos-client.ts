/**
 * Cliente del webhook FrontDesk del BackOffice SSOS (public/ssos/api/leads_webhook.php).
 *
 * Este sitio se exporta 100% estático (next.config.mjs: output "export"), sin
 * runtime de servidor — por eso este cliente llama al endpoint directamente
 * desde el navegador y NUNCA envía una API key: cualquier secreto embebido
 * en este bundle sería público. La autorización para este canal la resuelve
 * el backend validando el header Origin del navegador contra ALLOWED_ORIGINS
 * (ver api_require_key_or_allowed_origin() en public/ssos/config/helpers.php).
 */

export type CanalOrigen = "whatsapp" | "instagram" | "facebook"

export interface LeadPayload {
  nombreCompleto: string
  telefono: string
  objetivoSalud: string
  consentimientoLegal: boolean
  canalOrigen?: CanalOrigen
  email?: string
}

export type LeadResult =
  | { ok: true; action: "created" | "updated"; idLead: number }
  | { ok: false; code: string; message: string; errors?: string[] }

const SSOS_API_BASE = process.env.NEXT_PUBLIC_SSOS_API_BASE ?? "/ssos"

const DEFAULT_ERROR_MESSAGES: Record<string, string> = {
  LEGAL_PRIVACY_VIOLATION: "Debes aceptar el aviso de privacidad para continuar.",
  VALIDATION_ERROR: "Revisa los datos capturados: hay al menos un campo inválido.",
  UNAUTHORIZED: "No se pudo verificar el origen de la solicitud. Intenta de nuevo más tarde.",
  NETWORK_ERROR: "No se pudo conectar con el servidor. Verifica tu conexión e intenta de nuevo.",
  UNKNOWN_ERROR: "Ocurrió un problema al enviar tu información. Intenta de nuevo.",
}

export async function submitLead(payload: LeadPayload): Promise<LeadResult> {
  let response: Response
  try {
    response = await fetch(`${SSOS_API_BASE}/api/leads_webhook.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        nombre_completo: payload.nombreCompleto,
        telefono: payload.telefono,
        objetivo_salud: payload.objetivoSalud,
        consentimiento_legal: payload.consentimientoLegal,
        canal_origen: payload.canalOrigen ?? "whatsapp",
        email: payload.email,
      }),
    })
  } catch {
    return { ok: false, code: "NETWORK_ERROR", message: DEFAULT_ERROR_MESSAGES.NETWORK_ERROR }
  }

  const data = await response.json().catch(() => null)

  if (response.ok && data?.status === "success") {
    return { ok: true, action: data.action, idLead: data.id_lead }
  }

  const code: string = data?.code ?? "UNKNOWN_ERROR"
  return {
    ok: false,
    code,
    message: data?.message ?? DEFAULT_ERROR_MESSAGES[code] ?? DEFAULT_ERROR_MESSAGES.UNKNOWN_ERROR,
    errors: data?.errors,
  }
}
