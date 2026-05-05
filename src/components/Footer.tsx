import { useState, useEffect } from "react";
import { Car, Instagram, Facebook, Twitter, Mail, Phone, MapPin, Youtube, Linkedin, ChevronLeft, ChevronRight } from "lucide-react";
import { BUSINESS_INFO, LOCATIONS } from "../data/businessData";

interface FooterProps {
  /** Phase 2.6a-fix demo-readiness — wire Quick Links to real
   *  page navigation. Optional so any caller that doesn't pass
   *  a navigator falls back to the (silent) anchor behavior. */
  setCurrentPage?: (page: string) => void;
}

// Quick Links → currentPage key. Same vocabulary App.tsx uses.
const QUICK_LINKS: Array<{ label: string; page: string }> = [
  { label: "Home",     page: "home" },
  { label: "Services", page: "services" },
  { label: "Insurance", page: "insurance" },
  { label: "Gallery",  page: "gallery" },
  { label: "About",    page: "about" },
  { label: "Contact",  page: "contact" },
  { label: "Sitemap",  page: "sitemap" },
];

const USEFUL_LINKS: Array<{ label: string; page: string }> = [
  { label: "Service Centers",     page: "service-centers" },
  { label: "Offers & Discounts",  page: "offers" },
  { label: "Corporate Tie-ups",   page: "corporate" },
  { label: "Coupons",             page: "coupons" },
  { label: "Contact Us",          page: "contact" },
];

