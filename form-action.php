<?php
    // Process form submission and determine appropriate Weather Icon class

    require "./functions.php";

    // An environment variable "ONPOINT_KEY" must be defined with a valid OnPoint API key.
    // To acquire an OnPoint API key, visit https://developer.weathersource.com/#developer-account-sign-up
    $apiKey = $_ENV['ONPOINT_KEY'];

    // retreive latitude and longitude from form submission
    $country  = "";
    $postcode = "";
    if (array_key_exists('country', $_GET)) {
        $country = $_GET['country'];
    }
    if (array_key_exists('postcode', $_GET)) {
        $postcode = $_GET['postcode'];
    }

    // if lat/lng provided, retrieve current weather from Weather Source API
    $err        = "";
    $iconClass  = "";
    $conditions = "";
    if (!empty($country) && !empty($postcode) && !empty($apiKey)) {

        $timestamp   = gmdate('c'); // Use the current ISO 8601 timestamp for lookup.
        $countryEnc  = rawurlencode($country);
        $postcodeEnc = rawurlencode($postcode);

        // NOTE: we could use the nowcast resource, but then we would not get a magnitude for precipation, only a precip flag. Having a magnitude (i.e. inches per hour), allows us to be more descriptive with the icons.
        $historyUrl   = "https://api.weathersource.com/v1/{$apiKey}/postal_codes/{$postcodeEnc},{$countryEnc}/history.json?timestamp_eq={$timestamp}&period=hour";
        $astronomyUrl = "https://api.weathersource.com/v1/{$apiKey}/postal_codes/{$postcodeEnc},{$countryEnc}/astronomy.json?timestamp_eq={$timestamp}&period=day";

        $wx = onPointApiCall($historyUrl);

        // if $wx is a string, it is an error message
        if (is_string($wx)) {
            $err = $wx;
        } else {

            $astro = onPointApiCall($astronomyUrl);

            // if $astro is a string, it is an error message
            if (is_string($astro)) {
                $err = $astro;
            } else {
                $iconClass  = getIconClass($wx, $astro, $timestamp);
                $conditions = ucfirst(getClouds($wx) . ", " . getPrecip($wx) . ", " . getWind($wx) . ", " . getDayPhase($astro, $timestamp) . ".");
            }
        }
    }
