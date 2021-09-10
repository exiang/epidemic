# epidemic

I developed this for my wife who used to the old reporting format. This PHP generator will grab the open data from MOH and CITF github to dynamically generate the old graphical EDM format that MOH released everyday. 

This script run from command line on machine installed with > PHP7.2 and has GD enabled. Developed on Mac and havent tested on other OS yet.

Feel free to contribute.

## Running it:
```php generate.php 2020-12-31```

This command will execute to generate image reporting at `/output/yyyy-mm-dd/latest_status.jpg`
Make sure the related directory is writable.

For now, we only support the latest status summary reporting.

## Notes:
- Figures on this EDM is not 100% in sync with https://covidnow.moh.gov.my/
- The following discrepancy is observed as on 2021-09-08: 
  - icu cases in website: 1282 (icu_covid+icu_pui) while EDM: 904
  - vent cases in website: 744 (vent_covid+vent_pui) while EDM: 430
- I have decided to follow website as the calculation is known and clear
