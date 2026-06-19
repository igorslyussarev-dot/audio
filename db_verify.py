import sqlite3
import json

def verify_db():
    conn = sqlite3.connect('dialogs.db')
    cursor = conn.cursor()
    
    # Query utilizing INNER JOIN to verify relations
    cursor.execute('''
        SELECT d.id, d.mode, d.emp, d.topic, d.script, d.tone, d.lost_profit, t.content
        FROM dialogs d
        INNER JOIN transcripts t ON d.id = t.dialog_id
    ''')
    rows = cursor.fetchall()
    
    print(f"--- VERIFICATION ---")
    print(f"Total rows found (joined): {len(rows)}")
    
    # Print the first 5 records as a sample
    for row in rows[:5]:
        # Print sample, cleaning up HTML transcript length for readability
        transcript_sample = row[7][:60] + "..." if len(row[7]) > 60 else row[7]
        print(f"ID: {row[0]} | Mode: {row[1]} | Emp: {row[2]} | Topic: {row[3]} | Compliance: {row[4]} | Tone: {row[5]} | Lost profit: {row[6]} KZT")
        print(f"   -> Transcript sample: {transcript_sample}")
    
    conn.close()

if __name__ == "__main__":
    verify_db()
