import sqlite3
import json

def verify_db():
    conn = sqlite3.connect('dialogs.db')
    cursor = conn.cursor()
    
    # Query utilizing INNER JOIN to verify relations
    cursor.execute('''
        SELECT d.id, d.mode, d.emp, d.topic, d.script, d.tone, d.lost_profit, t.speaker, t.text, t.sequence_order
        FROM dialogs d
        INNER JOIN transcripts t ON d.id = t.dialog_id
        ORDER BY d.id, t.sequence_order
    ''')
    rows = cursor.fetchall()
    
    print(f"--- VERIFICATION ---")
    print(f"Total rows found (joined transcript lines): {len(rows)}")
    
    # Print a sample of lines
    for row in rows[:15]:
        print(f"ID: {row[0]} | Order: {row[9]} | {row[7]}: {row[8]}")
    
    conn.close()

if __name__ == "__main__":
    verify_db()
