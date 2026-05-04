/**
 * useOrders — Phase 2.5a.
 *
 * Three React Query hooks backed by the new /user/orders endpoints:
 *
 *   useOrdersList(params?)  → GET  /user/orders        (list view)
 *   useOrderDetail(id)      → GET  /user/orders/{id}   (detail view)
 *   useCancelOrder()        → POST /user/orders/{id}/cancel mutation
 *
 * Cancellation invalidates both the list and the affected detail
 * query so the UI refreshes without a manual refetch.
 */
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { fetchOrder, fetchOrders, postCancelOrder } from "../lib/api";
import type { OrderResource } from "../types/api";

interface OrdersListParams {
  page?: number;
  per_page?: number;
  status?: string;
}

export function useOrdersList(params?: OrdersListParams) {
  const q = useQuery({
    queryKey: ["orders", params ?? {}],
    queryFn: async ({ signal }) => (await fetchOrders(params, signal)),
    staleTime: 30_000,
  });

  return {
    orders: q.data?.orders ?? [],
    pagination: q.data?.pagination ?? null,
    isLoading: q.isLoading,
    isError: q.isError,
    error: q.error,
    refetch: () => { void q.refetch(); },
  };
}

export function useOrderDetail(orderId: number | null | undefined) {
  const enabled = typeof orderId === "number" && orderId > 0;
  const q = useQuery({
    queryKey: ["order", orderId ?? null],
    queryFn: async ({ signal }) =>
      (await fetchOrder(orderId as number, signal)).order,
    enabled,
    staleTime: 30_000,
  });

  return {
    order: (q.data ?? null) as OrderResource | null,
    isLoading: q.isLoading,
    isError: q.isError,
    error: q.error,
    refetch: () => { void q.refetch(); },
  };
}

export function useCancelOrder() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (args: { orderId: number; reason?: string | null }) => {
      const res = await postCancelOrder(args.orderId, args.reason ?? null);
      return res.order;
    },
    onSuccess: (order) => {
      qc.setQueryData(["order", order.id], order);
      qc.invalidateQueries({ queryKey: ["orders"] });
    },
  });
}
