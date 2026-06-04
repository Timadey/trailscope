import React from 'react';
import { TraceTimeline } from '../../Components/TraceTimeline';
import { JsonPreview } from '../../Components/JsonPreview';

export default function TraceShow({ trace, canViewTechnicalContext = true }: any) {
  return (
    <main className="trail-page">
      <header>
        <h1>{trace.method || '-'} {trace.path || '-'}</h1>
        <p>Status {trace.status_code || '-'} · {trace.duration_ms || 0}ms</p>
      </header>
      <section>
        <h2>Owner</h2>
        <p>{trace.owner_label || 'Unknown'}</p>
      </section>
      {canViewTechnicalContext && (
        <section>
          <h2>Request</h2>
          <JsonPreview value={trace.request} />
        </section>
      )}
      <section>
        <h2>Timeline</h2>
        <TraceTimeline steps={trace.steps || []} canViewTechnicalContext={canViewTechnicalContext} />
      </section>
    </main>
  );
}
