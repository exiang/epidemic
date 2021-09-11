# for mac, please install `brew install coreutils` first

cd input/moh
git checkout master
git pull
cd ..
cd input/citf
git checkout master
git pull
cd ..
echo $(gdate +%F)
echo $(gdate -d "yesterday" +%F)
php generate.php $(gdate +%F)
php generate.php $(gdate -d "yesterday" +%F)
git add output
git commit -am "Pulled down update of git sub modules and regenerated for $(date +%F) and day before"
git push