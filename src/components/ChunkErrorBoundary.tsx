import { Component, type ErrorInfo, type ReactNode } from "react";

/**
 * Phase 2.6b — error boundary for code-split chunk failures.
 *
 * React.lazy() resolves a dynamic import(); if the chunk request
 * fails (network blocked, CDN flaked, deploy mismatch where the
 * client has stale chunk hashes), the import promise rejects and
 * Suspense surfaces the error to the nearest error boundary. With
 * NO boundary, the entire React tree unmounts and the user sees a
 * blank page — exactly the failure mode this commit is here to
 * prevent.
 *
 * Catch-rules:
 *   - Errors whose message matches /Loading (CSS )?chunk|Failed to
 *     fetch dynamically imported module/i are chunk-load failures
 *     → render the retry UI.
 *   - Any other error is RE-THROWN. Page-level error boundaries
 *     (or the root error boundary) own non-chunk failures; this
 *     component is intentionally narrow.
 *
 * Recovery is via window.location.reload(): the client reloads
 * index.html, which fetches the latest chunk manifest and the
 * current chunk hashes. A reload is the only safe recovery for
 * the deploy-mismatch case (just retrying the same import URL
 * would 404 forever against the new manifest).
 *
 * The "Page failed to load" + "Reload" button copy is matched by
 * the chunk-fail Playwright test in tests/e2e/code-splitting.spec.ts.
 */
type Props = { children: ReactNode };
type State = { hasError: boolean; error: Error | null };

const CHUNK_ERROR_PATTERN =
  /Loading (CSS )?chunk|Failed to fetch dynamically imported module|Importing a module script failed/i;

function isChunkLoadError(error: unknown): boolean {
  if (!(error instanceof Error)) return false;
  return CHUNK_ERROR_PATTERN.test(error.message);
}

export default class ChunkErrorBoundary extends Component<Props, State> {
  // Explicit instance fields — React 19 in this project ships without
  // @types/react, so the inherited `props`/`state` are not typed and
  // TS otherwise complains "Property 'state' does not exist". Declaring
  // them here is purely a type-system shim; the runtime behaviour is
  // identical to the inherited fields.
  declare props: Props;
  state: State;

  constructor(props: Props) {
    super(props);
    this.state = { hasError: false, error: null };
  }

  static getDerivedStateFromError(error: unknown): State {
    if (isChunkLoadError(error)) {
      return { hasError: true, error: error as Error };
    }
    // Non-chunk error: re-throw so the next boundary up handles it.
    throw error;
  }

  componentDidCatch(error: Error, info: ErrorInfo): void {
    // Log only chunk errors; getDerivedStateFromError re-throws
    // everything else before this hook runs.
    if (isChunkLoadError(error)) {
      // eslint-disable-next-line no-console
      console.warn("[ChunkErrorBoundary] chunk load failed", {
        message: error.message,
        componentStack: info.componentStack,
      });
    }
  }

  private handleRetry = (): void => {
    window.location.reload();
  };

  render(): ReactNode {
    if (!this.state.hasError) {
      return this.props.children;
    }

    return (
      <div
        className="min-h-[60vh] flex items-center justify-center px-6"
        role="alert"
      >
        <div className="bg-white border border-border max-w-lg w-full py-12 px-6 sm:px-10 text-center">
          <h2 className="text-2xl sm:text-3xl font-black uppercase tracking-tighter text-neutral-900 mb-3">
            Page failed to load.
          </h2>
          <p className="text-sm text-neutral-500 leading-relaxed mb-8 max-w-md mx-auto">
            Your network may be slow or unstable, or this app was
            updated since you opened it. Reloading will fetch the
            latest version.
          </p>
          <button
            type="button"
            onClick={this.handleRetry}
            className="btn-ink btn-ink-primary inline-flex items-center gap-2 px-8 py-4 text-xs font-black uppercase tracking-widest"
          >
            Reload
          </button>
        </div>
      </div>
    );
  }
}
