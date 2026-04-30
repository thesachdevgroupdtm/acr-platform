/**
 * api.ts — single fetch wrapper for the React frontend.
 *
 * Reads VITE_API_BASE_URL from env (e.g. https://autocarrepair.in/api/v1).
 * Attaches a Sanctum personal-access-token from localStorage when present.
 * Throws ApiError on non-2xx, with parsed payload available for error UIs.
 */

const TOKEN_KEY = "acr_api_token_v1";
const BASE_URL =
  (import.meta as ImportMeta & { env: Record<string, string> }).env
    .VITE_API_BASE_URL?.replace(/\/+$/, "") || "";

export class ApiError extends Error {
  status: number;
  payload: unknown;
  constructor(status: number, message: string, payload?: unknown) {
    super(message);
    this.status = status;
    this.payload = payload;
  }
}

export function getToken(): string | null {
  try {
    return typeof window === "undefined"
      ? null
      : window.localStorage.getItem(TOKEN_KEY);
  } catch {
    return null;
  }
}

export function setToken(token: string | null) {
  try {
    if (typeof window === "undefined") return;
    if (token) window.localStorage.setItem(TOKEN_KEY, token);
    else window.localStorage.removeItem(TOKEN_KEY);
    window.dispatchEvent(new Event("acr-token-updated"));
  } catch {
    /* swallow */
  }
}

type Method = "GET" | "POST" | "PUT" | "PATCH" | "DELETE";

interface ApiOptions {
  method?: Method;
  body?: unknown;
  query?: Record<string, string | number | boolean | undefined | null>;
  signal?: AbortSignal;
  /** When true, do NOT throw on 401 — caller handles it. */
  allowUnauthorized?: boolean;
}

function buildUrl(path: string, query?: ApiOptions["query"]): string {
  const url = new URL(
    path.startsWith("http") ? path : `${BASE_URL}${path.startsWith("/") ? "" : "/"}${path}`,
    typeof window !== "undefined" ? window.location.origin : "http://localhost"
  );
  if (query) {
    for (const [k, v] of Object.entries(query)) {
      if (v === undefined || v === null || v === "") continue;
      url.searchParams.set(k, String(v));
    }
  }
  // Strip the dummy origin we used when BASE_URL is absolute
  return url.toString();
}

export async function api<T = unknown>(
  path: string,
  opts: ApiOptions = {}
): Promise<T> {
  const { method = "GET", body, query, signal, allowUnauthorized } = opts;

  const headers: Record<string, string> = {
    Accept: "application/json",
  };
  if (body !== undefined) headers["Content-Type"] = "application/json";

  const token = getToken();
  if (token) headers.Authorization = `Bearer ${token}`;

  const res = await fetch(buildUrl(path, query), {
    method,
    headers,
    body: body !== undefined ? JSON.stringify(body) : undefined,
    signal,
    credentials: "omit",
  });

  let payload: unknown = null;
  const ct = res.headers.get("content-type") || "";
  if (ct.includes("application/json")) {
    try {
      payload = await res.json();
    } catch {
      payload = null;
    }
  } else {
    try {
      payload = await res.text();
    } catch {
      payload = null;
    }
  }

  if (!res.ok) {
    if (res.status === 401 && !allowUnauthorized) {
      // Token expired or invalid — wipe so the next render shows logged-out state
      setToken(null);
    }
    const message =
      (payload && typeof payload === "object" && "message" in payload
        ? String((payload as { message: unknown }).message)
        : null) || `Request failed with ${res.status}`;
    throw new ApiError(res.status, message, payload);
  }

  return payload as T;
}

/* ───────────── Convenience methods ───────────── */

export const apiGet = <T = unknown>(p: string, q?: ApiOptions["query"], s?: AbortSignal) =>
  api<T>(p, { method: "GET", query: q, signal: s });

export const apiPost = <T = unknown>(p: string, b?: unknown) =>
  api<T>(p, { method: "POST", body: b });

export const apiPut = <T = unknown>(p: string, b?: unknown) =>
  api<T>(p, { method: "PUT", body: b });

export const apiDelete = <T = unknown>(p: string, b?: unknown) =>
  api<T>(p, { method: "DELETE", body: b });
