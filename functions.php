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
    if (in_array($dayPhase, array("nighttime", "civil twilight", "nautical twilight", "astronomical twilight"))) {
        // use night icons
        $isNight = true;
    } elseif (in_array($dayPhase, array("daytime", "sunrise", "sunset"))) {
        // use day icons
        $isNight = false;
    } else {
        // use neutral icons
        $isNight = null;
    }

    // determine appropriate icon from provided conditions
    if ($precip === "no precipitation") {
        if ($clouds === "clear" || $clouds === "mostly clear") {
            if ($wind === "no wind") {
                if ($isNight === true) {
                    // CONDITIONS: no precip, clear, no wind, night
                    $clas = "wi-night-clear";
                } elseif ($isNight === false) {
                    // CONDITIONS: no precip, clear, no wind, day
                    $clas = "wi-day-sunny";
                } elseif ($isNight === null) {
                    // CONDITIONS: no precip, clear, no wind, neutral
                    // there is no neutral "clear" icon, so use day icon as fallback
                    $clas = "wi-day-sunny";
                }
            } elseif ($wind === "light winds") {
                // insufficient icons to show light winds, use no wind icons
                if ($isNight === true) {
                    // CONDITIONS: no precip, clear, light winds, night
                    $clas = "wi-night-clear";
                } elseif ($isNight === false) {
                    // CONDITIONS: no precip, clear, light winds, day
                    $clas = "wi-day-sunny";
                } elseif ($isNight === null) {
                    // CONDITIONS: no precip, clear, light winds, neutral
                    // there is no neutral "clear" icon, so use day icon as fallback
                    $clas = "wi-day-sunny";
                }
            } elseif ($wind === "moderate winds") {
                if ($isNight === true) {
                    // CONDITIONS: no precip, clear, moderate winds, night
                    // this should be "wi-night-light-wind", but this icon is missing
                    $clas = "wi-windy";
                } elseif ($isNight === false) {
                    // CONDITIONS: no precip, clear, moderate winds, day
                    $clas = "wi-day-light-wind";
                } elseif ($isNight === null) {
                    // CONDITIONS: no precip, clear, moderate winds, neutral
                    $clas = "wi-windy";
                }
            } elseif ($wind === "strong winds") {
                if ($isNight === true) {
                    // CONDITIONS: no precip, clear, strong winds, night
                    // this should be "wi-night-windy", but this icon is missing
                    $clas = "wi-strong-wind";
                } elseif ($isNight === false) {
                    // CONDITIONS: no precip, clear, strong winds, day
                    $clas = "wi-day-windy";
                } elseif ($isNight === null) {
                    // CONDITIONS: no precip, clear, strong winds, neutral
                    $clas = "wi-strong-wind";
                }
            }
        } elseif ($clouds === "partly cloudy") {
            // use icons that have a small cloud, if available, otherwise, use cloudless icons
            if ($wind === "no wind") {
                if ($isNight === true) {
                    // CONDITIONS: no precip, partly cloudy, no wind, night
                    $clas = "wi-night-alt-partly-cloudy";
                } elseif ($isNight === false) {
                    // CONDITIONS: no precip, partly cloudy, no wind, day
                    $clas = "wi-day-sunny-overcast";
                } elseif ($isNight === null) {
                    // CONDITIONS: no precip, partly cloudy, no wind, neutral
                    // There is not a good small cloud neutral option, so go with clear skies
                    $clas = "wi-day-sunny";
                }
            } elseif ($wind === "light winds") {
                // insufficient icons to show light winds, use no wind icons
                if ($isNight === true) {
                    // CONDITIONS: no precip, partly cloudy, light winds, night
                    $clas = "wi-night-alt-partly-cloudy";
                } elseif ($isNight === false) {
                    // CONDITIONS: no precip, partly cloudy, light winds, day
                    $clas = "wi-day-sunny-overcast";
                } elseif ($isNight === null) {
                    // CONDITIONS: no precip, partly cloudy, light winds, neutral
                    // There is not a good small cloud neutral option, so go with clear skies
                    $clas = "wi-day-sunny";
                }
            } elseif ($wind === "moderate winds") {
                // There is not a good small cloud wind option, so go with clear skies
                if ($isNight === true) {
                    // CONDITIONS: no precip, partly cloudy, moderate winds, night
                    // this should be "wi-night-light-wind", but this icon is missing
                    $clas = "wi-windy";
                } elseif ($isNight === false) {
                    // CONDITIONS: no precip, partly cloudy, moderate winds, day
                    $clas = "wi-day-light-wind";
                } elseif ($isNight === null) {
                    // CONDITIONS: no precip, partly cloudy, moderate winds, neutral
                    $clas = "wi-windy";
                }
            } elseif ($wind === "strong winds") {
                // There is not a good small cloud wind option, so go with clear skies
                if ($isNight === true) {
                    // CONDITIONS: no precip, partly cloudy, strong winds, night
                    // this should be "wi-night-windy", but this icon is missing
                    $clas = "wi-strong-wind";
                } elseif ($isNight === false) {
                    // CONDITIONS: no precip, partly cloudy, strong winds, day
                    $clas = "wi-day-windy";
                } elseif ($isNight === null) {
                    // CONDITIONS: no precip, partly cloudy, strong winds, neutral
                    $clas = "wi-strong-wind";
                }
            }
        } elseif ($clouds === "mostly cloudy") {
            // use icons with a single large cloud, with a sun/moon in the frame, if available.
            if ($wind === "no wind") {
                if ($isNight === true) {
                    // CONDITIONS: no precip, mostly cloudy, no wind, night
                    $clas = "wi-night-alt-cloudy";
                } elseif ($isNight === false) {
                    // CONDITIONS: no precip, mostly cloudy, no wind, day
                    $clas = "wi-day-cloudy";
                } elseif ($isNight === null) {
                    // CONDITIONS: no precip, mostly cloudy, no wind, neutral
                    $clas = "wi-cloud";
                }
            } elseif ($wind === "light winds") {
                // insufficient icons to show light winds, use no wind icons
                if ($isNight === true) {
                    // CONDITIONS: no precip, mostly cloudy, light winds, night
                    $clas = "wi-night-alt-cloudy";
                } elseif ($isNight === false) {
                    // CONDITIONS: no precip, mostly cloudy, light winds, day
                    $clas = "wi-day-cloudy";
                } elseif ($isNight === null) {
                    // CONDITIONS: no precip, mostly cloudy, light winds, neutral
                    $clas = "wi-cloud";
                }
            } elseif ($wind === "moderate winds") {
                if ($isNight === true) {
                    // CONDITIONS: no precip, mostly cloudy, moderate winds, night
                    $clas = "wi-night-alt-cloudy-windy";
                } elseif ($isNight === false) {
                    // CONDITIONS: no precip, mostly cloudy, moderate winds, day
                    $clas = "wi-day-cloudy-windy";
                } elseif ($isNight === null) {
                    // CONDITIONS: no precip, mostly cloudy, moderate winds, neutral
                    $clas = "wi-cloudy-windy";
                }
            } elseif ($wind === "strong winds") {
                if ($isNight === true) {
                    // CONDITIONS: no precip, mostly cloudy, strong winds, night
                    $clas = "wi-night-alt-cloudy-gusts";
                } elseif ($isNight === false) {
                    // CONDITIONS: no precip, mostly cloudy, strong winds, day
                    $clas = "wi-day-cloudy-gusts";
                } elseif ($isNight === null) {
                    // CONDITIONS: no precip, mostly cloudy, strong winds, neutral
                    $clas = "wi-cloudy-gusts";
                }
            }
        } elseif ($clouds === "cloudy") {
            // use neutral icon (i.e. no sun or moon in sky)
            if ($wind === "no wind") {
                // CONDITIONS: no precip, cloudy, no wind, neutral
                $clas = "wi-cloudy";
            } elseif ($wind === "light winds") {
                // insufficient icons to show light winds, use no wind icons
                // CONDITIONS: no precip, cloudy, light winds, neutral
                $clas = "wi-cloudy";
            } elseif ($wind === "moderate winds") {
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
    } elseif ($precip == "moderate rain") {
        // There are only meaningful wind icons for non-precipitation, so wind is not considered here

        if ($clouds === "cloudy") {
            // use neutral icon (i.e. no sun or moon in sky)
            // CONDITIONS: moderate rain, cloudy, neutral
            // this should be "wi-rain", but the icon appears swapped with "wi-showers"
            $clas = "wi-showers";
        } else {
            // use icon with a sun/moon in the frame, if available.
            if ($isNight === true) {
                // CONDITIONS: moderate rain, night
                // this should be "wi-night-alt-rain", but the icon appears swapped with "wi-night-alt-showers"
                $clas = "wi-night-alt-showers";
            } elseif ($isNight === false) {
                // CONDITIONS: moderate rain, day
                // this should be "wi-day-rain", but the icon appears swapped with "wi-day-showers"
                $clas = "wi-day-showers";
            } elseif ($isNight === null) {
                // CONDITIONS: moderate rain, neutral
                // this should be "wi-rain", but the icon appears swapped with "wi-showers"
                $clas = "wi-showers";
            }
        }
    } elseif ($precip == "heavy rain") {
        // There are only meaningful wind icons for non-precipitation, so wind is not considered here

        if ($clouds === "cloudy") {
            // use neutral icon (i.e. no sun or moon in sky)
            // CONDITIONS: heavy rain, cloudy, neutral
            // this should be "wi-showers", but the icon appears swapped with "wi-rain"
            $clas = "wi-rain";
        } else {
            // use icon with a sun/moon in the frame, if available.
            if ($isNight === true) {
                // CONDITIONS: heavy rain, night
                // this should be "wi-night-alt-showers", but the icon appears swapped with "wi-night-alt-rain"
                $clas = "wi-night-alt-rain";
            } elseif ($isNight === false) {
                // CONDITIONS: heavy rain, day
                // this should be "wi-day-showers", but the icon appears swapped with "wi-day-rain"
                $clas = "wi-day-rain";
            } elseif ($isNight === null) {
                // CONDITIONS: heavy rain, neutral
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

    $precip = "no precipitation";
    $rain = $weatherResp['precip'];
    $snow = $weatherResp['snowfall'];

    // scale of rain intensity from AMS Glossary of Meteorology (https://web.archive.org/web/20100725142506/http://amsglossary.allenpress.com/glossary/search?id=rain1)
    if (0 < $snow) {
        $precip = "snow";
    } elseif (0 < $rain && $rain <= 0.1) {
        $precip = "light rain";
    } elseif (0.1 < $rain && $rain <= 0.3) {
        $precip = "moderate rain";
    } elseif (0.3 < $rain) {
        $precip = "heavy rain";
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
    $cldCvr = $weatherResp['cldCvr'];

    // percentage breakdown from "Sky Condition" definition at http://w1.weather.gov/glossary/
    $clouds = "clear";
    if (0 < $cldCvr && $cldCvr <= 25) {
        // the standard has the minimum value at 12.5%. Fudge it down to 0 to avoid the description
        // that says:" Clear, light rain."
        $clouds = "mostly clear";
    } elseif (25 < $cldCvr && $cldCvr <= 50) {
        $clouds = "partly cloudy";
    } elseif (50 < $cldCvr && $cldCvr <= 87.5) {
        $clouds = "mostly cloudy";
    } elseif (87.5 < $cldCvr) {
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

    $wind = "no wind";
    $windSpd = $weatherResp['windSpd'];

    // See Beaufort scale: https://en.wikipedia.org/wiki/Beaufort_scale
    if (8 < $windSpd && $windSpd <= 13) { // Baeufort scale 3
        $wind = "light winds";
    } elseif (13 < $windSpd && $windSpd <= 25) { // Baeufort scales 4-5
        $wind = "moderate winds";
    } elseif (25 < $windSpd) { // Baeufort scales 6+
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
        $dayPhase = "nighttime";
    } elseif ($astronomicalBegin < $time && $time <= $nauticalBegin) {
        $dayPhase = "astronomical twilight";
    } elseif ($nauticalBegin < $time && $time <= $civilBegin) {
        $dayPhase = "nautical twilight";
    } elseif ($civilBegin < $time && $time <= $sunriseBegin) {
        $dayPhase = "civil twilight";
    } elseif ($sunriseBegin < $time && $time <= $sunriseEnd) {
        $dayPhase = "sunrise";
    } elseif ($sunriseEnd < $time && $time <= $sunsetBegin) {
        $dayPhase = "daytime";
    } elseif ($sunsetBegin < $time && $time <= $sunsetEnd) {
        $dayPhase = "sunset";
    } elseif ($sunsetEnd < $time && $time <= $civilEnd) {
        $dayPhase = "civil twilight";
    } elseif ($civilEnd < $time && $time <= $nauticalEnd) {
        $dayPhase = "nautical twilight";
    } elseif ($nauticalEnd < $time && $time <= $astronomicalEnd) {
        $dayPhase = "astronomical twilight";
    } elseif ($astronomicalEnd < $time) {
        $dayPhase = "nighttime";
    }

    return $dayPhase;
}




















