<?php

namespace App\Http\Requests;

use App\Models\Property;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StorePropertyImageRequest extends FormRequest
{
    public const MAX_IMAGES_PER_PROPERTY = 20;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('property'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Property $property */
                $property = $this->route('property');
                $total = $property->images()->count() + count((array) $this->file('images', []));

                if ($total > self::MAX_IMAGES_PER_PROPERTY) {
                    $validator->errors()->add(
                        'images',
                        'A property can have at most '.self::MAX_IMAGES_PER_PROPERTY.' images.'
                    );
                }
            },
        ];
    }
}
