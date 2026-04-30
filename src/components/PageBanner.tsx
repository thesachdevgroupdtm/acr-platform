import { motion } from "motion/react";
import React, { ReactNode } from "react";

interface BreadcrumbItem {
  label: string;
  onClick?: () => void;
}

interface PageBannerProps {
  title: string;
  breadcrumbs: BreadcrumbItem[];
  label?: string;
  backgroundImage?: string;
  children?: ReactNode;
}

export default function PageBanner({
  title,
  breadcrumbs,
  label,
  backgroundImage = "https://images.unsplash.com/photo-1625047509168-a7026f36de04?auto=format&fit=crop&q=80&w=1200",
  children
}: PageBannerProps) {
  return (
    <div className="relative h-[40vh] min-h-[300px] flex items-center overflow-hidden mb-12">
      <img 
        src={backgroundImage} 
        className="absolute inset-0 w-full h-full object-cover opacity-30"
        alt={title}
        referrerPolicy="no-referrer"
      />
      <div className="absolute inset-0 bg-neutral-900/80" />
      <div className="absolute inset-0 bg-gradient-to-r from-primary/20 via-transparent to-transparent" />
      
      <div className="site-container relative z-10 w-full">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="max-w-4xl pt-10"
        >
          {/* Breadcrumb */}
          <div className="text-[10px] font-bold uppercase tracking-widest text-white/50 mb-6 flex flex-wrap items-center gap-2">
            {breadcrumbs.map((crumb, index) => (
              <React.Fragment key={index}>
                {crumb.onClick ? (
                  <span 
                    className="cursor-pointer hover:text-white transition-colors" 
                    onClick={crumb.onClick}
                  >
                    {crumb.label}
                  </span>
                ) : (
                  <span className="text-white">{crumb.label}</span>
                )}
                {index < breadcrumbs.length - 1 && (
                  <span className="text-white/30">/</span>
                )}
              </React.Fragment>
            ))}
          </div>

          {/* Label */}
          {label && (
            <span className="text-primary font-black uppercase tracking-[0.3em] mb-4 block text-xs">
              {label}
            </span>
          )}

          {/* Title */}
          <h1 className="text-4xl md:text-5xl lg:text-6xl text-white font-black leading-tight uppercase tracking-tighter shadow-sm">
            {title}
          </h1>

          {/* Optional Meta Info */}
          {children && (
            <div className="mt-6">
              {children}
            </div>
          )}
        </motion.div>
      </div>
    </div>
  );
}
