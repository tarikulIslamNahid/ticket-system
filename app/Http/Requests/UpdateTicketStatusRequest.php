<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([Ticket::STATUS_OPEN, Ticket::STATUS_CLOSED])],
        ];
    }
}
