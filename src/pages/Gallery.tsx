import { motion } from "motion/react";
import PageBanner from "../components/PageBanner";

interface GalleryProps {
  setCurrentPage?: (page: string) => void;
  openEstimate?: (isCorporate?: boolean, initialService?: string) => void;
}

export default function Gallery({ setCurrentPage }: GalleryProps) {
  const images = [
    {
      url: "https://images.unsplash.com/photo-1625047509168-a7026f36de04?auto=format&fit=crop&q=80&w=800",
      title: "Full Body Restoration",
      category: "Accident Repair",
      span: "md:col-span-2 md:row-span-2"
    },
    {
      url: "https://images.unsplash.com/photo-1517524206127-48bbd363f3d7?auto=format&fit=crop&q=80&w=800",
      title: "Precision Painting",
      category: "Denting & Painting",
      span: "md:col-span-1 md:row-span-1"
    },
    {
      url: "https://images.unsplash.com/photo-1590674899484-d5640e854abe?auto=format&fit=crop&q=80&w=800",
      title: "Chassis Alignment",
      category: "Structural",
      span: "md:col-span-1 md:row-span-1"
    },
    {
      url: "https://images.unsplash.com/photo-1530046339160-ce3e5b0c7a2f?auto=format&fit=crop&q=80&w=800",
      title: "Detailing Finish",
      category: "Polishing",
      span: "md:col-span-1 md:row-span-2"
    },
    {
      url: "https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?auto=format&fit=crop&q=80&w=800",
      title: "Workshop Interior",
      category: "Facility",
      span: "md:col-span-2 md:row-span-1"
    }
  ];

  return (
    <>
      <PageBanner
        title="Gallery"
        breadcrumbs={[
          { label: "Home", onClick: () => setCurrentPage?.("home") },
          { label: "Gallery" }
        ]}
      />
      <div className="section-spacing pt-0">
        <div className="site-container">

          <div className="grid grid-cols-1 md:grid-cols-3 gap-4 auto-rows-[250px]">
          {images.map((img, i) => (
            <motion.div
              key={i}
              initial={{ opacity: 0, scale: 0.98 }}
              whileInView={{ opacity: 1, scale: 1 }}
              transition={{ delay: i * 0.1 }}
              viewport={{ once: true }}
              className={`relative overflow-hidden group border border-border shadow-sm hover:shadow-xl ${img.span}`}
            >
              <img 
                src={img.url} 
                alt={img.title} 
                className="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 grayscale hover:grayscale-0"
                referrerPolicy="no-referrer"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-neutral-900/80 via-transparent to-transparent opacity-40 group-hover:opacity-80 transition-opacity" />
              <div className="absolute bottom-0 p-6 w-full transform translate-y-2 group-hover:translate-y-0 transition-transform">
                <span className="inline-block bg-primary text-white text-[8px] font-bold uppercase tracking-widest px-2 py-0.5 mb-2">
                  {img.category}
                </span>
                <h3 className="text-lg font-black uppercase tracking-tighter text-white">{img.title}</h3>
              </div>
            </motion.div>
          ))}
        </div>

        {/* Before/After Teaser */}
        <div className="mt-24 grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
          <div className="space-y-6">
            <h2 className="text-3xl md:text-4xl uppercase font-black leading-tight text-neutral-900">THE <br /><span className="text-primary">TRANSFORMATION.</span></h2>
            <p className="text-neutral-500 text-lg leading-relaxed">
              We specialize in complex color matching and structural repairs that leave 
              no trace of previous damage. Our work is indistinguishable from the factory finish.
            </p>
            <button className="bg-primary text-white px-8 py-3.5 font-bold uppercase tracking-widest text-xs hover:bg-primary-dark transition-colors">
              View Case Studies
            </button>
          </div>
          <div className="relative aspect-video overflow-hidden border border-border shadow-xl">
            <div className="absolute inset-0 flex">
              <div className="w-1/2 relative overflow-hidden">
                <img 
                  src="https://images.unsplash.com/photo-1590674899484-d5640e854abe?auto=format&fit=crop&q=80&w=800" 
                  alt="Before" 
                  className="absolute inset-0 w-full h-full object-cover grayscale"
                  referrerPolicy="no-referrer"
                />
                <div className="absolute top-3 left-3 bg-white/90 backdrop-blur px-2 py-0.5 text-[8px] font-bold uppercase text-neutral-900">Before</div>
              </div>
              <div className="w-1/2 relative overflow-hidden border-l border-primary">
                <img 
                  src="https://images.unsplash.com/photo-1590674899484-d5640e854abe?auto=format&fit=crop&q=80&w=800" 
                  alt="After" 
                  className="absolute inset-0 w-full h-full object-cover"
                  referrerPolicy="no-referrer"
                />
                <div className="absolute top-3 right-3 bg-primary px-2 py-0.5 text-[8px] font-bold uppercase text-white">After</div>
              </div>
            </div>
          </div>
        </div>
        </div>
      </div>
    </>
  );
}