export default function Footer({ setCurrentPage }: FooterProps) {
  const [currentLocationIdx, setCurrentLocationIdx] = useState(0);

  const navigate = (page: string) => {
    if (setCurrentPage) {
      setCurrentPage(page);
      window.scrollTo({ top: 0, behavior: "instant" as ScrollBehavior });
    }
  };

  useEffect(() => {
    const interval = setInterval(() => {
      setCurrentLocationIdx((prev) => (prev + 1) % LOCATIONS.length);
    }, 4000);
    return () => clearInterval(interval);
  }, []);

  const nextLocation = () => setCurrentLocationIdx((prev) => (prev + 1) % LOCATIONS.length);
  const prevLocation = () => setCurrentLocationIdx((prev) => (prev - 1 + LOCATIONS.length) % LOCATIONS.length);

  const socialLinks = [
    { Icon: Facebook, url: BUSINESS_INFO.social.facebook },
    { Icon: Twitter, url: BUSINESS_INFO.social.twitter },
    { Icon: Instagram, url: BUSINESS_INFO.social.instagram },
    { Icon: Linkedin, url: BUSINESS_INFO.social.linkedin },
    { Icon: Youtube, url: BUSINESS_INFO.social.youtube },
  ];

  const loc = LOCATIONS[currentLocationIdx];

  return (
    <>
      {/* SEO & Useful Info Section Above Footer */}
      <div className="bg-white py-12 border-t border-border">
        <div className="site-container">
          <div className="max-w-4xl">
             <h3 className="text-xl font-black uppercase text-primary-dark mb-4">India's Fastest-Growing Self-Owned Multi-Brand Network</h3>
             <p className="text-[13px] text-muted leading-relaxed mb-4">
               Auto Car Repair (ACR) is your ultimate destination for comprehensive car maintenance and premium repair. 
               Whether you need general servicing, expert denting and painting, advanced diagnostics, or cashless 
               insurance claims, our factory-trained experts use state-of-the-art tools to deliver pristine 
               quality. By strictly maintaining a 100% self-owned network with no outsourced locations, we guarantee absolute consistency.
             </p>
             <p className="text-[13px] text-muted leading-relaxed">
               Serving the entire Delhi NCR region with state-of-the-art service centers, we work with all major car brands and top insurance providers to offer 
               a hassle-free service. Trust us to maintain your vehicle's factory standards. Focus on the drive; leave the maintenance to the certified professionals at ACR.
             </p>
          </div>
        </div>
      </div>

      <footer className="bg-neutral-50 border-t border-border pt-16 pb-10">
        <div className="site-container">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-8 lg:gap-8 mb-16 px-4 lg:px-0">
          {/* Brand */}
          <div className="lg:col-span-3 space-y-5 lg:pr-6">
            <div className="flex flex-col items-start group hover:opacity-80 transition-opacity cursor-pointer" onClick={() => window.scrollTo(0,0)}>
              <div className="flex items-baseline gap-1">
                <span className="text-3xl font-black tracking-tighter text-primary leading-none mt-[-4px]">ACR</span>
                <span className="text-[7px] font-bold text-neutral-900 uppercase tracking-tighter">TM</span>
              </div>
              <div className="h-px w-full bg-neutral-900 mt-0.5" />
              <span className="text-[7px] font-black text-neutral-900 uppercase tracking-tighter mt-0.5">
                All Cars. One Repair Stop
              </span>
            </div>
            <p className="text-[13px] text-muted leading-relaxed">
              {BUSINESS_INFO.tagline}
            </p>
            <div className="flex gap-2.5 pt-2">
              {socialLinks.map((social, i) => (
                <a key={i} href={social.url} target="_blank" rel="noopener noreferrer" className="w-10 h-10 bg-white flex items-center justify-center hover:bg-primary hover:text-white transition-colors border border-border">
                  <social.Icon className="w-[18px] h-[18px]" />
                </a>
              ))}
            </div>
          </div>

          {/* Quick Links */}
          <div className="lg:col-span-2">
            <h4 className="text-[15px] font-black uppercase tracking-widest mb-6 text-neutral-900">Quick Links</h4>
            <ul className="space-y-3.5">
              {QUICK_LINKS.map((item) => (
                <li key={item.label}>
                  <button
                    onClick={() => navigate(item.page)}
                    className="text-[13px] font-medium text-muted hover:text-primary transition-colors block text-left"
                  >
                    {item.label}
                  </button>
                </li>
              ))}
            </ul>
          </div>

          {/* Useful Links */}
          <div className="lg:col-span-2">
            <h4 className="text-[15px] font-black uppercase tracking-widest mb-6 text-neutral-900">Useful Links</h4>
            <ul className="space-y-3.5">
              {USEFUL_LINKS.map((item) => (
                <li key={item.label}>
                  <button
                    onClick={() => navigate(item.page)}
                    className="text-[13px] font-medium text-muted hover:text-primary transition-colors block text-left"
                  >
                    {item.label}
                  </button>
                </li>
              ))}
            </ul>
          </div>

          {/* Services */}
          <div className="lg:col-span-2">
            <h4 className="text-[15px] font-black uppercase tracking-widest mb-6 text-neutral-900">Services</h4>
            <ul className="space-y-3.5">
              {["Regular Car Service", "Denting & Painting", "AC Service & Repair", "Insurance Claim", "Ceramic Coating", "Emergency Services"].map((item) => (
                <li key={item} className="text-[13px] font-medium text-muted">
                  {item}
                </li>
              ))}
            </ul>
          </div>

          {/* Contact Info (Dynamic) */}
          <div className="lg:col-span-3 lg:pl-2">
            <div className="flex flex-col gap-3 mb-6">
              <h4 className="text-[15px] font-black uppercase tracking-widest text-neutral-900 whitespace-nowrap">
                Contact Us
              </h4>
              <div className="flex items-center gap-1">
                <button onClick={prevLocation} className="p-1 border border-border hover:bg-white transition-colors">
                  <ChevronLeft className="w-3.5 h-3.5 text-neutral-600" />
                </button>
                <div className="text-[10px] font-bold tracking-widest text-neutral-500 w-10 text-center">
                  {currentLocationIdx + 1} / {LOCATIONS.length}
                </div>
                <button onClick={nextLocation} className="p-1 border border-border hover:bg-white transition-colors">
                  <ChevronRight className="w-3.5 h-3.5 text-neutral-600" />
                </button>
              </div>
            </div>
            
            <div className="mb-4 text-[11px] font-bold uppercase tracking-widest text-primary">
              {loc.name} Center
            </div>

            <ul className="space-y-4">
              <li className="flex items-start gap-3.5">
                <MapPin className="w-4 h-4 text-primary shrink-0 mt-0.5" />
                <a href={loc.url} target="_blank" rel="noopener noreferrer" className="text-muted hover:text-primary transition-colors text-[13px] leading-relaxed">
                  {loc.address}
                </a>
              </li>
              <li className="flex items-center gap-3.5">
                <Phone className="w-4 h-4 text-primary shrink-0" />
                <a href={`tel:+91${loc.phone}`} className="text-muted hover:text-primary transition-colors text-[13px] font-medium">
                  +91 {loc.phone}
                </a>
              </li>
              <li className="flex items-center gap-3.5">
                <Mail className="w-4 h-4 text-primary shrink-0" />
                <a href={`mailto:${BUSINESS_INFO.email}`} className="text-muted hover:text-primary transition-colors text-[13px] font-medium whitespace-nowrap">
                  {BUSINESS_INFO.email}
                </a>
              </li>
            </ul>
          </div>
        </div>

        <div className="pt-8 border-t border-border flex flex-col md:flex-row justify-between items-center gap-4 px-4 lg:px-0">
          <p className="text-[13px] font-medium text-muted">© 2026 Auto Car Repair. All rights reserved.</p>
          {/* Demo-readiness — Privacy / Terms pages aren't built yet,
              so these stay as plain non-interactive labels until the
              CMS routes for them land. Keeps the visual rhythm of
              the footer's right-side cluster without offering a
              dead link to click. */}
          <div className="flex items-center gap-8 text-[13px] font-medium text-muted">
            <span>Privacy Policy</span>
            <span>Terms of Service</span>
          </div>
        </div>
      </div>
    </footer>
    </>
  );
}
