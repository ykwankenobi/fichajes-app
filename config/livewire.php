<?php

return [
    // Serve the JavaScript from a static public path so CloudPanel/Nginx does
    // not mistake Livewire's hashed .js route for a missing file.
    'asset_url' => env('LIVEWIRE_ASSET_URL'),
];
