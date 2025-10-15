<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

/**
 * @OA\Schema(
 *     schema="CreateEventRequest",
 *     required={"name", "location", "start_time", "end_time", "max_capacity"},
 *     @OA\Property(property="name", type="string", maxLength=255, example="Tech Conference 2024"),
 *     @OA\Property(property="location", type="string", maxLength=255, example="Bangalore Convention Center"),
 *     @OA\Property(property="start_time", type="string", format="date-time", example="2024-12-01 09:00:00"),
 *     @OA\Property(property="end_time", type="string", format="date-time", example="2024-12-01 17:00:00"),
 *     @OA\Property(property="max_capacity", type="integer", minimum=1, maximum=10000, example=100)
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
        ];
    }

    public function messages(): array
    {
        return [
            'start_time.after' => 'Start time must be in the future.',
            'end_time.after' => 'End time must be after start time.',
            'max_capacity.min' => 'Maximum capacity must be at least 1.',
            'max_capacity.max' => 'Maximum capacity cannot exceed 10000.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('start_time') && !empty($this->start_time)) {
            $this->merge([
                'start_time' => $this->convertToIST($this->start_time),
            ]);
        }

        if ($this->has('end_time') && !empty($this->end_time)) {
            $this->merge([
                'end_time' => $this->convertToIST($this->end_time),
            ]);
        }
    }

    private function convertToIST(string $dateTime): string
    {
        try {
            return Carbon::parse($dateTime)
                ->setTimezone('Asia/Kolkata')
                ->toDateTimeString();
        } catch (\Exception $e) {
            return $dateTime;
        }
    }
}