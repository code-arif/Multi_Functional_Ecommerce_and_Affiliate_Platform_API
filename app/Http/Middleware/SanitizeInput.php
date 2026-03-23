<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * SanitizeInput Middleware
 *
 * Recursively strips dangerous HTML/script content from all
 * incoming request inputs before they reach controllers.
 *
 * Skipped fields:
 *   - password / password_confirmation (must not be modified)
 *   - description / content           (CMS fields allow rich HTML — validated separately)
 */
class SanitizeInput
{
    /**
     * Input keys that should NOT be sanitized (raw content allowed).
     */
    private array $except = [
        'password',
        'password_confirmation',
        'current_password',
    ];

    /**
     * Input keys that allow limited HTML (CMS / product description).
     */
    private array $allowHtml = [
        'description',
        'content',
        'body',
        'short_description',
    ];

    public function handle(Request $request, Closure $next)
    {
        // Sanitize query params
        $this->clean($request->query);

        // Sanitize request body
        $this->clean($request->request);

        return $next($request);
    }

    private function clean(\Symfony\Component\HttpFoundation\InputBag $bag): void
    {
        $cleaned = $this->sanitizeArray($bag->all());
        $bag->replace($cleaned);
    }

    private function sanitizeArray(array $data, string $prefix = ''): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            // Skip protected fields
            if (in_array($key, $this->except)) {
                $result[$key] = $value;
                continue;
            }

            if (is_array($value)) {
                $result[$key] = $this->sanitizeArray($value, $fullKey);
            } elseif (is_string($value)) {
                // Fields that allow limited HTML get strip_tags with allowed tags
                if (in_array($key, $this->allowHtml)) {
                    $result[$key] = $this->sanitizeHtml($value);
                } else {
                    $result[$key] = $this->sanitizeString($value);
                }
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Strip all HTML and encode entities — for plain text fields.
     */
    private function sanitizeString(string $value): string
    {
        // Remove null bytes
        $value = str_replace("\0", '', $value);

        // Strip all HTML tags
        $value = strip_tags($value);

        // Convert special chars to HTML entities to prevent XSS
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);

        // Decode once so legitimate chars (é, ñ, etc.) are preserved
        $value = htmlspecialchars_decode($value, ENT_QUOTES | ENT_HTML5);

        return trim($value);
    }

    /**
     * Strip dangerous tags but allow safe HTML for CMS fields.
     */
    private function sanitizeHtml(string $value): string
    {
        // Remove null bytes
        $value = str_replace("\0", '', $value);

        // Allow safe HTML tags only
        $allowedTags = '<p><br><b><i><u><strong><em><ul><ol><li><h1><h2><h3><h4><a><img><blockquote><code><pre><table><thead><tbody><tr><td><th><span><div>';

        $value = strip_tags($value, $allowedTags);

        // Remove dangerous attributes (onclick, onerror, javascript:, etc.)
        $value = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $value);
        $value = preg_replace('/\s*javascript\s*:/i', '', $value);
        $value = preg_replace('/\s*vbscript\s*:/i', '', $value);
        $value = preg_replace('/\s*data\s*:/i', '', $value);

        return $value;
    }
}