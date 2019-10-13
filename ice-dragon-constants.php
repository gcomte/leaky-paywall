<?php

class IceDragonConstants {

    // Basically the plugin's database.
    // All Ice Dragon data is serialized and stored within one single row of the table 'wp_options'.
    // This constant stores the tag that's being used in the field wp_options.option_name .
    const DB_STORAGE_KEY = 'ice-dragon-paywall';

    // Under what URL the plugin is located ==> adminphph?page=[name here]
    const TOP_LEVEL_PAGE_NAME = 'ice-dragon-lightning-paywall';


    const SETTINGS_KEY_HMAC_SECRET = 'ice_dragon_secret';
}