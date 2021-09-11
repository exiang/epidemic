# for mac, please install `brew install coreutils` first

cd input/moh
git checkout master
git pull
cd ..
cd input/citf
git checkout master
git pull
cd ..


input_start=2020-01-01
input_end=$(gdate +%F)

# After this, startdate and enddate will be valid ISO 8601 dates,
# or the script will have aborted when it encountered unparseable data
# such as input_end=abcd
startdate=$(gdate -I -d "$input_start") || exit -1
enddate=$(gdate -I -d "$input_end")     || exit -1

d="$startdate"
while [ "$d" != "$enddate" ]; do 
  echo $d
  d=$(gdate -I -d "$d + 1 day")
  php generate.php $d
done