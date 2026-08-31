<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Forwarding email domain
    |--------------------------------------------------------------------------
    |
    | Each user gets a generated forwarding address at this domain
    | (e.g. "abc123@fwd.flighttracker.app") that they can forward
    | confirmation emails to as an alternative to connecting Gmail/Outlook.
    |
    */

    'forwarding_domain' => env('FORWARDING_EMAIL_DOMAIN', 'fwd.flighttracker.app'),

    /*
    |--------------------------------------------------------------------------
    | Confidence review threshold
    |--------------------------------------------------------------------------
    |
    | Extractions from the parser with a confidence score below this value
    | are flagged with needs_review = true and surfaced via
    | GET /emails/pending-review instead of being silently trusted.
    |
    */

    'confidence_review_threshold' => (float) env('CONFIDENCE_REVIEW_THRESHOLD', 0.8),

    /*
    |--------------------------------------------------------------------------
    | Cross-language Redis queues
    |--------------------------------------------------------------------------
    |
    | The parse-job and trip-updates queues cross the Laravel/Python
    | boundary, so they carry plain JSON pushed/popped with raw Redis
    | list commands rather than Laravel's serialized queue jobs.
    |
    */

    'queues' => [
        'parse_jobs' => env('PARSE_JOBS_QUEUE_KEY', 'parse-jobs'),
        'trip_updates' => env('TRIP_UPDATES_QUEUE_KEY', 'trip-updates'),
    ],

];
