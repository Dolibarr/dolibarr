import re, html

page = open('tmp-dump/page.html', encoding='utf-8', errors='replace').read()
print('PAGE_SIZE=' + str(len(page)))
idxs = [m.start() for m in re.finditer(r'PHPSTAN', page)]
print('PHPSTAN_IDXS=' + str(idxs[:10]))
best = []
best_at = -1
for idx in idxs:
    window = page[idx:idx + 90000]
    for tm in re.finditer(r'<table[^>]*>(.*?)</table>', window, re.S):
        table = tm.group(1)
        rows = []
        for tr in re.findall(r'<tr[^>]*>(.*?)</tr>', table, re.S):
            tds = re.findall(r'<t[dh][^>]*>(.*?)</t[dh]>', tr, re.S)
            if len(tds) < 3:
                continue
            clean = [html.unescape(re.sub(r'<[^>]+>', '', t)).strip() for t in tds[:3]]
            rows.append('\t'.join(clean))
        if len(rows) > 5 and sum(1 for r in rows if '.php' in r) > 5 and len(rows) > len(best):
            best = rows
            best_at = idx
open('tmp-dump/phpstan_debt.txt', 'w').write('\n'.join(best))
print('BEST_AT=' + str(best_at))
print('ROWS=' + str(len(best)))
for r in best[:10]:
    print('ROW: ' + r)
