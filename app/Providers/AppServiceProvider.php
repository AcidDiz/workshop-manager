<?php

namespace App\Providers;

use App\Models\Workshop;
use App\Models\WorkshopRegistration;
use App\Observers\BroadcastAdminWorkshopParticipantsObserver;
use App\Observers\BroadcastUserWorkshopRegistrationStateObserver;
use App\Observers\BroadcastWorkshopAdminStatisticsObserver;
use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\Mime\Email;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureOutgoingMailLogging();

        Workshop::observe(BroadcastWorkshopAdminStatisticsObserver::class);
        WorkshopRegistration::observe(BroadcastWorkshopAdminStatisticsObserver::class);
        WorkshopRegistration::observe(BroadcastAdminWorkshopParticipantsObserver::class);
        WorkshopRegistration::observe(BroadcastUserWorkshopRegistrationStateObserver::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(
            fn (): ?Password => app()->isProduction()
                ? Password::min(12)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
                : null,
        );

        JsonResource::withoutWrapping();
    }

    /**
     * Log a copy of outgoing mail (in addition to SMTP / Mailtrap) when enabled.
     */
    protected function configureOutgoingMailLogging(): void
    {
        if (! config('mail.log_outgoing_messages')) {
            return;
        }

        Event::listen(MessageSending::class, function (MessageSending $event): void {
            $message = $event->message;
            if (! ($message instanceof Email)) {
                Log::debug('Mail outgoing (non-Email transport)', [
                    'class' => $message::class,
                ]);

                return;
            }

            $text = $message->getTextBody();
            $html = $message->getHtmlBody();
            $preview = $text;
            if ($preview === null && is_string($html)) {
                $preview = strip_tags($html);
            }
            if (is_string($preview) && strlen($preview) > 2000) {
                $preview = substr($preview, 0, 2000).'…';
            }

            $context = [
                'subject' => $message->getSubject(),
                'to' => collect($message->getTo())->map->getAddress()->values()->all(),
                'from' => collect($message->getFrom())->map->getAddress()->values()->all(),
                'preview' => $preview,
            ];

            $channel = config('mail.log_outgoing_channel');
            if (is_string($channel) && $channel !== '') {
                Log::channel($channel)->info('Mail outgoing', $context);
            } else {
                Log::info('Mail outgoing', $context);
            }
        });
    }
}
