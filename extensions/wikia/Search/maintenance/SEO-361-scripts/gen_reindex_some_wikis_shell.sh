#!/bin/bash

langs="en
es
pt
de
fr
ru
pl
zh"

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

