import React, { useState } from "react";
import { motion } from "motion/react";
import PageBanner from "../components/PageBanner";
import FAQAccordion from "../components/FAQAccordion";
import { 
  CheckCircle2, ArrowRight, Phone, MessageCircle, HelpCircle, Star, 
  MapPin, Shield, Zap, Award, Wrench, ThumbsUp, Loader2
} from "lucide-react";

interface CmsPageProps {
  setCurrentPage: (page: string) => void;
  openEstimate?: (isCorporate?: boolean, initialService?: string) => void;
}

export default function CmsPage({ setCurrentPage }: CmsPageProps) {
  const [formData, setFormData] = useState({ name: "", phone: "", model: "" });
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  const validate = () => {
    const newErrors: Record<string, string> = {};
    if (!formData.name.trim() || !/^[A-Za-z\s]+$/.test(formData.name)) newErrors.name = "Alphabets only";
    if (!formData.phone.trim() || !/^\d{10}$/.test(formData.phone)) newErrors.phone = "10 digits required";
    if (!formData.model.trim()) newErrors.model = "Required";
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (validate()) {
      setIsSubmitting(true);
      setTimeout(() => {
        setIsSubmitting(false);
        setFormData({ name: "", phone: "", model: "" });
        alert("Estimate request sent! We will contact you shortly.");
      }, 1000);
    }
  };

  return (
    <div className="bg-neutral-50 min-h-screen">
      <PageBanner
        title="VOLVO CAR SERVICE IN DELHI"
        breadcrumbs={[
          { label: "Home", onClick: () => setCurrentPage("home") },
          { label: "Brands", onClick: () => setCurrentPage("services") },
          { label: "Volvo Service" }
        ]}
      />

      {/* Main Section */}
      <section className="section-spacing pt-0">
        <div className="site-container">
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16">
            
            {/* LEFT: Sticky Booking Form */}
            <div className="lg:col-span-4 order-2 lg:order-1">
              <div className="sticky top-24 bg-white border-2 border-border p-8 shadow-2xl shadow-primary/5">
                <h3 className="text-2xl font-black uppercase tracking-tighter text-neutral-900 mb-2">
                  BOOK FREE ESTIMATE
                </h3>
                <p className="text-sm font-medium text-neutral-500 mb-8">Get priority scheduling & upfront pricing.</p>
                
                <form className="space-y-4" onSubmit={handleSubmit}>
                  <div className="relative">
                    <label className="text-xs font-bold uppercase tracking-widest text-neutral-400 mb-2 flex justify-between">
                      <span>Your Name *</span>
                      {errors.name && <span className="text-red-500 normal-case">{errors.name}</span>}
                    </label>
                    <input 
                      type="text" 
                      value={formData.name}
                      onChange={e => {
                        const val = e.target.value;
                        if (val === "" || /^[A-Za-z\s]+$/.test(val)) {
                          setFormData({...formData, name: val});
                          setErrors(prev => ({...prev, name: ''}));
                        }
                      }}
                      className={`w-full bg-neutral-50 border ${errors.name ? 'border-red-500' : 'border-border'} px-4 py-3 text-neutral-900 focus:outline-none focus:border-primary transition-colors`} 
                      placeholder="John Doe" 
                    />
                  </div>
                  <div className="relative">
                    <label className="text-xs font-bold uppercase tracking-widest text-neutral-400 mb-2 flex justify-between">
                      <span>Phone Number *</span>
                      {errors.phone && <span className="text-red-500 normal-case">{errors.phone}</span>}
                    </label>
                    <input 
                      type="tel" 
                      maxLength={10}
                      value={formData.phone}
                      onChange={e => {
                        const val = e.target.value;
                        if (val === "" || /^[0-9]+$/.test(val)) {
                          setFormData({...formData, phone: val});
                          setErrors(prev => ({...prev, phone: ''}));
                        }
                      }}
                      className={`w-full bg-neutral-50 border ${errors.phone ? 'border-red-500' : 'border-border'} px-4 py-3 text-neutral-900 focus:outline-none focus:border-primary transition-colors`} 
                      placeholder="9876543210" 
                    />
                  </div>
                  <div className="relative">
                    <label className="text-xs font-bold uppercase tracking-widest text-neutral-400 mb-2 flex justify-between">
                      <span>Car Model *</span>
                      {errors.model && <span className="text-red-500 normal-case">{errors.model}</span>}
                    </label>
                    <input 
                      type="text" 
                      value={formData.model}
                      onChange={e => { setFormData({...formData, model: e.target.value}); setErrors(prev => ({...prev, model: ''})); }}
                      className={`w-full bg-neutral-50 border ${errors.model ? 'border-red-500' : 'border-border'} px-4 py-3 text-neutral-900 focus:outline-none focus:border-primary transition-colors`} 
                      placeholder="Volvo XC90" 
                    />
                  </div>
                  <button disabled={isSubmitting} type="submit" className="btn-ink btn-ink-primary w-full py-4 mt-4 font-black uppercase tracking-widest flex justify-center items-center gap-2 group disabled:opacity-70">
                    {isSubmitting ? (
                      <><Loader2 className="w-4 h-4 animate-spin" /> Processing...</>
                    ) : (
                      <>Get Estimate <ArrowRight className="w-4 h-4 btn-arrow" /></>
                    )}
                  </button>
                  <p className="text-[10px] text-center text-neutral-400 font-medium uppercase tracking-wider mt-4">We respect your privacy. No spam.</p>
                </form>
              </div>
            </div>

            {/* RIGHT: Content */}
            <div className="lg:col-span-8 order-1 lg:order-2">
              <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }}>
                <h1 className="text-4xl md:text-5xl lg:text-6xl font-black uppercase tracking-tighter text-neutral-900 mb-6 leading-none">
                  AUTHORIZED LEVEL <span className="text-primary">VOLVO CAR SERVICE</span> IN DELHI
                </h1>
                <p className="text-xl text-neutral-600 font-medium leading-relaxed mb-8">
                  Experience dealership-quality Volvo repair and maintenance without the premium markup. Our certified technicians use genuine OEM/OES parts and advanced diagnostic tools specifically designed for Swedish engineering.
                </p>

                {/* Trust Indicators */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-16 pb-12 border-b border-border">
                  <div className="flex flex-col items-center text-center p-4 bg-white border border-border">
                    <div className="flex text-yellow-500 mb-2">
                      <Star className="w-4 h-4 fill-current"/><Star className="w-4 h-4 fill-current"/><Star className="w-4 h-4 fill-current"/><Star className="w-4 h-4 fill-current"/><Star className="w-4 h-4 fill-current"/>
                    </div>
                    <span className="text-xs font-bold uppercase tracking-widest text-neutral-900">4.9/5 Rating</span>
                    <span className="text-[10px] text-neutral-500 font-medium pb-1 border-b-2 border-primary/20">on Google</span>
                  </div>
                  <div className="flex flex-col items-center text-center p-4 bg-white border border-border">
                     <Award className="w-6 h-6 text-primary mb-2" />
                    <span className="text-xs font-bold uppercase tracking-widest text-neutral-900">Certified</span>
                    <span className="text-[10px] text-neutral-500 font-medium pb-1 border-b-2 border-primary/20">European Experts</span>
                  </div>
                  <div className="flex flex-col items-center text-center p-4 bg-white border border-border">
                     <Wrench className="w-6 h-6 text-primary mb-2" />
                    <span className="text-xs font-bold uppercase tracking-widest text-neutral-900">100% Genuine</span>
                    <span className="text-[10px] text-neutral-500 font-medium pb-1 border-b-2 border-primary/20">OEM Spare Parts</span>
                  </div>
                  <div className="flex flex-col items-center text-center p-4 bg-white border border-border">
                     <Shield className="w-6 h-6 text-primary mb-2" />
                    <span className="text-xs font-bold uppercase tracking-widest text-neutral-900">1 Year</span>
                    <span className="text-[10px] text-neutral-500 font-medium pb-1 border-b-2 border-primary/20">Service Warranty</span>
                  </div>
                </div>

                {/* Why Choose Us */}
                <div className="mb-16">
                   <h2 className="text-3xl font-black uppercase text-neutral-900 mb-8 tracking-tighter">
                    WHY CHOOSE US FOR YOUR <span className="text-primary">VOLVO?</span>
                  </h2>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {[
                      { title: "Specialized Diagnostics", desc: "We use VIDA/DICE diagnostic software exclusively for accurate Volvo fault detection.", icon: Zap },
                      { title: "Transparent Pricing", desc: "Get upfront repair estimates before any work begins. No hidden costs or surprises.", icon: ThumbsUp },
                      { title: "Pick & Drop Available", desc: "Complimentary doorstep pickup and delivery across Delhi NCR for major services.", icon: MapPin },
                      { title: "Dealership Alternative", desc: "Same quality of service, parts, and expertise but up to 40% more affordable.", icon: Award },
                    ].map((feature, i) => (
                      <div key={i} className="flex items-start gap-4 p-6 bg-white border border-border hover:shadow-lg transition-shadow">
                        <div className="bg-neutral-100 p-3 rounded-full shrink-0">
                           <feature.icon className="w-6 h-6 text-primary" />
                        </div>
                        <div>
                          <h4 className="text-sm font-black uppercase tracking-widest text-neutral-900 mb-2">{feature.title}</h4>
                          <p className="text-sm text-neutral-500 font-medium">{feature.desc}</p>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>

              </motion.div>
            </div>
          </div>
        </div>
      </section>

      {/* Services Under This Category */}
      <section className="py-20 bg-white">
        <div className="site-container">
           <h2 className="text-3xl md:text-4xl text-center font-black uppercase text-neutral-900 mb-16 tracking-tighter">
              VOLVO REPAIR & <span className="text-primary">MAINTENANCE SERVICES</span>
           </h2>
           <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
             {[
               { title: "Periodic Maintenance", desc: "Complete 60-point check, synthetic oil change, filter replacements.", img: "https://images.unsplash.com/photo-1632823462963-8a3c8e4e9a8f?auto=format&fit=crop&q=80&w=600" },
               { title: "Engine & Transmission", desc: "Timing belt replacements, gearbox overhauls, clutch plate changes.", img: "https://images.unsplash.com/photo-1486262715619-670810a044e1?auto=format&fit=crop&q=80&w=600" },
               { title: "Brake & Suspension", desc: "Brake pad replacement, disc skimming, air suspension repairs.", img: "https://images.unsplash.com/photo-1548690312-e3b507d8c110?auto=format&fit=crop&q=80&w=600" }
             ].map((svc, i) => (
                <div key={i} className="group cursor-pointer">
                  <div className="overflow-hidden h-[200px] mb-6 border border-border bg-neutral-900">
                    <img src={svc.img} alt={svc.title} className="w-full h-full object-cover group-hover:scale-110 opacity-80 transition-all duration-700" referrerPolicy="no-referrer" />
                  </div>
                  <h4 className="text-lg font-black uppercase tracking-tighter text-neutral-900 mb-2 group-hover:text-primary transition-colors">{svc.title}</h4>
                  <p className="text-sm text-neutral-500 font-medium leading-relaxed mb-4">{svc.desc}</p>
                  <span className="text-xs font-bold uppercase tracking-widest text-primary flex items-center gap-1 group-hover:gap-2 transition-all">
                    View Details <ArrowRight className="w-3 h-3" />
                  </span>
                </div>
             ))}
           </div>
        </div>
      </section>

      {/* Process Section */}
      <section className="py-20 bg-neutral-50">
        <div className="site-container">
          <div className="text-center mb-16 max-w-2xl mx-auto">
            <h2 className="text-3xl font-black uppercase text-neutral-900 tracking-tighter mb-4">
              HOW WE REPAIR <span className="text-primary">YOUR VOLVO</span>
            </h2>
            <p className="text-neutral-500 font-medium">A standardized 4-step process ensuring total transparency and high-quality results.</p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
            {[
              { step: "1", title: "Inspection", desc: "Detailed 60-point check and VIDA diagnostics." },
              { step: "2", title: "Estimate", desc: "Transparent quote with only required repairs." },
              { step: "3", title: "Repair", desc: "Work performed by Volvo-trained specialists." },
              { step: "4", title: "Delivery", desc: "Washed, quality-checked and ready for the road." }
            ].map((p, i) => (
              <div key={i} className="bg-white border border-border p-8 relative overflow-hidden group hover:border-primary/30 transition-colors shadow-sm">
                <div className="text-6xl font-black text-neutral-100 absolute -top-4 -right-2 transition-transform group-hover:scale-110">
                  {p.step}
                </div>
                <div className="relative z-10">
                  <h3 className="text-xl font-black uppercase tracking-tight text-neutral-900 mb-4">{p.title}</h3>
                  <p className="text-neutral-500 font-medium leading-relaxed">{p.desc}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Pricing / Packages */}
      <section className="py-20 bg-neutral-900 text-white">
        <div className="site-container">
          <div className="text-center mb-16 max-w-3xl mx-auto">
             <h2 className="text-3xl md:text-5xl font-black uppercase text-white tracking-tighter mb-4">
                TRANSPARENT <span className="text-primary">SERVICE PACKAGES</span>
             </h2>
             <p className="text-neutral-400 text-lg">No hidden fees. Only pay for what your Volvo actually needs.</p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
             {[
               { name: "Basic Checkup", price: "Starting ₹2,999", features: ["Computer Scanning", "Fluid Top-ups", "Brake Inspection", "Interior Vacuum"], popular: false },
               { name: "Standard Service", price: "Starting ₹6,499", features: ["Synthetic Engine Oil", "Oil Filter Change", "Air Filter Cleaning", "Suspension Check", "Washing & Polishing"], popular: true },
               { name: "Comprehensive", price: "Starting ₹11,999", features: ["All Standard Service items", "AC Filter Replacement", "Fuel Filter Change", "Wheel Alignment & Balancing", "Deep Interior Cleaning"], popular: false }
             ].map((pkg, i) => (
                <div key={i} className={`p-8 border-2 ${pkg.popular ? 'border-primary bg-primary/5' : 'border-neutral-800 bg-neutral-950'} relative flex flex-col hover:-translate-y-2 transition-transform duration-300`}>
                  {pkg.popular && <div className="absolute top-0 right-0 bg-primary text-white text-[10px] font-black uppercase tracking-widest px-4 py-1">MOST POPULAR</div>}
                  <h4 className="text-xl font-black uppercase tracking-tighter text-white mb-2">{pkg.name}</h4>
                  <div className="text-3xl text-primary font-bold mb-8 pb-8 border-b border-neutral-800">{pkg.price}</div>
                  <ul className="space-y-4 mb-8 flex-grow">
                     {pkg.features.map((feat, j) => (
                        <li key={j} className="flex items-start gap-3">
                           <CheckCircle2 className="w-5 h-5 text-neutral-500 shrink-0" />
                           <span className="text-sm font-medium text-neutral-300">{feat}</span>
                        </li>
                     ))}
                  </ul>
                  <button className={`btn-ink py-4 text-xs font-black uppercase tracking-widest transition-colors w-full ${pkg.popular ? 'btn-ink-primary' : 'btn-ink-white'}`}>
                    Select Package
                  </button>
                </div>
             ))}
          </div>
        </div>
      </section>

      {/* Before / After Section */}
      <section className="py-20 bg-white border-b border-border">
        <div className="site-container">
           <div className="text-center mb-16">
             <h2 className="text-3xl font-black uppercase text-neutral-900 tracking-tighter">
                OUR WORK <span className="text-primary">SPEAKS.</span>
             </h2>
           </div>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-5xl mx-auto">
             <div className="relative h-[300px] md:h-[400px]">
              <img 
                src="https://images.unsplash.com/photo-1601362840469-51e4d8d58785?auto=format&fit=crop&q=80&w=800" 
                alt="Before repair" 
                className="w-full h-full object-cover"
                referrerPolicy="no-referrer"
              />
              <div className="absolute top-4 left-4 bg-black/80 px-4 py-1.5 text-white font-black text-[10px] tracking-widest uppercase">BEFORE REPAIR</div>
            </div>
            <div className="relative h-[300px] md:h-[400px]">
              <img 
                src="https://images.unsplash.com/photo-1601362840469-51e4d8d58785?auto=format&fit=crop&q=80&w=800" 
                alt="After repair" 
                className="w-full h-full object-cover brightness-125 contrast-125 saturate-150"
                referrerPolicy="no-referrer"
              />
              <div className="absolute top-4 left-4 bg-primary px-4 py-1.5 text-white font-black text-[10px] tracking-widest uppercase shadow-lg shadow-primary/20">AFTER REPAIR</div>
            </div>
          </div>
        </div>
      </section>

      {/* Testimonials (Google Style) */}
      <section className="py-20 bg-neutral-50 border-b border-border">
         <div className="site-container">
            <h2 className="text-3xl font-black uppercase text-neutral-900 tracking-tighter text-center mb-4">
              VERIFIED <span className="text-primary">CUSTOMER REVIEWS</span>
            </h2>
            <p className="text-center text-neutral-500 font-medium mb-16">Trusted by thousands of Volvo owners across Delhi NCR.</p>
            
            <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
               {[
                 { name: "Arun Sharma", date: "2 weeks ago", text: "Excellent experience getting my Volvo XC60 serviced here. The staff is highly knowledgeable and the pricing was exactly what they estimated. Saved almost 30% compared to the authorized dealer." },
                 { name: "Priya Desai", date: "1 month ago", text: "Took my S90 for an AC issue. They diagnosed it quickly using their software and replaced the faulty sensor in a day. Very professional setup." },
                 { name: "Rahul Verma", date: "3 months ago", text: "Got ceramic coating done on my new Volvo. The shine is incredible and the work was flawless. Their workshop is exceptionally clean and well-maintained." }
               ].map((review, i) => (
                  <div key={i} className="bg-white p-8 border border-border shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all">
                     <div className="flex items-center justify-between mb-4">
                        <div className="flex items-center gap-3">
                           <div className="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-black uppercase text-lg">
                             {review.name.charAt(0)}
                           </div>
                           <div>
                             <h5 className="font-bold text-neutral-900 text-sm">{review.name}</h5>
                             <span className="text-[10px] text-neutral-400 font-medium uppercase tracking-widest">{review.date}</span>
                           </div>
                        </div>
                        {/* fake google logo shape */}
                        <div className="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center text-white text-xs font-bold shadow-sm">G</div>
                     </div>
                     <div className="flex text-yellow-500 mb-4">
                        <Star className="w-4 h-4 fill-current"/><Star className="w-4 h-4 fill-current"/><Star className="w-4 h-4 fill-current"/><Star className="w-4 h-4 fill-current"/><Star className="w-4 h-4 fill-current"/>
                     </div>
                     <p className="text-sm font-medium text-neutral-600 leading-relaxed">"{review.text}"</p>
                  </div>
               ))}
            </div>
         </div>
      </section>

      {/* FAQ Section */}
      <section className="py-20 bg-white">
        <div className="site-container max-w-4xl mx-auto">
          <div className="text-center mb-16">
            <h2 className="text-3xl font-black uppercase text-neutral-900 tracking-tighter mb-4">
              COMMONLY ASKED <span className="text-primary">QUESTIONS</span>
            </h2>
          </div>
          
          <FAQAccordion
            faqs={[
              { q: "Is my Volvo warranty valid if serviced here?", a: "We use 100% Genuine OEM parts and manufacturer-approved synthetic oils, ensuring your factory warranty remains unaffected as per the 'Right to Repair' guidelines." },
              { q: "Do you use authentic Volvo diagnostic software?", a: "Yes, we exclusively use VIDA diagnostic systems specifically designed for comprehensive Volvo scanning, programming, and fault finding." },
              { q: "How much time does a routine service take?", a: "A standard periodic maintenance service takes around 4-5 hours. We recommend booking an appointment to ensure zero waiting time." },
              { q: "Do you offer pick up and drop facilities?", a: "Yes, we offer complimentary pick up and drop services across major locations in Delhi NCR for scheduled maintenance and major repairs." },
              { q: "Is a cashless insurance facility available?", a: "We have tie-ups with all major private and PSU insurance companies providing hassle-free, cashless claim processing." },
              { q: "Can I just drop by for a quick checkup?", a: "While we highly recommend booking in advance to minimize waiting, walk-ins for minor issues and quick diagnoses are always welcome." },
            ]}
          />
        </div>
      </section>

      {/* Location + Contact */}
      <section className="py-20 bg-neutral-50 border-t border-border">
         <div className="site-container">
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center bg-white border border-border p-8 md:p-12 shadow-2xl shadow-primary/5">
               <div>
                  <h2 className="text-3xl md:text-4xl font-black uppercase tracking-tighter text-neutral-900 mb-6">
                    VISIT OUR <span className="text-primary">PREMIUM WORKSHOP</span>
                  </h2>
                  <p className="text-lg text-neutral-600 font-medium mb-8">
                    Strategically located in South Delhi. Our facility spans 10,000 sq.ft and features ultra-modern equipment dedicated to European luxury cars.
                  </p>
                  <div className="space-y-8">
                     <div className="flex items-start gap-4 p-4 bg-neutral-50 border border-border">
                       <MapPin className="w-6 h-6 text-primary shrink-0 mt-1" />
                       <div>
                         <h4 className="font-bold text-neutral-900 uppercase tracking-widest text-sm mb-1">Address</h4>
                         <p className="text-neutral-500 font-medium">B-45, Okhla Industrial Area Phase 1,<br/>New Delhi, Delhi 110020</p>
                       </div>
                     </div>
                     <div className="flex items-start gap-4 p-4 bg-neutral-50 border border-border">
                       <Phone className="w-6 h-6 text-primary shrink-0 mt-1" />
                       <div>
                         <h4 className="font-bold text-neutral-900 uppercase tracking-widest text-sm mb-1">Expert Helpline</h4>
                         <a href="tel:+919876543210" className="text-xl font-black text-neutral-900 hover:text-primary transition-colors">+91 98765 43210</a>
                       </div>
                     </div>
                  </div>
               </div>
               <div className="bg-neutral-300 h-full min-h-[400px] w-full flex items-center justify-center relative overflow-hidden group">
                  {/* Map Placeholder */}
                  <img src="https://images.unsplash.com/photo-1524661135-423995f22d0b?auto=format&fit=crop&q=80&w=800" className="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" alt="Map View" referrerPolicy="no-referrer" />
                  <div className="absolute inset-0 bg-neutral-900/30 group-hover:bg-neutral-900/10 transition-colors" />
                  <button className="bg-white px-6 py-3 shadow-xl z-10 flex items-center gap-3 font-black text-xs tracking-widest uppercase hover:bg-neutral-900 hover:text-white transition-colors border border-border">
                    <MapPin className="w-4 h-4 text-primary" /> View on Google Maps
                  </button>
               </div>
            </div>
         </div>
      </section>

      {/* Final CTA Section */}
      <section className="bg-neutral-900 py-32 relative overflow-hidden">
        <div className="site-container relative z-10 text-center max-w-4xl mx-auto">
          <h2 className="text-4xl md:text-6xl font-black text-white uppercase tracking-tighter mb-8 leading-none">
            DO NOT COMPROMISE ON <br/><span className="text-primary">YOUR VOLVO'S CARE.</span>
          </h2>
          <p className="text-xl text-neutral-400 mb-12 font-medium">
             Schedule your service today and experience the gold standard in premium automotive care.
          </p>
          <div className="flex flex-wrap items-center justify-center gap-4">
            <button className="btn-ink btn-ink-primary px-10 py-5 font-black text-sm tracking-widest uppercase shadow-xl shadow-primary/20 flex items-center gap-2 group">
              Book Appointment Now <ArrowRight className="w-4 h-4 btn-arrow" />
            </button>
            <a 
              href="https://wa.me/911234567890" 
              target="_blank" 
              rel="noreferrer"
              className="bg-[#25D366] text-white px-10 py-5 font-black text-sm tracking-widest uppercase flex items-center gap-2 hover:bg-[#20bd5a] transition-all duration-300 shadow-xl shadow-[#25D366]/20 hover:-translate-y-1"
            >
              <MessageCircle className="w-5 h-5" /> WhatsApp Us
            </a>
          </div>
        </div>
        
        {/* Subtle Background Elements */}
        <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] border border-white/5 rounded-full pointer-events-none" />
        <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] border border-white/5 rounded-full pointer-events-none" />
        <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[1000px] h-[1000px] border border-white/5 rounded-full pointer-events-none" />
      </section>

    </div>
  );
}
