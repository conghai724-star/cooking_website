from pathlib import Path
p=Path('app/views/admin/moderation/reports/index.php')
lines=p.open('r',encoding='utf-8',errors='replace').read().splitlines()
print(repr(lines[544]))
