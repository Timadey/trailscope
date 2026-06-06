import React from 'react';
import { Link } from '@inertiajs/react';

type Trace = {
  id: number;
  trace_id: string;
  method: string | null;
  path: string | null;
  status_code: number | null;
  owner_label: string | null;
  started_at: string | null;
  url: string;
};

export default function TraceIndex({ traces }: { traces: Trace[] }) {
  return (
    <main className="trail-page">
      <header>
        <h1>Traces</h1>
      </header>
      <table>
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
          {traces.map((trace) => (
            <tr key={trace.id}>
              <td>{trace.status_code ?? '-'}</td>
              <td>{trace.method ?? '-'}</td>
              <td>{trace.path ?? '-'}</td>
              <td>{trace.owner_label ?? 'Unknown'}</td>
              <td>{trace.started_at ?? '-'}</td>
              <td><Link href={trace.url}>Open</Link></td>
            </tr>
          ))}
        </tbody>
      </table>
    </main>
  );
}
