import React from 'react';
import { router } from '@inertiajs/react';

type Props = {
  title: string;
  subtitle?: string;
  logoutUrl?: string;
};

export function DashboardHeader({ title, subtitle, logoutUrl }: Props) {
  function logout() {
    if (logoutUrl) {
      router.post(logoutUrl);
    }
  }

  return (
    <header>
      <div>
        <h1>{title}</h1>
        {subtitle && <p>{subtitle}</p>}
      </div>
      {logoutUrl && (
        <button type="button" onClick={logout}>
          Logout
        </button>
      )}
    </header>
  );
}
