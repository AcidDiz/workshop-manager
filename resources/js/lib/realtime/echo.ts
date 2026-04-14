import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

type EchoAuthOptions = {
    csrfToken: string;
};

let echoInstance: Echo<'reverb'> | null = null;
let echoSignature: string | null = null;
let listenerCount = 0;

function resolveEchoConfig(csrfToken: string) {
    const key = import.meta.env.VITE_REVERB_APP_KEY;
    const host = import.meta.env.VITE_REVERB_HOST;

    if (!key || !host || !csrfToken) {
        return null;
    }

    const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'https';
    const forceTls = scheme === 'https';
    const port = Number(
        import.meta.env.VITE_REVERB_PORT ?? (forceTls ? '443' : '80'),
    );

    return {
        key,
        host,
        port,
        forceTls,
        signature: [key, host, port, scheme, csrfToken].join('|'),
    };
}

function createEchoClient({
    csrfToken,
}: EchoAuthOptions): Echo<'reverb'> | null {
    const config = resolveEchoConfig(csrfToken);

    if (!config) {
        return null;
    }

    window.Pusher = Pusher;

    echoSignature = config.signature;

    return new Echo({
        broadcaster: 'reverb',
        key: config.key,
        wsHost: config.host,
        wsPort: config.port,
        wssPort: config.port,
        forceTLS: config.forceTls,
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        },
        withCredentials: true,
    });
}

export function getEchoClient(options: EchoAuthOptions): Echo<'reverb'> | null {
    const config = resolveEchoConfig(options.csrfToken);

    if (!config) {
        return null;
    }

    if (echoInstance === null || echoSignature !== config.signature) {
        echoInstance?.disconnect();
        echoInstance = createEchoClient(options);
    }

    return echoInstance;
}

export function retainEchoClient(): void {
    listenerCount += 1;
}

export function releaseEchoClient(): void {
    listenerCount = Math.max(0, listenerCount - 1);

    if (listenerCount === 0) {
        echoInstance?.disconnect();
        echoInstance = null;
        echoSignature = null;
    }
}
