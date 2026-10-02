import urllib.request, re

def fetch(url):
    req = urllib.request.Request(url, headers={'User-Agent': 'ci'})
    return urllib.request.urlopen(req, timeout=120).read().decode('utf-8', errors='replace')

def dump_range(content, a, b):
    lines = content.split('\n')
    out = []
    for i in range(a - 1, min(b, len(lines))):
        out.append('%d: %s' % (i + 1, lines[i]))
    return '\n'.join(out)

out = []

files = {
  'propal': ('htdocs/comm/propal/class/propal.class.php', [(2040, 2120)]),
  'commande': ('htdocs/commande/class/commande.class.php', [(2310, 2390)]),
}
for key, (path, ranges) in files.items():
    c = fetch('https://raw.githubusercontent.com/Dolibarr/dolibarr/develop/' + path)
    out.append('===== ' + path + ' (len ' + str(len(c)) + ', lines ' + str(c.count(chr(10))) + ')')
    for (a, b) in ranges:
        out.append('--- lines %d-%d' % (a, b))
        out.append(dump_range(c, a, b))

# CommonObjectLine class: find it
c = fetch('https://raw.githubusercontent.com/Dolibarr/dolibarr/develop/htdocs/core/class/commonobject.class.php')
lines = c.split('\n')
idxs = [i for i, l in enumerate(lines) if 'multilangs' in l]
out.append('===== commonobject.class.php multilangs lines: ' + str([i + 1 for i in idxs]))
for i in idxs:
    out.append(dump_range(c, i - 4, i + 6))

# Where is CommonObjectLine class defined?
idxclass = [i for i, l in enumerate(lines) if re.match(r'(abstract )?class CommonObjectLine', l)]
out.append('===== CommonObjectLine class def at lines: ' + str([i + 1 for i in idxclass]))
for i in idxclass:
    out.append(dump_range(c, i - 1, i + 120))

# Product class multilangs
cp = fetch('https://raw.githubusercontent.com/Dolibarr/dolibarr/develop/htdocs/product/class/product.class.php')
plines = cp.split('\n')
pidx = [i for i, l in enumerate(plines) if re.search(r'multilangs', l)]
out.append('===== product.class.php multilangs lines: ' + str([i + 1 for i in pidx]))
for i in pidx:
    out.append(dump_range(cp, i - 3, i + 5))

open('cti_extract2.txt', 'w', encoding='utf-8').write('\n'.join(out))
print('written', len(out), 'blocks')
