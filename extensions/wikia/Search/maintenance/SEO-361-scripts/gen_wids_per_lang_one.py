#!/usr/bin/env python

import json
import sys
import urllib2

SOLR_URL = 'http://prod.search-master.service.sjc.consul:8983/solr/main/select?q={query}&fl=wid,wikipages,wiki_images&wt=json&indent=true&rows={limit}'
SOLR_QUERY = 'indexed:[2013-06-29T00%3A00%3A00Z+TO+2013-07-30T00%3A00%3A00Z]+AND+lang:{lang}+AND+NOT+html_{lang}:*'


lang = sys.argv[1]
print lang,

wids = []

f = open('wids-' + lang + '.txt', 'w')

url = SOLR_URL.format(query=SOLR_QUERY.format(lang=lang), limit=10000)
data = json.loads(urllib2.urlopen(url).read())
for doc in data['response']['docs']:
    total_pages = int(doc['wiki_images']) + int(doc['wikipages'])
    wids.append([total_pages, str(doc['wid'])])

wids = list(set([i[1] for i in sorted(wids, reverse=True)]))
f.write('\n'.join(wids) + '\n')
f.close()
print len(wids), 'wids'
