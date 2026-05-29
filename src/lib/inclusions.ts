/**
 * Phase 2 (D-2-3) — group service inclusions into GoMechanic-style buckets
 * for rendering: "Essential", "Performance", "Additional", in that order.
 *
 * Rules:
 *  - NULL group_name → bucket under "Essential" (D-1.5-2 safe default).
 *  - Within a group, order by `position`.
 *  - Empty groups are omitted from the returned array (so the UI never
 *    renders an empty section).
 */
import type { InclusionGroup, ServiceInclusionItem } from "./api";

const ORDER: InclusionGroup[] = ["Essential", "Performance", "Additional"];

export interface InclusionGroupResult {
  group: InclusionGroup;
  items: ServiceInclusionItem[];
}

export function groupInclusions(
  inclusions: ServiceInclusionItem[] | undefined | null,
): InclusionGroupResult[] {
  const buckets: Record<InclusionGroup, ServiceInclusionItem[]> = {
    Essential: [],
    Performance: [],
    Additional: [],
  };

  for (const inc of inclusions ?? []) {
    const key: InclusionGroup =
      inc.group_name === "Performance" || inc.group_name === "Additional"
        ? inc.group_name
        : "Essential"; // NULL or "Essential" → Essential
    buckets[key].push(inc);
  }

  return ORDER.map((group) => ({
    group,
    items: [...buckets[group]].sort((a, b) => a.position - b.position),
  })).filter((g) => g.items.length > 0);
}
