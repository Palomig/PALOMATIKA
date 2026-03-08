<?php

return [
    // Stable option-id format in miniapp task payloads.
    'stable_option_ids' => env('FEATURE_STABLE_OPTION_IDS', true),

    // Shuffle options for choice-like tasks in miniapp test UI.
    // Keep disabled by default for controlled rollout.
    'shuffle_options' => env('FEATURE_SHUFFLE_OPTIONS', false),
];
