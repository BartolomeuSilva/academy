/**
 * Minimal JSON client for endpoints that are not Inertia visits. It sends the
 * XSRF token from Laravel's cookie and throws an `HttpError` with the server
 * message on failure.
 */
export class HttpError extends Error {
    constructor(
        message: string,
        public readonly status: number,
    ) {
        super(message);
    }
}

function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

type RequestOptions = {
    /** Let the request outlive the page, e.g. when saving progress while leaving it. */
    keepalive?: boolean;
};

export function postJson<T>(
    url: string,
    body: unknown,
    options: RequestOptions = {},
): Promise<T> {
    return sendJson<T>('POST', url, body, options);
}

export function putJson<T>(
    url: string,
    body: unknown,
    options: RequestOptions = {},
): Promise<T> {
    return sendJson<T>('PUT', url, body, options);
}

async function sendJson<T>(
    method: 'POST' | 'PUT',
    url: string,
    body: unknown,
    { keepalive = false }: RequestOptions,
): Promise<T> {
    const response = await fetch(url, {
        method,
        keepalive,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        body: JSON.stringify(body),
    });

    const data = (await response.json().catch(() => ({}))) as {
        message?: string;
        errors?: Record<string, string[]>;
    };

    if (!response.ok) {
        const firstError = data.errors
            ? Object.values(data.errors)[0]?.[0]
            : undefined;

        throw new HttpError(
            firstError ?? data.message ?? 'Algo deu errado. Tente novamente.',
            response.status,
        );
    }

    return data as T;
}
