import React from 'react';

type Props = {
  value: unknown;
};

export function JsonPreview({ value }: Props) {
  return (
    <pre className="json-preview">
      {JSON.stringify(value, null, 2)}
    </pre>
  );
}
