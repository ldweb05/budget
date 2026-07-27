from pathlib import Path

sql = """SET FOREIGN_KEY_CHECKS=0;
TRUNCATE TABLE fondo_risparmio;
TRUNCATE TABLE spese_variabili;
TRUNCATE TABLE spese_fisse;
TRUNCATE TABLE mesi;
SET FOREIGN_KEY_CHECKS=1;
"""

Path("reset_budget_data.sql").write_text(sql, encoding="utf-8")
print("Creato reset_budget_data.sql")
