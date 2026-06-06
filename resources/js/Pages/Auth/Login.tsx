import React, { FormEvent, useState } from 'react';
import { router, usePage } from '@inertiajs/react';

type Props = { submitUrl: string };

export default function Login({ submitUrl }: Props) {
  const { props } = usePage<{ errors?: { email?: string } }>();
  const errors = (props as any).errors ?? {};

  const [email, setEmail]             = useState('');
  const [password, setPassword]       = useState('');
  const [showPwd, setShowPwd]         = useState(false);
  const [isLoading, setIsLoading]     = useState(false);

  function submit(e: FormEvent) {
    e.preventDefault();
    setIsLoading(true);
    router.post(submitUrl, { email, password }, {
      onFinish: () => setIsLoading(false),
    });
  }

  return (
    <main className="trail-page auth-page">
      {/* ── Left decorative panel ── */}
      <div className="auth-decorative">
        <div className="auth-grid-bg" />
        <div className="auth-glow-blob" />
        <div className="auth-decorative-inner">
          <div className="auth-logomark">
            <FootprintSvg />
          </div>
          <p className="auth-product-name">TrailScope</p>
          <p className="auth-tagline">
            Visualise and debug every user's journey through your application, in real-time.
          </p>
          <ul className="auth-feature-list">
            {[
              'Real-time HTTP request tracing',
              'User journey visualisation',
              'Full request context &amp; timeline',
              'Role-based access control',
            ].map((f) => (
              <li key={f} className="auth-feature-item">
                <span className="auth-feature-dot" />
                <span dangerouslySetInnerHTML={{ __html: f }} />
              </li>
            ))}
          </ul>
        </div>
      </div>

      {/* ── Right form panel ── */}
      <div className="auth-form-panel">
        <div className="auth-form-wrap animate-in">
          <div className="auth-form-heading">
            <h1 className="auth-form-title">Welcome back</h1>
            <p className="auth-form-sub">Sign in to your TrailScope dashboard</p>
          </div>

          <form onSubmit={submit} className="auth-form" noValidate>
            {/* Email */}
            <div className="form-group">
              <label className="form-label" htmlFor="login-email">
                Email address
              </label>
              <div className="form-input-wrap">
                <input
                  id="login-email"
                  type="email"
                  autoComplete="email"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="you@company.com"
                  className={`form-input${errors.email ? ' error' : ''}`}
                />
              </div>
              {errors.email && (
                <p className="form-error" role="alert">
                  <AlertCircleIcon />
                  {errors.email}
                </p>
              )}
            </div>

            {/* Password */}
            <div className="form-group">
              <label className="form-label" htmlFor="login-password">
                Password
              </label>
              <div className="form-input-wrap">
                <input
                  id="login-password"
                  type={showPwd ? 'text' : 'password'}
                  autoComplete="current-password"
                  required
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="••••••••"
                  className="form-input has-icon-right"
                />
                <button
                  type="button"
                  className="form-input-btn-right"
                  aria-label={showPwd ? 'Hide password' : 'Show password'}
                  onClick={() => setShowPwd((v) => !v)}
                >
                  {showPwd ? <EyeOffIcon /> : <EyeIcon />}
                </button>
              </div>
            </div>

            {/* Submit */}
            <button
              id="login-submit"
              type="submit"
              disabled={isLoading}
              className="btn btn-primary btn-lg btn-full"
              style={{ marginTop: '4px' }}
            >
              {isLoading && <SpinnerIcon />}
              {isLoading ? 'Signing in…' : 'Sign in'}
            </button>
          </form>

          <p className="auth-footer">
            Powered by <strong>TrailScope</strong>
          </p>
        </div>
      </div>
    </main>
  );
}

/* ── SVG Icons ── */

function FootprintSvg() {
  return (
    <svg width="40" height="40" viewBox="0 0 40 40" fill="none" aria-hidden="true">
      {/* Toes */}
      <circle cx="13" cy="8"  r="3.5" fill="white" opacity="0.95" />
      <circle cx="21" cy="6"  r="2.5" fill="white" opacity="0.80" />
      <circle cx="28" cy="9"  r="2.2" fill="white" opacity="0.70" />
      <circle cx="32" cy="14" r="1.8" fill="white" opacity="0.55" />
      {/* Main foot */}
      <path
        d="M8 17 Q9 13 14 15 Q20 17 18 26 Q16 32 11 30 Q6 28 8 17Z"
        fill="white"
        opacity="0.95"
      />
      {/* Small heel */}
      <path
        d="M22 18 Q24 15 27 17 Q30 19 29 25 Q28 29 25 28 Q21 26 22 18Z"
        fill="white"
        opacity="0.72"
      />
    </svg>
  );
}

function EyeIcon() {
  return (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
      <circle cx="12" cy="12" r="3" />
    </svg>
  );
}

function EyeOffIcon() {
  return (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
      <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
      <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24" />
      <line x1="1" y1="1" x2="23" y2="23" />
    </svg>
  );
}

function AlertCircleIcon() {
  return (
    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <circle cx="12" cy="12" r="10" />
      <line x1="12" y1="8" x2="12" y2="12" />
      <line x1="12" y1="16" x2="12.01" y2="16" />
    </svg>
  );
}

function SpinnerIcon() {
  return (
    <svg className="spinner" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" aria-hidden="true">
      <path d="M21 12a9 9 0 1 1-6.219-8.56" />
    </svg>
  );
}
