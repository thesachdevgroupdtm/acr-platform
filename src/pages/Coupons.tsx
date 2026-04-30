import { useState } from "react";
import { motion } from "motion/react";
import PageBanner from "../components/PageBanner";
import { Copy, CheckCircle2, Ticket, Clock } from "lucide-react";

interface CouponsProps {
  setCurrentPage: (page: string) => void;
  openEstimate?: (isCorporate?: boolean, initialService?: string) => void;
}

const COUPONS = [
  {
    id: 1,
    code: "FIRST10",
    description: "Get 10% off on your first regular car service",
    validity: "Valid till end of month",
    urgency: "New Customers Only"
  },
  {
    id: 2,
    code: "ACCOOL20",
    description: "Flat ₹500 off on complete AC servicing & gas top-up",
    validity: "Only for today",
    urgency: "High Demand"
  },
  {
    id: 3,
    code: "CERAMICPRO",
    description: "Free interior deep cleaning with Ceramic Coating",
    validity: "Limited Use",
    urgency: "Only 10 uses left"
  }
];

export default function Coupons({ setCurrentPage }: CouponsProps) {
  const [copiedId, setCopiedId] = useState<number | null>(null);

  const handleCopy = (id: number, code: string) => {
    navigator.clipboard.writeText(code);
    setCopiedId(id);
    setTimeout(() => {
      setCopiedId(null);
    }, 2000);
  };

  return (
    <>
      <PageBanner
        title="Get Exclusive Coupons"
        breadcrumbs={[
          { label: "Home", onClick: () => setCurrentPage("home") },
          { label: "Coupons" }
        ]}
      />
      
      <div className="section-spacing pt-0">
        <div className="site-container">
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {COUPONS.map((coupon, i) => (
              <motion.div
                key={coupon.id}
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.5, delay: i * 0.1 }}
                className="bg-white border-2 border-border border-dashed p-8 relative flex flex-col items-center text-center group hover:border-primary/50 transition-colors"
              >
                {/* Decoration */}
                <div className="absolute top-1/2 -translate-y-1/2 -left-4 w-8 h-8 rounded-full bg-neutral-100 hidden md:block" />
                <div className="absolute top-1/2 -translate-y-1/2 -right-4 w-8 h-8 rounded-full bg-neutral-100 hidden md:block" />

                <div className="inline-flex items-center gap-2 bg-primary/10 text-primary px-3 py-1 text-[10px] font-black uppercase tracking-widest mb-6">
                  <Ticket className="w-3 h-3" /> {coupon.urgency}
                </div>

                <div className="text-4xl font-black tracking-tighter text-neutral-900 mb-4 select-all">
                  {coupon.code}
                </div>
                
                <p className="text-neutral-500 font-medium leading-relaxed mb-6 flex-grow">
                  {coupon.description}
                </p>

                <div className="flex items-center justify-center gap-2 text-xs font-bold text-neutral-400 uppercase tracking-widest mb-8 border-b border-border w-full py-4">
                  <Clock className="w-3.5 h-3.5" /> {coupon.validity}
                </div>

                <button 
                  onClick={() => handleCopy(coupon.id, coupon.code)}
                  className={`w-full py-4 text-xs font-black uppercase tracking-widest flex items-center justify-center gap-2 transition-all duration-300 ${
                    copiedId === coupon.id 
                    ? 'bg-green-600 text-white' 
                    : 'bg-neutral-900 text-white hover:bg-primary'
                  }`}
                >
                  {copiedId === coupon.id ? (
                    <>COPIED! <CheckCircle2 className="w-4 h-4" /></>
                  ) : (
                    <>COPY & APPLY <Copy className="w-4 h-4" /></>
                  )}
                </button>
              </motion.div>
            ))}
          </div>

        </div>
      </div>
    </>
  );
}
