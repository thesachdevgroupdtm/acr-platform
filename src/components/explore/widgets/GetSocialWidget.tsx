import { Facebook, Instagram, Linkedin, Twitter, Youtube, ExternalLink } from "lucide-react";
import { BUSINESS_INFO } from "../../../data/businessData";

/**
 * Phase 4.5.1 — sidebar "Connect with us".
 *
 * Reuses the same social-link data source as the Footer
 * (`BUSINESS_INFO.social`) per spec D-4.5.1-6 — DO NOT
 * hardcode the URLs again here. Hidden links (empty URLs in
 * BUSINESS_INFO) are filtered out.
 */
const PLATFORMS: Array<{
  key: keyof typeof BUSINESS_INFO.social;
  label: string;
  Icon: typeof Facebook;
}> = [
  { key: "facebook",  label: "Facebook",  Icon: Facebook },
  { key: "instagram", label: "Instagram", Icon: Instagram },
  { key: "youtube",   label: "YouTube",   Icon: Youtube },
  { key: "linkedin",  label: "LinkedIn",  Icon: Linkedin },
  { key: "twitter",   label: "Twitter",   Icon: Twitter },
];

export default function GetSocialWidget() {
  const links = PLATFORMS.filter((p) => !!BUSINESS_INFO.social?.[p.key]);
  if (links.length === 0) return null;

  return (
    <aside
      data-testid="get-social-widget"
      className="bg-white border border-border p-5"
    >
      <h3 className="text-sm font-black uppercase tracking-tighter text-neutral-900 mb-3">
        Connect with us
      </h3>
      <ul className="space-y-2">
        {links.map((p) => {
          const url = BUSINESS_INFO.social[p.key]!;
          return (
            <li key={p.key}>
              <a
                href={url}
                target="_blank"
                rel="noopener noreferrer"
                data-testid={`social-${p.key}`}
                className="group flex items-center justify-between text-xs text-neutral-700 hover:text-primary transition-colors py-1"
              >
                <span className="inline-flex items-center gap-2">
                  <p.Icon className="w-4 h-4 text-primary" /> {p.label}
                </span>
                <ExternalLink className="w-3 h-3 text-neutral-300 group-hover:text-primary transition-colors" />
              </a>
            </li>
          );
        })}
      </ul>
    </aside>
  );
}
