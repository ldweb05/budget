from pathlib import Path

path = Path("www/db.php")
text = path.read_text(encoding="utf-8")

text = text.replace("$user = 'lorenzo';", "$user = getenv('MYSQL_USER');")
text = text.replace("$password = 'budget_password_2026';", "$password = getenv('MYSQL_PASSWORD');")
text = text.replace("$database = 'budget_db';", "$database = getenv('MYSQL_DATABASE');")

path.write_text(text, encoding="utf-8")
print("www/db.php aggiornato")
