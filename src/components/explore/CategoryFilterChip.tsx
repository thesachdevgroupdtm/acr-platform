import { useSearchParams } from "react-router-dom";
import { X } from "lucide-react";

interface Props {
  /** Display name for the active category (resolved by parent
   *  from the categories list since the URL only carries the slug). */
  categoryName: string | null;
}

/**
 * Phase 4.5.1 — visible chip when ?category=… is active.
 *
 * Click × clears the param, which triggers ExploreEditorial's
 * useQuery to re-fetch the unfiltered payload.
 */
export default function CategoryFilterChip({ categoryName }: Props) {
  const [params, setParams] = useSearchParams();
  if (!params.get("category")) return null;

  const onClear = () => {
    const next = new URLSearchParams(params);
    next.delete("category");
    setParams(next, { replace: true });
  };

  return (
    <div
      data-testid="category-filter-chip"
      className="bg-white border-b border-border"
    >
      <div className="site-container py-3 flex items-center gap-2 text-xs">
        <span className="font-bold uppercase tracking-widest text-neutral-500">
          Filtered by:
        </span>
        <span className="inline-flex items-center gap-2 bg-primary text-white px-3 py-1 text-[10px] font-bold uppercase tracking-widest">
          {categoryName ?? params.get("category")}
          <button
            type="button"
            onClick={onClear}
            aria-label="Clear category filter"
            data-testid="category-filter-clear"
            className="hover:bg-white/20 -mr-1 px-1"
          >
            <X className="w-3 h-3" />
          </button>
        </span>
      </div>
    </div>
  );
}
