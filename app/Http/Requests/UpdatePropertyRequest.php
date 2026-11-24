<?php

namespace App\Http\Requests;

class UpdatePropertyRequest extends StorePropertyRequest
{
    /**
     * Same rules as creation, but every field is optional on update.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return collect(parent::rules())
            ->map(fn (array $rules) => ['sometimes', ...array_diff($rules, ['required', 'sometimes'])])
            ->all();
    }
}
