# epidemic

I developed this for my wife who used to the old reporting format. This PHP generator will grab the open data from MOH and CITF github to dynamically generate in the old graphical reporting format that MOH released everyday prior to SEP 2021. 

Then, new reporting format (covidnow look & feel) is supported too.

## Running it:
```php generate.php 2020-12-31```

This command will execute to generate image reporting at `/output/yyyy-mm-dd/latest_status.jpg`
Make sure the related directory is writable.

## Setup:
This script run from command line on machine installed with > PHP7.2 and has GD enabled. Developed on Mac, cron run on ubuntu server.

GIt submodule is use here to get data from MOH and CITF. First time running pls do: `git submodule update --init --recursive`

on ubuntu server you may need to symlink gdate `sudo ln -s $(which date) /bin/gdate` for the sh to function correctly

execute run.sh will required git credential. To prevent it asking everytime, run `git config --global credential.helper cache`.
Then, run `./run.sh` and you will be prompt for credential, insert once to register and it will never ask again. 
If needed, you can unset it using `git config --global --unset user.password`

## Supported Output Format:

### latest_summary
This format was use by MOH up to 8th SEP 2021.

<img src="https://github.com/exiang/epidemic/blob/main/original/latest_status.jpg?raw=true" alt="Sample" width="200" />&nbsp;<img src="https://github.com/exiang/epidemic/blob/main/template/latest_status.jpg?raw=true" alt="Sample" width="200" />

### latest_summary.v1
This format is use by MOH starting from 9th SEP 2021 in align with the launching of covidnow.moh.gov.my and has the same look and feel of the website.
This report will be generated if your entry date is on and after 1st SEP 2021

<img src="https://github.com/exiang/epidemic/blob/main/original/latest_status.v1.jpg?raw=true" alt="Sample" width="200" />&nbsp;<img src="https://github.com/exiang/epidemic/blob/main/template/latest_status.v1.jpg?raw=true" alt="Sample" width="200" />

### latest_summary.v2
This format is use by MOH starting from 16th SEP 2021.
This report will be generated if your entry date is on and after 16th SEP 2021

## Notes:
- Figures on this reporting may not 100% in sync with https://covidnow.moh.gov.my/
- The following discrepancy is observed as on 2021-09-08: 
  - icu cases in website: 1282 (icu_covid+icu_pui) while mine: 904
  - vent cases in website: 744 (vent_covid+vent_pui) while mine: 430
- Discrepancy feedback been submitted to covidnow team thru twitter

## Contributes:
Feel free to contribute to this open source project
