import React, { useMemo, useState } from 'react';
import { Link, router } from '@inertiajs/react';

/* ── Types ── */
type Trace = {
  id: number;
  trace_id: string;
  method: string | null;
  path: string | null;
  status_code: number | null;
  owner_label: string | null;
  started_at: string | null;
  duration_ms: number | null;
  url: string;
  journey_url: string | null;
};

type PaginatedTraces = {
  data: Trace[];
  current_page: number;
  from: number | null;
  to: number | null;
  per_page: number;
  next_page_url: string | null;
  prev_page_url: string | null;
};

type Props = {
  traces: PaginatedTraces;
  logoutUrl: string;
};

/* ── Helpers ── */
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
  if (s < 60)  return `${s}s ago`;
  const m = Math.floor(s / 60);
  if (m < 60)  return `${m}m ago`;
  const h = Math.floor(m / 60);
  if (h < 24)  return `${h}h ago`;
  return `${Math.floor(h / 24)}d ago`;
}

function absoluteTime(dateStr: string | null): string {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleString(undefined, {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit', second: '2-digit',
  });
}

/* ── Component ── */
export default function TraceIndex({ traces, logoutUrl }: Props) {
  const [search, setSearch]       = useState('');
  const [methodFilter, setMethod] = useState('');
  const [statusFilter, setStatus] = useState('');

  /* Client-side filter on current page data */
  const filtered = useMemo(() => {
    return traces.data.filter((t) => {
      const matchSearch =
        !search ||
        (t.path?.toLowerCase().includes(search.toLowerCase()) ||
         t.owner_label?.toLowerCase().includes(search.toLowerCase()) ||
         t.trace_id?.toLowerCase().includes(search.toLowerCase()));
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
  }, [traces.data, search, methodFilter, statusFilter]);

  /* Stats computed from current page */
  const successCount = traces.data.filter(
    (t) => t.status_code !== null && t.status_code >= 200 && t.status_code < 300,
  ).length;
  const successRate =
    traces.data.length > 0
      ? Math.round((successCount / traces.data.length) * 100)
      : 0;
  const errorCount = traces.data.filter(
    (t) => t.status_code !== null && t.status_code >= 400,
  ).length;

  function doLogout() {
    router.post(logoutUrl);
  }

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

      {/* Content */}
      <div className="trail-content animate-in">
        {/* Page header */}
        <div className="page-header">
          <div>
            <h1 className="page-title">Traces</h1>
            <p className="page-subtitle">Inspect incoming HTTP requests and their journey</p>
          </div>
        </div>

        {/* Stats bar */}
        <div className="stats-bar">
          <div className="stat-card">
            <span className="stat-label">Requests (page)</span>
            <span className="stat-value">{traces.data.length}</span>
            <span className="stat-hint">
              {traces.from !== null && traces.to !== null
                ? `Showing ${traces.from}–${traces.to}`
                : 'Current page'}
            </span>
          </div>
          <div className="stat-card">
            <span className="stat-label">Success rate</span>
            <span className={`stat-value ${successRate >= 90 ? 'stat-value-success' : successRate < 70 ? 'stat-value-warn' : ''}`}>
              {successRate}%
            </span>
            <span className="stat-hint">{successCount} successful</span>
          </div>
          <div className="stat-card">
            <span className="stat-label">Errors</span>
            <span className={`stat-value ${errorCount > 0 ? 'stat-value-warn' : 'stat-value-success'}`}>
              {errorCount}
            </span>
            <span className="stat-hint">4xx / 5xx responses</span>
          </div>
          <div className="stat-card">
            <span className="stat-label">Page size</span>
            <span className="stat-value">{traces.per_page}</span>
            <span className="stat-hint">Traces per page</span>
          </div>
        </div>

        {/* Filter bar */}
        <div className="filter-bar">
          <div className="filter-search">
            <span className="filter-search-icon">
              <SearchIcon />
            </span>
            <input
              id="trace-search"
              type="search"
              className="filter-input"
              placeholder="Search path, owner, trace ID…"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
          </div>
          <select
            id="method-filter"
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
            id="status-filter"
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
              {filtered.length} / {traces.data.length} shown
            </span>
          )}
        </div>

        {/* Table */}
        <div className="traces-table-wrapper">
          {filtered.length === 0 ? (
            <div className="empty-state">
              <div className="empty-icon">
                <InboxIcon />
              </div>
              <p className="empty-title">No traces found</p>
              <p className="empty-desc">
                {search || methodFilter || statusFilter
                  ? 'Try adjusting your filters.'
                  : 'Traces will appear here once requests start flowing through your application.'}
              </p>
            </div>
          ) : (
            <table className="traces-table">
              <thead>
                <tr>
                  <th>Status</th>
                  <th>Method</th>
                  <th>Path</th>
                  <th>Owner</th>
                  <th>Started</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                {filtered.map((trace) => (
                  <tr key={trace.id}>
                    <td>
                      <span className={`status-badge ${statusClass(trace.status_code)}`}>
                        {trace.status_code ?? '—'}
                      </span>
                    </td>
                    <td>
                      <span className={`method-pill ${methodClass(trace.method)}`}>
                        {trace.method ?? '—'}
                      </span>
                    </td>
                    <td className="td-path" title={trace.path ?? undefined}>
                      {trace.path ?? '—'}
                    </td>
                    <td className="td-owner">
                      {trace.journey_url ? (
                        <Link href={trace.journey_url} className="owner-link">
                          {trace.owner_label ?? 'View journey'}
                        </Link>
                      ) : (
                        trace.owner_label ?? <span style={{ color: 'var(--text-muted)' }}>Unknown</span>
                      )}
                    </td>
                    <td className="td-time">
                      <span
                        className="relative-time"
                        data-absolute={absoluteTime(trace.started_at)}
                        title={absoluteTime(trace.started_at)}
                      >
                        {relativeTime(trace.started_at)}
                      </span>
                    </td>
                    <td className="td-actions">
                      <Link href={trace.url} className="btn btn-ghost btn-sm">
                        View
                        <ChevronRightIcon />
                      </Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}

          {/* Paginator */}
          {(traces.prev_page_url || traces.next_page_url) && (
            <div className="paginator">
              <span className="paginator-info">
                {traces.from !== null && traces.to !== null
                  ? `Showing ${traces.from} – ${traces.to}`
                  : `Page ${traces.current_page}`}
              </span>
              <div className="paginator-actions">
                {traces.prev_page_url ? (
                  <Link href={traces.prev_page_url} className="btn btn-ghost btn-sm">
                    <ChevronLeftIcon /> Previous
                  </Link>
                ) : (
                  <button type="button" className="btn btn-ghost btn-sm" disabled>
                    <ChevronLeftIcon /> Previous
                  </button>
                )}
                {traces.next_page_url ? (
                  <Link href={traces.next_page_url} className="btn btn-ghost btn-sm">
                    Next <ChevronRightIcon />
                  </Link>
                ) : (
                  <button type="button" className="btn btn-ghost btn-sm" disabled>
                    Next <ChevronRightIcon />
                  </button>
                )}
              </div>
            </div>
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

function SearchIcon() {
  return (
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <circle cx="11" cy="11" r="8" />
      <line x1="21" y1="21" x2="16.65" y2="16.65" />
    </svg>
  );
}

function InboxIcon() {
  return (
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <polyline points="22 12 16 12 14 15 10 15 8 12 2 12" />
      <path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z" />
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

function ChevronLeftIcon() {
  return (
    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <polyline points="15 18 9 12 15 6" />
    </svg>
  );
}
