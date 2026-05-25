import { useNavigate } from "react-router-dom";
import { Calendar, Clock, User } from "lucide-react";

interface Props {
  publishedAt?: string | null;
  body?: string;
  tags?: string[];
}

/**
 * Phase 4.5b-polish — author/date/read-time + clickable tags.
 *
 * Reading time = max(1 min, body word count / 200 words/min).
 * "By ACR Editorial Team" — single static byline until Phase
 * 4.5d adds the AuthorResource.
 *
 * Tags are clickable chips that route to /explore?search={tag}
 * so a reader who liked one article can see siblings tagged the
 * same way.
 */
export default function SeoPageMeta({ publishedAt, body, tags }: Props) {
  const navigate = useNavigate();

  const formattedDate = publishedAt
    ? new Date(publishedAt).toLocaleDateString("en-IN", {
        day: "numeric",
        month: "short",
        year: "numeric",
      })
    : null;

  const readMinutes = (() => {
    if (!body) return null;
    const text = body.replace(/<[^>]+>/g, " ");
    const words = text.trim().split(/\s+/).length;
    return Math.max(1, Math.round(words / 200));
  })();

  return (
    <div
      data-testid="seo-page-meta"
      className="flex flex-wrap items-center gap-x-6 gap-y-2 text-[10px] font-bold uppercase tracking-widest text-neutral-500 mt-6 mb-10 pb-6 border-b border-border"
    >
      <span className="inline-flex items-center gap-1.5">
        <User className="w-3 h-3 text-primary" /> ACR Editorial Team
      </span>

      {formattedDate && (
        <span className="inline-flex items-center gap-1.5">
          <Calendar className="w-3 h-3 text-primary" /> {formattedDate}
        </span>
      )}

      {readMinutes !== null && (
        <span className="inline-flex items-center gap-1.5">
          <Clock className="w-3 h-3 text-primary" /> {readMinutes} min read
        </span>
      )}

      {tags && tags.length > 0 && (
        <div className="flex flex-wrap items-center gap-1.5 ml-auto">
          {tags.slice(0, 5).map((tag) => (
            <button
              key={tag}
              type="button"
              onClick={() => navigate(`/explore?search=${encodeURIComponent(tag)}`)}
              data-testid={`article-tag-${tag}`}
              className="text-[10px] uppercase tracking-widest font-bold bg-neutral-100 text-neutral-600 hover:bg-primary hover:text-white px-2 py-1 transition-colors"
            >
              #{tag}
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
