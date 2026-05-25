import { useState } from "react";
import { Search, Car } from "lucide-react";
import { useModels } from "../../hooks/useVehicle";
import type { CarModel } from "../../lib/api";

/**
 * VehicleSelector — step 2 grid (model).
 *
 * GoMechanic-density pass: borderless cells, dominant model image
 * (~110px wide) with a large car-icon fallback, dense 3-col (2-col mobile)
 * grid. ACR-blue selection tint only. keepPreviousData stays off useModels
 * so a brand change shows this grid's skeleton, never the previous models.
 */
const nameOf = (m: CarModel) => m.name?.trim() || m.title?.trim() || "—";

const cellCls = (active: boolean) =>
  `flex flex-col items-center justify-center gap-2 p-2 min-h-[132px] rounded-none border text-center transition-colors ${
    active
      ? "border-primary bg-primary/5"
      : "border-transparent hover:border-primary/30 hover:bg-primary/5"
  }`;

interface Props {
  brandId: number;
  selectedId?: number;
  onSelect: (m: CarModel) => void;
}

export default function ModelGrid({ brandId, selectedId, onSelect }: Props) {
  const q = useModels(brandId);
  const [query, setQuery] = useState("");
  const models = q.data?.models ?? [];
  const filtered = models.filter(
    (m) => !query.trim() || nameOf(m).toLowerCase().includes(query.trim().toLowerCase()),
  );

  return (
    <div className="space-y-4">
      <div className="relative">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-neutral-400 pointer-events-none" />
        <input
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder="Search model…"
          className="w-full bg-white border border-border rounded-none pl-10 pr-3 py-3 text-sm text-neutral-900 focus:border-primary outline-none"
        />
      </div>

      {q.isLoading ? (
        <div className="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-5">
          {Array.from({ length: 3 }).map((_, i) => (
            <div key={i} className="rounded-none bg-neutral-100 animate-pulse min-h-[132px]" />
          ))}
        </div>
      ) : q.isError ? (
        <div className="text-center py-8">
          <p className="text-xs font-bold uppercase tracking-widest text-accent-dark mb-2">
            Couldn't load models
          </p>
          <button
            onClick={() => q.refetch()}
            className="text-[10px] font-black uppercase tracking-widest text-primary hover:underline"
          >
            Retry
          </button>
        </div>
      ) : filtered.length === 0 ? (
        <p className="text-center py-8 text-sm font-medium text-neutral-500">No models found</p>
      ) : (
        <div className="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-5">
          {filtered.map((m) => {
            const active = selectedId === m.id;
            const name = nameOf(m);
            return (
              <button
                key={m.id}
                type="button"
                onClick={() => onSelect(m)}
                className={cellCls(active)}
                aria-pressed={active}
              >
                <div className="h-20 w-full flex items-center justify-center">
                  {m.image ? (
                    <img
                      src={m.image}
                      alt={name}
                      className="max-h-full max-w-[112px] object-contain"
                      referrerPolicy="no-referrer"
                    />
                  ) : (
                    <Car className="w-12 h-12 text-neutral-400" strokeWidth={1.25} />
                  )}
                </div>
                <span className="text-sm font-medium text-neutral-900 leading-tight line-clamp-2">
                  {name}
                </span>
              </button>
            );
          })}
        </div>
      )}
    </div>
  );
}
