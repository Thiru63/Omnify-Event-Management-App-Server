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
 *     @OA\Property(property="name", type="string", maxLength=255, example="Tech Conference 2025"),
 *     @OA\Property(property="location", type="string", maxLength=255, example="Bangalore Convention Center"),
 *     @OA\Property(property="start_time", type="string", format="date-time", example="2025-12-01T09:00:00.000000Z"),
 *     @OA\Property(property="end_time", type="string", format="date-time", example="2025-12-01T17:00:00.000000Z"),
 *     @OA\Property(property="max_capacity", type="integer", format="int32", example=100),
 *     @OA\Property(property="current_attendees", type="integer", format="int32", example=0),
 *     @OA\Property(property="available_capacity", type="integer", format="int32", example=100),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-15T10:30:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-01-15T10:30:00.000000Z")
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
    \Log::info('Timezone Conversion Debug', [
        'event_id' => $this->id,
        'stored_start_time' => $this->start_time,
        'stored_end_time' => $this->end_time,
        'requested_timezone' => $timezone
    ]);

    // Method 1: Direct conversion from stored UTC times
    $startTimeUTC = Carbon::parse($this->start_time)->setTimezone('UTC');
    $endTimeUTC = Carbon::parse($this->end_time)->setTimezone('UTC');
    
    $startTimeLocal = $startTimeUTC->copy()->setTimezone($timezone);
    $endTimeLocal = $endTimeUTC->copy()->setTimezone($timezone);

    // Method 2: Alternative approach - ensure we're starting from UTC
    $startTimeAlt = Carbon::createFromFormat('Y-m-d H:i:s', $this->start_time, 'UTC')
        ->setTimezone($timezone);
    $endTimeAlt = Carbon::createFromFormat('Y-m-d H:i:s', $this->end_time, 'UTC')
        ->setTimezone($timezone);

    \Log::info('Timezone Conversion Results', [
        'method_1_start' => $startTimeLocal->format('Y-m-d H:i:s P'),
        'method_1_end' => $endTimeLocal->format('Y-m-d H:i:s P'),
        'method_2_start' => $startTimeAlt->format('Y-m-d H:i:s P'),
        'method_2_end' => $endTimeAlt->format('Y-m-d H:i:s P'),
        'stored_times_are_utc' => $this->start_time === $startTimeUTC->format('Y-m-d H:i:s')
    ]);

    return [
        'id' => $this->id,
        'name' => $this->name,
        'location' => $this->location,
        
        // Use method 2 for more explicit UTC handling
        'start_time' => $startTimeAlt->format('c'),
        'end_time' => $endTimeAlt->format('c'),
        
        'start_time_display' => $startTimeAlt->format('D, M j, Y g:i A'),
        'end_time_display' => $endTimeAlt->format('D, M j, Y g:i A'),
        
        'start_time_local' => $startTimeAlt->format('Y-m-d H:i:s'),
        'end_time_local' => $endTimeAlt->format('Y-m-d H:i:s'),
        
        'max_capacity' => $this->max_capacity,
        'current_attendees' => $this->current_attendees,
        'available_capacity' => $this->max_capacity - $this->current_attendees,
        
        'created_at' => $this->created_at->setTimezone($timezone)->format('c'),
        'updated_at' => $this->updated_at->setTimezone($timezone)->format('c'),
        
        'timezone' => $timezone,
        'timezone_offset' => $startTimeAlt->format('P'),
        
        // Debug info
        '_debug' => [
            'stored_start_utc' => $this->start_time,
            'stored_end_utc' => $this->end_time,
            'conversion_applied' => 'UTC → ' . $timezone,
            'expected_input_time' => '2025-10-17 14:36:00 IST'
        ]
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
        $connection = config('database.default');
        
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
                // Database-agnostic case-insensitive search
                $this->addCaseInsensitiveSearch($q, $field, $searchTerm, $connection);
            }
        }
    });
}

/**
 * Add case-insensitive search based on database type
 */
private function addCaseInsensitiveSearch($query, string $field, string $searchTerm, string $connection): void
{
    $searchTerm = strtolower($searchTerm);
    
    switch ($connection) {
        case 'sqlite':
            // SQLite: Use LOWER() function
            $query->orWhereRaw('LOWER(' . $field . ') LIKE ?', ['%' . $searchTerm . '%']);
            break;
            
        case 'mysql':
            // MySQL: Use LOWER() or COLLATE for case-insensitive search
            $query->orWhereRaw('LOWER(' . $field . ') LIKE ?', ['%' . $searchTerm . '%']);
            break;
            
        case 'pgsql':
            // PostgreSQL: Use ILIKE (case-insensitive)
            $query->orWhere($field, 'ILIKE', '%' . $searchTerm . '%');
            break;
            
        default:
            // Fallback: Use LIKE (may be case-sensitive depending on database)
            $query->orWhere($field, 'LIKE', '%' . $searchTerm . '%');
            break;
    }
}

    // Add this method to the Event model class
public function attendees()
{
    return $this->hasMany(Attendee::class);
}
}