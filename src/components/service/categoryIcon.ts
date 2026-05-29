import type * as React from "react";
import {
  BatteryCharging,
  Snowflake,
  Disc3,
  Paintbrush,
  Sparkles,
  Cog,
  Lightbulb,
  Wrench,
  ClipboardCheck,
  AlertCircle,
  Shield,
} from "lucide-react";

/**
 * Phase 2c — shared fallback icon per service category. Service/category
 * images are 0% populated in acr_v3, so the ServiceCard fallback tile leans
 * on these glyphs. Extracted from ServiceCategory.tsx so Layer 1 (/services
 * tabs) and Layer 2 (/category/:slug) resolve the SAME icon.
 */
const CATEGORY_ICON: Record<string, React.ComponentType<{ className?: string }>> = {
  "car-battery": BatteryCharging,
  "car-ac-service-repair": Snowflake,
  "car-brake-wheel-maintenance": Disc3,
  "car-denting-painting": Paintbrush,
  "car-care-detailing": Sparkles,
  "car-clutch-work": Cog,
  "car-suspension-work": Cog,
  "car-lights-and-glass-work": Lightbulb,
  "car-repairs-inspection": Wrench,
  "car-inspection": ClipboardCheck,
  "car-emergency-services": AlertCircle,
  "car-insurance-claim": Shield,
  "regular-car-service": Wrench,
};

export const categoryIcon = (
  slug: string
): React.ComponentType<{ className?: string }> => CATEGORY_ICON[slug] ?? Wrench;
