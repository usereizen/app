#!/usr/bin/env python

import json
import urllib2
import sys
import multiprocessing.pool as mpool
from threading import Thread, Lock
from time import sleep

SOLR_URL = 'http://prod.search-master.service.sjc.consul:8983/solr/main/select?q={query}&fl=id,html_{lang}&wt=json&indent=true&rows={limit}'
SOLR_QUERY = 'indexed:[2013-{dateFrom}:00:00Z+TO+2013-{dateTo}:00:00Z]+AND+lang:{lang}+AND+NOT+html_{lang}:*'

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

datetimes = ['06-29T00', '06-30T00', '06-30T12', '07-01T00', '07-02T00', '07-03T00', '07-04T00', '07-05T00']

data = {}
lock = Lock()

gen_ids = len(sys.argv) > 1 and sys.argv[1] == 'gen_ids'


def print_data():
    if gen_ids:
        return
    lock.acquire()
    print '\n'
    print '\n'
    print '\n'
    print '\n'
    print '\n'
    print '\n'
    print '\n'
    print '\n'
    print '\n'
    print '\n'
    for key in sorted(data.keys()):
        print key, data[key]
    lock.release()


def query(output, lang, dateFrom, dateTo):
    query = SOLR_QUERY.format(lang=lang, dateFrom=dateFrom, dateTo=dateTo)
    url = SOLR_URL.format(query=query, limit=2000 * 1000, lang=lang)
    key = '%s_%s' % (dateFrom, dateTo)
    if not gen_ids:
        output[key] = '__'
    print_data()
    res = json.loads(urllib2.urlopen(url).read())
    numFound = res['response']['numFound']
    html_f = 'html_' + lang
    assert numFound < 1900 * 1000

    broken_ids = []
    docs = res['response']['docs']
    for doc in docs:
        if html_f not in doc or 'id' not in doc:
            continue
        if doc[html_f] == ' ':
            broken_ids.append(doc['id'])
    if gen_ids:
        output[key] = broken_ids
    else:
        output[key] = len(broken_ids)
    print_data()


pool = mpool.ThreadPool(40)

for lang in langs:
    data[lang] = {}
    for i in range(len(datetimes) - 1):
        pool.apply_async(query, args=(data[lang], lang, datetimes[i], datetimes[i + 1]))

pool.close()
pool.join()

if gen_ids:
    print json.dumps(data)
    sys.exit(0)


print '\n'
print '\n'
print '\n'
print '\n'


for lang in langs:
    if gen_ids:
        sum = []
    else:
        sum = 0

    for key in data[lang]:
        sum += data[lang][key]

    print lang, sum


