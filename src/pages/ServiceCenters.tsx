import { motion } from "motion/react";
import { useNavigate } from "react-router-dom";
import { MapPin, Phone, Star, ArrowRight, Clock, Shield } from "lucide-react";
import { LOCATIONS } from "../data/businessData";
import PageBanner from "../components/PageBanner";

interface ServiceCentersProps {
  openEstimate?: (isCorporate?: boolean, initialService?: string) => void;
}

export default function ServiceCenters(_props: ServiceCentersProps) {
  const navigate = useNavigate();
  return (
    <>
      <PageBanner
        title="Our Centres"
        breadcrumbs={[
          { label: "Home", onClick: () => navigate("/") },
          { label: "Service Centers" }
        ]}
      />
      <div className="section-spacing pt-0">
        <div className="site-container">

          {/* Centers Grid */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {LOCATIONS.map((center, i) => (
            <motion.div
              key={center.id}
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              transition={{ delay: i * 0.1 }}
              viewport={{ once: true }}
              className="bg-white border border-border group overflow-hidden shadow-sm hover:shadow-xl transition-all"
            >
              <div className="relative h-56 overflow-hidden">
                <img 
                  src={center.image} 
                  alt={center.name} 
                  className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                  referrerPolicy="no-referrer"
                />
                <div className="absolute top-3 right-3 bg-primary text-white px-2 py-0.5 text-[8px] font-bold uppercase tracking-widest">
                  {center.rating} <Star className="w-2.5 h-2.5 inline-block fill-current" />
                </div>
              </div>
              
              <div className="p-6">
                <h3 className="text-xl font-black uppercase mb-3 text-neutral-900">{center.name}</h3>
                <div className="space-y-3 mb-6">
                  <p className="text-[13px] text-muted leading-relaxed mb-4">
                    {center.name} is a premier ACR facility equipped with the latest diagnostic tools and highly trained technicians. 
                    Serving the local community, it ensures your vehicle receives meticulous, factory-standard care for all repair needs.
                  </p>
                  <div className="flex items-start gap-3 text-xs text-neutral-500">
                    <MapPin className="w-4 h-4 text-primary shrink-0" />
                    <span>{center.address}</span>
                  </div>
                  <div className="flex items-center gap-3 text-xs text-neutral-500">
                    <Phone className="w-4 h-4 text-primary shrink-0" />
                    <span>{center.phone}</span>
                  </div>
                </div>

                <div className="flex flex-wrap gap-2 mb-6">
                  {center.features.map((feature, j) => (
                    <span key={j} className="bg-neutral-50 text-[8px] font-bold uppercase tracking-widest px-2 py-1 text-neutral-400 border border-border">
                      {feature}
                    </span>
                  ))}
                </div>

                <button 
                  onClick={() => navigate(`/center/${center.id}`)}
                  className="w-full border border-primary text-primary py-3 text-[10px] font-bold uppercase tracking-widest hover:bg-primary hover:text-white transition-all flex items-center justify-center gap-2"
                >
                  View Centre Details <ArrowRight className="w-4 h-4" />
                </button>
              </div>
            </motion.div>
          ))}
        </div>

        {/* Global Standards */}
        <div className="mt-24 grid grid-cols-1 md:grid-cols-3 gap-12 border-t border-border pt-16">
          <div className="text-center space-y-3">
            <div className="bg-neutral-50 w-12 h-12 flex items-center justify-center mx-auto border border-border">
              <Shield className="w-6 h-6 text-primary" />
            </div>
            <h4 className="text-lg font-black uppercase text-neutral-900">Standardized Quality</h4>
            <p className="text-sm text-neutral-500">Uniform quality checks and repair protocols across all our NCR locations.</p>
          </div>
          <div className="text-center space-y-3">
            <div className="bg-neutral-50 w-12 h-12 flex items-center justify-center mx-auto border border-border">
              <Clock className="w-6 h-6 text-primary" />
            </div>
            <h4 className="text-lg font-black uppercase text-neutral-900">Centralized Tracking</h4>
            <p className="text-sm text-neutral-500">Track your vehicle's repair status in real-time regardless of the location.</p>
          </div>
          <div className="text-center space-y-3">
            <div className="bg-neutral-50 w-12 h-12 flex items-center justify-center mx-auto border border-border">
              <Star className="w-6 h-6 text-primary" />
            </div>
            <h4 className="text-lg font-black uppercase text-neutral-900">Expert Mobility</h4>
            <p className="text-sm text-neutral-500">Our master technicians travel between centres for specialized restoration tasks.</p>
          </div>
        </div>
        </div>
      </div>
    </>
  );
}
