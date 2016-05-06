#!/usr/bin/env python

import json
import sys
import urllib2

# batch: 0, 1, 2 or 3
batch = int(sys.argv[1])

langs = (
    'en',
    'es',
    'pt',
    'de',
    'fr',
    'ru',
    'pl',
    'zh',
    'ja',
    'nl',
    'ko',
    'it',
    'fi',
    'tr',
    'hu',
    'sv',
    'ro',
    'el',
    'no',
    'id',
    'th',
    'da',
    'bg',
    'ca',
    'ar',
    'gl',
    'fa',
    'hi',
    'eu',
    'lv',
    'ga',
)

SOLR_URL = 'http://prod.search-master.service.sjc.consul:8983/solr/main/select?q={query}&fl=wid,wikipages,wiki_images&wt=json&indent=true&rows={limit}'
SOLR_QUERY = 'indexed:[2013-06-29T00%3A00%3A00Z+TO+2013-07-30T00%3A00%3A00Z]+AND+lang:{lang}+AND+NOT+html_{lang}:*'


for lang in langs[batch::4]:
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
