<?php

namespace Mkhodroo\AltfuelTicket\Requests;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Mkhodroo\AltfuelTicket\Models\TicketCatagory;

class TicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        if($this->file('files')){
            foreach ($this->file('files') as $file) {
                if ($file &&  $file->getSize() >= config('ATConfig.max-attach-file-size') * 1024) {
                    throw ValidationException::withMessages([
                        'title' => "حجم فایل بیش از مقدار مجاز است. مقدار مجاز: " . config('ATConfig.max-attach-file-size') . "KB",
                    ]);
                }
                if ($file && !in_array($file->getClientMimeType(), config('ATConfig.attachment-file-types'))) {
                    throw ValidationException::withMessages([
                        'title' => "فایل پشتیبانی نمیشود. فایل های مجاز: " . implode(' یا ', config('ATConfig.attachment-file-types-translate')),
                    ]);
                }
            }
        }
        
        if (!$this->input('text') and !$this->file('payload')) {
            throw ValidationException::withMessages([
                'title' => "متن یا صدا را تکمیل کنید",
            ]);
        }

        if (!$this->input('ticket_id')) {
            Log::info($this->input('ticket_id'));
            $conversionRules = $this->conversionTypeRules();
            $rules = [
                'catagory' => 'required|integer',
                'title' => 'required|string',
            ];
            if (!empty($conversionRules)) {
                $rules['conversion_type'] = $conversionRules;
            }
            return $rules;
        }
        return [];
    }

    public function messages()
    {
        return [
            'catagory.required' => "لطفا دسته بندی را انتخاب کنید",
            'title.required' => "لطفا عنوان را وارد کنید",
            'conversion_type.required' => "لطفا نوع تبدیل را انتخاب کنید",
            'conversion_type.in' => "نوع تبدیل انتخاب شده معتبر نیست",
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate()
    {
    }

    private function conversionTypeRules(): array
    {
        $types = array_keys(config('ATConfig.conversion_types', []));
        if (empty($types)) {
            return [];
        }

        $categoryId = $this->input('catagory');
        $category = $categoryId ? TicketCatagory::find($categoryId) : null;

        $rules = [];
        if ($category && $category->conversion_type_enabled && $category->conversion_type_required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        $rules[] = Rule::in($types);

        return $rules;
    }
}
