import { useEffect, useState } from 'react';
import type { Estimate } from './types';

export interface EstimatePayload {
    project_type_id: number;
    pages: number | null;
    options: Record<number, number>;
    phone: string;
    whatsapp_opt_in: boolean;
    attachment_count: number;
}

function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

/** Asks the server for the price whenever the selections change (debounced). */
export function useEstimate(url: string, payload: EstimatePayload | null) {
    const [estimate, setEstimate] = useState<Estimate | null>(null);
    const [error, setError] = useState(false);
    const [loading, setLoading] = useState(false);
    const body = payload ? JSON.stringify(payload) : '';

    useEffect(() => {
        if (!body) {
            setEstimate(null);
            setError(false);
            setLoading(false);

            return;
        }

        const controller = new AbortController();
        setLoading(true);
        const timer = setTimeout(async () => {
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    signal: controller.signal,
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-XSRF-TOKEN': xsrfToken(),
                    },
                    body,
                });
                if (res.ok) {
                    setEstimate((await res.json()) as Estimate);
                    setError(false);
                } else {
                    setError(true);
                }
            } catch (e) {
                if ((e as Error).name !== 'AbortError') setError(true);
            } finally {
                if (!controller.signal.aborted) setLoading(false);
            }
        }, 250);

        return () => {
            clearTimeout(timer);
            controller.abort();
        };
    }, [url, body]);

    return { estimate, error, loading };
}
