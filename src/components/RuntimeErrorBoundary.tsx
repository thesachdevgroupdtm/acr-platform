import { Component, type ErrorInfo, type ReactNode } from "react";

/**
 * Runtime error boundary — sister to ChunkErrorBoundary.
 *
 * ChunkErrorBoundary catches dynamic-import failures and re-throws
 * everything else. That meant ANY runtime exception in a route
 * component (null deref in cart UI, undefined access in checkout,
 * etc.) unmounted the entire React tree and blanked the page — the
 * "crash" the operator reported during multi-step flows like
 * add-to-cart → login → checkout.
 *
 * This boundary catches non-chunk runtime errors and renders a
 * friendly retry surface. Recovery paths:
 *   - Navigate to a different route → the parent passes a new
 *     `resetKey` (the pathname) → `getDerivedStateFromProps` clears
 *     `hasError`. The next render shows the new route's children.
 *   - "Reload Page" button → window.location.reload().
 *
 * No "Try Again in place" button: this project ships React 19 with
 * no @types/react, so `this.setState` is not type-visible on the
 * class. The reset-on-navigation path covers the common case
 * (almost every recoverable error is followed by the user clicking
 * elsewhere), and Reload is the escape hatch for the rest.
 *
 * Chunk errors RE-THROW so the outer ChunkErrorBoundary handles
 * them (its reload-only recovery is the right move for stale chunk
 * manifests).
 */
interface Props {
  children: ReactNode;
  /** Opaque value that resets the boundary when it changes. The
   *  parent passes `useLocation().pathname` so a navigation away
   *  from an errored page clears the error UI. */
  resetKey?: string;
}

interface State {
  hasError: boolean;
  error: Error | null;
  lastResetKey: string | undefined;
}

const CHUNK_ERROR_PATTERN =
  /Loading (CSS )?chunk|Failed to fetch dynamically imported module|Importing a module script failed/i;

function isChunkError(err: unknown): boolean {
  if (!(err instanceof Error)) return false;
  return CHUNK_ERROR_PATTERN.test(err.message);
}

export default class RuntimeErrorBoundary extends Component<Props, State> {
  declare props: Props;
  state: State;

  constructor(props: Props) {
    super(props);
    this.state = {
      hasError: false,
      error: null,
      lastResetKey: props.resetKey,
    };
  }

  /**
   * When the parent passes a new resetKey (e.g. navigation away from
   * the errored page) clear hasError so the next render shows the
   * new children. Pure static hook — no setState needed.
   */
  static getDerivedStateFromProps(
    props: Props,
    state: State,
  ): Partial<State> | null {
    if (props.resetKey !== state.lastResetKey) {
      return {
        hasError: false,
        error: null,
        lastResetKey: props.resetKey,
      };
    }
    return null;
  }

  static getDerivedStateFromError(error: Error): Partial<State> {
    if (isChunkError(error)) throw error;
    return { hasError: true, error };
  }

  componentDidCatch(error: Error, info: ErrorInfo): void {
    if (isChunkError(error)) return;
    // eslint-disable-next-line no-console
    console.error("[RuntimeErrorBoundary] caught", error, info.componentStack);
  }

  private handleReload = (): void => {
    window.location.reload();
  };

  render(): ReactNode {
    if (!this.state.hasError) return this.props.children;

    return (
      <div className="min-h-[60vh] flex items-center justify-center p-6">
        <div className="bg-white border border-border shadow-xl p-6 sm:p-8 max-w-md w-full text-center">
          <h2 className="text-xl sm:text-2xl font-black uppercase tracking-tighter text-neutral-900 mb-2">
            Something <span className="text-primary">Broke.</span>
          </h2>
          <p className="text-sm text-neutral-600 leading-relaxed mb-5">
            A part of the page hit an unexpected error. Your data is
            still safe — navigate to another page or reload to recover.
          </p>
          <button
            type="button"
            onClick={this.handleReload}
            className="btn-ink btn-ink-primary px-5 py-2.5 text-[10px] font-black uppercase tracking-widest justify-center"
          >
            Reload Page
          </button>
          {this.state.error?.message && (
            <p className="text-[10px] font-mono text-neutral-400 mt-4 break-all">
              {this.state.error.message}
            </p>
          )}
        </div>
      </div>
    );
  }
}
