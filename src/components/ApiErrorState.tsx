import type * as React from "react";
import { AlertCircle, RefreshCw } from "lucide-react";

interface ApiErrorStateProps {
  /** Short, user-facing failure message. Default: "Couldn't load." */
  message?: string;
  /** Optional one-line elaboration (e.g. status code, network hint). */
  detail?: string;
  /** Retry handler — typically `() => refetch()` from the calling hook. */
  onRetry?: () => void;
  /** Compact inline variant (smaller padding, no icon background). */
  inline?: boolean;
  /** Override the test id so tests can target multiple instances. */
  "data-testid"?: string;
}

/**
 * Phase 4.2.5 — reusable API error state.
 *
 * Replaces ad-hoc "Couldn't load X." paragraphs across the app
 * with a single component that surfaces the failure AND provides
 * an explicit retry path. Per locked decision D-4.2.5-2,
 * silent fallbacks (default-to-empty on error) are not acceptable.
 *
 * Visual contract: matches the existing accent-dark error styling
 * used on Coupons / MyBookings / OrderDetail pages so introducing
 * this component does not change the overall look.
 */
export const ApiErrorState: React.FC<ApiErrorStateProps> = ({
  message = "Couldn't load.",
  detail,
  onRetry,
  inline = false,
  "data-testid": testId = "api-error-state",
}) => {
  return (
    <div
      data-testid={testId}
      className={`bg-white border border-accent-dark/30 ${
        inline ? "py-4 px-4" : "py-12 px-6"
      } text-center`}
    >
      {!inline && (
        <div className="w-11 h-11 bg-accent-dark/10 mx-auto mb-3 flex items-center justify-center">
          <AlertCircle className="w-6 h-6 text-accent-dark" />
        </div>
      )}
      <p className="text-sm font-bold text-accent-dark">{message}</p>
      {detail && (
        <p className="text-[11px] text-neutral-500 mt-1">{detail}</p>
      )}
      {onRetry && (
        <button
          type="button"
          onClick={onRetry}
          className="mt-3 inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-primary hover:underline"
        >
          <RefreshCw className="w-3 h-3" /> Retry
        </button>
      )}
    </div>
  );
};

export default ApiErrorState;
