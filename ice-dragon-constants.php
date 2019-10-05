<?php

// Basically the plugin's database.
// All Ice Dragon data is serialized and stored within one single row of the table 'wp_options'.
// This constant stores the tag that's being used in the field wp_options.option_name .
define('DB_STORAGE_KEY', 'ice-dragon-paywall');

// Under what URL the plugin is located ==> adminphph?page=[name here]
define('TOP_LEVEL_PAGE_NAME', 'ice-dragon-lightning-paywall');