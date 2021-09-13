# epidemic

I developed this for my wife who used to the old reporting format. This PHP generator will grab the open data from MOH and CITF github to dynamically generate in the old graphical reporting format that MOH released everyday prior to September 2021. 

This script run from command line on machine installed with > PHP7.2 and has GD enabled. Developed on Mac but havent tested on other OS yet.

## Running it:
```php generate.php 2020-12-31```

This command will execute to generate image reporting at `/output/yyyy-mm-dd/latest_status.jpg`
Make sure the related directory is writable.

For now, it only support the `latest status` summary reporting.

## Setup:
GIt submodule is use here to get data from MOH and CITF. First time running pls do: `git submodule update --init --recursive`

on ubuntu server you may need to symlink gdate `sudo ln -s $(which date) /bin/gdate` for the sh to function correctly

execute run.sh will required git credential. To prevent it asking everytime, run `git config --global credential.helper cache`.
Then, run `./run.sh` and you will be prompt for credential, insert once to register and it will never ask again. 
If needed, you can unset it using `git config --global --unset user.password`

## Supported Output Format:

### Latest Summary (latest_summary)
This format was use by MOH from ? until 8 SEP 2021.

<img src="https://github.com/exiang/epidemic/blob/main/original/latest_status.jpg?raw=true" alt="Sample" width="200"/>

### Latest Summary version 1 (latest_summary.v1, in progress)
This format is use by MOH from 9 SEP 2021 until ? align with the launching of covidnow.moh.gov.my

<img src="https://github.com/exiang/epidemic/blob/main/original/latest_status.v1.jpg?raw=true" alt="Sample" width="200"/>

## Notes:
- Figures on this reporting may not 100% in sync with https://covidnow.moh.gov.my/
- The following discrepancy is observed as on 2021-09-08: 
  - icu cases in website: 1282 (icu_covid+icu_pui) while mine: 904
  - vent cases in website: 744 (vent_covid+vent_pui) while mine: 430
- Discrepancy feedback been submitted to covidnow team thru twitter

## Contributes:
Feel free to contribute to this open source project
