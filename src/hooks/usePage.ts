/**
 * usePage — fetches a CMS page + its sections.
 *
 *   const { data, isLoading } = usePage("about-us");
 *   data?.page.sections.forEach(s => render(s.type, s.content));
 */
import { useQuery } from "@tanstack/react-query";
import { fetchPage, type PageResponse } from "../lib/api";

export function usePage(slug: string) {
  return useQuery<PageResponse>({
    queryKey: ["page", slug],
    queryFn: ({ signal }) => fetchPage(slug, signal),
    enabled: !!slug,
  });
}
