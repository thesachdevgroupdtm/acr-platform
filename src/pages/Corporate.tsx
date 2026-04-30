import { motion } from "motion/react";
import { Shield, Users, BarChart, Clock, CheckCircle2, MessageSquare, ArrowRight } from "lucide-react";
import PageBanner from "../components/PageBanner";

interface CorporateProps {
  setCurrentPage?: (page: string) => void;
  openEstimate?: () => void;
}

export default function Corporate({ setCurrentPage, openEstimate }: CorporateProps) {
  const benefits = [
    {
      title: "Dedicated Manager",
      desc: "A single point of contact for all your fleet's maintenance and repair needs.",
      icon: Users
    },
    {
      title: "Priority Service",
      desc: "Minimal downtime for your vehicles with our 'First-In, First-Out' fleet priority.",
      icon: Clock
    },
    {
      title: "Bulk Pricing",
      desc: "Customized rate cards and volume-based discounts for corporate partners.",
      icon: BarChart
    },
    {
      title: "Insurance Support",
      desc: "Direct tie-ups with all major insurance companies for cashless fleet repairs.",
      icon: Shield
    }
  ];

  return (
    <>
      <PageBanner
        title="Corporate & Fleet"
        breadcrumbs={[
          { label: "Home", onClick: () => setCurrentPage?.("home") },
          { label: "Corporate" }
        ]}
      />
      <div className="section-spacing pt-0">
        <div className="site-container">
          {/* Hero Section */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center mb-16">
          <motion.div
            initial={{ opacity: 0, x: -50 }}
            animate={{ opacity: 1, x: 0 }}
          >
            <h1 className="text-4xl md:text-5xl mb-6 text-neutral-900">ENTERPRISE <br /><span className="text-primary">FLEET.</span></h1>
            <p className="text-lg text-neutral-500 leading-relaxed mb-8">
              Dealership-grade maintenance and high-precision collision repair exclusively tailored for corporate fleets, large-scale car rentals, and government organizations.
            </p>
            <div className="flex flex-wrap gap-4">
              <button onClick={() => openEstimate?.()} className="btn-ink btn-ink-primary px-8 py-3.5 font-bold uppercase tracking-widest text-xs">
                Request a Quote <ArrowRight className="w-4 h-4 btn-arrow" />
              </button>
            </div>
          </motion.div>
          <div className="relative">
            <img 
              src="https://images.unsplash.com/photo-1517524206127-48bbd363f3d7?auto=format&fit=crop&q=80&w=1000" 
              alt="Fleet Management" 
              className="border border-border relative z-10 shadow-xl grayscale hover:grayscale-0 transition-all duration-700"
              referrerPolicy="no-referrer"
            />
          </div>
        </div>

        {/* Benefits Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-16">
          {benefits.map((benefit, i) => (
            <motion.div
              key={i}
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              transition={{ delay: i * 0.1 }}
              viewport={{ once: true }}
              className="bg-white p-8 border border-border hover:border-primary transition-colors group shadow-sm hover:shadow-xl"
            >
              <benefit.icon className="w-10 h-10 text-primary mb-6 group-hover:scale-110 transition-transform" />
              <h3 className="text-lg font-black uppercase mb-3 text-neutral-900">{benefit.title}</h3>
              <p className="text-xs text-neutral-500 leading-relaxed">{benefit.desc}</p>
            </motion.div>
          ))}
        </div>

        {/* Process Section */}
        <div className="bg-neutral-50 p-8 md:p-12 border border-border mb-16">
          <div className="max-w-3xl mx-auto text-center mb-12">
            <h2 className="text-3xl md:text-4xl mb-6 uppercase font-black text-neutral-900">THE ENTERPRISE <span className="text-primary">MODEL.</span></h2>
            <p className="text-lg text-neutral-500">We don't just fix cars; we architect your fleet's lifecycle to guarantee maximum operational uptime.</p>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {[
              { step: "01", title: "Audit", desc: "Our specialists execute a forensic-level audit of your fleet's condition and maintenance history." },
              { step: "02", title: "Contract", desc: "A custom-engineered SLA and pricing model scaled precisely to your operational volume." },
              { step: "03", title: "Execution", desc: "Flawless end-to-end service, from secure pickup to pristine delivery, transparently tracked." }
            ].map((item, i) => (
              <div key={i} className="relative p-6 bg-white border border-border">
                <span className="text-4xl font-black text-neutral-100 mb-4 block">{item.step}</span>
                <h4 className="text-lg font-black uppercase mb-3 text-neutral-900">{item.title}</h4>
                <p className="text-xs text-neutral-500 leading-relaxed">{item.desc}</p>
              </div>
            ))}
          </div>
        </div>

        {/* CTA Section */}
        <div className="bg-white border border-primary p-8 md:p-12 flex flex-col md:flex-row items-center justify-between gap-10">
          <div className="max-w-xl">
            <h2 className="text-neutral-900 text-3xl md:text-4xl font-black uppercase mb-4">READY TO ELEVATE YOUR FLEET?</h2>
            <p className="text-neutral-500 text-lg">Join 50+ enterprise partners who implicitly trust ACR for their fleet operations.</p>
          </div>
          <button onClick={() => openEstimate?.()} className="btn-ink btn-ink-primary px-10 py-5 font-bold uppercase tracking-widest text-sm flex items-center gap-3 whitespace-nowrap">
            Get Corporate Quote <MessageSquare className="w-5 h-5 btn-arrow" />
          </button>
        </div>
        </div>
      </div>
    </>
  );
}
