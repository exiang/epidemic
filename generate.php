<?php
@date_default_timezone_set('Asia/Kuala_Lumpur');

// default to yesterday date
$date = date('Y-m-d', strtotime('yesterday'));
$options = getopt("date:");
if (!empty($argv['1'])) {
    $date = $argv['1'];
}

echo sprintf("Generate `Latest Status` Report for: %s\n", $date);
generateLatestStatus($date);

function generateLatestStatus($date)
{
    $templateFile = getcwd().'/template/latest_status.jpg';
    $outputPath = sprintf('%s/output/%s', getcwd(), $date);
    mkdir($outputPath, 0777, true);
    $outputFile = sprintf('%s/latest_status.jpg', $outputPath);
    $width = $height = 1080;

    // create from template
    $im = @imagecreatefromjpeg($templateFile);

    // fonts
    $fontLatoBlack = 'font/Lato-Black.ttf';
    $fontLatoBold = 'font/Lato-Bold.ttf';

    // preset
    $colorBlack = imagecolorallocate($im, 0, 0, 0);
    $colorWhite = imagecolorallocate($im, 255, 255, 255);
    list($dateYear, $dateMonth, $dateDay) = explode('-', $date);

    // variables
    $csvCasesMalaysia = loadCsvCasesMalaysia();
    $accumulatedCase = $accumulatedRecoverCase = 0;
    $newCase = $newRecoverCase = 0;
    foreach ($csvCasesMalaysia as $row) {
        if(str_replace('-', '', $row['date']) <= str_replace('-', '', $date))
        {
            $accumulatedCase += $row['cases_new'];
            $accumulatedRecoverCase += $row['cases_recovered'];
        }

        if ($row['date'] == $date) {
            $newCase = $row['cases_new'];
            $importCase = $row['cases_import'];
            $localCase = $row['cases_new'] - $importCase;
            $newRecoverCase = $row['cases_recovered'];
        }
    }

    $csvDeathsMalaysia = loadCsvDeathsMalaysia();
    $accumulatedDeathCase = $newDeathCase = 0;
    foreach ($csvDeathsMalaysia as $row) {
        if(str_replace('-', '', $row['date']) <= str_replace('-', '', $date))
        {
            $accumulatedDeathCase += $row['deaths_new'];
        }
        
        if ($row['date'] == $date) {
            $newDeathCase = $row['deaths_new'];
        }
    }

    $csvIcu = loadCsvIcu();
    $icuCase = 0;
    $ventCase = 0;
    foreach($csvIcu as $row)
    {
        if ($row['date'] == $date) {
            $icuCase += $row['icu_covid'] + $row['icu_pui'];
            $ventCase += $row['vent_covid'] + $row['vent_pui'];
        }
    }

    $csvPkrc = loadCsvPkrc();
    $pkrcCase = 0;
    foreach($csvPkrc as $row)
    {
        if ($row['date'] == $date) {
            $pkrcCase += $row['pkrc_covid'] + $row['pkrc_pui'];
        }
    }

    $csvHospital = loadCsvHospital();
    $hospitalCase = 0;
    foreach($csvHospital as $row)
    {
        if ($row['date'] == $date) {
            $hospitalCase += $row['hosp_covid'] + $row['hosp_pui'];
        }
    }
    // todo: stuck here, cant find the home quarantine data
    $activeCase = $hospitalCase + $pkrcCase + $icuCase;
    //$activeCase = 248673;

    //
    // write date
    writeLeft($im, sprintf('%s.%s.%s', ltrim($dateDay, '0'), ltrim($dateMonth, '0'), $dateYear), 20, 58, 110, $colorBlack, $fontLatoBlack);

    // write accumulated case
    writeCenter($im, number_format($accumulatedCase), 48, 680, 170, 335, 67, $colorWhite, $fontLatoBold);

    //
    // write new case
    writeCenter($im, number_format($newCase), 53, 55, 328, 460, 90, imagecolorallocate($im, 238, 66, 43), $fontLatoBold);

    // write local case
    writeCenter($im, number_format($localCase), 35, 73, 465, 175, 50, $colorWhite, $fontLatoBold);

    // write import case
    writeCenter($im, number_format($importCase), 35, 320, 465, 175, 50, $colorWhite, $fontLatoBold);

    //
    // write new recover case
    writeCenter($im, number_format($newRecoverCase), 53, 567, 328, 460, 90, imagecolorallocate($im, 6, 61, 30), $fontLatoBold);

    // write accumulated recover case
    writeCenter($im, number_format($accumulatedRecoverCase), 35, 565, 465, 455, 50, $colorWhite, $fontLatoBold);


    //
    // todo: write active case
    // writeCenter($im, number_format($activeCase), 53, 55, 599, 460, 90, imagecolorallocate($im, 51, 27, 89), $fontLatoBold);

    // write icu case
    writeCenter($im, number_format($icuCase), 35, 73, 734, 175, 50, $colorWhite, $fontLatoBold);
    // write ventil case
    writeCenter($im, number_format($ventCase), 35, 320, 690, 175, 50, $colorWhite, $fontLatoBold);

    //
    // write new death case
    writeCenter($im, number_format($newDeathCase), 53, 567, 590, 460, 90, imagecolorallocate($im, 60, 34, 20), $fontLatoBold);
    // write accumulated death case
    writeCenter($im, number_format($accumulatedDeathCase), 35, 565, 740, 455, 50, $colorWhite, $fontLatoBold);

    // Output the image
    header('Content-Type: image/jpeg');
    imagejpeg($im, $outputFile);
    imagedestroy($im);
}

