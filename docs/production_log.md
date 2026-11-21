python3 - <<'EOF' > /tmp/copy_welcome.txt
import re
p='resources/views/contact.blade.php'
for i,l in enumerate(open(p,encoding='utf-8'),1):
    t=re.sub(r'<[^>]+>',' ',l)
    t=re.sub(r'\s+',' ',t).strip()
    if len(t)>2 and not t.startswith(('@','{{--')) and not re.match(r'^[\w:@.-]+=',t):
        print(f'{i}: {t}')
EOF
cat /tmp/copy_welcome.txt
grep -niE "carousel|swiper|slide" resources/views/welcome.blade.php | head -20