import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { BrowserRouter } from "react-router-dom";
import App from "./App.tsx";
import "./index.css";

// Single QueryClient for the whole app. Defaults are tuned for a CMS where
// content (categories, services, brands) is stable and rarely refetched.
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 5 * 60 * 1000,            // 5 minutes
      gcTime: 30 * 60 * 1000,              // 30 minutes
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
});

// Phase 3A — react-router-dom v7. The Vite production build is
// served from /app/ (vite.config.ts `base`), so the router's
// basename must match. In dev BASE_URL is "/"; in prod it's "/app/".
// react-router accepts either form, but we trim a trailing slash
// for cleanliness.
const ROUTER_BASENAME = (import.meta.env.BASE_URL || "/").replace(/\/+$/, "") || "/";

createRoot(document.getElementById("root")!).render(
  <StrictMode>
    <QueryClientProvider client={queryClient}>
      <BrowserRouter basename={ROUTER_BASENAME}>
        <App />
      </BrowserRouter>
    </QueryClientProvider>
  </StrictMode>
);
