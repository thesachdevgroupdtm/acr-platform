import { useEffect, useMemo, useRef, useState, type FormEvent } from "react";
import { useNavigate } from "react-router-dom";
import { Search, Clock, X } from "lucide-react";
import type { ExploreCard } from "../../lib/api";

interface Props {
  /** Flattened pool of cards from the Explore payload — used for client-side filtering. */
  pool: ExploreCard[];
}

const RECENT_KEY = "acr_explore_recent";
const MAX_RECENT = 6;
const MAX_SUGGESTIONS = 8;

/**
 * Phase 4.5 — client-side search.
 *
 * Per D-4.5-6: NO backend search endpoint. Filters the
 * already-loaded ExplorePayload card pool (hero + trending +
 * categories.items + rails). Matches title / excerpt /
 * category.name. Highlights matched substring with <mark>.
 *
 * Recent searches persist in localStorage (max 6 entries),
 * surfaced when the input is focused but empty.
 */
export default function ExploreSearch({ pool }: Props) {
  const navigate = useNavigate();
  const [query, setQuery] = useState("");
  const [focused, setFocused] = useState(false);
  const [recents, setRecents] = useState<string[]>([]);
  const inputRef = useRef<HTMLInputElement | null>(null);

  /* Read recents on mount */
  useEffect(() => {
    try {
      const raw = window.localStorage.getItem(RECENT_KEY);
      if (raw) {
        const parsed = JSON.parse(raw);
        if (Array.isArray(parsed)) {
          setRecents(parsed.filter((s) => typeof s === "string").slice(0, MAX_RECENT));
        }
      }
    } catch {
      /* localStorage unavailable / corrupt — silent */
    }
  }, []);

  const persistRecent = (term: string) => {
    if (!term.trim()) return;
    const next = [term, ...recents.filter((r) => r !== term)].slice(0, MAX_RECENT);
    setRecents(next);
    try {
      window.localStorage.setItem(RECENT_KEY, JSON.stringify(next));
    } catch {
      /* silent */
    }
  };

  const clearRecents = () => {
    setRecents([]);
    try {
      window.localStorage.removeItem(RECENT_KEY);
    } catch {
      /* silent */
    }
  };

  /* De-dupe pool by slug (hero/trending/categories overlap). */
  const dedupedPool = useMemo(() => {
    const seen = new Set<string>();
    return pool.filter((c) => {
      if (seen.has(c.slug)) return false;
      seen.add(c.slug);
      return true;
    });
  }, [pool]);

  const suggestions = useMemo(() => {
    const q = query.trim().toLowerCase();
    if (!q) return [];
    return dedupedPool
      .filter((c) => {
        const hay = `${c.title} ${c.excerpt ?? ""} ${c.category?.name ?? ""}`.toLowerCase();
        return hay.includes(q);
      })
      .slice(0, MAX_SUGGESTIONS);
  }, [query, dedupedPool]);

  const onSelect = (slug: string, term: string) => {
    persistRecent(term);
    setQuery("");
    setFocused(false);
    inputRef.current?.blur();
    navigate(`/${slug}`);
  };

  const onSubmit = (e: FormEvent) => {
    e.preventDefault();
    if (suggestions.length > 0) {
      onSelect(suggestions[0].slug, query.trim());
    }
  };

  return (
    <div className="relative w-full max-w-[600px] mx-auto" data-testid="explore-search-shell">
      <form onSubmit={onSubmit}>
        <div className="relative">
          <Search className="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-neutral-400 pointer-events-none" />
          <input
            ref={inputRef}
            type="search"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            onFocus={() => setFocused(true)}
            onBlur={() => window.setTimeout(() => setFocused(false), 150)}
            placeholder="Search articles, brands, services…"
            data-testid="explore-search-input"
            className="w-full pl-11 pr-4 py-3 bg-white border border-border focus:border-primary outline-none text-sm transition-colors"
          />
        </div>
      </form>

      {focused && (query ? suggestions.length > 0 : recents.length > 0) && (
        <div
          className="absolute z-30 left-0 right-0 mt-1 bg-white border border-border shadow-lg max-h-[60vh] overflow-y-auto"
          data-testid="explore-search-dropdown"
        >
          {/* Recent searches when input is empty */}
          {!query && recents.length > 0 && (
            <div className="p-3">
              <div className="flex items-center justify-between mb-2">
                <span className="text-[10px] font-bold uppercase tracking-widest text-neutral-400">
                  Recent
                </span>
                <button
                  type="button"
                  onClick={clearRecents}
                  className="text-[10px] uppercase tracking-widest text-neutral-400 hover:text-primary inline-flex items-center gap-1"
                >
                  <X className="w-3 h-3" /> Clear
                </button>
              </div>
              {recents.map((r) => (
                <button
                  key={r}
                  type="button"
                  onClick={() => setQuery(r)}
                  className="w-full text-left flex items-center gap-2 px-2 py-2 text-sm text-neutral-700 hover:bg-neutral-50"
                >
                  <Clock className="w-3 h-3 text-neutral-400" /> {r}
                </button>
              ))}
            </div>
          )}

          {/* Live suggestions */}
          {query && suggestions.length > 0 && (
            <ul>
              {suggestions.map((c) => (
                <li key={c.slug}>
                  <button
                    type="button"
                    onClick={() => onSelect(c.slug, query.trim())}
                    data-testid={`explore-search-suggestion-${c.slug}`}
                    className="w-full text-left px-4 py-3 hover:bg-neutral-50 flex items-start gap-3 border-b border-border last:border-b-0"
                  >
                    <div className="flex-1 min-w-0">
                      <p className="text-sm font-bold text-neutral-900 line-clamp-1">
                        <Highlighted text={c.title} term={query} />
                      </p>
                      {c.excerpt && (
                        <p className="text-xs text-neutral-500 line-clamp-1 mt-0.5">
                          <Highlighted text={c.excerpt} term={query} />
                        </p>
                      )}
                    </div>
                    {c.category?.name && (
                      <span className="shrink-0 inline-block bg-primary text-white px-2 py-0.5 text-[8px] font-bold uppercase tracking-widest">
                        {c.category.name}
                      </span>
                    )}
                    <span className="shrink-0 text-[10px] text-neutral-400 uppercase tracking-widest font-bold">
                      {c.reading_time_minutes} min
                    </span>
                  </button>
                </li>
              ))}
            </ul>
          )}
        </div>
      )}

      {/* Empty-state for "typed but no match" — shown only when
          there's a query, dropdown is open, but suggestions = 0. */}
      {focused && query && suggestions.length === 0 && (
        <div
          className="absolute z-30 left-0 right-0 mt-1 bg-white border border-border shadow-lg p-6 text-center text-sm text-neutral-500"
          data-testid="explore-search-empty"
        >
          No matches for "<span className="font-bold text-neutral-900">{query}</span>".
        </div>
      )}
    </div>
  );
}

function Highlighted({ text, term }: { text: string; term: string }) {
  const t = term.trim();
  if (!t) return <>{text}</>;
  const idx = text.toLowerCase().indexOf(t.toLowerCase());
  if (idx < 0) return <>{text}</>;
  const before = text.slice(0, idx);
  const match = text.slice(idx, idx + t.length);
  const after = text.slice(idx + t.length);
  return (
    <>
      {before}
      <mark className="bg-primary/15 text-primary not-italic px-0.5">{match}</mark>
      {after}
    </>
  );
}
