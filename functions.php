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
            if ($wind === "") {
                if ($isNight === true) {
                    // CONDITIONS: no precip, no clouds, no wind, night
                    $clas = "wi-night-clear";
                } elseif ($isNight === false) {
                    // CONDITIONS: no precip, no clouds, no wind, day
                    $clas = "wi-day-sunny";
                } elseif ($isNight === null) {
                    // CONDITIONS: no precip, no clouds, no wind, neutral
                    // there is no neutral "clear" icon, so use day icon as fallback
                    $clas = "wi-day-sunny";
                }
            } elseif ($wind === "light winds") {
                // insufficient icons to show light winds, use no wind icons
                if ($isNight === true) {
                    // CONDITIONS: no precip, no clouds, light winds, night
                    $clas = "wi-night-clear";
                } elseif ($isNight === false) {
                    // CONDITIONS: no precip, no clouds, light winds, day
                    $clas = "wi-day-sunny";
                } elseif ($isNight === null) {
                    // CONDITIONS: no precip, no clouds, light winds, neutral
                    // there is no neutral "clear" icon, so use day icon as fallback
                    $clas = "wi-day-sunny";
                }
            } elseif ($wind === "winds") {
                if ($isNight === true) {
                    // CONDITIONS: no precip, no clouds, winds, night
                    // this should be "wi-night-light-wind", but this icon is missing
                    $clas = "wi-windy";
                } elseif ($isNight === false) {
                    // CONDITIONS: no precip, no clouds, winds, day
                    $clas = "wi-day-light-wind";
                } elseif ($isNight === null) {
                    // CONDITIONS: no precip, no clouds, winds, neutral
                    $clas = "wi-windy";
                }
            } elseif ($wind === "strong winds") {
                if ($isNight === true) {
                    // CONDITIONS: no precip, no clouds, strong winds, night
                    // this should be "wi-night-windy", but this icon is missing
                    $clas = "wi-strong-wind";
                } elseif ($isNight === false) {
                    // CONDITIONS: no precip, no clouds, strong winds, day
                    $clas = "wi-day-windy";
                } elseif ($isNight === null) {
                    // CONDITIONS: no precip, no clouds, strong winds, neutral
                    $clas = "wi-strong-wind";
                }
            }
        } elseif ($clouds === "scattered clouds") {
            // use icons that have a small cloud, if available, otherwise, use cloudless icons
            if ($wind === "") {
                if ($isNight === true) {
                    // CONDITIONS: no precip, scattered clouds, no wind, night
                    $clas = "wi-night-alt-partly-cloudy";
                } elseif ($isNight === false) {
                    // CONDITIONS: no precip, scattered clouds, no wind, day
                    $clas = "wi-day-sunny-overcast";
                } elseif ($isNight === null) {
                    // CONDITIONS: no precip, scattered clouds, no wind, neutral
                    // There is not a good small cloud neutral option, so go with clear skies
                    $clas = "wi-day-sunny";
                }
            } elseif ($wind === "light winds") {
                // insufficient icons to show light winds, use no wind icons
                if ($isNight === true) {
                    // CONDITIONS: no precip, scattered clouds, light winds, night
                    $clas = "wi-night-alt-partly-cloudy";
                } elseif ($isNight === false) {
                    // CONDITIONS: no precip, scattered clouds, light winds, day
                    $clas = "wi-day-sunny-overcast";
                } elseif ($isNight === null) {
                    // CONDITIONS: no precip, scattered clouds, light winds, neutral
                    // There is not a good small cloud neutral option, so go with clear skies
                    $clas = "wi-day-sunny";
                }
            } elseif ($wind === "winds") {
                // There is not a good small cloud wind option, so go with clear skies
                if ($isNight === true) {
                    // CONDITIONS: no precip, scattered clouds, winds, night
                    // this should be "wi-night-light-wind", but this icon is missing
                    $clas = "wi-windy";
                } elseif ($isNight === false) {
                    // CONDITIONS: no precip, scattered clouds, winds, day
                    $clas = "wi-day-light-wind";
                } elseif ($isNight === null) {
                    // CONDITIONS: no precip, scattered clouds, winds, neutral
                    $clas = "wi-windy";
                }
            } elseif ($wind === "strong winds") {
                // There is not a good small cloud wind option, so go with clear skies
                if ($isNight === true) {
                    // CONDITIONS: no precip, scattered clouds, strong winds, night
                    // this should be "wi-night-windy", but this icon is missing
                    $clas = "wi-strong-wind";
                } elseif ($isNight === false) {
                    // CONDITIONS: no precip, scattered clouds, strong winds, day
                    $clas = "wi-day-windy";
                } elseif ($isNight === null) {
                    // CONDITIONS: no precip, scattered clouds, strong winds, neutral
                    $clas = "wi-strong-wind";
                }
            }
        } elseif ($clouds === "partly cloudy") {
            // use icons with a single large cloud, with a sun/moon in the frame, if available.
            if ($wind === "") {
                if ($isNight === true) {
                    // CONDITIONS: no precip, partly cloudy, no wind, night
                    $clas = "wi-night-alt-cloudy";
                } elseif ($isNight === false) {
                    // CONDITIONS: no precip, partly cloudy, no wind, day
                    $clas = "wi-day-cloudy";
                } elseif ($isNight === null) {
                    // CONDITIONS: no precip, partly cloudy, no wind, neutral
                    $clas = "wi-cloud";
                }
            } elseif ($wind === "light winds") {
                // insufficient icons to show light winds, use no wind icons
                if ($isNight === true) {
                    // CONDITIONS: no precip, partly cloudy, light winds, night
                    $clas = "wi-night-alt-cloudy";
                } elseif ($isNight === false) {
                    // CONDITIONS: no precip, partly cloudy, light winds, day
                    $clas = "wi-day-cloudy";
                } elseif ($isNight === null) {
                    // CONDITIONS: no precip, partly cloudy, light winds, neutral
                    $clas = "wi-cloud";
                }
            } elseif ($wind === "winds") {
                if ($isNight === true) {
                    // CONDITIONS: no precip, partly cloudy, winds, night
                    $clas = "wi-night-alt-cloudy-windy";
                } elseif ($isNight === false) {
                    // CONDITIONS: no precip, partly cloudy, winds, day
                    $clas = "wi-day-cloudy-windy";
                } elseif ($isNight === null) {
                    // CONDITIONS: no precip, partly cloudy, winds, neutral
                    $clas = "wi-cloudy-windy";
                }
            } elseif ($wind === "strong winds") {
                if ($isNight === true) {
                    // CONDITIONS: no precip, partly cloudy, strong winds, night
                    $clas = "wi-night-alt-cloudy-gusts";
                } elseif ($isNight === false) {
                    // CONDITIONS: no precip, partly cloudy, strong winds, day
                    $clas = "wi-day-cloudy-gusts";
                } elseif ($isNight === null) {
                    // CONDITIONS: no precip, partly cloudy, strong winds, neutral
                    $clas = "wi-cloudy-gusts";
                }
            }
        } elseif ($clouds === "cloudy") {
            // use neutral icon (i.e. no sun or moon in sky)
            if ($wind === "") {
                // CONDITIONS: no precip, cloudy, no wind, neutral
                $clas = "wi-cloudy";
            } elseif ($wind === "light winds") {
                // insufficient icons to show light winds, use no wind icons
                // CONDITIONS: no precip, cloudy, light winds, neutral
                $clas = "wi-cloudy";
            } elseif ($wind === "winds") {
                // CONDITIONS: no precip, cloudy, winds, neutral
                $clas = "wi-cloudy-windy";
            } elseif ($wind === "strong winds") {
                // CONDITIONS: no precip, cloudy, strong winds, neutral
                $clas = "wi-cloudy-gusts";
            }
        }
    } elseif ($precip == "snow") {
        // There are only meaningful wind icons for non-precipitation, so wind is not considered here

        if ($clouds === "cloudy") {
            // use neutral icon (i.e. no sun or moon in sky)
            // CONDITIONS: snow, cloudy, neutral
            $clas = "wi-snow";
        } else {
            // use icon with a sun/moon in the frame, if available.
            if ($isNight === true) {
                // CONDITIONS: snow, night
                $clas = "wi-night-alt-snow";
            } elseif ($isNight === false) {
                // CONDITIONS: snow, day
                $clas = "wi-day-snow";
            } elseif ($isNight === null) {
                // CONDITIONS: snow, neutral
                $clas = "wi-snow";
            }
        }
    } elseif ($precip == "sprinkles") {
        // There are only meaningful wind icons for non-precipitation, so wind is not considered here
        // insufficient icons to show sprinkles, use light rain icons

        if ($clouds === "cloudy") {
            // use neutral icon (i.e. no sun or moon in sky)
            // CONDITIONS: sprinkles, cloudy, neutral
            $clas = "wi-sprinkle";
        } else {
            // use icon with a sun/moon in the frame, if available.
            if ($isNight === true) {
                // CONDITIONS: sprinkles, night
                $clas = "wi-night-alt-sprinkle";
            } elseif ($isNight === false) {
                // CONDITIONS: sprinkles, day
                $clas = "wi-day-sprinkle";
            } elseif ($isNight === null) {
                // CONDITIONS: sprinkles, neutral
                $clas = "wi-sprinkle";
            }
        }
    } elseif ($precip == "light rain") {
        // There are only meaningful wind icons for non-precipitation, so wind is not considered here

        if ($clouds === "cloudy") {
            // use neutral icon (i.e. no sun or moon in sky)
            // CONDITIONS: light rain, cloudy, neutral
            $clas = "wi-sprinkle";
        } else {
            // use icon with a sun/moon in the frame, if available.
            if ($isNight === true) {
                // CONDITIONS: light rain, night
                $clas = "wi-night-alt-sprinkle";
            } elseif ($isNight === false) {
                // CONDITIONS: light rain, day
                $clas = "wi-day-sprinkle";
            } elseif ($isNight === null) {
                // CONDITIONS: light rain, neutral
                $clas = "wi-sprinkle";
            }
        }
    } elseif ($precip == "rain") {
        // There are only meaningful wind icons for non-precipitation, so wind is not considered here

        if ($clouds === "cloudy") {
            // use neutral icon (i.e. no sun or moon in sky)
            // CONDITIONS: rain, cloudy, neutral
            // this should be "wi-rain", but the icon appears swapped with "wi-showers"
            $clas = "wi-showers";
        } else {
            // use icon with a sun/moon in the frame, if available.
            if ($isNight === true) {
                // CONDITIONS: rain, night
                // this should be "wi-night-alt-rain", but the icon appears swapped with "wi-night-alt-showers"
                $clas = "wi-night-alt-showers";
            } elseif ($isNight === false) {
                // CONDITIONS: rain, day
                // this should be "wi-day-rain", but the icon appears swapped with "wi-day-showers"
                $clas = "wi-day-showers";
            } elseif ($isNight === null) {
                // CONDITIONS: rain, neutral
                // this should be "wi-rain", but the icon appears swapped with "wi-showers"
                $clas = "wi-showers";
            }
        }
    } elseif ($precip == "showers") {
        // There are only meaningful wind icons for non-precipitation, so wind is not considered here

        if ($clouds === "cloudy") {
            // use neutral icon (i.e. no sun or moon in sky)
            // CONDITIONS: showers, cloudy, neutral
            // this should be "wi-showers", but the icon appears swapped with "wi-rain"
            $clas = "wi-rain";
        } else {
            // use icon with a sun/moon in the frame, if available.
            if ($isNight === true) {
                // CONDITIONS: showers, night
                // this should be "wi-night-alt-showers", but the icon appears swapped with "wi-night-alt-rain"
                $clas = "wi-night-alt-rain";
            } elseif ($isNight === false) {
                // CONDITIONS: showers, day
                // this should be "wi-day-showers", but the icon appears swapped with "wi-day-rain"
                $clas = "wi-day-rain";
            } elseif ($isNight === null) {
                // CONDITIONS: showers, neutral
                // this should be "wi-showers", but the icon appears swapped with "wi-rain"
                $clas = "wi-rain";
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

    if (25 < $cldCvr && $cldCvr <= 50) {
        $clouds = "scattered clouds";
    } elseif (50 < $cldCvr && $cldCvr <= 75) {
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




















