/**
 * useHome — fetches the unified /home payload.
 * One round-trip; all home-page sections derive from this.
 */
import { useQuery } from "@tanstack/react-query";
import { fetchHome, type HomeResponse } from "../lib/api";

export const HOME_KEY = ["home"] as const;

export function useHome() {
  return useQuery<HomeResponse>({
    queryKey: HOME_KEY,
    queryFn: ({ signal }) => fetchHome(signal),
  });
}
