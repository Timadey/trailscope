import React, { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';

export default function Login({ submitUrl }: { submitUrl: string }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');

  function submit(event: FormEvent) {
    event.preventDefault();
    router.post(submitUrl, { email, password });
  }

  return (
    <main className="trail-page auth-page">
      <form onSubmit={submit}>
        <h1>Trail</h1>
        <label>
          Email
          <input type="email" value={email} onChange={(event) => setEmail(event.target.value)} />
        </label>
        <label>
          Password
          <input type="password" value={password} onChange={(event) => setPassword(event.target.value)} />
        </label>
        <button type="submit">Sign in</button>
      </form>
    </main>
  );
}
