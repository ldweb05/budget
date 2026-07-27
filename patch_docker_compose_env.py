from pathlib import Path

path = Path("docker-compose.yml")
text = path.read_text(encoding="utf-8")

text = text.replace(
    "      MYSQL_ROOT_PASSWORD: root_password_super_segreta_2026",
    "      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}"
)
text = text.replace(
    "      MYSQL_DATABASE: budget_db",
    "      MYSQL_DATABASE: ${MYSQL_DATABASE}"
)
text = text.replace(
    "      MYSQL_USER: lorenzo",
    "      MYSQL_USER: ${MYSQL_USER}"
)
text = text.replace(
    "      MYSQL_PASSWORD: budget_password_2026",
    "      MYSQL_PASSWORD: ${MYSQL_PASSWORD}"
)

path.write_text(text, encoding="utf-8")
print("docker-compose.yml aggiornato")
