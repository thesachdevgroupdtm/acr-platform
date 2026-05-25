/**
 * useApiQuery — generic data-loading hook backed by React Query.
 *
 * Surface kept stable for legacy call sites:
 *   const q = useApiQuery(["home"], (signal) => fetchHome(signal));
 *   q.data | q.isLoading | q.error | q.status | q.refetch()
 *
 * Prefer the domain hooks in src/hooks/use{Home,Services,Vehicle,Pricing}.ts
 * for new code — those have meaningful query keys and stronger typing.
 */
import { useQuery } from "@tanstack/react-query";

export type QueryStatus = "pending" | "loading" | "success" | "error" | "idle";

export interface UseApiQueryResult<T> {
  data: T | null;
  error: string | null;
  status: QueryStatus;
  isLoading: boolean;
  refetch: () => void;
}

export function useApiQuery<T>(
  key: ReadonlyArray<unknown>,
  loader: (signal: AbortSignal) => Promise<T>,
  options?: { enabled?: boolean }
): UseApiQueryResult<T> {
  const q = useQuery<T, Error>({
    queryKey: key as unknown as readonly unknown[],
    queryFn: ({ signal }) => loader(signal),
    enabled: options?.enabled !== false,
  });

  return {
    data: (q.data as T | undefined) ?? null,
    error: q.error?.message ?? null,
    status: q.status as QueryStatus,
    isLoading: q.isLoading || q.isFetching && q.data === undefined,
    refetch: () => { void q.refetch(); },
  };
}
