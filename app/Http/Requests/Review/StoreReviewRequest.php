<?php

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                'integer',
                'exists:products,id',
                // One review per product per user (per order)
                Rule::unique('reviews')->where(function ($query) {
                    return $query->where('user_id', $this->user()->id)
                        ->where('order_id', $this->order_id);
                }),
            ],
            'order_id'   => 'nullable|integer|exists:orders,id',
            'rating'     => 'required|integer|min:1|max:5',
            'title'      => 'nullable|string|min:3|max:100',
            'body'       => 'nullable|string|min:10|max:2000',
            'images'     => 'nullable|array|max:3',
            'images.*'   => 'image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.unique' => 'You have already reviewed this product for this order.',
            'rating.min'        => 'Rating must be at least 1 star.',
            'rating.max'        => 'Rating cannot exceed 5 stars.',
            'body.min'          => 'Review body must be at least 10 characters.',
        ];
    }
}
