#!/bin/bash

batch="$1"

langs0="en fr ja fi ro th ar eu"
langs1="es ru nl tr el da gl lv"
langs2="pt pl ko hu no bg fa ga"
langs3="de zh it sv id ca hi"

[ $batch = 0 ] && langs=$langs0
[ $batch = 1 ] && langs=$langs1
[ $batch = 2 ] && langs=$langs2
[ $batch = 3 ] && langs=$langs3

for lang in $langs; do
  cat arts-"$lang".txt | grep '"]$'
done |
  sort -unr |
  awk '{ print "SERVER_ID=" $2 " php reindex-some-wikis.php " $2 }' > ../reindex-some-wikis.sh


echo '<?php' > ../reindex-some-wikis-data.php
for lang in $langs; do
  cat arts-"$lang".txt | grep '"]$'
done |
  sort -unr |
  sed -e 's/[0-9]\+ \([0-9]\+\) /$w[\1] = /' -e 's/$/;/' >> ../reindex-some-wikis-data.php

php -l ../reindex-some-wikis-data.php

echo "Top wikis to index:"
for lang in $langs; do
  cat arts-"$lang".txt | grep '"]$' 
done |
  sort -unr |
  awk '{ print $2, $1 }' |
  head -n 20

