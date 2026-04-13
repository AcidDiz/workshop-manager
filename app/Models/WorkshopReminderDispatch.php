<?php

namespace App\Models;

use App\Enums\Workshop\WorkshopReminderKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkshopReminderDispatch extends Model
{
    protected $fillable = [
        'workshop_id',
        'user_id',
        'kind',
        'dispatch_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => WorkshopReminderKind::class,
            'dispatch_date' => 'date',
        ];
    }

    /** @return BelongsTo<Workshop, $this> */
    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
