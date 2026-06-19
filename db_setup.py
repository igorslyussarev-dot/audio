import sqlite3
import json

def setup_db():
    db_file = 'dialogs.db'
    
    conn = sqlite3.connect(db_file)
    cursor = conn.cursor()

    # Enable Foreign Key support in SQLite
    cursor.execute("PRAGMA foreign_keys = ON;")

    # Drop existing tables to recreate schema cleanly
    cursor.execute("DROP TABLE IF EXISTS transcripts;")
    cursor.execute("DROP TABLE IF EXISTS dialogs;")

    # Create normalized tables without 'review' and 'audit'
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS dialogs (
            id INTEGER PRIMARY KEY,
            mode TEXT NOT NULL,
            emp TEXT NOT NULL,
            time TEXT NOT NULL,
            topic TEXT NOT NULL,
            script TEXT NOT NULL,
            tone TEXT NOT NULL,
            lost_profit INTEGER NOT NULL
        )
    ''')

    cursor.execute('''
        CREATE TABLE IF NOT EXISTS transcripts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            dialog_id INTEGER NOT NULL,
            content TEXT NOT NULL,
            FOREIGN KEY (dialog_id) REFERENCES dialogs(id) ON DELETE CASCADE
        )
    ''')

    # Dialogs datasets from demo.html (audit and review fields removed)
    azs_dialogs = [
        { 
            "id": 4829, 
            "emp": "Асель Н.", 
            "time": "14:20", 
            "topic": "Заправка АИ-95 + Попытка допродажи", 
            "script": "57%", 
            "tone": "Нейтральный", 
            "transcript": "<p><b>Кассир:</b> Добрый день, добро пожаловать!</p><p><b>Клиент:</b> Здравствуйте. Мне АИ-95 на 5000 тенге.</p><p><b>Кассир:</b> Хорошо. Кофе брать не будете?</p><p><b>Клиент:</b> Нет, спасибо.</p><p><b>Кассир:</b> Что-нибудь еще?</p><p><b>Клиент:</b> Возьму вот эту воду и печенье. (Сумма товаров в магазине: 2200 тг)</p><p><b>Кассир:</b> Хорошо. 3 колонка, АИ-95 на 5000 тенге плюс сопутствующие товары. Оплата картой?</p><p><b>Клиент:</b> Да, картой.</p><p><i>(Кассир проводит оплату, выдает чек в руки молча)</i></p><p><b>Кассир:</b> Счастливого пути!</p><p><b>Клиент:</b> До свидания.</p>",
            "lost_profit": 3000
        },
        { 
            "id": 4830, 
            "emp": "Анна К.", 
            "time": "14:45", 
            "topic": "Идеальный чек: Топливо + Кофе + Выпечка + Промо", 
            "script": "100%", 
            "tone": "Позитивный", 
            "transcript": "<p><b>Кассир:</b> Добрый день, добро пожаловать на PETRO DEMO! Меня зовут Анна. Какое топливо заправляем?</p><p><b>Клиент:</b> Здравствуйте, АИ-92 на 4000 тенге.</p><p><b>Кассир:</b> Отлично. Попробуйте наш фирменный свежемолотый кофе в дорогу! Вам большой стакан — это выгодно и удобно держать.</p><p><b>Клиент:</b> Давайте капучино.</p><p><b>Кассир:</b> К кофе рекомендую нашу свежую выпечку — возьмите вкусный хот-дог или самсу. Вам один или парочку?</p><p><b>Клиент:</b> Давайте один хот-дог.</p><p><b>Кассир:</b> Супер! У вас уже покупка на 3200 тенге в магазине. Возьмите еще бутылку воды или стеклоомыватель, сумма превысит 4000 тенге, и мы подарим вам талон на 2 литра АИ-92!</p><p><b>Клиент:</b> О, давайте воду тогда.</p><p><b>Кассир:</b> Отлично. Повторим заказ: 2 колонка, АИ-92 на 4000 тенге, плюс большой капучино, хот-дог и вода. Общая сумма магазина 4100 тенге. Оплата картой?</p><p><b>Клиент:</b> Да.</p><p><i>(Кассир проводит оплату, выдает чек и талон на бензин молча)</i></p><p><b>Кассир:</b> Пожалуйста, ваш чек и талон на 2 литра бензина. Счастливого пути и хорошего дня! Ждем вас снова!</p><p><b>Клиент:</b> Спасибо большое, взаимно!</p>",
            "lost_profit": 0
        },
        { 
            "id": 4831, 
            "emp": "Дмитрий С.", 
            "time": "15:10", 
            "topic": "Заправка АИ-92 + Вопрос про чек", 
            "script": "71%", 
            "tone": "Нейтральный", 
            "transcript": "<p><b>Кассир:</b> Добрый день! Добро пожаловать!</p><p><b>Клиент:</b> Здравствуйте. 5 колонка, АИ-92 на 3000 тенге.</p><p><b>Кассир:</b> Возьмите наш фирменный кофе в дорогу! Вам большой стакан?</p><p><b>Клиент:</b> Да, давайте большой капучино.</p><p><b>Кассир:</b> Перекусите у нас, рекомендую свежие пирожки или самсу.</p><p><b>Клиент:</b> Нет, спасибо, только кофе.</p><p><b>Кассир:</b> Хорошо. 5 колонка, АИ-92 на 3000 тенге и большой капучино. Оплата наличными?</p><p><b>Клиент:</b> Да, наличными.</p><p><b>Кассир:</b> Вам чек нужен?</p><p><b>Клиент:</b> Да, давайте.</p><p><i>(Кассир выдает сдачу и чек)</i></p><p><b>Кассир:</b> Всего хорошего! Счастливого пути!</p><p><b>Клиент:</b> До свидания.</p>",
            "lost_profit": 0
        },
        { 
            "id": 4828, 
            "emp": "Павел Б.", 
            "time": "13:50", 
            "topic": "Быстрая оплата топлива без допродаж", 
            "script": "29%", 
            "tone": "Нейтральный", 
            "transcript": "<p><b>Кассир:</b> Здрасьте.</p><p><b>Клиент:</b> Добрый день. Мне 20 литров АИ-92 на 4 колонку.</p><p><b>Кассир:</b> 4600 тенге к оплате.</p><p><b>Клиент:</b> Держите карту.</p><p><b>Кассир:</b> Что-нибудь еще?</p><p><b>Клиент:</b> Нет.</p><p><b>Кассир:</b> Чек нужен?</p><p><b>Клиент:</b> Да.</p><p><i>(Кассир выдает чек молча)</i></p><p><b>Кассир:</b> До свидания.</p>",
            "lost_profit": 3000
        }
    ]

    pharmacy_dialogs = [
        { 
            "id": 2045, 
            "emp": "Виктория", 
            "time": "11:15", 
            "topic": "Антибиотик + Пробиотик", 
            "script": "100%", 
            "tone": "Позитивный", 
            "transcript": "<p><b>Фармацевт:</b> Здравствуйте! Чем могу вам помочь?</p><p><b>Клиент:</b> Здравствуйте, мне нужен Амоксиклав.</p><p><b>Фармацевт:</b> Да, есть в наличии. Вам дозировку 1000 мг назначили?</p><p><b>Клиент:</b> Да, ее.</p><p><b>Фармацевт:</b> Обратите внимание: поскольку это сильный антибиотик, к нему обязательно нужен пробиотик для защиты кишечника и микрофлоры. Могу предложить Линекс форте или Бифиформ. Какой предпочитаете?</p><p><b>Клиент:</b> Давайте Линекс.</p><p><b>Фармацевт:</b> Отлично. Также сейчас сезон простуд, могу порекомендовать витамин C по акции. Желаете?</p><p><b>Клиент:</b> Да, давайте.</p><p><b>Фармацевт:</b> Хорошо. Итого: Амоксиклав 1000 мг, Линекс форте и витамин C. С вас 5400 тенге. Оплата картой?</p><p><b>Клиент:</b> Да, картой.</p><p><i>(Проводит оплату, выдает чек)</i></p><p><b>Фармацевт:</b> Пожалуйста, ваш чек и препараты. Будьте здоровы, не болейте!</p>",
            "lost_profit": 0
        },
        { 
            "id": 2044, 
            "emp": "Ирина", 
            "time": "10:50", 
            "topic": "Замена препарата", 
            "script": "55%", 
            "tone": "Напряженный", 
            "transcript": "<p><b>Фармацевт:</b> Здравствуйте.</p><p><b>Клиент:</b> Добрый день. Есть Но-Шпа форте?</p><p><b>Фармацевт:</b> Нет, закончилась.</p><p><b>Клиент:</b> Жаль. До свидания.</p><p><b>Фармацевт:</b> До свидания.</p>",
            "lost_profit": 1200
        },
        { 
            "id": 2043, 
            "emp": "Евгений", 
            "time": "10:20", 
            "topic": "Курс витаминов", 
            "script": "90%", 
            "tone": "Позитивный", 
            "transcript": "<p><b>Фармацевт:</b> Здравствуйте! Что вас интересует?</p><p><b>Клиент:</b> Мне нужен комплекс витаминов для энергии.</p><p><b>Фармацевт:</b> Могу предложить Супрадин, отличный сбалансированный комплекс.</p><p><b>Клиент:</b> А есть что-то подешевле? Он дорогой.</p><p><b>Фармацевт:</b> Есть наш отечественный Компливит, он дешевле в три раза, но состав у Супрадина более полный и усваивается лучше, плюс шипучие таблетки быстрее действуют. Для быстрого эффекта Супрадин лучше подойдет.</p><p><b>Клиент:</b> Хорошо, давайте тогда Супрадин.</p><p><b>Фармацевт:</b> Пожалуйста. Оплата наличными?</p><p><b>Клиент:</b> Да.</p><p><i>(Проводит оплату, выдает чек)</i></p><p><b>Фармацевт:</b> Ваш чек и витамины. Принимайте по одной таблетке утром. Всего хорошего, приходите к нам еще!</p>",
            "lost_profit": 0
        }
    ]

    def generate_archive_dialogs(base_list):
        sorted_base = sorted(base_list, key=lambda x: x["id"], reverse=True)
        result = list(sorted_base)
        
        last_id = sorted_base[-1]["id"]
        last_time_str = sorted_base[-1]["time"]
        hours, minutes = map(int, last_time_str.split(':'))
        
        for i in range(24):
            base = sorted_base[i % len(sorted_base)]
            last_id -= 1
            
            minutes -= 15
            if minutes < 0:
                minutes += 60
                hours -= 1
                if hours < 0:
                    hours += 24
            time_str = f"{hours:02d}:{minutes:02d}"
            
            archive_item = dict(base)
            archive_item["id"] = last_id
            archive_item["time"] = time_str
            result.append(archive_item)
            
        return result

    # Generate complete sets
    full_azs = generate_archive_dialogs(azs_dialogs)
    full_pharmacy = generate_archive_dialogs(pharmacy_dialogs)

    # Insert into dialogs and transcripts
    def insert_records(dataset, mode_name):
        for d in dataset:
            # 1. Insert into dialogs table
            cursor.execute('''
                INSERT OR REPLACE INTO dialogs (id, mode, emp, time, topic, script, tone, lost_profit)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ''', (
                d["id"],
                mode_name,
                d["emp"],
                d["time"],
                d["topic"],
                d["script"],
                d["tone"],
                d["lost_profit"]
            ))
            
            # 2. Insert transcript into transcripts table
            cursor.execute('''
                INSERT INTO transcripts (dialog_id, content)
                VALUES (?, ?)
            ''', (
                d["id"],
                d["transcript"]
            ))

    insert_records(full_azs, "azs")
    insert_records(full_pharmacy, "pharmacy")

    conn.commit()
    print(f"Database successfully populated! Total records: {len(full_azs) + len(full_pharmacy)}")
    conn.close()

if __name__ == "__main__":
    setup_db()
