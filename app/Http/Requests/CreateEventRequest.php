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
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'max_capacity' => 'required|integer|min:1|max:10000',
            'timezone' => 'required|string|timezone',
        ];
    }

    public function messages(): array
    {
        return [
            'start_time.after' => 'Start time must be in the future in the specified timezone.',
            'end_time.after' => 'End time must be after start time.',
            'max_capacity.min' => 'Maximum capacity must be at least 1.',
            'max_capacity.max' => 'Maximum capacity cannot exceed 10000.',
            'timezone.required' => 'Timezone is required.',
            'timezone.timezone' => 'The timezone must be a valid timezone identifier.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // REMOVE UTC conversion from here - NO CONVERSION IN prepareForValidation
        // Just do basic parsing if needed, but no timezone conversion
        if ($this->has('start_time') && !empty($this->start_time)) {
            $this->merge([
                'start_time' => Carbon::parse($this->start_time)->format('Y-m-d H:i:s'),
            ]);
        }

        if ($this->has('end_time') && !empty($this->end_time)) {
            $this->merge([
                'end_time' => Carbon::parse($this->end_time)->format('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Custom validation for timezone-aware date validation
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $timezone = $this->input('timezone');
            $startTime = $this->input('start_time');
            $endTime = $this->input('end_time');

            // Validate timezone first
            if (!in_array($timezone, DateTimeZone::listIdentifiers())) {
                $validator->errors()->add('timezone', 'The selected timezone is invalid.');
                return;
            }

            try {
                // Parse times in the specified timezone for validation
                $startTimeInTz = Carbon::parse($startTime)->setTimezone($timezone);
                $endTimeInTz = Carbon::parse($endTime)->setTimezone($timezone);
                $nowInTz = Carbon::now($timezone);

                // Validate start time is in the future IN THE SPECIFIED TIMEZONE
                if ($startTimeInTz->lte($nowInTz)) {
                    $validator->errors()->add('start_time', 
                        "Start time must be in the future in the specified timezone. " .
                        "Current time in $timezone is " . $nowInTz->format('Y-m-d H:i:s') .
                        ", your start time is " . $startTimeInTz->format('Y-m-d H:i:s')
                    );
                }

                // Validate end time is after start time
                if ($endTimeInTz->lte($startTimeInTz)) {
                    $validator->errors()->add('end_time', 
                        'End time must be after start time in the specified timezone.'
                    );
                }

            } catch (\Exception $e) {
                $validator->errors()->add('start_time', 'Failed to parse date times with the specified timezone.');
            }
        });
    }

    /**
     * Convert to UTC after validation passes - SINGLE CONVERSION POINT
     */
    public function getValidatedData(): array
    {
        $validated = parent::validated();
        $timezone = $validated['timezone'];

        \Log::info('Single UTC Conversion - Starting', [
            'original_start' => $validated['start_time'],
            'original_end' => $validated['end_time'],
            'timezone' => $timezone
        ]);

        // Convert times to UTC for storage - ONLY ONCE
        $validated['start_time'] = $this->convertToUTC($validated['start_time'], $timezone);
        $validated['end_time'] = $this->convertToUTC($validated['end_time'], $timezone);

        \Log::info('Single UTC Conversion - Completed', [
            'converted_start' => $validated['start_time'],
            'converted_end' => $validated['end_time']
        ]);

        return $validated;
    }

    private function convertToUTC(string $dateTime, string $timezone): string
    {
        try {
            \Log::info('UTC Conversion - Using createFromFormat', [
                'input' => $dateTime,
                'timezone' => $timezone
            ]);

            // USE createFromFormat with explicit timezone
            $carbon = Carbon::createFromFormat('Y-m-d H:i:s', $dateTime, $timezone);
            
            $converted = $carbon->setTimezone('UTC')->format('Y-m-d H:i:s');

            \Log::info('UTC Conversion - Result', [
                'input' => $dateTime . ' ' . $timezone,
                'output' => $converted . ' UTC',
                'method' => 'createFromFormat'
            ]);

            return $converted;
            
        } catch (\Exception $e) {
            \Log::error('UTC conversion failed', [
                'datetime' => $dateTime,
                'timezone' => $timezone,
                'error' => $e->getMessage()
            ]);
            
            return $dateTime;
        }
    }

    // Remove these extra methods - they're not needed
    // public static function convertToUTC2() and public static function testConversionFlow()
}