import sys

path = 'htdocs/core/class/commonobject.class.php'
with open(path, 'rb') as f:
    data = f.read()

# Sanity checks: no carriage returns, UTF-8 decodable
assert b'\r' not in data, 'CR found in source file'
data.decode('utf-8')

targets = [
    b'\t\t\t\t\t\t\t\t/** @var CommandeFournisseur $this */\n',
    b'\t\t\t\t\t\t\t\t/** @var FactureFournisseur $this */\n'
]
for t in targets:
    count = data.count(t)
    assert count == 1, 'expected exactly 1 occurrence of %r, found %d' % (t, count)
    data = data.replace(t, b'')

assert b'\r' not in data
data.decode('utf-8')
with open(path, 'wb') as f:
    f.write(data)
print('PATCH_OK')
