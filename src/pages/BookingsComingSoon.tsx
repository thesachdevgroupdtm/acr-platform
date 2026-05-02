/**
 * Phase 2.3.2 — placeholder rendered when FEATURES.bookingsList is off.
 *
 * Pre-2.5 the MyBookings page surfaced "0 BOOKINGS" because the
 * client-side fake Checkout never wrote anywhere readable. This
 * page replaces that with an explicit "coming soon" alongside the
 * user's profile card so logged-in users see something useful.
 */
import { ArrowRight, Package, Phone } from "lucide-react";
import PageBanner from "../components/PageBanner";
import { BUSINESS_INFO } from "../data/businessData";
import { useAuth } from "../hooks/useAuth";

interface BookingsComingSoonProps {
  setCurrentPage: (page: string) => void;
  openAuth: (tab?: "login" | "signup", redirectTo?: string) => void;
}

export default function BookingsComingSoon({
  setCurrentPage,
  openAuth,
}: BookingsComingSoonProps) {
  const { user, isAuthenticated, logout } = useAuth();

  return (
    <>
      <PageBanner
        title="My Bookings"
        breadcrumbs={[
          { label: "Home", onClick: () => setCurrentPage("home") },
          { label: "My Bookings" },
        ]}
      />

      <div className="pb-14 pt-8">
        <div className="site-container">
          {!isAuthenticated || !user ? (
            <div className="bg-white border border-border py-16 px-6 text-center max-w-2xl mx-auto">
              <h2 className="text-2xl uppercase font-black text-neutral-900 tracking-tighter mb-2">
                Login to view bookings
              </h2>
              <p className="text-sm text-neutral-500 mb-6">
                Sign in to see your booking history once we launch online
                booking.
              </p>
              <button
                onClick={() => openAuth("login", "my-bookings")}
                className="btn-ink btn-ink-primary px-7 py-3.5 text-xs font-black uppercase tracking-widest inline-flex items-center gap-2"
              >
                Login <ArrowRight className="w-4 h-4 btn-arrow" />
              </button>
            </div>
          ) : (
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-10">
              {/* User card sidebar (mirrors MyBookings layout) */}
              <aside className="space-y-4 lg:sticky lg:self-start lg:top-28">
                <div className="bg-primary text-white p-6">
                  <div className="w-14 h-14 bg-white text-primary flex items-center justify-center text-2xl font-black mb-3">
                    {user.name.charAt(0).toUpperCase()}
                  </div>
                  <h3 className="text-lg font-black uppercase tracking-tighter">
                    {user.name}
                  </h3>
                  <p className="text-xs text-white/70 mb-1 truncate">
                    {user.email}
                  </p>
                  <p className="text-xs text-white/70">+91 {user.phone}</p>
                </div>
                <button
                  onClick={() => {
                    if (confirm("Log out of your account?")) logout();
                  }}
                  className="w-full bg-white border border-border py-3 text-xs font-black uppercase tracking-widest text-neutral-700 hover:border-accent-dark hover:text-accent-dark transition-colors"
                >
                  Logout
                </button>
              </aside>

              {/* Coming soon notice */}
              <div className="lg:col-span-2">
                <div className="bg-white border border-border py-16 px-6 text-center">
                  <div className="w-14 h-14 bg-neutral-100 mx-auto mb-4 flex items-center justify-center">
                    <Package className="w-7 h-7 text-neutral-400" />
                  </div>
                  <h2 className="text-2xl sm:text-3xl uppercase font-black text-neutral-900 tracking-tighter mb-2">
                    Booking history{" "}
                    <span className="text-primary">coming soon.</span>
                  </h2>
                  <p className="text-sm text-neutral-500 mb-6 max-w-md mx-auto leading-relaxed">
                    Your booking history will appear here once we launch
                    online booking. To check on a current service, please
                    contact our team.
                  </p>
                  <div className="flex flex-col sm:flex-row gap-3 justify-center">
                    <button
                      onClick={() => setCurrentPage("services")}
                      className="btn-ink btn-ink-primary px-7 py-3.5 text-xs font-black uppercase tracking-widest inline-flex items-center justify-center gap-2"
                    >
                      Browse Services <ArrowRight className="w-4 h-4 btn-arrow" />
                    </button>
                    <a
                      href={`tel:+91${BUSINESS_INFO.phone}`}
                      className="bg-white border border-border text-neutral-700 px-7 py-3.5 text-xs font-black uppercase tracking-widest hover:border-primary hover:text-primary transition-colors inline-flex items-center justify-center gap-2"
                    >
                      <Phone className="w-4 h-4" /> Call +91 {BUSINESS_INFO.phone}
                    </a>
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
    </>
  );
}
