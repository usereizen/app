#!/bin/bash

batch=1

while sleep 60; do
   if [ `pgrep -fa reindex  | wc -l` -lt 8 ];
     ./gen_wids_per_lang.py
     ./gen_articles_to_reindex_in_parallel.sh $batch
     ./gen_reindex_some_wikis_shell.sh $batch
     batch=$((batch+2))
     batch=$((batch%4))
     (
       cd ..
       cat reindex-some-wikis.sh | parallel -j 10 -u
     )
   fi
done

