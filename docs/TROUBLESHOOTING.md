python - <<'PY'
from pathlib import Path

file = Path("docs/PRODUCTION_LOG.md")

with file.open("r", encoding="utf-8") as f:
    for line_number, line in enumerate(f, start=1):
        if line_number > 2000:
            break
        print(f"{line_number}: {line}", end="")
PY





python - <<'PY'
from pathlib import Path

file = Path("docs/PRODUCTION_LOG.md")

with file.open("r", encoding="utf-8") as f:
    for line_number, line in enumerate(f, start=1):
        if line_number >= 2000:
            print(f"{line_number}: {line}", end="")
PY