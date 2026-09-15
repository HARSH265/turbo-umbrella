<?php

/**
 * config/pagination.php
 *
 * Single source of truth for list page sizes.
 *
 * Listings previously hard-coded 5, 15 or 20 per page independently, so Maintenance
 * paged every 5 rows while Vehicles showed no pager until row 21. Change `per_page`
 * here to move every listing at once.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Rows Per Page
    |--------------------------------------------------------------------------
    | Used by every index/listing screen (complaints, maintenance, notices,
    | visitors, vehicles, amenities, flats, users, societies, towers, logs).
    */
    'per_page' => (int) env('PAGINATION_PER_PAGE', 15),

    /*
    |--------------------------------------------------------------------------
    | Dashboard Widget Rows
    |--------------------------------------------------------------------------
    | Dashboard panels are summaries, not full listings, so they stay short.
    */
    'dashboard_per_page' => (int) env('PAGINATION_DASHBOARD_PER_PAGE', 5),

];
