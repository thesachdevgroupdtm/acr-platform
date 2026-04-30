import { motion } from "motion/react";
import { FileCheck, ShieldAlert, BadgeCheck, HelpCircle, ArrowRight } from "lucide-react";
import PageBanner from "../components/PageBanner";

interface InsuranceProps {
  setCurrentPage?: (page: string) => void;
  openEstimate?: (isCorporate?: boolean, initialService?: string) => void;
}

export default function Insurance({ setCurrentPage }: InsuranceProps) {
  return (
    <>
      <PageBanner
        title="Insurance Claims"
        breadcrumbs={[
          { label: "Home", onClick: () => setCurrentPage?.("home") },
          { label: "Insurance" }
        ]}
      />
      <div className="section-spacing pt-0">
        <div className="site-container">
          {/* Hero */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center mb-16">
          <motion.div
            initial={{ opacity: 0, x: -50 }}
            animate={{ opacity: 1, x: 0 }}
          >
            <h1 className="text-4xl md:text-5xl mb-6 text-neutral-900">ABSOLUTE <br /><span className="text-primary">EASE.</span></h1>
            <p className="text-lg text-neutral-500 leading-relaxed mb-8">
              Post-collision insurance processing shouldn't compound your stress. As a premium multi-brand network, we operate as a direct cashless facility with every major Indian insurer. We manage the entire bureaucratic process—from surveyor inspection to final approval—so you don't have to.
            </p>
            <div className="flex flex-col gap-3">
              {[
                "Direct Cashless Facility",
                "On-site Surveyor Inspection",
                "End-to-end Documentation Support",
                "Genuine Parts Guarantee"
              ].map((item, i) => (
                <div key={i} className="flex items-center gap-3">
                  <div className="w-5 h-5 bg-neutral-50 border border-primary/20 flex items-center justify-center shrink-0">
                    <BadgeCheck className="w-3.5 h-3.5 text-primary" />
                  </div>
                  <span className="text-sm font-bold uppercase tracking-tighter text-neutral-900">{item}</span>
                </div>
              ))}
            </div>
          </motion.div>

          <div className="relative">
            <div className="relative bg-white p-8 border border-border shadow-xl">
              <ShieldAlert className="w-10 h-10 text-primary mb-6" />
              <h3 className="text-2xl mb-4 uppercase font-black text-neutral-900">Partner Insurers</h3>
              <p className="text-sm text-neutral-500 mb-6">We work with all leading insurance providers in India to provide seamless cashless repairs at our centres.</p>
              <div className="grid grid-cols-2 gap-3">
                {["HDFC ERGO", "ICICI Lombard", "Bajaj Allianz", "Tata AIG", "New India", "United India"].map((name) => (
                  <div key={name} className="bg-neutral-50 p-2.5 border border-border text-center font-bold uppercase tracking-widest text-[10px] text-neutral-900">
                    {name}
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>

        {/* Claim Process */}
        <div className="bg-neutral-50 p-8 md:p-12 border border-border">
          <h2 className="text-3xl md:text-4xl mb-12 text-neutral-900 uppercase font-black">THE CASHLESS <span className="text-primary">PROCESS.</span></h2>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {[
              {
                title: "Immediate Response",
                desc: "Call us instantly post-accident. We deploy expert towing to our nearest facility and assist in filing the FIR and initial claims.",
                icon: HelpCircle
              },
              {
                title: "Survey & Clearance",
                desc: "Our internal engineers relentlessly coordinate with the insurer's surveyor for an exact, zero-compromise repair estimate.",
                icon: FileCheck
              },
              {
                title: "Factory Restoration",
                desc: "Upon clearance, we execute the flawless restoration with genuine parts. You simply pay the standard policy deductible.",
                icon: ArrowRight
              }
            ].map((step, i) => (
              <div key={i} className="space-y-4">
                <div className="w-12 h-12 bg-white border border-border flex items-center justify-center">
                  <step.icon className="w-6 h-6 text-primary" />
                </div>
                <h4 className="text-lg font-black uppercase text-neutral-900">{step.title}</h4>
                <p className="text-xs text-neutral-500 leading-relaxed">{step.desc}</p>
              </div>
            ))}
          </div>
        </div>

        {/* FAQ Preview */}
        <div className="mt-32 max-w-3xl mx-auto text-center">
          <h2 className="text-3xl mb-8 uppercase font-black text-neutral-900">Have Questions?</h2>
          <p className="text-muted mb-10">Our insurance experts are available to guide you through the complexities of your specific policy.</p>
          <button className="bg-primary text-white px-10 py-4 font-black uppercase tracking-tighter hover:bg-primary-dark transition-colors">
            Talk to Claim Expert
          </button>
        </div>
        </div>
      </div>
    </>
  );
}
