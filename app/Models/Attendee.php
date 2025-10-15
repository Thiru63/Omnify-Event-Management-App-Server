<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="Attendee",
 *     type="object",
 *     required={"id", "event_id", "name", "email"},
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="event_id", type="integer", format="int64", example=1),
 *     @OA\Property(property="name", type="string", maxLength=255, example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", maxLength=255, example="john@example.com"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Attendee extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'email'
    ];

    /**
     * Get the event that owns the attendee.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Scope a query to only include attendees for a specific event.
     */
    public function scopeForEvent($query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    /**
     * Scope a query to search attendees by name or email.
     */
    public function scopeSearch($query, string $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('name', 'ILIKE', '%' . $searchTerm . '%')
              ->orWhere('email', 'ILIKE', '%' . $searchTerm . '%');
        });
    }
}