import { ArrowRight } from "lucide-react";
import PageBanner from "../components/PageBanner";
import { DB_SERVICE_CATEGORIES, DB_SUB_SERVICES, LOCATIONS } from "../data/businessData";

interface SitemapProps {
  setCurrentPage: (page: string) => void;
  openEstimate?: (isCorporate?: boolean, initialService?: string) => void;
}

export default function Sitemap({ setCurrentPage }: SitemapProps) {
  return (
    <>
      <PageBanner
        title="Sitemap"
        breadcrumbs={[
          { label: "Home", onClick: () => setCurrentPage("home") },
          { label: "Sitemap" }
        ]}
      />
      <div className="section-spacing bg-white">
        <div className="site-container">
          <div className="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-4 gap-12">
            
            {/* Main Links */}
            <div>
              <h3 className="text-xl font-black uppercase text-primary-dark mb-6 border-b border-border pb-4">Main Pages</h3>
              <ul className="space-y-4">
                {["Home", "Services", "Service Centers", "Insurance", "Corporate", "Gallery", "About", "Contact", "Offers", "Coupons"].map(page => (
                  <li key={page}>
                    <button 
                      onClick={() => setCurrentPage(page.toLowerCase().replace(' ', '-'))}
                      className="text-muted hover:text-primary transition-colors text-sm font-medium flex items-center gap-2 group"
                    >
                      <ArrowRight className="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity" />
                      {page}
                    </button>
                  </li>
                ))}
              </ul>
            </div>

            {/* Service Categories */}
            <div>
              <h3 className="text-xl font-black uppercase text-primary-dark mb-6 border-b border-border pb-4">Service Categories</h3>
              <ul className="space-y-4">
                {DB_SERVICE_CATEGORIES.map(category => (
                  <li key={category.id}>
                    <button 
                      onClick={() => setCurrentPage(`category-${category.slug}`)}
                      className="text-muted hover:text-primary transition-colors text-sm font-medium flex items-center gap-2 group"
                    >
                      <ArrowRight className="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity" />
                      {category.title}
                    </button>
                  </li>
                ))}
              </ul>
            </div>

            {/* Sub Services */}
            <div>
              <h3 className="text-xl font-black uppercase text-primary-dark mb-6 border-b border-border pb-4">All Services</h3>
              <ul className="space-y-4">
                {DB_SUB_SERVICES.map(service => {
                  const category = DB_SERVICE_CATEGORIES.find(c => c.id === service.sc_id);
                  if (!category) return null;
                  return (
                    <li key={service.id}>
                      <button 
                        onClick={() => setCurrentPage(`service-${category.slug}/${service.slug}`)}
                        className="text-muted hover:text-primary transition-colors text-sm font-medium flex items-center gap-2 group text-left"
                      >
                        <ArrowRight className="w-3 h-3 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity" />
                        {service.title}
                      </button>
                    </li>
                  )
                })}
              </ul>
            </div>

            {/* Service Centers */}
            <div>
              <h3 className="text-xl font-black uppercase text-primary-dark mb-6 border-b border-border pb-4">Service Centers</h3>
              <ul className="space-y-4">
                {LOCATIONS.map(location => (
                  <li key={location.id}>
                    <button 
                      onClick={() => setCurrentPage(`center-${location.id}`)}
                      className="text-muted hover:text-primary transition-colors text-sm font-medium flex items-center gap-2 group text-left"
                    >
                      <ArrowRight className="w-3 h-3 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity" />
                      {location.name}
                    </button>
                  </li>
                ))}
              </ul>
            </div>

          </div>
        </div>
      </div>
    </>
  );
}
