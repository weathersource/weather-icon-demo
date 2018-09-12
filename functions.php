<?php

/**
 * Query The OnPoint API and return the result.
 *
 * @param $url  string  The API request URL for the OnPoint API.
 *
 * @return mixed  If the API request is successful, an array of data is returned.
 *                Otherwise, the response is a plain-text string error message.
 */
function onPointApiCall($url) {
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
    ));
    $resp = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if (!$err) {
        $arr = json_decode($resp, true);

        if (array_key_exists("message", $arr)) {
            $r = $arr["message"];
        } else {
            $r = $arr[0]; // Use the first time period returned
        }
    } else {
        $r = $err;
    }

    return $r;
}

/**
 *  Evaluates provided weather and astronomy to identify a suitable Weather Icons icon.
 *  See https://erikflowers.github.io/weather-icons/ for weather icons.
 *
 *  @param $weatherResp  array  An array of weather parameters **for a single hour** as returned
 *                              by the OnPoint API history resource.
 *  @param $astroResp    array  OPTIONAL. An array of astronomy parameters **for a single day**
 *                              as returned by the OnPoint API astronomy resource. If omitted,
 *                              neutral icons will be used (i.e. agnostic to day or night)
 *  @param $timestamp   string  OPTIONAL. An ISO 8601 timestamp for the included data. This is
 *                              used to determine where the data falls within the day/night
 *                              cycle. If omitted, the current time is assumed.
 *
 *  @return  string  A class name for a Weather Icons icon that matches the provided weather
 *                   conditions.
 */
