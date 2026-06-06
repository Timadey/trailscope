import React, { useState } from 'react';
import { JsonPreview } from './JsonPreview';

type Step = {
  id: number;
  message: string;
  context: unknown;
  recorded_at: string;
};

type Props = {
  steps: Step[];
  canViewTechnicalContext: boolean;
  traceStartedAt?: string | null;
};

function offsetLabel(stepTime: string, startTime?: string | null): string {
  if (!startTime) return stepTime ? formatTime(stepTime) : '';
  const diff = new Date(stepTime).getTime() - new Date(startTime).getTime();
  if (diff < 0) return formatTime(stepTime);
  if (diff < 1000) return `+${diff}ms`;
  return `+${(diff / 1000).toFixed(2)}s`;
}

function formatTime(dateStr: string): string {
  return new Date(dateStr).toLocaleTimeString(undefined, {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
}

export function TraceTimeline({ steps, canViewTechnicalContext, traceStartedAt }: Props) {
  const [openId, setOpenId] = useState<number | null>(null);

  if (steps.length === 0) {
    return (
      <div className="empty-state" style={{ padding: '2rem' }}>
        <p className="empty-desc">No timeline steps recorded for this trace.</p>
      </div>
    );
  }

  return (
    <div className="timeline" role="list">
      {steps.map((step) => {
        const isOpen = openId === step.id;
        return (
          <div
            key={step.id}
            className={`timeline-item${isOpen ? ' open' : ''}`}
            role="listitem"
          >
            <div className="timeline-dot" />

            <button
              id={`step-${step.id}`}
              type="button"
              className="timeline-btn"
              onClick={() => setOpenId(isOpen ? null : step.id)}
              aria-expanded={isOpen}
            >
              <span className="timeline-msg">{step.message}</span>
              <span className="timeline-meta">
                <time
                  className="timeline-time"
                  dateTime={step.recorded_at}
                  title={new Date(step.recorded_at).toLocaleString()}
                >
                  {offsetLabel(step.recorded_at, traceStartedAt)}
                </time>
                {canViewTechnicalContext && (
                  <ChevronIcon isOpen={isOpen} />
                )}
              </span>
            </button>

            {isOpen && canViewTechnicalContext && (
              <div className="timeline-context">
                <JsonPreview value={step.context} />
              </div>
            )}
          </div>
        );
      })}
    </div>
  );
}

function ChevronIcon({ isOpen }: { isOpen: boolean }) {
  return (
    <svg
      className="timeline-chevron"
      width="14"
      height="14"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2.5"
      strokeLinecap="round"
      strokeLinejoin="round"
      style={{ transform: isOpen ? 'rotate(180deg)' : 'rotate(0deg)', transition: 'transform 200ms ease' }}
      aria-hidden="true"
    >
      <polyline points="6 9 12 15 18 9" />
    </svg>
  );
}
