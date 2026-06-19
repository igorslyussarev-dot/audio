import sqlite3
import json

def verify_db():
    conn = sqlite3.connect('dialogs.db')
    cursor = conn.cursor()
    
    # Query all rows
    cursor.execute("SELECT id, mode, emp, topic, script, tone, lost_profit FROM dialogs")
    rows = cursor.fetchall()
    
    print(f"--- VERIFICATION ---")
    print(f"Total rows found: {len(rows)}")
    for row in rows:
        print(f"ID: {row[0]} | Mode: {row[1]} | Emp: {row[2]} | Topic: {row[3]} | Compliance: {row[4]} | Tone: {row[5]} | Lost profit: {row[6]} KZT")
    
    conn.close()

if __name__ == "__main__":
    verify_db()
