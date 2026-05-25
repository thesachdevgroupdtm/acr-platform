import type * as React from "react";
import { useMemo, useState } from "react";
import { CheckCircle2, Loader2 } from "lucide-react";
import { useBrands, useModels, useServices, type ServiceRef } from "../../../hooks/explore/useLookups";
import { useLeadSubmit, type LeadPayload } from "../../../hooks/explore/useLeadSubmit";

/**
 * Phase 4.5.3 — replaces NewsletterWidget.
 *
 * Sidebar lead-capture form. 6 fields:
 *   Name (required), Email, Phone (required), Brand, Model, Service.
 * Brand → Model is a cascading dependency (Model is disabled until
 * a Brand is picked).
 *
 * Submit posts to /api/v1/leads. 422 errors render inline next to
 * the failing field. Success replaces the form with a thank-you
 * card; auto-resets after 5s.
 */

const inputClass =
  "w-full bg-white border border-border px-3 py-2 text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/30 transition-colors disabled:bg-neutral-100 disabled:cursor-not-allowed";

const labelClass =
  "block text-[10px] font-bold uppercase tracking-widest text-neutral-500 mb-1.5";

export default function LeadFormWidget() {
  const [name,      setName]      = useState("");
  const [email,     setEmail]     = useState("");
  const [phone,     setPhone]     = useState("");
  const [brandId,   setBrandId]   = useState<number | "">("");
  const [modelId,   setModelId]   = useState<number | "">("");
  const [serviceId, setServiceId] = useState<number | "">("");

  const brandsQuery   = useBrands();
  const modelsQuery   = useModels(typeof brandId === "number" ? brandId : null);
  const servicesQuery = useServices();

  const { submit, state, errors, generalError, reset } = useLeadSubmit();

  // Group services by category for <optgroup>.
  const servicesByCategory = useMemo(() => {
    const map = new Map<string, { name: string; rows: ServiceRef[] }>();
    for (const s of servicesQuery.data ?? []) {
      const key = s.category?.slug ?? "_uncategorized";
      const label = s.category?.name ?? "Other";
      if (!map.has(key)) map.set(key, { name: label, rows: [] });
      map.get(key)!.rows.push(s);
    }
    return Array.from(map.values()).sort((a, b) => a.name.localeCompare(b.name));
  }, [servicesQuery.data]);

  const onBrandChange = (val: string) => {
    const next = val === "" ? "" : Number(val);
    setBrandId(next);
    setModelId("");
  };

  const handleSubmit = (e?: React.MouseEvent | React.FormEvent) => {
    e?.preventDefault();
    if (state === "submitting") return;
    const payload: LeadPayload = {
      name: name.trim(),
      phone: phone.trim(),
    };
    if (email.trim() !== "")          payload.email      = email.trim();
    if (typeof brandId === "number")  payload.brand_id   = brandId;
    if (typeof modelId === "number")  payload.model_id   = modelId;
    if (typeof serviceId === "number")payload.service_id = serviceId;
    void submit(payload);
  };

  // Reset form fields after success auto-resets the hook to idle.
  if (state === "success") {
    return (
      <aside
        data-testid="lead-form-widget-success"
        className="bg-white border border-border p-5"
      >
        <div className="flex flex-col items-center text-center py-2">
          <CheckCircle2 className="w-10 h-10 text-primary mb-2" />
          <h3 className="text-sm font-black uppercase tracking-tighter text-neutral-900 mb-1">
            Thanks!
          </h3>
          <p className="text-xs text-neutral-500 leading-relaxed">
            We'll call you within 24 hours.
          </p>
          <button
            type="button"
            onClick={() => {
              setName(""); setEmail(""); setPhone("");
              setBrandId(""); setModelId(""); setServiceId("");
              reset();
            }}
            className="mt-4 text-[10px] font-bold uppercase tracking-widest text-primary hover:underline"
          >
            Send another →
          </button>
        </div>
      </aside>
    );
  }

  return (
    <aside
      data-testid="lead-form-widget"
      className="bg-white border border-border p-5"
    >
      <h3 className="text-sm font-black uppercase tracking-tighter text-neutral-900 mb-1">
        Get a callback
      </h3>
      <p className="text-[11px] text-neutral-500 leading-snug mb-4">
        Tell us about your car. We'll call you within 24 hours.
      </p>

      <form className="space-y-3" onSubmit={handleSubmit}>
        {/* Name */}
        <div>
          <label className={labelClass} htmlFor="lf-name">
            Name <span className="text-primary">*</span>
          </label>
          <input
            id="lf-name"
            type="text"
            required
            value={name}
            onChange={(e) => setName(e.target.value)}
            placeholder="Your full name"
            className={inputClass}
            data-testid="lead-form-name"
          />
          {errors.name && (
            <p className="mt-1 text-[10px] text-red-600">{errors.name}</p>
          )}
        </div>

        {/* Email */}
        <div>
          <label className={labelClass} htmlFor="lf-email">Email</label>
          <input
            id="lf-email"
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            placeholder="you@example.com"
            className={inputClass}
            data-testid="lead-form-email"
          />
          {errors.email && (
            <p className="mt-1 text-[10px] text-red-600">{errors.email}</p>
          )}
        </div>

        {/* Phone */}
        <div>
          <label className={labelClass} htmlFor="lf-phone">
            Phone <span className="text-primary">*</span>
          </label>
          <input
            id="lf-phone"
            type="tel"
            required
            inputMode="numeric"
            pattern="[6-9][0-9]{9}"
            maxLength={10}
            value={phone}
            onChange={(e) => setPhone(e.target.value.replace(/\D/g, "").slice(0, 10))}
            placeholder="10-digit mobile"
            className={inputClass}
            data-testid="lead-form-phone"
          />
          {errors.phone && (
            <p className="mt-1 text-[10px] text-red-600">{errors.phone}</p>
          )}
        </div>

        {/* Brand */}
        <div>
          <label className={labelClass} htmlFor="lf-brand">Brand</label>
          <select
            id="lf-brand"
            value={brandId}
            onChange={(e) => onBrandChange(e.target.value)}
            className={inputClass}
            data-testid="lead-form-brand"
            disabled={brandsQuery.isLoading}
          >
            <option value="">{brandsQuery.isLoading ? "Loading…" : "Select brand"}</option>
            {brandsQuery.data?.map((b) => (
              <option key={b.id} value={b.id}>{b.name}</option>
            ))}
          </select>
          {errors.brand_id && (
            <p className="mt-1 text-[10px] text-red-600">{errors.brand_id}</p>
          )}
        </div>

        {/* Model — disabled until a Brand is picked */}
        <div>
          <label className={labelClass} htmlFor="lf-model">Model</label>
          <select
            id="lf-model"
            value={modelId}
            onChange={(e) => setModelId(e.target.value === "" ? "" : Number(e.target.value))}
            className={inputClass}
            data-testid="lead-form-model"
            disabled={typeof brandId !== "number" || modelsQuery.isLoading}
          >
            <option value="">
              {typeof brandId !== "number"
                ? "Select brand first"
                : modelsQuery.isLoading
                  ? "Loading…"
                  : "Select model"}
            </option>
            {modelsQuery.data?.map((m) => (
              <option key={m.id} value={m.id}>{m.name}</option>
            ))}
          </select>
          {errors.model_id && (
            <p className="mt-1 text-[10px] text-red-600">{errors.model_id}</p>
          )}
        </div>

        {/* Service — grouped by category */}
        <div>
          <label className={labelClass} htmlFor="lf-service">Service</label>
          <select
            id="lf-service"
            value={serviceId}
            onChange={(e) => setServiceId(e.target.value === "" ? "" : Number(e.target.value))}
            className={inputClass}
            data-testid="lead-form-service"
            disabled={servicesQuery.isLoading}
          >
            <option value="">
              {servicesQuery.isLoading ? "Loading…" : "Select service"}
            </option>
            {servicesByCategory.map((g) => (
              <optgroup key={g.name} label={g.name}>
                {g.rows.map((s) => (
                  <option key={s.id} value={s.id}>{s.name}</option>
                ))}
              </optgroup>
            ))}
          </select>
          {errors.service_id && (
            <p className="mt-1 text-[10px] text-red-600">{errors.service_id}</p>
          )}
        </div>

        {/* General error (rendered above submit button) */}
        {state === "error" && generalError && (
          <p
            data-testid="lead-form-error"
            className="text-[11px] text-red-600 leading-snug"
          >
            {generalError}
          </p>
        )}

        {/* Submit */}
        <button
          type="submit"
          disabled={state === "submitting"}
          data-testid="lead-form-submit"
          className="w-full bg-primary text-white text-xs font-black uppercase tracking-widest py-2.5 hover:bg-primary/90 transition-colors disabled:bg-primary/60 disabled:cursor-not-allowed inline-flex items-center justify-center gap-2"
        >
          {state === "submitting" && <Loader2 className="w-3.5 h-3.5 animate-spin" />}
          {state === "submitting" ? "Sending…" : "Get a callback"}
        </button>
      </form>
    </aside>
  );
}
