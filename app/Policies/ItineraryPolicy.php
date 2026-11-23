<?php

namespace App\Policies;

use App\Models\Itinerary\Itinerary;
use App\Models\User;

/**
 * Anyone with an itinerary's link can view it. Changing it takes either the edit token handed out when it was
 * created (sent as the X-Itinerary-Token header) or being a user who owns it (user_itineraries).
 */
class ItineraryPolicy
{
    public const TOKEN_HEADER = 'X-Itinerary-Token';

    public function view(?User $user, Itinerary $itinerary): bool
    {
        return true;
    }

    public function update(?User $user, Itinerary $itinerary, ?string $token = null): bool
    {
        return $itinerary->isOwnedBy($user) || $itinerary->hasEditToken($token);
    }

    public function delete(?User $user, Itinerary $itinerary, ?string $token = null): bool
    {
        return $this->update($user, $itinerary, $token);
    }

    /** Saving someone's itinerary to your account requires proving you can edit it. */
    public function claim(?User $user, Itinerary $itinerary, ?string $token = null): bool
    {
        return $user !== null && $this->update($user, $itinerary, $token);
    }
}
