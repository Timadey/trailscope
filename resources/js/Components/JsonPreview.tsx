import React, { useState } from 'react';

type Props = { value: unknown };

/* ── Syntax-highlight JSON string ── */
function highlightJson(value: unknown): string {
  const raw = JSON.stringify(value, null, 2);
  if (!raw) return '';

  return raw
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(
      /("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+-]?\d+)?)/g,
      (match) => {
        if (/^"/.test(match)) {
          if (/:$/.test(match)) {
            // JSON key
            return `<span class="jk">${match}</span>`;
          }
          // JSON string value
          return `<span class="js">${match}</span>`;
        }
        if (/true|false/.test(match)) {
          return `<span class="jb">${match}</span>`;
        }
        if (/null/.test(match)) {
          return `<span class="jl">${match}</span>`;
        }
        // number
        return `<span class="jn">${match}</span>`;
      },
    );
}

export function JsonPreview({ value }: Props) {
  const [copied, setCopied] = useState(false);

  function copyToClipboard() {
    const text = JSON.stringify(value, null, 2);
    navigator.clipboard.writeText(text).then(() => {
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    });
  }

  if (value === null || value === undefined) {
    return (
      <div className="json-preview-wrap">
        <pre className="json-preview">
          <span className="jl">null</span>
        </pre>
      </div>
    );
  }

  return (
    <div className="json-preview-wrap">
      <button
        type="button"
        className={`json-copy-btn${copied ? ' copied' : ''}`}
        onClick={copyToClipboard}
        aria-label="Copy JSON to clipboard"
        title={copied ? 'Copied!' : 'Copy'}
      >
        {copied ? <CheckIcon /> : <CopyIcon />}
        {copied ? 'Copied!' : 'Copy'}
      </button>
      <pre
        className="json-preview"
        /* eslint-disable-next-line react/no-danger */
        dangerouslySetInnerHTML={{ __html: highlightJson(value) }}
      />
    </div>
  );
}

function CopyIcon() {
  return (
    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
      <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
    </svg>
  );
}

function CheckIcon() {
  return (
    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <polyline points="20 6 9 17 4 12" />
    </svg>
  );
}
