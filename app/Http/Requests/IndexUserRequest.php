<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class IndexUserRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    return Gate::allows('manage_users');
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    return [
      'search' => 'nullable|string',
      'sort_by' => 'nullable|in:name,email,created_at',
      'sort_dir' => 'nullable|in:asc,desc',
      'per_page' => 'nullable|integer|min:1|max:100',
      'page' => 'nullable|integer|min:1',
    ];
  }

  public function messages()
  {
    return [
      'sort_by.in' => 'The sort_by field must be one of name, email, or created_at.',
      'sort_dir.in' => 'The sort_dir field must be either asc or desc.',
    ];
  }
}
