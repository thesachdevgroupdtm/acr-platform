import { useState, FormEvent } from "react";
import { motion } from "motion/react";
import { useNavigate } from "react-router-dom";
import { Phone, Mail, MapPin, Clock, MessageCircle, Send, ArrowRight, CheckCircle2 } from "lucide-react";
import { BUSINESS_INFO } from "../data/businessData";
import PageBanner from "../components/PageBanner";

interface ContactProps {
  openEstimate?: (isCorporate?: boolean, initialService?: string) => void;
}

export default function Contact(_props: ContactProps) {
  const navigate = useNavigate();
  const [formData, setFormData] = useState({
    name: "",
    phone: "",
    carInfo: "",
    service: "Accident Repair",
    message: "",
  });
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [submitted, setSubmitted] = useState(false);

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    const errs: Record<string, string> = {};
    if (!formData.name.trim()) errs.name = "Full name is required";
    else if (!/^[A-Za-z][A-Za-z\s.'-]*$/.test(formData.name.trim())) errs.name = "Only alphabets are allowed";
    if (!formData.phone) errs.phone = "Phone number is required";
    else if (!/^\d{10}$/.test(formData.phone)) errs.phone = "Enter exactly 10 digits";
    if (!formData.message.trim()) errs.message = "Please describe what you need";
    setErrors(errs);
    if (Object.keys(errs).length === 0) {
      setSubmitted(true);
      setTimeout(() => {
        setSubmitted(false);
        setFormData({ name: "", phone: "", carInfo: "", service: "Accident Repair", message: "" });
      }, 4000);
    }
  };
  return (
    <>
      <PageBanner
        title="Contact Us"
        breadcrumbs={[
          { label: "Home", onClick: () => navigate("/") },
          { label: "Contact" }
        ]}
      />
      <div className="section-spacing pt-0">
        <div className="site-container">

          <div className="grid grid-cols-1 lg:grid-cols-2 gap-12">
          {/* Contact Info */}
          <div className="space-y-8">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="bg-white p-6 border border-border shadow-sm">
                <Phone className="w-6 h-6 text-primary mb-4" />
                <h4 className="text-sm font-black uppercase mb-1 text-neutral-900">Call Us</h4>
                <p className="text-xs text-neutral-500">+91 {BUSINESS_INFO.phone}</p>
                <p className="text-xs text-neutral-500">Moti Nagar, Gurugram, Noida, Okhla</p>
              </div>
              <div className="bg-white p-6 border border-border shadow-sm">
                <Mail className="w-6 h-6 text-primary mb-4" />
                <h4 className="text-sm font-black uppercase mb-1 text-neutral-900">Email Us</h4>
                <p className="text-xs text-neutral-500">{BUSINESS_INFO.email}</p>
              </div>
              <div className="bg-white p-6 border border-border shadow-sm">
                <MapPin className="w-6 h-6 text-primary mb-4" />
                <h4 className="text-sm font-black uppercase mb-1 text-neutral-900">Visit Us</h4>
                <p className="text-xs text-neutral-500">Multiple Locations</p>
                <p className="text-xs text-neutral-500">Delhi NCR Region</p>
              </div>
              <div className="bg-white p-6 border border-border shadow-sm">
                <Clock className="w-6 h-6 text-primary mb-4" />
                <h4 className="text-sm font-black uppercase mb-1 text-neutral-900">Working Hours</h4>
                <p className="text-xs text-neutral-500">Mon - Sat: 9AM - 7PM</p>
                <p className="text-xs text-neutral-500">Sunday: Closed</p>
              </div>
            </div>

            <a 
              href={BUSINESS_INFO.whatsapp}
              target="_blank"
              rel="noopener noreferrer"
              className="bg-primary p-8 flex items-center justify-between group cursor-pointer shadow-xl"
            >
              <div>
                <h4 className="text-white text-xl font-black uppercase mb-1">WhatsApp Support</h4>
                <p className="text-white/70 text-xs font-bold">Fastest way to get an estimate</p>
              </div>
              <div className="bg-white p-3 group-hover:scale-110 transition-transform">
                <MessageCircle className="w-6 h-6 text-primary" />
              </div>
            </a>
          </div>

          {/* Contact Form */}
          <div className="bg-white border border-border p-8 md:p-10 shadow-xl">
            <h3 className="text-2xl font-black uppercase mb-8 text-neutral-900">Request an Estimate</h3>
            {submitted ? (
              <div className="bg-primary/5 border border-primary p-8 text-center space-y-3">
                <CheckCircle2 className="w-12 h-12 text-primary mx-auto" />
                <p className="text-base font-black uppercase text-primary-dark">Thank you {formData.name || "there"}!</p>
                <p className="text-sm text-neutral-500">Your request has been received. Our team will reach out within 15 minutes.</p>
              </div>
            ) : (
            <form className="space-y-4" onSubmit={handleSubmit} noValidate>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-1.5">
                  <label className="text-[8px] font-bold uppercase tracking-widest text-neutral-400">Full Name *</label>
                  <input
                    type="text"
                    value={formData.name}
                    onChange={e => {
                      const cleaned = e.target.value.replace(/[^A-Za-z\s.'-]/g, '');
                      setFormData({...formData, name: cleaned});
                      if (errors.name) setErrors(er => ({...er, name: ''}));
                    }}
                    className={`w-full bg-neutral-50 border ${errors.name ? 'border-accent-dark' : 'border-border'} px-4 py-3 text-sm focus:border-primary outline-none transition-colors text-neutral-900`}
                    placeholder="John Doe"
                  />
                  {errors.name && <p className="text-[10px] font-bold text-accent-dark mt-1">{errors.name}</p>}
                </div>
                <div className="space-y-1.5">
                  <label className="text-[8px] font-bold uppercase tracking-widest text-neutral-400">Phone Number *</label>
                  <input
                    type="tel"
                    inputMode="numeric"
                    maxLength={10}
                    value={formData.phone}
                    onChange={e => {
                      const v = e.target.value.replace(/\D/g, '').slice(0, 10);
                      setFormData({...formData, phone: v});
                      if (errors.phone) setErrors(er => ({...er, phone: ''}));
                    }}
                    className={`w-full bg-neutral-50 border ${errors.phone ? 'border-accent-dark' : 'border-border'} px-4 py-3 text-sm focus:border-primary outline-none transition-colors text-neutral-900`}
                    placeholder="10-digit number"
                  />
                  {errors.phone && <p className="text-[10px] font-bold text-accent-dark mt-1">{errors.phone}</p>}
                </div>
              </div>
              <div className="space-y-1.5">
                <label className="text-[8px] font-bold uppercase tracking-widest text-neutral-400">Car Model & Year</label>
                <input
                  type="text"
                  value={formData.carInfo}
                  onChange={e => setFormData({...formData, carInfo: e.target.value})}
                  className="w-full bg-neutral-50 border border-border px-4 py-3 text-sm focus:border-primary outline-none transition-colors text-neutral-900"
                  placeholder="BMW 3 Series (2022)"
                />
              </div>
              <div className="space-y-1.5">
                <label className="text-[8px] font-bold uppercase tracking-widest text-neutral-400">Service Required</label>
                <select
                  value={formData.service}
                  onChange={e => setFormData({...formData, service: e.target.value})}
                  className="w-full bg-neutral-50 border border-border px-4 py-3 text-sm focus:border-primary outline-none transition-colors appearance-none text-neutral-900"
                >
                  <option>Accident Repair</option>
                  <option>Denting & Painting</option>
                  <option>Insurance Claim</option>
                  <option>Ceramic Coating</option>
                  <option>Other</option>
                </select>
              </div>
              <div className="space-y-1.5">
                <label className="text-[8px] font-bold uppercase tracking-widest text-neutral-400">Message *</label>
                <textarea
                  value={formData.message}
                  onChange={e => {
                    setFormData({...formData, message: e.target.value});
                    if (errors.message) setErrors(er => ({...er, message: ''}));
                  }}
                  className={`w-full bg-neutral-50 border ${errors.message ? 'border-accent-dark' : 'border-border'} px-4 py-3 text-sm focus:border-primary outline-none transition-colors min-h-[120px] text-neutral-900`}
                  placeholder="Describe the damage or service needed..."
                />
                {errors.message && <p className="text-[10px] font-bold text-accent-dark mt-1">{errors.message}</p>}
              </div>
              <button type="submit" className="btn-ink btn-ink-primary w-full py-4 text-[10px] font-bold uppercase tracking-widest flex items-center justify-center gap-2">
                Send Request <ArrowRight className="w-4 h-4 btn-arrow" />
              </button>
            </form>
            )}
          </div>
        </div>

        {/* Map Placeholder */}
        <div className="mt-24 h-[400px] bg-neutral-50 border border-border relative overflow-hidden flex items-center justify-center">
          <div className="relative z-10 text-center">
            <MapPin className="w-10 h-10 text-primary mx-auto mb-4" />
            <h3 className="text-2xl font-black uppercase mb-2 text-neutral-900">Find Us on the Map</h3>
            <p className="text-sm text-neutral-500 mb-6">Interactive map loading...</p>
            <button className="btn-ink btn-ink-outline px-6 py-2.5 text-[10px] font-bold uppercase tracking-widest flex items-center justify-center gap-2">
              Open in Google Maps <ArrowRight className="w-4 h-4 btn-arrow" />
            </button>
          </div>
        </div>
        </div>
      </div>
    </>
  );
}
