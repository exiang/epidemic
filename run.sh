# for mac, please install `brew install coreutils` first

git pull origin main
cd input/moh
git checkout main
git pull
cd ../..
cd input/citf
git checkout main
git pull
cd ../..
echo $(gdate +%F)
echo $(gdate -d "yesterday" +%F)
echo $(gdate -d "-2 days" +%F)
php generate.php $(gdate +%F)
php generate.php $(gdate -d "yesterday" +%F)
php generate.php $(gdate -d "-2 days" +%F)
git add output
git commit -am "Pulled down update of git sub modules and regenerated for $(date +%F) and up to 2 days before"
git push