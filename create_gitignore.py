from pathlib import Path

content = """db_data/
*.log
.env
.DS_Store
"""

Path(".gitignore").write_text(content, encoding="utf-8")
print(".gitignore creato")
