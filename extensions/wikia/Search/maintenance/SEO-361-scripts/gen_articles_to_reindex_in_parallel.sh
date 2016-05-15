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
    echo ./gen_articles_to_reindex.py $lang
done | parallel -u -j 20
