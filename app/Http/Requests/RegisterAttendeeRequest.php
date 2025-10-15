<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="RegisterAttendeeRequest",
 *     required={"name", "email"},
 *     @OA\Property(property="name", type="string", maxLength=255, example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", maxLength=255, example="john@example.com")
 * )
 */
class RegisterAttendeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $eventId = $this->route('event_id');

        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('attendees')->where(function ($query) use ($eventId) {
                    return $query->where('event_id', $eventId);
                })
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already registered for the event.',
            'name.required' => 'Attendee name is required.',
            'email.required' => 'Attendee email is required.',
            'email.email' => 'Please provide a valid email address.',
        ];
    }
}