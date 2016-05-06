#!/usr/bin/env python

import json
import sys
import urllib2

SOLR_URL = 'http://prod.search-master.service.sjc.consul:8983/solr/main/select?q={query}&fl=id,html_{lang}&wt=json&indent=true&rows={limit}'
SOLR_QUERY = 'indexed:[2013-06-29T00%3A00%3A00Z+TO+2013-07-30T00%3A00%3A00Z]+AND+wid:{wid}+AND+lang:{lang}+AND+NOT+html_{lang}:*'

total_num = 0

lang = sys.argv[1]
wids = open('wids-' + lang + '.txt').read().split('\n')

f = open('arts-' + lang + '.txt', 'w')

for wid in wids:
    if not wid:
        continue

    if total_num > 20000:
        break

    print lang, wid

    arts = []
    html_f = 'html_' + lang

    url = SOLR_URL.format(query=SOLR_QUERY.format(lang=lang, wid=wid), limit=12000, lang=lang)
    data = json.loads(urllib2.urlopen(url).read())

    for doc in data['response']['docs']:
        if html_f not in doc:
            continue

        if doc[html_f] == ' ':
            assert doc['id']
            arts.append(doc['id'].replace(wid + '_', ''))

    num = len(arts)
    print lang, wid, num, 'documents'

    if len(arts) == 0:
        continue

    arts = sorted(arts[:10000])
    total_num += num
    f.write('{num} {wid} {artsJson}\n'.format(num=num, wid=wid, artsJson=json.dumps(arts)))
    f.flush()

f.close()
print '\n\n', lang, 'total:', total_num, '\n\n'
