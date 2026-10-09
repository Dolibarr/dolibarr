import re, html

page = open('tmp-dump/page.html', encoding='utf-8', errors='replace').read()
print('PAGE_SIZE=' + str(len(page)))
out = open('tmp-dump/debug.txt', 'w')
out.write('PAGE_SIZE=' + str(len(page)) + '\n')
idxs = [m.start() for m in re.finditer(r'PHPSTAN', page)]
out.write('PHPSTAN_IDXS=' + str(idxs[:20]) + '\n')
for n, idx in enumerate(idxs[:8]):
    out.write('\n===== OCCURRENCE ' + str(n) + ' at ' + str(idx) + ' =====\n')
    out.write(page[idx:idx + 3500])
    out.write('\n')
# count php-ish patterns
out.write('\nhtdocs php occurrences: ' + str(len(re.findall(r'htdocs/', page))) + '\n')
# show a sample around first htdocs occurrence
h = page.find('htdocs/')
if h >= 0:
    out.write('\n===== AROUND FIRST htdocs =====\n')
    out.write(page[max(0, h - 1500):h + 1500])
out.close()
print('done')
