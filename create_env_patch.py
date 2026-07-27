from pathlib import Path

env = """MYSQL_ROOT_PASSWORD=root_password_super_segreta_2026
MYSQL_DATABASE=budget_db
MYSQL_USER=lorenzo
MYSQL_PASSWORD=budget_password_2026

APP_USER=lorenzo
APP_PASSWORD=Summer77@
"""

Path(".env").write_text(env, encoding="utf-8")

gitignore = Path(".gitignore")
lines = gitignore.read_text(encoding="utf-8").splitlines() if gitignore.exists() else []
if ".env" not in lines:
    lines.append(".env")
gitignore.write_text("\n".join(lines) + "\n", encoding="utf-8")

print("Creati .env e .gitignore aggiornato")
