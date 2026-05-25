import type * as React from "react";
import { Inbox } from "lucide-react";

interface EmptyStateProps {
  title: string;
  hint?: string;
  /** Optional Lucide icon (any element). Defaults to a neutral inbox glyph. */
  icon?: React.ReactNode;
  /** Compact inline variant. */
  inline?: boolean;
  "data-testid"?: string;
}

/**
 * Phase 4.2.5 — reusable empty-data state.
 *
 * Distinguishes "API succeeded but returned zero rows" from
 * "API failed" (use ApiErrorState for the latter). Conflating
 * the two — the bug pattern that caused operator's
 * "Couldn't load coupons" report when it surfaced inside
 * CouponPickerModal — is exactly what this split prevents.
 */
export const EmptyState: React.FC<EmptyStateProps> = ({
  title,
  hint,
  icon,
  inline = false,
  "data-testid": testId = "empty-state",
}) => {
  return (
    <div
      data-testid={testId}
      className={`bg-white border border-border ${
        inline ? "py-4 px-4" : "py-12 px-6"
      } text-center`}
    >
      {!inline && (
        <div className="w-11 h-11 bg-neutral-100 mx-auto mb-3 flex items-center justify-center">
          {icon ?? <Inbox className="w-6 h-6 text-neutral-400" />}
        </div>
      )}
      <h3 className="text-sm font-black uppercase tracking-tighter text-neutral-900">
        {title}
      </h3>
      {hint && (
        <p className="text-[11px] text-neutral-500 mt-1">{hint}</p>
      )}
    </div>
  );
};

export default EmptyState;
