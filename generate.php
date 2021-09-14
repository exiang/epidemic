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
generateLatestStatusV1($date);

// only available after Sep 2021
function generateLatestStatusV1($date)
{
    $templateFile = getcwd().'/template/latest_status.v1.jpg';
    $outputPath = sprintf('%s/output/%s', getcwd(), $date);
    $outputFile = sprintf('%s/latest_status.v1.jpg', $outputPath);
    $width = $height = 1080;

    // create from template
    $im = @imagecreatefromjpeg($templateFile);

    // fonts
    $fontInterMedium = 'font/Inter-Medium.ttf';
    $fontInterBold = 'font/Inter-Bold.ttf';

    // preset
    $colorBlack = imagecolorallocate($im, 0, 0, 0);
    $colorWhite = imagecolorallocate($im, 255, 255, 255);
    $colorTextBg = imagecolorallocate($im, 203, 204, 206);
    $colorGreenBar = imagecolorallocate($im, 95, 210, 151);
    $colorYellowBar = imagecolorallocate($im, 242, 213, 70);
    $colorOrangeBar = imagecolorallocate($im, 198, 131, 56);
    $colorRedBar = imagecolorallocate($im, 197, 33, 34);
    $colorBrownBar = imagecolorallocate($im, 101, 37, 42);
    list($dateYear, $dateMonth, $dateDay) = explode('-', $date);
    $dateTimestamp = mktime('23', '59', '00', $dateMonth, $dateDay, $dateYear);


    if((int)sprintf('%s%s%s', $dateYear, $dateMonth, $dateDay) < 20210901) {
        echo "Sorry, LatestStatusV1 report not available prior to 2021-Sep-01\n";
        return;
    }
    
    // variables
    $csvCasesMalaysia = loadCsvCasesMalaysia();
    $accumulatedLocalCase = $accumulatedImportCase = $accumulatedCase = $accumulatedRecoverCase = 0;
    $newCase = $newRecoverCase = 0;
    foreach ($csvCasesMalaysia as $row) {
        if (str_replace('-', '', $row['date']) <= str_replace('-', '', $date)) {
            $accumulatedCase += $row['cases_new'];
            $accumulatedImportCase += $row['cases_import'];
            $accumulatedLocalCase += $row['cases_new'] - $row['cases_import'];
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
    $accumulatedDeathCase = $newDeathCase =  $accumulatedBIDeathCase = $newBIDeathCase = 0;
    foreach ($csvDeathsMalaysia as $row) {
        if (str_replace('-', '', $row['date']) <= str_replace('-', '', $date)) {
            $accumulatedDeathCase += $row['deaths_new'];
            $accumulatedBIDeathCase += $row['deaths_bid'];
        }
        
        if ($row['date'] == $date) {
            $newDeathCase = $row['deaths_new'];
            $newBIDeathCase = $row['deaths_bid'];
        }
    }

    $csvIcu = loadCsvIcu();
    $icuCase = 0;
    $ventCase = 0;
    foreach ($csvIcu as $row) {
        if ($row['date'] == $date) {
            $icuCase += $row['icu_covid'] + $row['icu_pui'];
            $ventCase += $row['vent_covid'] + $row['vent_pui'];
        }
    }

    $csvPkrc = loadCsvPkrc();
    $pkrcCase = 0;
    foreach ($csvPkrc as $row) {
        if ($row['date'] == $date) {
            $pkrcCase += $row['pkrc_covid'] + $row['pkrc_pui'];
        }
    }

    $csvHospital = loadCsvHospital();
    $hospitalCase = 0;
    foreach ($csvHospital as $row) {
        if ($row['date'] == $date) {
            $hospitalCase += $row['hosp_covid'] + $row['hosp_pui'];
        }
    }

    $csvPopulation = loadCsvPopulation();
    $malaysiaPopulation = 0;
    foreach ($csvPopulation as $row) {
        if ($row['state'] == 'Malaysia') {
            $malaysiaPopulationArray = $row;
        }
    }

    // calc activeCase
    $activeCase = $accumulatedCase - $accumulatedRecoverCase - $accumulatedDeathCase;
    $yesterdayActiveCase = ($accumulatedCase - $newCase) -  ($accumulatedRecoverCase - $newRecoverCase) - ($accumulatedDeathCase - $newDeathCase);

    // calc quarantineAtHome
    $qaHomeCase = $activeCase - $pkrcCase - $hospitalCase - $icuCase;
    
    // vaccination
    $dateVax = sprintf('%s/%s/%s', $dateDay, $dateMonth, $dateYear);
    $csvVax = loadCsvVaccination();
    $accumulatedVax = $accumulatedPartialVax = $accumulatedFullVax = 0;
    foreach ($csvVax as $row) {
        if ($row['date'] <= $date) {
            $accumulatedVax = $row['cumul'];
            list($dateYearVax, $dateMonthVax, $dateDayVax) = explode('-', $row['date']);
            $dateVax = sprintf('%s/%s/%s', $dateDayVax, $dateMonthVax, $dateYearVax);
            $accumulatedPartialVax = $row['cumul_partial'];
            $accumulatedFullVax = $row['cumul_full'];
        }
    }

    // check to proceed to generate or not
    if (empty($newCase) && empty($newDeathCase) && empty($newRecoverCase) && empty($activeCase)) {
        echo "\nNot enough data to generate!\n";
        return;
    }


    //
    // write date
    writeCenter($im, sprintf('%s', date('d F Y h.i', $dateTimestamp)), 16, 173, 60, 270, 25, $colorWhite, $fontInterBold);

    
    // write local case
    writeRight($im, number_format($accumulatedLocalCase), 16, 170, 530, $colorBlack, $fontInterMedium);
    writeLeft($im, (($localCase>0)?'+':'').number_format($localCase), 16, 185, 530, $colorBlack, $fontInterBold, $colorTextBg);

    // write import case
    writeRight($im, number_format($accumulatedImportCase), 16, 170, 618, $colorBlack, $fontInterMedium);
    writeLeft($im, (($importCase>0)?'+':'').number_format($importCase), 16, 185, 618, $colorBlack, $fontInterBold, $colorTextBg);

    //
    // write active case
    writeCenter($im, number_format($activeCase), 18, 445, 190, 110, 32, $colorBlack, $fontInterMedium);
    // write compare to past
    writeCenter($im, number_format($activeCase-$yesterdayActiveCase), 18, 555, 190, 110, 32, $colorBlack, $fontInterBold);
    

    // write quarantine at home case
    $box = writeLeft($im, number_format($qaHomeCase), 18, 508, 350, $colorBlack, $fontInterMedium);
    writeLeft($im, sprintf('%s %%', number_format((float)round(($qaHomeCase)/$activeCase * 100, 2, PHP_ROUND_HALF_UP), 1, '.', ',')), 18, $box[2]+10, $box[3]-4, $colorBlack, $fontInterBold, $colorTextBg);
    unset($box);

    // write quarantine center case
    $box = writeLeft($im, number_format($pkrcCase), 18, 508, 455, $colorBlack, $fontInterMedium);
    writeLeft($im, sprintf('%s %%', number_format((float)round(($pkrcCase)/$activeCase * 100, 2, PHP_ROUND_HALF_UP), 1, '.', ',')), 18, $box[2]+10, $box[3]-4, $colorBlack, $fontInterBold, $colorTextBg);
    unset($box);

    // write hospital case
    $box = writeLeft($im, number_format($hospitalCase), 18, 510, 564, $colorBlack, $fontInterMedium);
    writeLeft($im, sprintf('%s %%', number_format((float)round(($hospitalCase)/$activeCase * 100, 2, PHP_ROUND_HALF_UP), 1, '.', ',')), 18, $box[2]+10, $box[3]-4, $colorBlack, $fontInterBold, $colorTextBg);
    unset($box);

    // write icu case (without ventil)
    $box = writeLeft($im, number_format($icuCase-$ventCase), 18, 510, 684, $colorBlack, $fontInterMedium);
    writeLeft($im, sprintf('%s %%', number_format((float)round(($icuCase-$ventCase)/$activeCase * 100, 2, PHP_ROUND_HALF_UP), 1, '.', ',')), 18, $box[2]+10, $box[3], $colorBlack, $fontInterBold, $colorTextBg);
    unset($box);

    // write ventil case
    $box = writeLeft($im, number_format($ventCase), 18, 500, 785, $colorBlack, $fontInterMedium);
    writeLeft($im, sprintf('%s %%', number_format((float)round(($ventCase)/$activeCase * 100, 2, PHP_ROUND_HALF_UP), 1, '.', ',')), 18, $box[2]+10, $box[3], $colorBlack, $fontInterBold, $colorTextBg);
    unset($box);

    //
    // write accumulated recover case
    $box = writeRight($im, number_format($accumulatedRecoverCase), 16, 925, 413, $colorBlack, $fontInterMedium);
    // write new recover case
    writeLeft($im, (($newRecoverCase>0)?'+':'').number_format($newRecoverCase), 16, $box[2]+10, $box[3], $colorBlack, $fontInterBold, $colorTextBg);
    unset($box);

    //
    // write accumulated death case
    $box = writeRight($im, number_format($accumulatedDeathCase), 16, 930, 640, $colorBlack, $fontInterMedium);
    // write new death case
    writeLeft($im, (($newDeathCase>0)?'+':'').number_format($newDeathCase), 16, $box[2]+10, $box[3], $colorBlack, $fontInterBold, $colorTextBg);
    unset($box);

    //
    // write accumulated BID death case
    $box = writeRight($im, number_format($accumulatedBIDeathCase), 16, 930, 750, $colorBlack, $fontInterMedium);
    // write new death case
    writeLeft($im, (($newBIDeathCase>0)?'+':'').number_format($newBIDeathCase), 16, $box[2]+10, $box[3], $colorBlack, $fontInterBold, $colorTextBg);
    unset($box);

    //
    //
    // draw percentage bar
    $barStartX = 33;$barStartY = 872;
    $barEndX = 1085;$barEndY = 888;
    $barWidth = $barEndX - $barStartX;
    $greenBarWidth = round(($barWidth * (($qaHomeCase)/$activeCase * 100)/100), 0, PHP_ROUND_HALF_UP);
    $yellowBarWidth = round(($barWidth * (($pkrcCase)/$activeCase * 100)/100), 0, PHP_ROUND_HALF_UP);
    $orangeBarWidth = round(($barWidth * (($hospitalCase)/$activeCase * 100)/100), 0, PHP_ROUND_HALF_UP);
    $redBarWidth = round(($barWidth * (($icuCase-$ventCase)/$activeCase * 100)/100), 0, PHP_ROUND_HALF_UP);
    $brownBarWidth = round(($barWidth * (($ventCase)/$activeCase * 100)/100), 0, PHP_ROUND_HALF_UP);

    //echo $greenBarWidth."|".$yellowBarWidth."|".$orangeBarWidth."|".$redBarWidth."|".$brownBarWidth."=".$barWidth;exit;

    imagefilledrectangle($im, $barStartX, $barStartY, $greenBarWidth, $barEndY, $colorGreenBar);
    imagefilledrectangle($im, $greenBarWidth, $barStartY, $greenBarWidth+$yellowBarWidth, $barEndY, $colorYellowBar);
    imagefilledrectangle($im, $greenBarWidth+$yellowBarWidth, $barStartY, $greenBarWidth+$yellowBarWidth+$orangeBarWidth, $barEndY, $colorOrangeBar);
    imagefilledrectangle($im, $greenBarWidth+$yellowBarWidth+$orangeBarWidth, $barStartY, $greenBarWidth+$yellowBarWidth+$orangeBarWidth+$redBarWidth, $barEndY, $colorRedBar);
    imagefilledrectangle($im, $greenBarWidth+$yellowBarWidth+$orangeBarWidth+$redBarWidth, $barStartY, $greenBarWidth+$yellowBarWidth+$orangeBarWidth+$redBarWidth+$brownBarWidth, $barEndY, $colorBrownBar);

    // write quarantine at home case (green)
    writeLeft($im, sprintf('%s / %s %%', number_format($qaHomeCase), number_format((float)round(($qaHomeCase)/$activeCase * 100, 2, PHP_ROUND_HALF_UP), 1, '.', ',')), 13, 55, 948, $colorBlack, $fontInterMedium); 
    // write quarantine center case (yellow)
    writeLeft($im, sprintf('%s / %s %%', number_format($pkrcCase), number_format((float)round(($pkrcCase)/$activeCase * 100, 2, PHP_ROUND_HALF_UP), 1, '.', ',')), 13, 294, 948, $colorBlack, $fontInterMedium); 
    // write hospital case (orange)
    writeLeft($im, sprintf('%s / %s %%', number_format($hospitalCase), number_format((float)round(($hospitalCase)/$activeCase * 100, 2, PHP_ROUND_HALF_UP), 1, '.', ',')), 13, 486, 948, $colorBlack, $fontInterMedium); 
    // write icu case (without ventil) (red)
    writeLeft($im, sprintf('%s / %s %%', number_format($icuCase-$ventCase), number_format((float)round(($icuCase-$ventCase)/$activeCase * 100, 2, PHP_ROUND_HALF_UP), 1, '.', ',')), 13, 660, 948, $colorBlack, $fontInterMedium); 
    // write ventil case (brown)
    writeLeft($im, sprintf('%s / %s %%', number_format($ventCase), number_format((float)round(($ventCase)/$activeCase * 100, 2, PHP_ROUND_HALF_UP), 1, '.', ',')), 13, 906, 948, $colorBlack, $fontInterMedium); 

    // Output the image
    mkdir($outputPath, 0777, true);
    header('Content-Type: image/jpeg');
    imagejpeg($im, $outputFile);
    imagedestroy($im);
}

function generateLatestStatus($date)
{
    $templateFile = getcwd().'/template/latest_status.jpg';
    $outputPath = sprintf('%s/output/%s', getcwd(), $date);
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
        if (str_replace('-', '', $row['date']) <= str_replace('-', '', $date)) {
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
        if (str_replace('-', '', $row['date']) <= str_replace('-', '', $date)) {
            $accumulatedDeathCase += $row['deaths_new'];
        }
        
        if ($row['date'] == $date) {
            $newDeathCase = $row['deaths_new'];
        }
    }

    $csvIcu = loadCsvIcu();
    $icuCase = 0;
    $ventCase = 0;
    foreach ($csvIcu as $row) {
        if ($row['date'] == $date) {
            $icuCase += $row['icu_covid'] + $row['icu_pui'];
            $ventCase += $row['vent_covid'] + $row['vent_pui'];
        }
    }

    $csvPkrc = loadCsvPkrc();
    $pkrcCase = 0;
    foreach ($csvPkrc as $row) {
        if ($row['date'] == $date) {
            $pkrcCase += $row['pkrc_covid'] + $row['pkrc_pui'];
        }
    }

    $csvHospital = loadCsvHospital();
    $hospitalCase = 0;
    foreach ($csvHospital as $row) {
        if ($row['date'] == $date) {
            $hospitalCase += $row['hosp_covid'] + $row['hosp_pui'];
        }
    }

    $csvPopulation = loadCsvPopulation();
    $malaysiaPopulation = 0;
    foreach ($csvPopulation as $row) {
        if ($row['state'] == 'Malaysia') {
            $malaysiaPopulationArray = $row;
        }
    }

    // calc activeCase
    $activeCase = $accumulatedCase - $accumulatedRecoverCase - $accumulatedDeathCase;
    
    // vaccination
    $dateVax = sprintf('%s/%s/%s', $dateDay, $dateMonth, $dateYear);
    $csvVax = loadCsvVaccination();
    $accumulatedVax = $accumulatedPartialVax = $accumulatedFullVax = 0;
    foreach ($csvVax as $row) {
        if ($row['date'] <= $date) {
            $accumulatedVax = $row['cumul'];
            list($dateYearVax, $dateMonthVax, $dateDayVax) = explode('-', $row['date']);
            $dateVax = sprintf('%s/%s/%s', $dateDayVax, $dateMonthVax, $dateYearVax);
            $accumulatedPartialVax = $row['cumul_partial'];
            $accumulatedFullVax = $row['cumul_full'];
        }
    }

    // check to proceed to generate or not
    if (empty($newCase) && empty($newDeathCase) && empty($newRecoverCase) && empty($activeCase)) {
        echo "\nNot enough data to generate!\n";
        return;
    }


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
    // write active case
    writeCenter($im, number_format($activeCase), 53, 55, 599, 460, 90, imagecolorallocate($im, 51, 27, 89), $fontLatoBold);

    // write icu case
    writeCenter($im, number_format($icuCase), 35, 73, 734, 175, 50, $colorWhite, $fontLatoBold);
    // write ventil case
    writeCenter($im, number_format($ventCase), 35, 320, 690, 175, 50, $colorWhite, $fontLatoBold);

    //
    // write new death case
    writeCenter($im, number_format($newDeathCase), 53, 567, 590, 460, 90, imagecolorallocate($im, 60, 34, 20), $fontLatoBold);
    // write accumulated death case
    writeCenter($im, number_format($accumulatedDeathCase), 35, 565, 740, 455, 50, $colorWhite, $fontLatoBold);

    // vaccination
    writeCenter($im, sprintf('(Sehingga %s)', $dateVax), 16, 800, 855, 210, 24, $colorWhite, $fontLatoBold);
    writeCenter($im, number_format($accumulatedVax), 35, 672, 982, 368, 40, $colorWhite, $fontLatoBold);

    // write partial vax percentage
    writeCenter($im, sprintf('%s %%', number_format((float)round($accumulatedPartialVax/$malaysiaPopulationArray['pop'] * 100, 2, PHP_ROUND_HALF_UP), 1, '.', ',')), 15, 382, 915, 63, 43, $colorWhite, $fontLatoBold);
    // write partial vax number
    writeCenter($im, sprintf('%s juta', number_format($accumulatedPartialVax/1000000, 1, '.', '')), 30, 465, 918, 160, 43, $colorWhite, $fontLatoBold);
    ;
    // write full vax percentage
    writeCenter($im, sprintf('%s %%', number_format((float)round($accumulatedFullVax/$malaysiaPopulationArray['pop'] * 100, 2, PHP_ROUND_HALF_UP), 1, '.', ',')), 15, 382, 975, 63, 43, $colorWhite, $fontLatoBold);
    ;
    // write full vax number
    writeCenter($im, sprintf('%s juta', number_format($accumulatedFullVax/1000000, 1, '.', '')), 30, 465, 978, 160, 43, $colorWhite, $fontLatoBold);

    // Output the image
    mkdir($outputPath, 0777, true);
    header('Content-Type: image/jpeg');
    imagejpeg($im, $outputFile);
    imagedestroy($im);
}

// x, y start from lower left of first character
function writeLeft($im, $text, $size, $x, $y, $color, $font, $bgColor=false)
{
    $box = imagettfbbox($size, 0, $font, $text);

    if ($bgColor != false) {    
        $padding = 4;
        imagefilledrectangle($im, $x+$box[6]-$padding, $y+$box[7]-$padding, $x+$box[2]+$padding, $y+$box[3]+$padding, $bgColor);
    }
    imagettftext($im, $size, 0, $x, $y, $color, $font, $text);

    return array($x+$box[6], $y+$box[7], $x+$box[2], $y+$box[3]);
}

// x, y start from lower right of first character
function writeRight($im, $text, $size, $x, $y, $color, $font)
{
    $box = imagettfbbox($size, 0, $font, $text);
    
    $textWidth = abs($box[2]) - abs($box[0]);
    $textHeight = abs($box[5]) - abs($box[3]);
    $x = $x - $textWidth;

    imagettftext($im, $size, 0, $x, $y, $color, $font, $text);

    return array($x, $y-$textHeight, $x+$box[2], $y);
}

// x, y start from top left of the box
function writeCenter($im, $text, $size, $xOffset, $yOffset, $boxWidth, $boxHeight, $color, $font)
{
    $box = imagettfbbox($size, 0, $font, $text);
    $text_width = abs($box[2]) - abs($box[0]);
    $text_height = abs($box[5]) - abs($box[3]);
    $x = $xOffset + ($boxWidth - $text_width) / 2;
    $y = $yOffset + ($boxHeight + $text_height) / 2;

    // add text
    imagettftext($im, $size, 0, $x, $y, $color, $font, $text);

    return array($x, $y-$text_height, $x+$text_width, $y);
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

function loadCsvVaccination()
{
    $file = sprintf('%s/input/citf/vaccination/vax_malaysia.csv', getcwd());
    $rows   = array_map('str_getcsv', file($file));
    $header = array_shift($rows);
    $csv    = array();
    foreach ($rows as $row) {
        $csv[] = array_combine($header, $row);
    }

    return $csv;
}
function loadCsvPopulation()
{
    $file = sprintf('%s/input/moh/static/population.csv', getcwd());
    $rows   = array_map('str_getcsv', file($file));
    $header = array_shift($rows);
    $csv    = array();
    foreach ($rows as $row) {
        $csv[] = array_combine($header, $row);
    }

    return $csv;
}
