<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * @OA\Schema(
 *     schema="Event",
 *     type="object",
 *     required={"id", "name", "location", "start_time", "end_time", "max_capacity", "current_attendees"},
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="name", type="string", maxLength=255, example="Tech Conference 2024"),
 *     @OA\Property(property="location", type="string", maxLength=255, example="Bangalore Convention Center"),
 *     @OA\Property(property="start_time", type="string", format="date-time", example="2024-12-01T09:00:00.000000Z"),
 *     @OA\Property(property="end_time", type="string", format="date-time", example="2024-12-01T17:00:00.000000Z"),
 *     @OA\Property(property="max_capacity", type="integer", format="int32", example=100),
 *     @OA\Property(property="current_attendees", type="integer", format="int32", example=0),
 *     @OA\Property(property="available_capacity", type="integer", format="int32", example=100),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z")
 * )
 */
class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'start_time',
        'end_time', 
        'max_capacity',
        'current_attendees',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function hasAvailableCapacity(): bool
    {
        return $this->current_attendees < $this->max_capacity;
    }

    public function toTimezone(string $timezone): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'location' => $this->location,
            'start_time' => $this->start_time->setTimezone($timezone)->toISOString(),
            'end_time' => $this->end_time->setTimezone($timezone)->toISOString(),
            'max_capacity' => $this->max_capacity,
            'current_attendees' => $this->current_attendees,
            'available_capacity' => $this->max_capacity - $this->current_attendees,
            'created_at' => $this->created_at->setTimezone($timezone)->toISOString(),
            'updated_at' => $this->updated_at->setTimezone($timezone)->toISOString(),
        ];
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('start_time', '>', now());
    }

    public function scopeWithAvailableSeats(Builder $query): Builder
    {
        return $query->whereRaw('max_capacity > current_attendees');
    }

    public function scopeLocationIn(Builder $query, array $locations): Builder
    {
        return $query->whereIn('location', $locations);
    }

    public function scopeSearch(Builder $query, string $searchTerm, array $fields): Builder
    {
        return $query->where(function ($q) use ($searchTerm, $fields) {
            foreach ($fields as $field) {
                if ($field === 'start_time' || $field === 'end_time') {
                    try {
                        $searchDate = Carbon::parse($searchTerm)->format('Y-m-d');
                        $q->orWhereDate($field, $searchDate);
                    } catch (\Exception $e) {
                        continue;
                    }
                } elseif (in_array($field, ['max_capacity', 'current_attendees'])) {
                    if (is_numeric($searchTerm)) {
                        $q->orWhere($field, $searchTerm);
                    }
                } else {
                    $q->orWhere($field, 'ILIKE', '%' . $searchTerm . '%');
                }
            }
        });
    }

    // Add this method to the Event model class
public function attendees()
{
    return $this->hasMany(Attendee::class);
}
}