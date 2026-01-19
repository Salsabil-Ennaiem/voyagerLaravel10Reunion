<?php

namespace App\Observers;

use App\Models\Organisation;
use Illuminate\Support\Facades\Cache;

class OrganisationObserver
{
    /**
     * Handle the Organisation "created" event.
     */
    public function created(Organisation $organisation): void
    {
        Cache::forget('organisations_all');
    }

    /**
     * Handle the Organisation "updated" event.
     */
    public function updated(Organisation $organisation): void
    {
        Cache::forget('organisations_all');
    }

    /**
     * Handle the Organisation "deleted" event.
     */
    public function deleted(Organisation $organisation): void
    {
        Cache::forget('organisations_all');
    }

    /**
     * Handle the Organisation "restored" event.
     */
    public function restored(Organisation $organisation): void
    {
        Cache::forget('organisations_all');
    }

    /**
     * Handle the Organisation "force deleted" event.
     */
    public function forceDeleted(Organisation $organisation): void
    {
        Cache::forget('organisations_all');
    }
}
