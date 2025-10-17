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
 *     @OA\Property(property="start_time", type="string", format="date-time", example="2024-12-01T09:00:00Z"),
 *     @OA\Property(property="end_time", type="string", format="date-time", example="2024-12-01T17:00:00Z"),
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
        // Simply parse and format the datetime without timezone conversion
        // Let Laravel handle UTC storage automatically
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
}