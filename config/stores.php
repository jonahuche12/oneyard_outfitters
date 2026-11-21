<?php

return [

    /*
    | Number of market days (days with an opening balance entered) without a
    | submitted remission before a store is flagged.
    */
    'remittance_flag_days' => (int) env('STORE_REMITTANCE_FLAG_DAYS', 2),

    /*
    | Remission tracking counts only days on or after this date (Y-m-d).
    | Set it to the deployment date. Null means no cut-over filter.
    */
    'remittance_cutover_date' => env('STORE_REMITTANCE_CUTOVER_DATE'),

];
