import React from 'react';
import { Link } from '@inertiajs/react';

export default function UserJourney({ ownerType, ownerId, traces }: any) {
  return (
    <main className="trail-page">
      <header>
        <h1>User Journey</h1>
        <p>{ownerType}: {ownerId}</p>
      </header>
      {traces.map((trace: any) => (
        <article key={trace.id} className="journey-row">
          <strong>{trace.status_code || '-'}</strong>
          <span>{trace.method || '-'} {trace.path || '-'}</span>
          <Link href={`/trail/traces/${trace.id}`}>Open</Link>
        </article>
      ))}
    </main>
  );
}