function getIconClass($weatherResp, $astroResp = null, $timestamp = null) {

    $clas = "";

    // extract plain text weather conditions from data
    $precip   = getPrecip($weatherResp);
    $clouds   = getClouds($weatherResp);
    $wind     = getWind($weatherResp);
    $dayPhase = getDayPhase($astroResp, $timestamp);

    // simplify day phase to just day and night
    if (in_array($dayPhase, array("night", "civil twilight", "nautical twilight", "astronomical twilight"))) {
        // use night icons
        $isNight = true;
    } elseif (in_array($dayPhase, array("day", "sunrise", "sunset"))) {
        // use day icons
        $isNight = false;
    } else {
        // use neutral icons
        $isNight = null;
    }

    // determine appropriate icon from provided conditions
    if ($precip === "") {
        if ($clouds === "") {
            if ($wind === "" || $wind === "light winds") {
                if ($isNight === true) {
                    $clas = "wi-night-clear";
                } elseif ($isNight === false) {
                    $clas = "wi-day-sunny";
                } elseif ($isNight === null) {
                    // there is no neutral "clear" icon, so use day icon as fallback
                    $clas = "wi-day-sunny";
                }
            } elseif ($wind === "winds" || $wind === "strong winds") {
                if ($wind === "winds") {
                    if ($isNight === true) {
                        // this should be "wi-night-light-wind", but this icon is missing
                        $clas = "wi-windy";
                    } elseif ($isNight === false) {
                        $clas = "wi-day-light-wind";
                    } elseif ($isNight === null) {
                        $clas = "wi-windy";
                    }
                } elseif ($wind === "strong winds") {
                    if ($isNight === true) {
                        // this should be "wi-night-windy", but this icon is missing
                        $clas = "wi-strong-wind";
                    } elseif ($isNight === false) {
                        $clas = "wi-day-windy";
                    } elseif ($isNight === null) {
                        $clas = "wi-strong-wind";
                    }
                }
            }
        } elseif ($clouds === "partly cloudy" || $clouds === "cloudy") {
            if ($wind === "" || $wind === "light winds") {
                if ($isNight === true) {
                    if ($clouds === "partly cloudy") {
                        $clas = "wi-night-alt-partly-cloudy";
                    } elseif ($clouds === "cloudy") {
                        $clas = "wi-night-alt-cloudy";
                    }
                } elseif ($isNight === false) {
                    if ($clouds === "partly cloudy") {
                        $clas = "wi-day-sunny-overcast";
                    } elseif ($clouds === "cloudy") {
                        $clas = "wi-day-cloudy";
                    }
                } elseif ($isNight === null) {
                    if ($clouds === "partly cloudy") {
                        $clas = "wi-cloud";
                    } elseif ($clouds === "cloudy") {
                        $clas = "wi-cloudy";
                    }
                }
            } elseif ($wind === "winds") {
                if ($isNight === true) {
                    $clas = "wi-night-alt-cloudy-windy";
                } elseif ($isNight === false) {
                    $clas = "wi-day-cloudy-windy";
                } elseif ($isNight === null) {
                    $clas = "wi-cloudy-windy";
                }
            } elseif ($wind === "strong winds") {
                if ($isNight === true) {
                    $clas = "wi-night-alt-cloudy-gusts";
                } elseif ($isNight === false) {
                    $clas = "wi-day-cloudy-gusts";
                } elseif ($isNight === null) {
                    $clas = "wi-cloudy-gusts";
                }
            }
        }
    } elseif (in_array($precip, array("snow", "sprinkles", "light rain", "rain", "showers"))) {
        // There are only meaningful wind icons for non-precipitation, so wind is not considered here

        // if it is cloudy, use neutral icon (i.e. no sun or moon in sky)
        if ($clouds === "cloudy") {
            if ($precip === "snow") {
                $clas = "wi-snow";
            } elseif ($precip === "sprinkles" || $precip === "light rain") {
                $clas = "wi-sprinkle";
            } elseif ($precip === "rain") {
                // this should be "wi-rain", but the icon appears swapped with "wi-showers"
                $clas = "wi-showers";
            } elseif ($precip === "showers") {
                // this should be "wi-showers", but the icon appears swapped with "wi-rain"
                $clas = "wi-rain";
            }
        } else {
            if ($isNight === true) {
                if ($precip === "snow") {
                    $clas = "wi-night-alt-snow";
                } elseif ($precip === "sprinkles" || $precip === "light rain") {
                    $clas = "wi-night-alt-sprinkle";
                } elseif ($precip === "rain") {
                    // this should be "wi-night-alt-rain", but the icon appears swapped with "wi-night-alt-showers"
                    $clas = "wi-night-alt-showers";
                } elseif ($precip === "showers") {
                    // this should be "wi-night-alt-showers", but the icon appears swapped with "wi-night-alt-rain"
                    $clas = "wi-night-alt-rain";
                }
            } elseif ($isNight === false) {
                if ($precip === "snow") {
                    $clas = "wi-day-snow";
                } elseif ($precip === "sprinkles" || $precip === "light rain") {
                    $clas = "wi-day-sprinkle";
                } elseif ($precip === "rain") {
                    // this should be "wi-day-rain", but the icon appears swapped with "wi-day-showers"
                    $clas = "wi-day-showers";
                } elseif ($precip === "showers") {
                    // this should be "wi-day-showers", but the icon appears swapped with "wi-day-rain"
                    $clas = "wi-day-rain";
                }
            } elseif ($isNight === null) {
                if ($precip === "snow") {
                    $clas = "wi-snow";
                } elseif ($precip === "sprinkles" || $precip === "light rain") {
                    $clas = "wi-sprinkle";
                } elseif ($precip === "rain") {
                    // this should be "wi-rain", but the icon appears swapped with "wi-showers"
                    $clas = "wi-showers";
                } elseif ($precip === "showers") {
                    // this should be "wi-showers", but the icon appears swapped with "wi-rain"
                    $clas = "wi-rain";
                }
            }
        }
    }

    return $clas;
}

/**
 *  Get plain language string for precipitiation conditions from the provided data.
 *
 *  @param $weatherResp  array  An array of weather parameters **for a single hour** as returned
 *                              by the OnPoint API history resource.
 *
 *  @return  string  A plain language string describing the condition.
 */
function getPrecip($weatherResp) {

    $precip = "";
    $rain = $weatherResp['precip'];
    $snow = $weatherResp['snowfall'];

    if (0 < $snow) {
        $precip = "snow";
    } elseif (0 < $rain && $rain <= 0.1) {
        $precip = "sprinkles";
    } elseif (0.1 < $rain && $rain <= 0.25) {
        $precip = "light rain";
    } elseif (0.25 < $rain && $rain <= .75) {
        $precip = "rain";
    } elseif (0.75 < $rain) {
        $precip = "showers";
    }

    return $precip;
}

