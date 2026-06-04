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
};

export function TraceTimeline({ steps, canViewTechnicalContext }: Props) {
  const [openStepId, setOpenStepId] = useState<number | null>(null);

  return (
    <div className="timeline">
      {steps.map((step) => (
        <div key={step.id} className="timeline-step">
          <button type="button" onClick={() => setOpenStepId(openStepId === step.id ? null : step.id)}>
            <span>{step.message}</span>
            <time>{step.recorded_at}</time>
          </button>
          {openStepId === step.id && canViewTechnicalContext && <JsonPreview value={step.context} />}
        </div>
      ))}
    </div>
  );
}
