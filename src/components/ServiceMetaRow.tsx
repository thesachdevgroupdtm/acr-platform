import type * as React from "react";
import { Clock, ShieldCheck, CalendarClock, Truck } from "lucide-react";

/**
 * Phase 2 (D-2-5) — shared service meta row: duration / warranty /
 * interval. Renders ONLY the fields that are non-null and collapses
 * gracefully when everything is missing (returns null). ACR brand skin
 * (blue icons, Workshop-Black labels, muted values). Used by the category
 * card meta line (`variant="compact"`) and the detail meta strip
 * (`variant="detail"`).
 */
interface Props {
  timeTakes?: string | number | null;
  timeUnit?: string | null;
  warranty?: string | null;
  /** interval_info — rendered with a "Recommended" label (D-2-5). */
  interval?: string | null;
  /** Phase 2b-cont (D-2b-7) — append a STATIC "Free Pickup & Drop" item
   *  on the Layer-3 detail meta row. Off by default so the compact
   *  category-card usage is unaffected. */
  freePickup?: boolean;
  variant?: "compact" | "detail";
  className?: string;
}

interface Item {
  icon: React.ComponentType<{ className?: string }>;
  label: string;
  value: string;
}

export default function ServiceMetaRow({
  timeTakes,
  timeUnit,
  warranty,
  interval,
  freePickup = false,
  variant = "compact",
  className = "",
}: Props) {
  const items: Item[] = [];

  const durationVal =
    timeTakes !== null && timeTakes !== undefined && String(timeTakes).trim() !== ""
      ? `${timeTakes}${timeUnit ? " " + timeUnit : ""}`
      : null;
  if (durationVal) items.push({ icon: Clock, label: "Duration", value: durationVal });
  if (warranty && warranty.trim() !== "")
    items.push({ icon: ShieldCheck, label: "Warranty", value: warranty });
  if (interval && interval.trim() !== "")
    items.push({ icon: CalendarClock, label: "Recommended", value: interval });
  // Static, non-null — appended last so it never crowds out real data.
  if (freePickup) items.push({ icon: Truck, label: "Pickup & Drop", value: "Free" });

  if (items.length === 0) return null;

  if (variant === "detail") {
    return (
      <div className={`grid grid-cols-1 sm:grid-cols-3 gap-3 ${className}`.trim()}>
        {items.map((it) => {
          const Icon = it.icon;
          return (
            <div
              key={it.label}
              className="flex items-start gap-2.5 bg-neutral-50 border border-border px-3 py-2.5"
            >
              <Icon className="w-4 h-4 text-primary shrink-0 mt-0.5" />
              <div className="min-w-0">
                <p className="text-[9px] font-bold uppercase tracking-widest text-neutral-400">
                  {it.label}
                </p>
                <p className="text-xs font-bold text-neutral-900 leading-snug">{it.value}</p>
              </div>
            </div>
          );
        })}
      </div>
    );
  }

  // compact — inline meta line for cards
  return (
    <div className={`flex flex-wrap items-center gap-x-3 gap-y-1 ${className}`.trim()}>
      {items.map((it) => {
        const Icon = it.icon;
        return (
          <span key={it.label} className="inline-flex items-center gap-1 text-[11px] text-neutral-600">
            <Icon className="w-3.5 h-3.5 text-primary shrink-0" />
            <span className="font-medium">{it.value}</span>
          </span>
        );
      })}
    </div>
  );
}
