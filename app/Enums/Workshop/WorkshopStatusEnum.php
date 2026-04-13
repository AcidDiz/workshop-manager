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
