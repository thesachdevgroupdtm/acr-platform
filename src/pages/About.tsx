import { motion } from "motion/react";
import { Users, History, Target, Heart, Shield } from "lucide-react";
import { BUSINESS_INFO } from "../data/businessData";
import PageBanner from "../components/PageBanner";

interface AboutProps {
  setCurrentPage?: (page: string) => void;
  openEstimate?: (isCorporate?: boolean, initialService?: string) => void;
}

export default function About({ setCurrentPage }: AboutProps) {
  return (
    <>
      <PageBanner
        title="About Us"
        breadcrumbs={[
          { label: "Home", onClick: () => setCurrentPage?.("home") },
          { label: "About" }
        ]}
      />
      <div className="section-spacing pt-0">
        <div className="site-container">
          {/* Story Section */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center mb-16">
          <motion.div
            initial={{ opacity: 0, x: -50 }}
            animate={{ opacity: 1, x: 0 }}
          >
            <h1 className="text-4xl md:text-5xl mb-6 text-neutral-900">OUR <span className="text-primary">STORY.</span></h1>
            <p className="text-lg text-neutral-500 leading-relaxed mb-6">
              {BUSINESS_INFO.about}
            </p>
            <div className="grid grid-cols-2 gap-6">
              {[
                { label: "Founded", value: "2011" },
                { label: "Vehicles Restored", value: "25k+" },
                { label: "Expert Technicians", value: "45+" },
                { label: "Workshop Size", value: "15k sqft" },
              ].map((stat, i) => (
                <div key={i} className="border-l border-primary/30 pl-4">
                  <div className="text-xl font-black uppercase tracking-tighter text-neutral-900">{stat.value}</div>
                  <div className="text-[10px] text-neutral-400 uppercase tracking-widest font-bold">{stat.label}</div>
                </div>
              ))}
            </div>
          </motion.div>

          <div className="relative">
            <img 
              src="https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?auto=format&fit=crop&q=80&w=1000" 
              alt="Our Workshop" 
              className="border border-border relative z-10 shadow-xl grayscale hover:grayscale-0 transition-all duration-700"
              referrerPolicy="no-referrer"
            />
          </div>
        </div>

        {/* Values Section */}
        <div className="bg-neutral-50 p-8 md:p-12 border border-border mb-16">
          <h2 className="text-3xl md:text-4xl mb-12 text-center uppercase font-black text-neutral-900">CORE <span className="text-primary">VALUES.</span></h2>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            {[
              { title: "Precision", desc: "We measure success in millimeters. Every structural repair is executed to factory-exact dimensions.", icon: Target },
              { title: "Zero Outsourcing", desc: "Total control. Every vehicle remains within our self-owned network to guarantee sheer quality.", icon: Shield },
              { title: "Perfection", desc: "Uncompromising standards. That passion reflects in the flawless, mirror-like finish of our paintwork.", icon: Heart },
              { title: "Expertise", desc: "Multi-brand specialists. Our technicians undergo rigorous, continuous training across all luxury and premium makes.", icon: Users },
            ].map((value, i) => (
              <div key={i} className="text-center space-y-4">
                <div className="w-12 h-12 bg-white border border-border flex items-center justify-center mx-auto">
                  <value.icon className="w-6 h-6 text-primary" />
                </div>
                <h4 className="text-lg font-black uppercase text-neutral-900">{value.title}</h4>
                <p className="text-xs text-neutral-500 leading-relaxed">{value.desc}</p>
              </div>
            ))}
          </div>
        </div>

        {/* Team Section */}
        <div className="text-center max-w-4xl mx-auto">
          <h2 className="text-3xl md:text-4xl mb-8 uppercase font-black text-neutral-900">MEET THE <span className="text-primary">EXPERTS.</span></h2>
          <p className="text-lg text-neutral-500 leading-relaxed mb-12">
            Our team consists of certified structural engineers, master painters, 
            and insurance specialists dedicated to your vehicle's perfection.
          </p>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {[
              { name: "Rajesh Kumar", role: "Master Painter", img: "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=400" },
              { name: "Amit Singh", role: "Structural Engineer", img: "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&q=80&w=400" },
              { name: "Sanjay Verma", role: "Claim Specialist", img: "https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=400" },
            ].map((member, i) => (
              <div key={i} className="group">
                <div className="relative aspect-[3/4] overflow-hidden mb-4 border border-border grayscale hover:grayscale-0 transition-all duration-500">
                  <img 
                    src={member.img} 
                    alt={member.name} 
                    className="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                    referrerPolicy="no-referrer"
                  />
                </div>
                <h4 className="text-lg font-black uppercase text-neutral-900">{member.name}</h4>
                <p className="text-primary font-bold uppercase tracking-widest text-[10px]">{member.role}</p>
              </div>
            ))}
          </div>
        </div>
        </div>
      </div>
    </>
  );
}