function writeLeft($im, $text, $size, $x, $y, $color, $font)
{
    imagettftext($im, $size, 0, $x, $y, $color, $font, $text);
}

function writeCenter($im, $text, $size, $xOffset, $yOffset, $boxWidth, $boxHeight, $color, $font)
{
    $box = imagettfbbox($size, 0, $font, $text);
    $text_width = abs($box[2]) - abs($box[0]);
    $text_height = abs($box[5]) - abs($box[3]);
    $x = $xOffset + ($boxWidth - $text_width) / 2;
    $y = $yOffset + ($boxHeight + $text_height) / 2;

    // add text
    imagettftext($im, $size, 0, $x, $y, $color, $font, $text);
}

function loadCsvCasesMalaysia()
{
    $file = sprintf('%s/input/moh/epidemic/cases_malaysia.csv', getcwd());
    $rows   = array_map('str_getcsv', file($file));
    $header = array_shift($rows);
    $csv    = array();
    foreach ($rows as $row) {
        $csv[] = array_combine($header, $row);
    }

    return $csv;
}

function loadCsvDeathsMalaysia()
{
    $file = sprintf('%s/input/moh/epidemic/deaths_malaysia.csv', getcwd());
    $rows   = array_map('str_getcsv', file($file));
    $header = array_shift($rows);
    $csv    = array();
    foreach ($rows as $row) {
        $csv[] = array_combine($header, $row);
    }

    return $csv;
}
function loadCsvIcu()
{
    $file = sprintf('%s/input/moh/epidemic/icu.csv', getcwd());
    $rows   = array_map('str_getcsv', file($file));
    $header = array_shift($rows);
    $csv    = array();
    foreach ($rows as $row) {
        $csv[] = array_combine($header, $row);
    }

    return $csv;
}
function loadCsvPkrc()
{
    $file = sprintf('%s/input/moh/epidemic/pkrc.csv', getcwd());
    $rows   = array_map('str_getcsv', file($file));
    $header = array_shift($rows);
    $csv    = array();
    foreach ($rows as $row) {
        $csv[] = array_combine($header, $row);
    }

    return $csv;
}
function loadCsvHospital()
{
    $file = sprintf('%s/input/moh/epidemic/hospital.csv', getcwd());
    $rows   = array_map('str_getcsv', file($file));
    $header = array_shift($rows);
    $csv    = array();
    foreach ($rows as $row) {
        $csv[] = array_combine($header, $row);
    }

    return $csv;
}
