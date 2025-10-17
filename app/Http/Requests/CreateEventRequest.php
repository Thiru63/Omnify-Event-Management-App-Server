<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;
use DateTimeZone;

/**
 * @OA\Schema(
 *     schema="CreateEventRequest",
 *     required={"name", "location", "start_time", "end_time", "max_capacity", "timezone"},
 *     @OA\Property(property="name", type="string", maxLength=255, example="Tech Conference 2025"),
 *     @OA\Property(property="location", type="string", maxLength=255, example="Bangalore Convention Center"),
 *     @OA\Property(property="start_time", type="string", format="date-time", example="2025-12-01 09:00:00"),
 *     @OA\Property(property="end_time", type="string", format="date-time", example="2025-12-01 17:00:00"),
 *     @OA\Property(property="max_capacity", type="integer", minimum=1, maximum=10000, example=100),
 *     @OA\Property(
 *         property="timezone",
 *         type="string",
 *         description="Timezone identifier (e.g., Asia/Kolkata, America/New_York)",
 *         example="Asia/Kolkata"
 *     )
 * )
 */
class CreateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'max_capacity' => 'required|integer|min:1|max:10000',
            'timezone' => 'required|string|timezone', // Laravel's timezone validation rule
        ];
    }

    public function messages(): array
    {
        return [
            'start_time.after' => 'Start time must be in the future.',
            'end_time.after' => 'End time must be after start time.',
            'max_capacity.min' => 'Maximum capacity must be at least 1.',
            'max_capacity.max' => 'Maximum capacity cannot exceed 10000.',
            'timezone.required' => 'Timezone is required.',
            'timezone.timezone' => 'The timezone must be a valid timezone identifier.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('start_time') && !empty($this->start_time)) {
            $this->merge([
                'start_time' => $this->convertToUTC(
                    $this->start_time, 
                    $this->input('timezone', 'UTC')
                ),
            ]);
        }

        if ($this->has('end_time') && !empty($this->end_time)) {
            $this->merge([
                'end_time' => $this->convertToUTC(
                    $this->end_time,
                    $this->input('timezone', 'UTC')
                ),
            ]);
        }
    }

    private function convertToUTC(string $dateTime, string $timezone): string
    {
        try {
            // Parse the datetime in the specified timezone
            $carbon = Carbon::parse($dateTime);
            
            // If no timezone in the datetime string, set the input timezone
            if (!$carbon->timezone || $carbon->timezone->getName() === 'UTC') {
                $carbon->setTimezone($timezone);
            }
            
            // Convert to UTC for database storage
            return $carbon->setTimezone('UTC')->format('Y-m-d H:i:s');
            
        } catch (\Exception $e) {
            // If parsing fails, return original (validation will catch it)
            return $dateTime;
        }
    }

    /**
     * Custom validation for timezone and time consistency
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->has('start_time') || !$this->has('timezone')) {
                return;
            }

            // Validate that the timezone is valid
            if (!in_array($this->timezone, DateTimeZone::listIdentifiers())) {
                $validator->errors()->add('timezone', 'The selected timezone is invalid.');
                return;
            }

            // Additional validation: Check if times make sense in the given timezone
            try {
                $startTime = Carbon::parse($this->start_time)->setTimezone($this->timezone);
                $endTime = Carbon::parse($this->end_time)->setTimezone($this->timezone);
                
                // Check if start time is in the future in the specified timezone
                $nowInTimezone = Carbon::now($this->timezone);
                if ($startTime->lte($nowInTimezone)) {
                    $validator->errors()->add('start_time', 'Start time must be in the future in the specified timezone.');
                }
                
                // Check if end time is after start time
                if ($endTime->lte($startTime)) {
                    $validator->errors()->add('end_time', 'End time must be after start time in the specified timezone.');
                }
                
            } catch (\Exception $e) {
                $validator->errors()->add('timezone', 'Failed to validate times with the specified timezone.');
            }
        });
    }
}