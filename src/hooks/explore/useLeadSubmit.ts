import { useState } from "react";
import { ApiError, apiPost } from "../../lib/api";

/**
 * Phase 4.5.3 — POST /api/v1/leads from the explore lead form.
 *
 * State machine: idle → submitting → success | error.
 * `success` auto-transitions back to `idle` after 5s so the form
 * resets without operator clicking anything.
 */

export interface LeadPayload {
  name: string;
  email?: string;
  phone: string;
  brand_id?: number | null;
  model_id?: number | null;
  service_id?: number | null;
}

interface LeadResponse {
  ok: boolean;
  lead_id: number;
}

type State = "idle" | "submitting" | "success" | "error";

export function useLeadSubmit() {
  const [state, setState] = useState<State>("idle");
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [generalError, setGeneralError] = useState<string | null>(null);

  const submit = async (payload: LeadPayload) => {
    setState("submitting");
    setErrors({});
    setGeneralError(null);
    try {
      await apiPost<LeadResponse>("/leads", payload);
      setState("success");
      // Auto-reset after 5s so the form returns to its initial state.
      setTimeout(() => {
        setState("idle");
        setErrors({});
        setGeneralError(null);
      }, 5000);
    } catch (err) {
      if (err instanceof ApiError && err.status === 422) {
        // Laravel validation — payload is `{ message, errors: { field: [msgs] } }`.
        const data = err.payload as
          | { message?: string; errors?: Record<string, string[]> }
          | undefined;
        const fieldErrors: Record<string, string> = {};
        if (data?.errors) {
          for (const [key, msgs] of Object.entries(data.errors)) {
            fieldErrors[key] = Array.isArray(msgs) ? msgs[0] ?? "" : String(msgs);
          }
        }
        setErrors(fieldErrors);
        setGeneralError(data?.message ?? "Please check the highlighted fields.");
      } else {
        setGeneralError(
          err instanceof Error ? err.message : "Something went wrong. Please try again.",
        );
      }
      setState("error");
    }
  };

  const reset = () => {
    setState("idle");
    setErrors({});
    setGeneralError(null);
  };

  return { submit, state, errors, generalError, reset };
}
