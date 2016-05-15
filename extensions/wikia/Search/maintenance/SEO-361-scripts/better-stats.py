#!/usr/bin/env python

import json
import urllib2
import sys

SOLR_URL = 'http://prod.search-master.service.sjc.consul:8983/solr/main/select?q={query}&fl=wid,html_{lang}&wt=json&indent=true&rows={limit}'
SOLR_QUERY = 'indexed:[2013-{dateFrom}:00:00Z+TO+2013-{dateTo}:00:00Z]+AND+lang:{lang}+AND+NOT+html_{lang}:*'

top_langs = (
    'en',
    'es',
    'pt',
    'de',
    'fr',
    'ru',
    'pl',
    'zh',
)

other_langs = (
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

dates = ['06-29', '06-30', '07-01', '07-02', '07-03', '07-04', '07-05']
top_times = ['T%02d' % (i, ) for i in range(0, 24, 6)]
other_times = ['T00']
top_times = ['T00']

for lang in top_langs + other_langs:
    datetimes = []
    for date in dates:
        times = top_times if lang in top_langs else other_times
        for time in times:
            datetimes.append(date + time)
    numBroken = 0
    html_f = 'html_' + lang
    for i in range(len(datetimes) - len(times)):
        query = SOLR_QUERY.format(lang=lang, dateFrom=datetimes[i], dateTo=datetimes[i + 1])
        print query, 
        url = SOLR_URL.format(query=query, limit=1, lang=lang)
        data = json.loads(urllib2.urlopen(url).read())
        numFound = data['response']['numFound']
        print numFound
        continue
        assert numFound < 500000
        docs = data['response']['docs']
        for doc in docs:
            if html_f not in doc:
                continue
            if doc[html_f] == ' ':
                numBroken += 1
    print lang, numBroken

