<?php
    // $countries set in countries.php
    require "./countries.php";

    // $country, $postcode, $iconClass and $err all set in form-action.php
    require "./form-action.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Weather Icon Demonstration</title>
    <link rel="stylesheet" type="text/css" href="css/weather-icons.min.css">
    <style type="text/css" media="screen">
        .error {
            color: red;
        }
        #result {
            text-align: center;
        }
        .wi {
            color: #924da3;
            font-size: 250px;
        }
    </style>
</head>
<body>
    <h1>Choose Location:</h1>
    <?php if (!empty($err)) : ?>
        <p class="error"><?= $err ?></p>
    <?php endif; ?>
    <form method="get" accept-charset="utf-8">
        <div>
            <label for="country">Country:
                <select id="country" name="country">
                    <option value="">--Please choose a country--</option>
                    <?php foreach ($countries as $iso => $name) :?>
                        <?php $selected = ($iso === $country) ? "selected" : ""; ?>
                        <option value="<?= $iso ?>" <?= $selected ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div>
            <label for="postcode">Postcode:
                <input type="text" id="postcode" name="postcode" value="<?= $postcode ?>" />
            </label>
        </div>
        <div>
            <input type="submit" value="Submit" />
        </div>
    </form>
    <?php if (!empty($iconClass)) : ?>
        <h1>Current Weather:</h1>
        <p id="result"><i class="wi <?= $iconClass ?>"></i></p>
    <?php endif; ?>
    <p><small>Powered by the <a href="https://developer.weathersource.com/">OnPoint API</a> and <a href="https://erikflowers.github.io/weather-icons/">Weather Icons</a>. Source code is vailable at <a href="https://github.com/weathersource/weather-icon-demo">Github</a>.</small></p>
</body>
</html>

