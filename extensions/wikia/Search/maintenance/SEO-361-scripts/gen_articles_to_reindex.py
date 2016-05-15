#!/usr/bin/env python

import json
import sys
import urllib2

SOLR_URL = 'http://prod.search-master.service.sjc.consul:8983/solr/main/select?q={query}&fl=wid,id,html_{lang}&wt=json&indent=true&rows={limit}'
SOLR_QUERY = 'indexed:[2013-06-29T00%3A00%3A00Z+TO+2013-07-30T00%3A00%3A00Z]+AND+lang:{lang}+AND+NOT+html_{lang}:*'

lang = sys.argv[1]

print lang

arts = {}
html_f = 'html_' + lang

url = SOLR_URL.format(query=SOLR_QUERY.format(lang=lang), limit=100000, lang=lang)
data = json.loads(urllib2.urlopen(url).read())

for doc in data['response']['docs']:
    if html_f not in doc:
        continue

    if doc[html_f] == ' ':
        assert doc['id']
        wid = str(doc['wid'])
        wprefix = wid + '_'
        if doc['id'] == '328780':
            continue
        if wid not in arts:
            arts[wid] = []
        arts[wid].append(doc['id'].replace(wprefix, ''))

f = open('arts-' + lang + '.txt', 'w')
for wid in arts:
    arts_wid = sorted(arts[wid])
    f.write('{num} {wid} {artsJson}\n'.format(num=len(arts_wid), wid=wid, artsJson=json.dumps(arts_wid)))
f.close()
