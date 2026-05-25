import { useState } from "react";
import { Search } from "lucide-react";
import { useBrands } from "../../hooks/useVehicle";
import type { CarBrand } from "../../lib/api";

/**
 * VehicleSelector — step 1 grid (manufacturer).
 *
 * GoMechanic-density pass (SELECTOR-DENSITY): borderless image-first cells,
 * large logos (~80px), dense 3-col (2-col mobile) grid. ACR palette only —
 * selected = ACR Blue (`primary`) thin border + light blue tint, never a
 * heavy permanent box.
 */
const nameOf = (b: CarBrand) => b.name?.trim() || b.title?.trim() || "—";

const cellCls = (active: boolean) =>
  `flex flex-col items-center justify-center gap-2 p-2 min-h-[124px] rounded-none border text-center transition-colors ${
    active
      ? "border-primary bg-primary/5"
      : "border-transparent hover:border-primary/30 hover:bg-primary/5"
  }`;

interface Props {
  selectedId?: number;
  onSelect: (b: CarBrand) => void;
}

export default function BrandGrid({ selectedId, onSelect }: Props) {
  const q = useBrands();
  const [query, setQuery] = useState("");
  const brands = q.data?.brands ?? [];
  const filtered = brands.filter(
    (b) => !query.trim() || nameOf(b).toLowerCase().includes(query.trim().toLowerCase()),
  );

  return (
    <div className="space-y-4">
      <div className="relative">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-neutral-400 pointer-events-none" />
        <input
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder="Search manufacturer…"
          className="w-full bg-white border border-border rounded-none pl-10 pr-3 py-3 text-sm text-neutral-900 focus:border-primary outline-none"
        />
      </div>

      {q.isLoading ? (
        <div className="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-5">
          {Array.from({ length: 9 }).map((_, i) => (
            <div key={i} className="rounded-none bg-neutral-100 animate-pulse min-h-[124px]" />
          ))}
        </div>
      ) : q.isError ? (
        <div className="text-center py-8">
          <p className="text-xs font-bold uppercase tracking-widest text-accent-dark mb-2">
            Couldn't load brands
          </p>
          <button
            onClick={() => q.refetch()}
            className="text-[10px] font-black uppercase tracking-widest text-primary hover:underline"
          >
            Retry
          </button>
        </div>
      ) : filtered.length === 0 ? (
        <p className="text-center py-8 text-sm font-medium text-neutral-500">No manufacturers found</p>
      ) : (
        <div className="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-5">
          {filtered.map((b) => {
            const active = selectedId === b.id;
            const name = nameOf(b);
            return (
              <button
                key={b.id}
                type="button"
                onClick={() => onSelect(b)}
                className={cellCls(active)}
                aria-pressed={active}
              >
                <div className="w-20 h-20 flex items-center justify-center">
                  {b.image ? (
                    <img
                      src={b.image}
                      alt={name}
                      className="max-w-full max-h-full object-contain"
                      referrerPolicy="no-referrer"
                    />
                  ) : (
                    <span className="text-3xl font-bold text-primary">{name.charAt(0)}</span>
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
