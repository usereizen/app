#!/usr/bin/env python

import json
import urllib2

SOLR_URL = 'http://prod.search-master.service.sjc.consul:8983/solr/main/select?q={query}&fl=wid&wt=json&indent=true&rows={limit}'
SOLR_QUERY = 'indexed:[2013-06-29T00%3A00%3A00Z+TO+2013-07-05T00%3A00%3A00Z]+AND+lang:{lang}+AND+NOT+html_{lang}:*'

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

for lang in langs:
    url = SOLR_URL.format(query=SOLR_QUERY.format(lang=lang), limit=1)
    data = json.loads(urllib2.urlopen(url).read())
    print lang + '\t' + str(data['response']['numFound'])
