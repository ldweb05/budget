from pathlib import Path

path = Path("www/config.php")
text = path.read_text(encoding="utf-8")

text = text.replace(
    "define('USER_APP', 'lorenzo');",
    "define('USER_APP', getenv('APP_USER'));"
)
text = text.replace(
    "define('PASS_APP', 'Summer77@');",
    "define('PASS_APP', getenv('APP_PASSWORD'));"
)

path.write_text(text, encoding="utf-8")
print("www/config.php aggiornato")
