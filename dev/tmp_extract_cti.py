import re, html

raw = open('page.html', encoding='utf-8', errors='replace').read()
raw = re.sub(r'(?is)<(script|style)[^>]*>.*?</\1>', '', raw)
raw = re.sub(r'(?i)</t[dh]>', ' | ', raw)
raw = re.sub(r'(?i)</tr>', '\n', raw)
raw = re.sub(r'(?i)</(p|div|li|h[1-6]|table)>', '\n', raw)
txt = re.sub(r'(?s)<[^>]+>', '', raw)
txt = html.unescape(txt)
txt = re.sub(r'[ \t]+', ' ', txt)
txt = re.sub(r'\n{3,}', '\n\n', txt)
open('page.txt', 'w', encoding='utf-8').write(txt)
n = len(txt)
print('text length', n)
i = 0
part = 0
while i < n:
    open('cti_part%02d.txt' % part, 'w', encoding='utf-8').write(txt[i:i+25000])
    i += 25000
    part += 1
print('parts', part)
