<?php

namespace Tests\Support {
    use CurlHandle;

    /**
     * Stands in for the network behind the legacy cURL controllers. While armed it records what
     * each request would carry and answers curl_exec() from a script, so a test can check which
     * credential a request holds without anything leaving the machine.
     *
     * The controllers call curl_init(), curl_setopt(), curl_setopt_array() and curl_exec()
     * unqualified, and PHP resolves such a call in the caller's namespace before the global one.
     * The functions below, in App\Http\Controllers and App\Http\Controllers\API, therefore see
     * those calls once this file is loaded. Handles stay real cURL handles and every option still
     * reaches cURL; only curl_exec() is answered here, and only while armed. Disarmed, every call
     * passes straight through to cURL.
     *
     * Load this file with require_once at the top of a test file. PHPUnit reads every test file
     * before it runs a test, so no controller call site is bound to the global function first.
     */
    final class CurlRecorder
    {
        /** @var list<array{url: mixed, method: mixed, fields: mixed, headers: list<mixed>, options: array<int, mixed>}> */
        public static array $requests = [];

        /** @var array<int, array<int, mixed>> options set on each open handle, by handle id */
        private static array $options = [];

        /** @var list<string|false> */
        private static array $answers = [];

        private static bool $armed = false;

        /** @param  list<string|false>  $answers  one per curl_exec(), in order */
        public static function arm(array $answers): void
        {
            self::$armed = true;
            self::$answers = $answers;
            self::$requests = [];
            self::$options = [];
        }

        public static function disarm(): void
        {
            self::$armed = false;
            self::$answers = [];
            self::$options = [];
        }

        public static function armed(): bool
        {
            return self::$armed;
        }

        public static function opened(CurlHandle $handle, ?string $url): void
        {
            self::$options[spl_object_id($handle)] = $url === null ? [] : [CURLOPT_URL => $url];
        }

        public static function set(CurlHandle $handle, int $option, mixed $value): void
        {
            self::$options[spl_object_id($handle)][$option] = $value;
        }

        public static function answer(CurlHandle $handle): string|false
        {
            $id = spl_object_id($handle);
            $options = self::$options[$id] ?? [];
            unset(self::$options[$id]);

            self::$requests[] = [
                'url' => $options[CURLOPT_URL] ?? null,
                'method' => $options[CURLOPT_CUSTOMREQUEST] ?? null,
                'fields' => $options[CURLOPT_POSTFIELDS] ?? null,
                'headers' => array_values((array) ($options[CURLOPT_HTTPHEADER] ?? [])),
                'options' => $options,
            ];

            return array_shift(self::$answers) ?? false;
        }
    }
}

namespace App\Http\Controllers {
    use Tests\Support\CurlRecorder;

    function curl_init(?string $url = null)
    {
        $handle = \curl_init($url);

        if ($handle !== false && CurlRecorder::armed()) {
            CurlRecorder::opened($handle, $url);
        }

        return $handle;
    }

    function curl_setopt($handle, int $option, mixed $value): bool
    {
        if (CurlRecorder::armed()) {
            CurlRecorder::set($handle, $option, $value);
        }

        return \curl_setopt($handle, $option, $value);
    }

    function curl_setopt_array($handle, array $options): bool
    {
        if (CurlRecorder::armed()) {
            foreach ($options as $option => $value) {
                CurlRecorder::set($handle, $option, $value);
            }
        }

        return \curl_setopt_array($handle, $options);
    }

    function curl_exec($handle): string|bool
    {
        return CurlRecorder::armed() ? CurlRecorder::answer($handle) : \curl_exec($handle);
    }
}

namespace App\Http\Controllers\API {
    use Tests\Support\CurlRecorder;

    function curl_init(?string $url = null)
    {
        $handle = \curl_init($url);

        if ($handle !== false && CurlRecorder::armed()) {
            CurlRecorder::opened($handle, $url);
        }

        return $handle;
    }

    function curl_setopt($handle, int $option, mixed $value): bool
    {
        if (CurlRecorder::armed()) {
            CurlRecorder::set($handle, $option, $value);
        }

        return \curl_setopt($handle, $option, $value);
    }

    function curl_setopt_array($handle, array $options): bool
    {
        if (CurlRecorder::armed()) {
            foreach ($options as $option => $value) {
                CurlRecorder::set($handle, $option, $value);
            }
        }

        return \curl_setopt_array($handle, $options);
    }

    function curl_exec($handle): string|bool
    {
        return CurlRecorder::armed() ? CurlRecorder::answer($handle) : \curl_exec($handle);
    }
}
