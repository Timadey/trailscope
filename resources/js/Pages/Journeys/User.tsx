import React, { useMemo, useState } from 'react';
import { Link, router } from '@inertiajs/react';

type Trace = {
  id: number;
  method: string | null;
  path: string | null;
  status_code: number | null;
  started_at: string | null;
  duration_ms: number | null;
  url: string;
};

type Props = {
  ownerType: string;
  ownerId: string;
  traces: Trace[];
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

function relativeTime(dateStr: string | null): string {
  if (!dateStr) return '—';
  const ms = Date.now() - new Date(dateStr).getTime();
  const s = Math.floor(ms / 1000);
  if (s < 60) return `${s}s ago`;
  const m = Math.floor(s / 60);
  if (m < 60) return `${m}m ago`;
  const h = Math.floor(m / 60);
  if (h < 24) return `${h}h ago`;
  return `${Math.floor(h / 24)}d ago`;
}

function absoluteTime(dateStr: string | null): string {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleString(undefined, {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit', second: '2-digit',
  });
}

export default function UserJourney({ ownerType, ownerId, traces, logoutUrl }: Props) {
  const [search, setSearch]       = useState('');
  const [methodFilter, setMethod] = useState('');
  const [statusFilter, setStatus] = useState('');

  const filtered = useMemo(() => {
    return traces.filter((t) => {
      const matchSearch =
        !search ||
        t.path?.toLowerCase().includes(search.toLowerCase());
      const matchMethod =
        !methodFilter || t.method?.toLowerCase() === methodFilter.toLowerCase();
      const matchStatus =
        !statusFilter ||
        (statusFilter === '2xx' && t.status_code !== null && t.status_code >= 200 && t.status_code < 300) ||
        (statusFilter === '3xx' && t.status_code !== null && t.status_code >= 300 && t.status_code < 400) ||
        (statusFilter === '4xx' && t.status_code !== null && t.status_code >= 400 && t.status_code < 500) ||
        (statusFilter === '5xx' && t.status_code !== null && t.status_code >= 500);
      return matchSearch && matchMethod && matchStatus;
    });
  }, [traces, search, methodFilter, statusFilter]);

  const successCount = traces.filter(
    (t) => t.status_code !== null && t.status_code >= 200 && t.status_code < 300,
  ).length;
  const errorCount = traces.filter(
    (t) => t.status_code !== null && t.status_code >= 400,
  ).length;

  function doLogout() { router.post(logoutUrl); }

  const ownerLabel = `${ownerType}: ${ownerId}`;

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
          <Link href="../traces" className="btn btn-ghost btn-sm">
            <ChevronLeftIcon /> Traces
          </Link>
          <button
            id="logout-btn"
            type="button"
            className="btn btn-ghost btn-sm"
            onClick={doLogout}
          >
            <LogoutIcon /> Sign out
          </button>
        </div>
      </nav>

      <div className="trail-content animate-in">
        {/* Journey header */}
        <div className="journey-header">
          <div>
            <h1 className="page-title">User Journey</h1>
            <p className="page-subtitle" style={{ marginTop: '4px' }}>
              Chronological request history for this identity
            </p>
          </div>
          <div style={{ display: 'flex', alignItems: 'center', gap: 'var(--sp-4)', flexWrap: 'wrap' }}>
            <span className="journey-owner-tag">
              <UserIcon />
              {ownerLabel}
            </span>
            <div className="journey-stat">
              <strong>{traces.length}</strong> requests
            </div>
            {traces.length > 0 && (
              <>
                <div className="journey-stat">
                  <span style={{ color: 'var(--status-2xx)' }}>●</span>
                  <strong>{successCount}</strong> OK
                </div>
                <div className="journey-stat">
                  <span style={{ color: 'var(--status-4xx)' }}>●</span>
                  <strong>{errorCount}</strong> errors
                </div>
              </>
            )}
          </div>
        </div>

        {/* Filter bar */}
        <div className="filter-bar">
          <div className="filter-search">
            <span className="filter-search-icon">
              <SearchIcon />
            </span>
            <input
              id="journey-search"
              type="search"
              className="filter-input"
              placeholder="Search by path…"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
          </div>
          <select
            id="journey-method-filter"
            className="filter-select"
            value={methodFilter}
            onChange={(e) => setMethod(e.target.value)}
            aria-label="Filter by method"
          >
            <option value="">All methods</option>
            <option value="GET">GET</option>
            <option value="POST">POST</option>
            <option value="PUT">PUT</option>
            <option value="PATCH">PATCH</option>
            <option value="DELETE">DELETE</option>
          </select>
          <select
            id="journey-status-filter"
            className="filter-select"
            value={statusFilter}
            onChange={(e) => setStatus(e.target.value)}
            aria-label="Filter by status"
          >
            <option value="">All statuses</option>
            <option value="2xx">2xx — Success</option>
            <option value="3xx">3xx — Redirect</option>
            <option value="4xx">4xx — Client error</option>
            <option value="5xx">5xx — Server error</option>
          </select>
          {(search || methodFilter || statusFilter) && (
            <span className="filter-count">
              {filtered.length} / {traces.length} shown
            </span>
          )}
        </div>

        {/* Feed */}
        <div className="journey-feed">
          {/* Column headers */}
          <div className="journey-feed-header">
            <span className="journey-feed-th">Status</span>
            <span className="journey-feed-th">Method</span>
            <span className="journey-feed-th">Path</span>
            <span className="journey-feed-th">Started</span>
            <span className="journey-feed-th">Duration</span>
            <span className="journey-feed-th"></span>
          </div>

          {filtered.length === 0 ? (
            <div className="empty-state">
              <div className="empty-icon">
                <RouteIcon />
              </div>
              <p className="empty-title">No requests found</p>
              <p className="empty-desc">
                {search || methodFilter || statusFilter
                  ? 'Try adjusting your filters.'
                  : 'No requests have been traced for this user yet.'}
              </p>
            </div>
          ) : (
            filtered.map((trace) => (
              <div key={trace.id} className="journey-row">
                <span className={`status-badge ${statusClass(trace.status_code)}`}>
                  {trace.status_code ?? '—'}
                </span>
                <span className={`method-pill ${methodClass(trace.method)}`}>
                  {trace.method ?? '—'}
                </span>
                <span className="journey-path" title={trace.path ?? undefined}>
                  {trace.path ?? '—'}
                </span>
                <span
                  className="journey-time relative-time"
                  data-absolute={absoluteTime(trace.started_at)}
                  title={absoluteTime(trace.started_at)}
                >
                  {relativeTime(trace.started_at)}
                </span>
                <span className="journey-time">
                  {trace.duration_ms !== null && trace.duration_ms !== undefined
                    ? `${trace.duration_ms}ms`
                    : '—'}
                </span>
                <Link href={trace.url} className="btn btn-ghost btn-sm">
                  View <ChevronRightIcon />
                </Link>
              </div>
            ))
          )}
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

function UserIcon() {
  return (
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
      <circle cx="12" cy="7" r="4" />
    </svg>
  );
}

function SearchIcon() {
  return (
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <circle cx="11" cy="11" r="8" />
      <line x1="21" y1="21" x2="16.65" y2="16.65" />
    </svg>
  );
}

function ChevronLeftIcon() {
  return (
    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <polyline points="15 18 9 12 15 6" />
    </svg>
  );
}

function ChevronRightIcon() {
  return (
    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <polyline points="9 18 15 12 9 6" />
    </svg>
  );
}

function RouteIcon() {
  return (
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <circle cx="6" cy="19" r="3" />
      <path d="M9 19h8.5a3.5 3.5 0 0 0 0-7h-11a3.5 3.5 0 0 1 0-7H15" />
      <circle cx="18" cy="5" r="3" />
    </svg>
  );
}
