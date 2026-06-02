import { useState } from "react";
import type * as React from "react";
import { motion, AnimatePresence } from "motion/react";
import { useNavigate, useParams } from "react-router-dom";
import { MapPin, Phone, Star, Clock, Shield, CheckCircle2, MessageCircle, Send, Camera, Info, ChevronDown } from "lucide-react";
import { useServiceCenters } from "../hooks/useServiceCenters";
import PageBanner from "../components/PageBanner";

interface ServiceCenterDetailProps {
  openEstimate?: (isCorporate?: boolean, initialService?: string) => void;
}

export default function ServiceCenterDetail(_props: ServiceCenterDetailProps) {
  const navigate = useNavigate();
  // /center/:id route — :id is the slug (was LOCATIONS[].id, now service_centers.slug).
  const { id: centerId = "" } = useParams<{ id: string }>();
  const { centers, isLoading } = useServiceCenters();
  const [openServiceIdx, setOpenServiceIdx] = useState<number | null>(null);
  const [openAmenityIdx, setOpenAmenityIdx] = useState<number | null>(null);

  // Booking form state with validation
  const [bookingData, setBookingData] = useState({
    name: "",
    phone: "",
    service: "Accident Repair",
  });
  const [bookingErrors, setBookingErrors] = useState<Record<string, string>>({});
  const [bookingSubmitted, setBookingSubmitted] = useState(false);

  const handleBookingSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const errs: Record<string, string> = {};
    if (!bookingData.name.trim()) errs.name = "Full name is required";
    else if (!/^[A-Za-z][A-Za-z\s.'-]*$/.test(bookingData.name.trim())) errs.name = "Only alphabets are allowed";
    if (!bookingData.phone) errs.phone = "Phone number is required";
    else if (!/^\d{10}$/.test(bookingData.phone)) errs.phone = "Enter exactly 10 digits";
    setBookingErrors(errs);
    if (Object.keys(errs).length === 0) {
      setBookingSubmitted(true);
      // Reset after 3 seconds
      setTimeout(() => {
        setBookingSubmitted(false);
        setBookingData({ name: "", phone: "", service: "Accident Repair" });
      }, 3000);
    }
  };

  const center = centers.find((c) => c.slug === centerId) ?? centers[0];

  if (isLoading || !center) {
    return (
      <>
        <PageBanner
          title="Loading…"
          breadcrumbs={[
            { label: "Home", href: "/" },
            { label: "Centers", href: "/service-centers" },
          ]}
        />
        <div className="site-container py-16">
          <div className="h-6 w-48 bg-neutral-200 animate-pulse" />
          <div className="mt-3 h-4 w-96 bg-neutral-100 animate-pulse" />
        </div>
      </>
    );
  }

  const stats = [
    { label: "Workshop Size", value: "15,000+ Sq Ft" },
    { label: "Paint Booths", value: "Premium Booths" },
    { label: "Technicians", value: "Certified Experts" },
    { label: "Monthly Repairs", value: "250+ Cars" }
  ];

  const servicesData = [
    { 
      title: "Accident Repair & Claims", 
      desc: "Seamless handling of insurance claims, cashless repair options, and complete accidental damage restoration." 
    },
    { 
      title: "Denting & Painting", 
      desc: "Precision dent removal and eco-friendly paint-matching, restoring your car's factory-fresh finish." 
    },
    { 
      title: "Mechanical Restoration", 
      desc: "Comprehensive engine and chassis repairs done by expert technicians using premium diagnostic tools." 
    },
    { 
      title: "Ceramic Coating & PPF", 
      desc: "High-quality paint protection films and 10H glass coatings to shield against scratches and UV damage." 
    },
    { 
      title: "Chassis Alignment", 
      desc: "Laser-guided wheel and chassis alignment ensuring a stable, safe, and balanced driving experience." 
    },
    { 
      title: "AC & Electrical Work", 
      desc: "Advanced checks and repair of air conditioning, wiring, sensors, and overall electrical systems." 
    }
  ];

  const amenitiesData = [
    { 
      title: "Premium Lounge", 
      desc: "Relax in our climate-controlled customer lounge while you wait, complete with comfortable seating." 
    },
    { 
      title: "Wi-Fi & Coffee", 
      desc: "Enjoy complimentary high-speed internet access and premium beverages during your visit." 
    },
    { 
      title: "CCTV Monitoring", 
      desc: "24/7 high-definition security camera surveillance ensuring the total safety of your vehicle." 
    },
    { 
      title: "Valet Pickup/Drop", 
      desc: "Convenient doorstep vehicle pickup and timely delivery after the service is completed." 
    }
  ];

  return (
    <>
      <PageBanner
        title={center.name}
        breadcrumbs={[
          { label: "Home", href: "/" },
          { label: "Centers", href: "/service-centers" },
          { label: center.name }
        ]}
      />

      <div className="pb-32 pt-10">
        <div className="site-container">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-12">
          {/* Main Content */}
          <div className="lg:col-span-2 space-y-16">
            {/* Stats Grid */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
              {stats.map((stat, i) => (
                <div key={i} className="bg-neutral-50 p-5 border border-border text-center">
                  <p className="text-[8px] font-bold uppercase tracking-widest text-neutral-400 mb-1.5">{stat.label}</p>
                  <p className="text-lg font-black text-neutral-900">{stat.value}</p>
                </div>
              ))}
            </div>

            {/* Services & Amenities */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-10">
              <div className="space-y-6">
                <h2 className="section-heading flex items-center gap-3">
                  <Shield className="w-6 h-6 text-primary" />
                  SERVICES <span className="section-heading-accent">OFFERED.</span>
                </h2>
                <div className="space-y-3">
                  {servicesData.map((service, i) => {
                    const isOpen = openServiceIdx === i;
                    return (
                      <div key={i} className={`bg-white border border-border shadow-sm transition-colors ${isOpen ? 'border-primary/50' : ''}`}>
                        <button 
                          className="w-full flex items-center justify-between p-3"
                          onClick={() => setOpenServiceIdx(isOpen ? null : i)}
                        >
                          <div className="flex items-center gap-3 text-left">
                            <CheckCircle2 className={`w-4 h-4 shrink-0 transition-colors ${isOpen ? 'text-primary' : 'text-neutral-400'}`} />
                            <span className={`font-bold text-xs ${isOpen ? 'text-primary' : 'text-neutral-700'}`}>{service.title}</span>
                          </div>
                          <motion.div
                            animate={{ rotate: isOpen ? 180 : 0 }}
                            transition={{ duration: 0.2 }}
                          >
                            <ChevronDown className="w-4 h-4 text-neutral-400" />
                          </motion.div>
                        </button>
                        <AnimatePresence>
                          {isOpen && (
                            <motion.div
                              initial={{ height: 0, opacity: 0 }}
                              animate={{ height: "auto", opacity: 1 }}
                              exit={{ height: 0, opacity: 0 }}
                              transition={{ duration: 0.2 }}
                              className="overflow-hidden"
                            >
                              <div className="pl-10 pr-3 pb-3 pt-0 text-[11px] text-muted leading-relaxed">
                                {service.desc}
                              </div>
                            </motion.div>
                          )}
                        </AnimatePresence>
                      </div>
                    );
                  })}
                </div>
              </div>
              <div className="space-y-6">
                <h2 className="section-heading flex items-center gap-3">
                  <Info className="w-6 h-6 text-primary" />
                  <span className="section-heading-accent">AMENITIES.</span>
                </h2>
                <div className="space-y-3">
                  {amenitiesData.map((amenity, i) => {
                    const isOpen = openAmenityIdx === i;
                    return (
                      <div key={i} className={`bg-white border border-border shadow-sm transition-colors ${isOpen ? 'border-primary/50' : ''}`}>
                        <button 
                          className="w-full flex items-center justify-between p-3"
                          onClick={() => setOpenAmenityIdx(isOpen ? null : i)}
                        >
                          <div className="flex items-center gap-3 text-left">
                            <CheckCircle2 className={`w-4 h-4 shrink-0 transition-colors ${isOpen ? 'text-primary' : 'text-neutral-400'}`} />
                            <span className={`font-bold text-xs ${isOpen ? 'text-primary' : 'text-neutral-700'}`}>{amenity.title}</span>
                          </div>
                          <motion.div
                            animate={{ rotate: isOpen ? 180 : 0 }}
                            transition={{ duration: 0.2 }}
                          >
                            <ChevronDown className="w-4 h-4 text-neutral-400" />
                          </motion.div>
                        </button>
                        <AnimatePresence>
                          {isOpen && (
                            <motion.div
                              initial={{ height: 0, opacity: 0 }}
                              animate={{ height: "auto", opacity: 1 }}
                              exit={{ height: 0, opacity: 0 }}
                              transition={{ duration: 0.2 }}
                              className="overflow-hidden"
                            >
                              <div className="pl-10 pr-3 pb-3 pt-0 text-[11px] text-muted leading-relaxed">
                                {amenity.desc}
                              </div>
                            </motion.div>
                          )}
                        </AnimatePresence>
                      </div>
                    );
                  })}
                </div>
              </div>
            </div>

            {/* Map Placeholder */}
            <div className="space-y-6">
              <h2 className="section-heading">
                LOCATION <span className="section-heading-accent">MAP.</span>
              </h2>
              <div className="h-[300px] bg-neutral-50 border border-border flex items-center justify-center relative overflow-hidden">
                <div className="relative z-10 text-center">
                  <MapPin className="w-10 h-10 text-primary mx-auto mb-3" />
                  <p className="text-neutral-500 text-sm font-bold mb-4">Interactive Map Loading...</p>
                  <button className="bg-white border border-primary text-primary px-6 py-2.5 text-[10px] font-bold uppercase tracking-widest hover:bg-primary hover:text-white transition-all">
                    Open in Google Maps
                  </button>
                </div>
              </div>
            </div>
          </div>

          {/* Sidebar - Booking Form */}
          <div className="lg:col-span-1">
            <div className="sticky top-28 bg-white border border-border p-8 shadow-xl">
              <h2 className="section-heading mb-6">
                BOOK A <span className="section-heading-accent">VISIT.</span>
              </h2>
              {bookingSubmitted ? (
                <div className="bg-primary/5 border border-primary p-6 text-center space-y-2">
                  <CheckCircle2 className="w-10 h-10 text-primary mx-auto" />
                  <p className="text-sm font-bold text-primary-dark">Thank you {bookingData.name || "there"}!</p>
                  <p className="text-xs text-neutral-500">Your visit request has been received. We'll call you shortly.</p>
                </div>
              ) : (
              <form className="space-y-4" onSubmit={handleBookingSubmit} noValidate>
                <div className="space-y-1.5">
                  <label className="text-[8px] font-bold uppercase tracking-widest text-neutral-400">Full Name *</label>
                  <input
                    type="text"
                    value={bookingData.name}
                    onChange={e => {
                      const cleaned = e.target.value.replace(/[^A-Za-z\s.'-]/g, '');
                      setBookingData({...bookingData, name: cleaned});
                      if (bookingErrors.name) setBookingErrors(er => ({...er, name: ''}));
                    }}
                    className={`w-full bg-neutral-50 border ${bookingErrors.name ? 'border-accent-dark' : 'border-border'} p-3 text-sm focus:border-primary outline-none transition-colors`}
                    placeholder="John Doe"
                  />
                  {bookingErrors.name && <p className="text-[10px] font-bold text-accent-dark mt-1">{bookingErrors.name}</p>}
                </div>
                <div className="space-y-1.5">
                  <label className="text-[8px] font-bold uppercase tracking-widest text-neutral-400">Phone Number *</label>
                  <input
                    type="tel"
                    inputMode="numeric"
                    maxLength={10}
                    value={bookingData.phone}
                    onChange={e => {
                      const v = e.target.value.replace(/\D/g, '').slice(0, 10);
                      setBookingData({...bookingData, phone: v});
                      if (bookingErrors.phone) setBookingErrors(er => ({...er, phone: ''}));
                    }}
                    className={`w-full bg-neutral-50 border ${bookingErrors.phone ? 'border-accent-dark' : 'border-border'} p-3 text-sm focus:border-primary outline-none transition-colors`}
                    placeholder="10-digit number"
                  />
                  {bookingErrors.phone && <p className="text-[10px] font-bold text-accent-dark mt-1">{bookingErrors.phone}</p>}
                </div>
                <div className="space-y-1.5">
                  <label className="text-[8px] font-bold uppercase tracking-widest text-neutral-400">Service Required</label>
                  <select
                    value={bookingData.service}
                    onChange={e => setBookingData({...bookingData, service: e.target.value})}
                    className="w-full bg-neutral-50 border border-border p-3 text-sm focus:border-primary outline-none transition-colors appearance-none"
                  >
                    <option>Accident Repair</option>
                    <option>Denting & Painting</option>
                    <option>Ceramic Coating</option>
                    <option>Mechanical Service</option>
                  </select>
                </div>
                <button type="submit" className="w-full bg-primary text-white py-4 text-[10px] font-bold uppercase tracking-widest flex items-center justify-center gap-2 hover:bg-primary-dark transition-colors">
                  Confirm Booking <Send className="w-4 h-4" />
                </button>
              </form>
              )}

              <div className="mt-8 pt-8 border-t border-border space-y-4">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-neutral-50 border border-border flex items-center justify-center shrink-0">
                    <Clock className="w-5 h-5 text-primary" />
                  </div>
                  <div>
                    <p className="text-[10px] font-bold uppercase text-neutral-900">Working Hours</p>
                    <p className="text-xs text-neutral-500">Mon - Sat: 9AM - 7PM</p>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-neutral-50 border border-border flex items-center justify-center shrink-0">
                    <MessageCircle className="w-5 h-5 text-primary" />
                  </div>
                  <div>
                    <p className="text-[10px] font-bold uppercase text-neutral-900">WhatsApp Support</p>
                    <p className="text-xs text-neutral-500">Instant response within 10 mins</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        </div>
      </div>
    </>
  );
}
