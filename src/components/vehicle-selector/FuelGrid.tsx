import { Fuel, Droplet, Zap, Wind, type LucideIcon } from "lucide-react";
import { useFuels } from "../../hooks/useVehicle";
import type { FuelType } from "../../lib/api";

/**
 * VehicleSelector — step 3 grid (fuel).
 *
 * GoMechanic-density pass: borderless cells, large fuel icon/image (~72px),
 * dense 3-col (2-col mobile) grid. ACR-blue selection tint only.
 */
const nameOf = (f: FuelType) => f.name?.trim() || f.title?.trim() || "—";

function iconFor(name: string): LucideIcon {
  const n = name.toLowerCase();
  if (/electric|ev|battery/.test(n)) return Zap;
  if (/diesel/.test(n)) return Droplet;
  if (/cng|lpg|hybrid/.test(n)) return Wind;
  return Fuel;
}

const cellCls = (active: boolean) =>
  `flex flex-col items-center justify-center gap-2 p-2 min-h-[112px] rounded-none border text-center transition-colors ${
    active
      ? "border-primary bg-primary/5"
      : "border-transparent hover:border-primary/30 hover:bg-primary/5"
  }`;

interface Props {
  /** Fuels are scoped to this model's slug (MODEL_FUEL_SCOPE). */
  modelSlug: string;
  selectedId?: number;
  onSelect: (f: FuelType) => void;
}

export default function FuelGrid({ modelSlug, selectedId, onSelect }: Props) {
  const q = useFuels(modelSlug);
  const fuels = q.data?.fuels ?? [];

  return (
    <div className="space-y-3">
      {q.isLoading ? (
        <div className="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-5">
          {Array.from({ length: 2 }).map((_, i) => (
            <div key={i} className="rounded-none bg-neutral-100 animate-pulse min-h-[112px]" />
          ))}
        </div>
      ) : q.isError ? (
        <div className="text-center py-8">
          <p className="text-xs font-bold uppercase tracking-widest text-accent-dark mb-2">
            Couldn't load fuel types
          </p>
          <button
            onClick={() => q.refetch()}
            className="text-[10px] font-black uppercase tracking-widest text-primary hover:underline"
          >
            Retry
          </button>
        </div>
      ) : fuels.length === 0 ? (
        <p className="text-center py-8 text-sm font-medium text-neutral-500">
          No fuel types for this model
        </p>
      ) : (
        <div className="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-5">
          {fuels.map((f) => {
            const active = selectedId === f.id;
            const name = nameOf(f);
            const Icon = iconFor(name);
            return (
              <button
                key={f.id}
                type="button"
                onClick={() => onSelect(f)}
                className={cellCls(active)}
                aria-pressed={active}
              >
                <div className="w-[72px] h-[72px] flex items-center justify-center">
                  {f.image ? (
                    <img
                      src={f.image}
                      alt={name}
                      className="max-w-full max-h-full object-contain"
                      referrerPolicy="no-referrer"
                    />
                  ) : (
                    <Icon className="w-14 h-14 text-primary" strokeWidth={1.5} />
                  )}
                </div>
                <span className="text-sm font-medium text-neutral-900 leading-tight">{name}</span>
              </button>
            );
          })}
        </div>
      )}
    </div>
  );
}
