<?php

declare(strict_types=1);

// Copyright (c) 2026 Fedaa Mohamed. All rights reserved.

namespace App\Http\Requests;

use App\Domain\SiteCheck\Dto\ScanInput;
use App\Support\Network\UnsafeUrlException;
use App\Support\Network\UrlNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class ScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'target' => ['required', 'string', 'max:2048'],
            'dkim_selectors' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $target = $this->input('target');
        if (is_string($target)) {
            $this->merge(['target' => trim($target)]);
        }

        $selectors = $this->input('dkim_selectors');
        if (is_string($selectors)) {
            $this->merge(['dkim_selectors' => trim($selectors)]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $validator->errors()->has('target')) {
                $target = (string) $this->input('target');

                try {
                    (new UrlNormalizer)->normalize($target);
                } catch (UnsafeUrlException $exception) {
                    $validator->errors()->add('target', $this->urlError($exception->reason));
                }
            }

            foreach ($this->rawSelectors() as $selector) {
                if (preg_match('/^[a-z0-9]([a-z0-9-]{0,62})$/i', $selector) !== 1) {
                    $validator->errors()->add('dkim_selectors', __('sitecheck.errors.dkim_selector'));

                    return;
                }
            }
        });
    }

    public function scanInput(): ScanInput
    {
        return new ScanInput((string) $this->input('target'), $this->rawSelectors());
    }

    /** @return list<string> */
    public function rawSelectors(): array
    {
        $raw = (string) $this->input('dkim_selectors', '');
        if (trim($raw) === '') {
            return [];
        }

        $parts = preg_split('/[\s,]+/', $raw) ?: [];
        $selectors = [];
        foreach ($parts as $part) {
            $part = strtolower(trim($part));
            if ($part !== '') {
                $selectors[$part] = true;
            }
        }

        return array_slice(array_keys($selectors), 0, 5);
    }

    private function urlError(string $reason): string
    {
        $key = 'sitecheck.errors.url.'.$reason;

        return trans()->has($key) ? (string) __($key) : (string) __('sitecheck.errors.url.generic');
    }
}
