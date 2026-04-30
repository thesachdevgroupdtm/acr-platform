import { motion } from "motion/react";
import PageBanner from "../components/PageBanner";
import { ArrowRight, Star, Clock, AlertCircle } from "lucide-react";

interface OffersProps {
  setCurrentPage: (page: string) => void;
  openEstimate?: (isCorporate?: boolean, initialService?: string) => void;
}

const OFFERS = [
  {
    id: 1,
    title: "PREMIUM CERAMIC COATING",
    benefit: "9H Graphene protection with 5 years warranty",
    discount: "FLAT ₹5000 OFF",
    urgencyText: "ONLY 3 SLOTS LEFT",
    rating: 4.9,
    customers: "12,000+",
    image: "https://images.unsplash.com/photo-1625047509168-a7026f36de04?auto=format&fit=crop&q=80&w=800"
  },
  {
    id: 2,
    title: "MASTER FULL SERVICE",
    benefit: "Comprehensive 60-point check + synthetic oil",
    discount: "20% OFF",
    urgencyText: "ENDS TODAY",
    rating: 4.8,
    customers: "25,000+",
    image: "https://images.unsplash.com/photo-1632823462963-8a3c8e4e9a8f?auto=format&fit=crop&q=80&w=800"
  },
  {
    id: 3,
    title: "AC DEEP CLEANING",
    benefit: "Evaporator wash, gas top-up & odor removal",
    discount: "FLAT ₹1000 OFF",
    urgencyText: "HIGH DEMAND",
    rating: 4.9,
    customers: "8,500+",
    image: "https://images.unsplash.com/photo-1563720223185-11003d516935?auto=format&fit=crop&q=80&w=800"
  }
];

export default function Offers({ setCurrentPage }: OffersProps) {
  return (
    <>
      <PageBanner
        title="Offers & Deals"
        breadcrumbs={[
          { label: "Home", onClick: () => setCurrentPage("home") },
          { label: "Offers" }
        ]}
      />
      <div className="section-spacing pt-0">
        <div className="site-container">
          
          <div className="max-w-3xl mb-16">
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
            >
              <h2 className="text-4xl md:text-6xl font-black uppercase text-neutral-900 tracking-tighter mb-4 leading-none">
                LIMITED TIME <span className="text-primary">OFFERS.</span>
              </h2>
              <p className="text-lg md:text-xl text-neutral-500 leading-relaxed font-medium">
                Dealership-grade services at transparent prices. Limited slots available.
              </p>
            </motion.div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {OFFERS.map((offer, i) => (
              <motion.div
                key={offer.id}
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.5, delay: i * 0.1 }}
                className="group relative bg-white border border-border flex flex-col overflow-hidden hover:-translate-y-2 transition-all duration-300 shadow-sm hover:shadow-2xl hover:shadow-primary/10 hover:border-primary/30"
              >
                {/* Image & Overlays */}
                <div className="relative h-[250px] overflow-hidden bg-neutral-900">
                  <img 
                    src={offer.image} 
                    alt={offer.title} 
                    className="absolute inset-0 w-full h-full object-cover opacity-60 group-hover:scale-110 group-hover:opacity-70 transition-all duration-700"
                    referrerPolicy="no-referrer"
                  />
                  <div className="absolute inset-0 bg-gradient-to-t from-neutral-900 via-neutral-900/50 to-transparent" />
                  
                  {/* Urgency Tag */}
                  <div className="absolute top-4 left-4 z-10 flex items-center gap-1.5 bg-red-600/90 backdrop-blur-sm text-white px-3 py-1.5 text-[10px] font-black uppercase tracking-widest animate-pulse border border-red-500">
                    <AlertCircle className="w-3 h-3" />
                    {offer.urgencyText}
                  </div>

                  {/* Discount Text - Very Bold */}
                  <div className="absolute bottom-4 left-4 right-4 z-10">
                    <div className="inline-block bg-primary text-white px-4 py-1 mb-3 text-xs font-black uppercase tracking-widest shadow-md">
                      {offer.discount}
                    </div>
                    <h3 className="text-2xl md:text-3xl font-black uppercase tracking-tighter text-white leading-tight">
                      {offer.title}
                    </h3>
                  </div>
                </div>

                <div className="p-6 md:p-8 flex-grow flex flex-col justify-between">
                  <div>
                    <p className="text-neutral-600 font-medium mb-6 leading-relaxed">
                      {offer.benefit}
                    </p>

                    {/* Trust Element */}
                    <div className="flex items-center gap-4 text-xs font-bold text-neutral-500 uppercase tracking-widest mb-8 pb-6 border-b border-border">
                      <div className="flex items-center gap-1 text-yellow-500">
                        <Star className="w-4 h-4 fill-current" />
                        <span className="text-neutral-900">{offer.rating}</span>
                      </div>
                      <span className="w-1 h-1 rounded-full bg-border"></span>
                      <span>Trusted by {offer.customers}</span>
                    </div>
                  </div>

                  <a 
                    href="https://wa.me/911234567890?text=I'm%20interested%20in%20the%20offer!" 
                    target="_blank"
                    rel="noreferrer"
                    className="btn-ink btn-ink-primary w-full py-5 text-sm font-black uppercase tracking-widest flex items-center justify-center gap-2 group/btn"
                  >
                    CLAIM NOW <ArrowRight className="w-5 h-5 btn-arrow transition-transform duration-300 group-hover/btn:-rotate-45" />
                  </a>
                </div>
              </motion.div>
            ))}
          </div>

          {/* Bottom CTA */}
          <div className="mt-24 p-10 md:p-16 bg-neutral-900 text-center relative overflow-hidden">
            <div className="relative z-10">
              <h3 className="text-3xl md:text-4xl font-black text-white uppercase tracking-tighter mb-6">
                NEED A CUSTOM SOLUTION?
              </h3>
              <p className="text-neutral-400 mb-10 text-lg max-w-2xl mx-auto">
                Speak directly with our multi-brand technical experts. We'll diligently inspect your vehicle and configure the perfect service package with maximum savings.
              </p>
              <div className="flex flex-wrap items-center justify-center gap-4">
                <button className="btn-ink btn-ink-primary px-8 py-4 font-bold text-sm tracking-widest uppercase shadow-lg shadow-primary/20">
                  Call Now <ArrowRight className="w-4 h-4 btn-arrow" />
                </button>
                <a 
                  href="https://wa.me/911234567890" 
                  target="_blank" 
                  rel="noreferrer"
                  className="bg-[#25D366] text-white px-8 py-4 font-bold text-sm tracking-widest uppercase flex items-center gap-2 hover:bg-[#20bd5a] transition-colors"
                >
                  WhatsApp
                </a>
                <button className="btn-ink btn-ink-white px-8 py-4 font-bold text-sm tracking-widest uppercase">
                  Get Free Inspection
                </button>
              </div>
            </div>
            
            {/* Simple abstract bg */}
            <div className="absolute inset-0 opacity-10 flex items-center justify-center pointer-events-none">
              <div className="w-[800px] h-[800px] border-[40px] border-white rounded-full -translate-y-1/2"></div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
