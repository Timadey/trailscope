import React from 'react';
import { Link, router } from '@inertiajs/react';
import { TraceTimeline } from '../../Components/TraceTimeline';
import { JsonPreview } from '../../Components/JsonPreview';

type Trace = {
  id: number;
  method: string | null;
  path: string | null;
  status_code: number | null;
  duration_ms: number | null;
  owner_label: string | null;
  owner_type: string | null;
  owner_id: string | null;
  journey_url: string | null;
  started_at: string | null;
  request: unknown;
  response: unknown;
  exception: unknown;
  steps: Step[];
};

type Step = {
  id: number;
  message: string;
  context: unknown;
  recorded_at: string;
};

type Props = {
  trace: Trace;
  canViewTechnicalContext?: boolean;
  logoutUrl: string;
};

function statusClass(code: number | null): string {
  if (!code) return 'status-unk';
  if (code < 300) return 'status-2xx';
  if (code < 400) return 'status-3xx';
  if (code < 500) return 'status-4xx';
  return 'status-5xx';
}

function methodClass(method: string | null): string {
  if (!method) return 'method-unk';
  return `method-${method.toLowerCase()}`;
}

export default function TraceShow({ trace, canViewTechnicalContext = true, logoutUrl }: Props) {
  function doLogout() {
    router.post(logoutUrl);
  }

  const titleLabel = `${trace.method ?? '—'} ${trace.path ?? '—'}`;

  return (
    <div className="trail-page">
      {/* Nav */}
      <nav className="trail-nav">
        <div className="trail-nav-brand">
          <div className="trail-nav-icon">
            <NavFootprintSvg />
          </div>
          <span className="trail-nav-wordmark">TrailScope</span>
        </div>
        <div className="trail-nav-right">
          <button
            id="logout-btn"
            type="button"
            className="btn btn-ghost btn-sm"
            onClick={doLogout}
          >
            <LogoutIcon />
            Sign out
          </button>
        </div>
      </nav>

      <div className="trail-content animate-in">
        {/* Breadcrumb */}
        <nav className="breadcrumb" aria-label="Breadcrumb">
          <Link href="../traces" className="">Traces</Link>
          <span className="breadcrumb-sep">/</span>
          <span className="breadcrumb-cur" title={titleLabel}>{titleLabel}</span>
        </nav>

        {/* Hero */}
        <div className="trace-hero">
          <div className="trace-hero-top">
            <span className={`method-pill ${methodClass(trace.method)}`} style={{ fontSize: '0.875rem', padding: '4px 12px' }}>
              {trace.method ?? '—'}
            </span>
            <p className="trace-hero-path">{trace.path ?? '—'}</p>
          </div>
          <div className="trace-hero-meta">
            <span className={`status-badge ${statusClass(trace.status_code)}`} style={{ fontSize: '0.8125rem' }}>
              {trace.status_code ?? '—'}
            </span>

            {trace.duration_ms !== null && trace.duration_ms !== undefined && (
              <span className="duration-badge">
                <ClockIcon />
                {trace.duration_ms}ms
              </span>
            )}

            {trace.owner_label && (
              <span className="owner-chip">
                <UserIcon />
                {trace.owner_label}
              </span>
            )}

            {trace.journey_url && (
              <Link href={trace.journey_url} className="btn btn-ghost btn-sm">
                View journey
                <ChevronRightIcon />
              </Link>
            )}

            {trace.started_at && (
              <span className="chip">
                {new Date(trace.started_at).toLocaleString(undefined, {
                  month: 'short', day: 'numeric',
                  hour: '2-digit', minute: '2-digit', second: '2-digit',
                })}
              </span>
            )}
          </div>
        </div>

        {/* Two-column grid */}
        <div className="trace-grid">
          {/* Timeline */}
          <div className="trace-section">
            <div className="trace-section-head">
              <span className="trace-section-label">
                Timeline — {(trace.steps ?? []).length} step{(trace.steps ?? []).length !== 1 ? 's' : ''}
              </span>
            </div>
            <div className="trace-section-body">
              <TraceTimeline
                steps={trace.steps ?? []}
                canViewTechnicalContext={canViewTechnicalContext}
                traceStartedAt={trace.started_at}
              />
            </div>
          </div>

          {/* Request / Response / Exception panels */}
          <div style={{ display: 'flex', flexDirection: 'column', gap: 'var(--sp-4)' }}>
            {canViewTechnicalContext && trace.request !== null && trace.request !== undefined && (
              <div className="trace-section">
                <div className="trace-section-head">
                  <span className="trace-section-label">Request</span>
                </div>
                <div className="trace-section-body" style={{ padding: 0 }}>
                  <JsonPreview value={trace.request} />
                </div>
              </div>
            )}

            {canViewTechnicalContext && trace.response !== null && trace.response !== undefined && (
              <div className="trace-section">
                <div className="trace-section-head">
                  <span className="trace-section-label">Response</span>
                </div>
                <div className="trace-section-body" style={{ padding: 0 }}>
                  <JsonPreview value={trace.response} />
                </div>
              </div>
            )}

            {canViewTechnicalContext && trace.exception !== null && trace.exception !== undefined && (
              <div className="trace-section" style={{ borderColor: 'var(--status-4xx-bg)' }}>
                <div className="trace-section-head" style={{ borderColor: 'var(--status-4xx-bg)' }}>
                  <span className="trace-section-label" style={{ color: 'var(--status-4xx)' }}>
                    Exception
                  </span>
                </div>
                <div className="trace-section-body" style={{ padding: 0 }}>
                  <JsonPreview value={trace.exception} />
                </div>
              </div>
            )}

            {!canViewTechnicalContext && (
              <div className="trace-section">
                <div className="empty-state" style={{ padding: 'var(--sp-8)' }}>
                  <div className="empty-icon">
                    <LockIcon />
                  </div>
                  <p className="empty-title">Technical context restricted</p>
                  <p className="empty-desc">
                    Your account role does not have permission to view request and response data.
                  </p>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

/* ── Icons ── */
function NavFootprintSvg() {
  return (
    <svg width="18" height="18" viewBox="0 0 40 40" fill="none" aria-hidden="true">
      <circle cx="13" cy="8"  r="3.5" fill="white" opacity="0.95" />
      <circle cx="21" cy="6"  r="2.5" fill="white" opacity="0.8" />
      <circle cx="28" cy="9"  r="2.2" fill="white" opacity="0.7" />
      <circle cx="32" cy="14" r="1.8" fill="white" opacity="0.55" />
      <path d="M8 17 Q9 13 14 15 Q20 17 18 26 Q16 32 11 30 Q6 28 8 17Z" fill="white" opacity="0.95" />
      <path d="M22 18 Q24 15 27 17 Q30 19 29 25 Q28 29 25 28 Q21 26 22 18Z" fill="white" opacity="0.72" />
    </svg>
  );
}

function LogoutIcon() {
  return (
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
      <polyline points="16 17 21 12 16 7" />
      <line x1="21" y1="12" x2="9" y2="12" />
    </svg>
  );
}

function ClockIcon() {
  return (
    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <circle cx="12" cy="12" r="10" />
      <polyline points="12 6 12 12 16 14" />
    </svg>
  );
}

function UserIcon() {
  return (
    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
      <circle cx="12" cy="7" r="4" />
    </svg>
  );
}

function LockIcon() {
  return (
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
      <path d="M7 11V7a5 5 0 0 1 10 0v4" />
    </svg>
  );
}
