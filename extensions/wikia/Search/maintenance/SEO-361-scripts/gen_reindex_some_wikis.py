#!/usr/bin/env python

import json

out = {}
data = json.loads(open('ids.json').read())

for lang in data:
    for period in data[lang]:
        if data[lang][period] == -1:
            print 'ERROR for', lang
            continue
        for id in data[lang][period]:
            wid, aid = id.split('_')
            if wid not in out:
                out[wid] = []
            out[wid].append(aid)

limit = 10 * 1000;
for offset in range(0, 1600 * 1000, limit):
    f1 = open('../batch-' + str(offset) + '.php', 'w')
    f2 = open('../start-batch-' + str(offset) + '.sh', 'w')
    f1.write('<?php\n')
    for wid in sorted(out.keys()):
        ids = out[wid][offset:offset+limit]
        count = len(out[wid])
        if len(ids):
            f1.write('$w[%s] = %s;\n' % (wid, json.dumps(ids)))
            f2.write('AAAA=%d SERVER_ID=%s php ./reindex-batch-%d.php %s %d\n' % (999999999 - count, wid, offset, wid, count))
    f1.close()
    f2.close()

