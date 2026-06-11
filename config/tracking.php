<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Location Collection Intervals
    |--------------------------------------------------------------------------
    |
    | Configure how frequently location data is collected based on rider state.
    | Times are in seconds.
    |
    */

    'collection_intervals' => [
        // Standard collection: Every 2 minutes (balanced accuracy & battery)
        'default' => env('TRACKING_INTERVAL_DEFAULT', 120), // 2 minutes

        // When rider is stationary (speed < 0.5 m/s)
        'stationary' => env('TRACKING_INTERVAL_STATIONARY', 300), // 5 minutes

        // When rider is near installation (within 500m)
        'near_installation' => env('TRACKING_INTERVAL_NEAR_INSTALLATION', 60), // 1 minute
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention Policy
    |--------------------------------------------------------------------------
    |
    | Configure how long to keep detailed location data before aggregation.
    | Days are counted from the recorded_at timestamp.
    |
    */

    'retention' => [
        // Keep data for 3 calendar months only (current + 2 previous)
        'keep_months' => env('RETENTION_KEEP_MONTHS', 3),

        // Delete data older than 3 months
        'auto_delete_enabled' => env('RETENTION_AUTO_DELETE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Automatic Cleanup Schedule
    |--------------------------------------------------------------------------
    |
    | Enable/disable automatic data aggregation and cleanup.
    |
    */

    'auto_cleanup' => [
        'enabled' => env('AUTO_CLEANUP_ENABLED', true),

        // Run daily aggregation at 2 AM
        'daily_aggregation_time' => '02:00',

        // Run weekly archival on Sunday at 3 AM
        'weekly_archival_time' => '03:00',
        'weekly_archival_day' => 'sunday',

        // Run monthly cleanup on 1st of month at 4 AM
        'monthly_cleanup_time' => '04:00',
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Optimization
    |--------------------------------------------------------------------------
    |
    | Settings to optimize database performance.
    |
    */

    'optimization' => [
        // Batch size for bulk operations
        'batch_size' => 1000,

        // Enable query caching for historical data
        'cache_enabled' => true,
        'cache_ttl' => 3600, // 1 hour

        // Index refresh frequency (in days)
        'index_refresh_days' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Accuracy Thresholds
    |--------------------------------------------------------------------------
    |
    | Quality control for location data.
    |
    */

    'accuracy' => [
        // Maximum acceptable GPS accuracy in meters
        'max_accuracy' => 100,

        // Minimum speed to consider as "moving" (m/s)
        'moving_threshold' => 1.0,

        // Maximum realistic speed (m/s) - 150 km/h
        'max_speed' => 41.7,

        // Minimum distance between points to record (meters)
        'min_distance' => 10,
    ],
];
