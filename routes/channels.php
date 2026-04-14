<?php

use App\Models\Workshop;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('admin.workshop-statistics', function ($user) {
    return $user->can('create', Workshop::class);
});

Broadcast::channel('admin.workshops.{workshopId}', function ($user, int $workshopId) {
    $workshop = Workshop::query()->find($workshopId);

    return $workshop !== null && $user->can('update', $workshop);
});