/**
 *  Get plain language string for cloud conditions from the provided data.
 *
 *  @param $weatherResp  array  An array of weather parameters **for a single hour** as returned
 *                              by the OnPoint API history resource.
 *
 *  @return  string  A plain language string describing the condition.
 */
function getClouds($weatherResp) {

    $clouds = "";
    $cldCvr = $weatherResp['cldCvr'];

    if (25 < $cldCvr && $cldCvr <= 75) {
        $clouds = "partly cloudy";
    } elseif (75 < $cldCvr) {
        $clouds = "cloudy";
    }

    return $clouds;
 }

/**
 *  Get plain language string for wind conditions from the provided data.
 *
 *  @param $weatherResp  array  An array of weather parameters **for a single hour** as returned
 *                              by the OnPoint API history resource.
 *
 *  @return  string  A plain language string describing the condition.
 */
function getWind($weatherResp) {

    $wind = "";
    $windSpd = $weatherResp['windSpd'];

    if (4 < $windSpd && $windSpd <= 8) {
        $wind = "light winds";
    } elseif (8 < $windSpd && $windSpd <= 25) {
        $wind = "winds";
    } elseif (25 < $windSpd) {
        $wind = "strong winds";
    }

    return $wind;
}

/**
 *  Get plain language string for day phase from the provided data.
 *
 *  @param $astroResp    array  An array of astronomy parameters **for a single day**
 *                              as returned by the OnPoint API astronomy resource.
 *  @param $timestamp   string  OPTIONAL. An ISO 8601 timestamp for the included data. This is
 *                              used to determine where the data falls within the day/night
 *                              cycle. If omitted, the current time is assumed.
 *
 *  @return  string  A plain language day phase.
 */
function getDayPhase($astroResp, $timestamp = null) {

    // if no astronomy, return null
    if ($astroResp === null) {
        return;
    }

    if ($timestamp === null) {
        $time = time(); // Use the current time
    } else {
        $time = strtotime($timestamp);
    }

    $dayPhase = "";

    // Sunrise/Sunset
    $sr = strtotime($astroResp["sunrise"]);
    $ss = strtotime($astroResp["sunset"]);

    // Sunrise plus/minus 5 minutes
    $sunriseBegin = $sr - 5*60;
    $sunriseEnd = $sr + 5*60;

    // Sunset plus/minus 5 minutes
    $sunsetBegin = $ss - 5*60;
    $sunsetEnd = $ss + 5*60;

    // Astronomical Twilight
    $astronomicalBegin = strtotime($astroResp["astronomical_twilight_begin"]);
    $astronomicalEnd = strtotime($astroResp["astronomical_twilight_end"]);

    // Civil Twilight
    $civilBegin = strtotime($astroResp["civil_twilight_begin"]);
    $civilEnd = strtotime($astroResp["civil_twilight_end"]);

    // Nautical Twilight
    $nauticalBegin = strtotime($astroResp["nautical_twilight_begin"]);
    $nauticalEnd = strtotime($astroResp["nautical_twilight_end"]);

    if ($time <= $astronomicalBegin) {
        $dayPhase = "night";
    } elseif ($astronomicalBegin < $time && $time <= $nauticalBegin) {
        $dayPhase = "astronomical twilight";
    } elseif ($nauticalBegin < $time && $time <= $civilBegin) {
        $dayPhase = "nautical twilight";
    } elseif ($civilBegin < $time && $time <= $sunriseBegin) {
        $dayPhase = "civil twilight";
    } elseif ($sunriseBegin < $time && $time <= $sunriseEnd) {
        $dayPhase = "sunrise";
    } elseif ($sunriseEnd < $time && $time <= $sunsetBegin) {
        $dayPhase = "day";
    } elseif ($sunsetBegin < $time && $time <= $sunsetEnd) {
        $dayPhase = "sunset";
    } elseif ($sunsetEnd < $time && $time <= $civilEnd) {
        $dayPhase = "civil twilight";
    } elseif ($civilEnd < $time && $time <= $nauticalEnd) {
        $dayPhase = "nautical twilight";
    } elseif ($nauticalEnd < $time && $time <= $astronomicalEnd) {
        $dayPhase = "astronomical twilight";
    } elseif ($astronomicalEnd < $time) {
        $dayPhase = "night";
    }

    return $dayPhase;
}




















