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
    echo ./gen_articles_to_reindex.py $lang
done | parallel -u -j 20
