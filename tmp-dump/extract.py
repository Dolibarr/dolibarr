import urllib.request

url = 'https://raw.githubusercontent.com/Dolibarr/dolibarr/develop/htdocs/core/class/commonobject.class.php'
req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
data = urllib.request.urlopen(req, timeout=120).read().decode('utf-8', 'replace')
lines = data.split('\n')
out = open('tmp-dump/code_slice2.txt', 'w')
for i in range(3010, 3132):
    if i < len(lines):
        out.write(str(i + 1) + '\t' + lines[i].replace('\r', '<CR>') + '\n')
out.close()
print('done')
