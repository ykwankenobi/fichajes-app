<?php

$configuredPath = trim((string) env('KIOSK_PATH', ''), '/');

return [
    /*
     * Every installation gets a different, non-obvious URL derived from APP_KEY.
     * KIOSK_PATH may be set when a company needs to choose its own path.
     */
    'path' => $configuredPath !== ''
        ? $configuredPath
        : 'terminal-'.substr(hash_hmac('sha256', 'work-time-kiosk', (string) env('APP_KEY')), 0, 32),
];
