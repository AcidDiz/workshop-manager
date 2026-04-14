import { usePage } from '@inertiajs/vue3';
import { toValue, watch } from 'vue';
import type { MaybeRefOrGetter } from 'vue';
import {
    getEchoClient,
    releaseEchoClient,
    retainEchoClient,
} from '@/lib/realtime/echo';

type EchoEventHandler<TPayload> = (payload: TPayload) => void;

type UseEchoPrivateEventOptions<TEvents extends Record<string, unknown>> = {
    channel: MaybeRefOrGetter<string>;
    listeners: {
        [TEvent in keyof TEvents]?: EchoEventHandler<TEvents[TEvent]>;
    };
    enabled?: MaybeRefOrGetter<boolean>;
};

export function useEchoPrivateEvent<TEvents extends Record<string, unknown>>({
    channel,
    listeners,
    enabled = true,
}: UseEchoPrivateEventOptions<TEvents>): void {
    const page = usePage();

    watch(
        () => ({
            channel: toValue(channel),
            enabled: toValue(enabled),
        }),
        ({ channel, enabled }, _, onCleanup) => {
            if (!enabled || channel.trim() === '') {
                return;
            }

            const eventListeners = Object.entries(listeners).filter(
                ([, handler]) => typeof handler === 'function',
            ) as Array<[string, EchoEventHandler<unknown>]>;

            if (eventListeners.length === 0) {
                return;
            }

            const csrfToken = page.props.csrf_token;

            if (typeof csrfToken !== 'string' || csrfToken === '') {
                return;
            }

            const echo = getEchoClient({ csrfToken });

            if (echo === null) {
                return;
            }

            const channelInstance = echo.private(channel);

            retainEchoClient();

            for (const [event, handler] of eventListeners) {
                channelInstance.listen(`.${event}`, handler);
            }

            onCleanup(() => {
                for (const [event, handler] of eventListeners) {
                    channelInstance.stopListening(`.${event}`, handler);
                }

                releaseEchoClient();
            });
        },
        {
            immediate: true,
        },
    );
}
