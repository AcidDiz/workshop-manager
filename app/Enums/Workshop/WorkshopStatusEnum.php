<?php

namespace App\Enums\Workshop;

/**
 * Allowed `status` query values for the workshops index (admin table + user card filters).
 */
enum WorkshopStatusEnum: string
{
    case All = 'all';
    case Upcoming = 'upcoming';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::All => 'Upcoming and closed',
            self::Upcoming => 'Upcoming',
            self::Closed => 'Closed',
        };
    }

    /**
     * Tailwind utility classes for status badges (keep in sync with scanned `@source` in `resources/css/app.css`).
     */
    public function badgeClassName(): string
    {
        return match ($this) {
            self::All => 'border-transparent bg-muted text-muted-foreground',
            self::Upcoming => 'border-transparent bg-emerald-500/15 text-emerald-700 dark:text-emerald-400',
            self::Closed => 'border-transparent bg-zinc-500/15 text-zinc-600 dark:text-zinc-300',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function filterSelectOptions(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
