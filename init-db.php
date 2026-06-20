<?php
$host = 'localhost';
$port = '5432';
$user = 'postgres';
$pass = 'j3qq4h7h2v';

try {
    // 1. Создаем базу данных audiobag, если её нет
    $dsn = "pgsql:host=$host;port=$port;dbname=postgres";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Проверяем существование базы данных
    $stmt = $pdo->prepare("SELECT 1 FROM pg_database WHERE datname = 'audiobag'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->exec("CREATE DATABASE audiobag");
        echo "База данных audiobag успешно создана.\n";
    } else {
        echo "База данных audiobag уже существует.\n";
    }

    // 2. Подключаемся к audiobag и создаем таблицы
    $dsn = "pgsql:host=$host;port=$port;dbname=audiobag";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Удаляем старые таблицы, если они есть (каскадно)
    $pdo->exec("DROP TABLE IF EXISTS dialogs CASCADE");
    $pdo->exec("DROP TABLE IF EXISTS employees CASCADE");
    $pdo->exec("DROP TABLE IF EXISTS stations CASCADE");

    // Создаем таблицу станций
    $pdo->exec("
        CREATE TABLE stations (
            id VARCHAR(50) PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            mode VARCHAR(20) NOT NULL
        )
    ");

    // Создаем таблицу сотрудников
    $pdo->exec("
        CREATE TABLE employees (
            id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            role VARCHAR(100) NOT NULL,
            mode VARCHAR(20) NOT NULL
        )
    ");

    // Создаем таблицу диалогов
    $pdo->exec("
        CREATE TABLE dialogs (
            id SERIAL PRIMARY KEY,
            mode VARCHAR(20) NOT NULL,
            station_id VARCHAR(50) REFERENCES stations(id),
            employee_id INT REFERENCES employees(id),
            time BIGINT NOT NULL,
            operation_type TEXT NOT NULL,
            score INT NOT NULL,
            tone VARCHAR(50) NOT NULL,
            is_problem BOOLEAN NOT NULL DEFAULT FALSE,
            is_upsell BOOLEAN NOT NULL DEFAULT FALSE,
            transcript JSONB NOT NULL
        )
    ");
    // Создаем индексы для ускорения выборок и JOIN-запросов (нежные запросы)
    $pdo->exec("CREATE INDEX idx_dialogs_mode ON dialogs(mode)");
    $pdo->exec("CREATE INDEX idx_dialogs_station ON dialogs(station_id)");
    $pdo->exec("CREATE INDEX idx_dialogs_employee ON dialogs(employee_id)");
    $pdo->exec("CREATE INDEX idx_dialogs_filters ON dialogs(is_problem, is_upsell)");
    $pdo->exec("CREATE INDEX idx_stations_mode ON stations(mode)");

    echo "Таблицы и индексы успешно созданы.\n";

    // 3. Заполняем демо-данными
    // Станции
    $stations = [
        ['azs-1', 'АЗС №1 (пр. Аль-Фараби)', 'azs'],
        ['azs-2', 'АЗС №2 (ул. Саина)', 'azs'],
        ['pharm-1', 'Аптека №1 (ул. Гоголя)', 'pharmacy'],
        ['pharm-2', 'Аптека №2 (пр. Достык)', 'pharmacy'],
    ];
    $stmt = $pdo->prepare("INSERT INTO stations (id, name, mode) VALUES (?, ?, ?)");
    foreach ($stations as $st) {
        $stmt->execute($st);
    }

    // Сотрудники
    $employees = [
        ['Дмитрий С.', 'Оператор АЗС', 'azs'],
        ['Анна К.', 'Оператор АЗС', 'azs'],
        ['Асель Н.', 'Оператор АЗС', 'azs'],
        ['Павел Б.', 'Оператор АЗС', 'azs'],
        ['Мария И.', 'Фармацевт', 'pharmacy'],
        ['Елена В.', 'Фармацевт', 'pharmacy'],
    ];
    $stmt = $pdo->prepare("INSERT INTO employees (name, role, mode) VALUES (?, ?, ?)");
    foreach ($employees as $emp) {
        $stmt->execute($emp);
    }

    // Получаем сгенерированные ID сотрудников
    $stmt = $pdo->query("SELECT id, name FROM employees");
    $empMap = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $empMap[$row['name']] = $row['id'];
    }

    // Диалоги
    $dialogs = [
        [
            'azs', 'azs-1', $empMap['Дмитрий С.'], strtotime('2026-06-20 15:10:00'), 'Заправка АИ-92 + Вопрос про чек', 71, 'Нейтральный',
            1, 0, // is_problem=true, is_upsell=false
            json_encode([
                ['speaker' => 'КАССИР', 'text' => 'Добрый день! Какое топливо заправляем?'],
                ['speaker' => 'КЛИЕНТ', 'text' => 'Здравствуйте, 92-й на тысячу тенге, пожалуйста.'],
                ['speaker' => 'КАССИР', 'text' => 'Хорошо, вторая колонка. Оплата картой или наличными?'],
                ['speaker' => 'КЛИЕНТ', 'text' => 'Картой. А чек дадите?'],
                ['speaker' => 'КАССИР', 'text' => 'Да, конечно, вот ваш чек. Хорошего пути!']
            ], JSON_UNESCAPED_UNICODE)
        ],
        [
            'azs', 'azs-1', $empMap['Анна К.'], strtotime('2026-06-20 14:45:00'), 'Идеальный чек: Топливо + Кофе + Выпечка + Промо', 100, 'Позитивный',
            0, 1, // is_problem=false, is_upsell=true
            json_encode([
                ['speaker' => 'КАССИР', 'text' => 'Здравствуйте! Рады видеть вас на нашей АЗС. Меня зовут Анна.'],
                ['speaker' => 'КЛИЕНТ', 'text' => 'Привет! Мне 95-й до полного.'],
                ['speaker' => 'КАССИР', 'text' => 'Сделаем. Попробуйте сегодня наш свежесваренный капучино с фирменным сиропом и нежный круассан по акции.'],
                ['speaker' => 'КЛИЕНТ', 'text' => 'О, давайте кофе и круассан, отличная идея.'],
                ['speaker' => 'КАССИР', 'text' => 'Отличный выбор! Приложите карту. Ваш чек, приятного аппетита и хорошего дня!']
            ], JSON_UNESCAPED_UNICODE)
        ],
        [
            'azs', 'azs-2', $empMap['Асель Н.'], strtotime('2026-06-20 14:20:00'), 'Заправка АИ-95 + Попытка допродажи', 57, 'Нейтральный',
            1, 0, // is_problem=true, is_upsell=false
            json_encode([
                ['speaker' => 'КАССИР', 'text' => 'Добрый день. Что заливаем?'],
                ['speaker' => 'КЛИЕНТ', 'text' => '95-й на 5000 тенге.'],
                ['speaker' => 'КАССИР', 'text' => 'Кофе, чай не желаете?'],
                ['speaker' => 'КЛИЕНТ', 'text' => 'Нет, спасибо.'],
                ['speaker' => 'КАССИР', 'text' => 'Оплата прошла, пожалуйста.']
            ], JSON_UNESCAPED_UNICODE)
        ],
        [
            'azs', 'azs-2', $empMap['Павел Б.'], strtotime('2026-06-20 11:15:00'), 'Заправка АИ-95 без доп продаж', 47, 'Негативный',
            1, 0, // is_problem=true, is_upsell=false
            json_encode([
                ['speaker' => 'КАССИР', 'text' => 'Здравствуйте. Какая колонка?'],
                ['speaker' => 'КЛИЕНТ', 'text' => 'Третья, 95-й.'],
                ['speaker' => 'КАССИР', 'text' => 'Сумма?'],
                ['speaker' => 'КЛИЕНТ', 'text' => 'До полного.'],
                ['speaker' => 'КАССИР', 'text' => 'Оплачивайте. Всё готов.']
            ], JSON_UNESCAPED_UNICODE)
        ],
        [
            'pharmacy', 'pharm-1', $empMap['Мария И.'], strtotime('2026-06-20 10:30:00'), 'Покупка терафлю + Апселл витамина C', 90, 'Позитивный',
            0, 1, // is_problem=false, is_upsell=true
            json_encode([
                ['speaker' => 'ФАРМАЦЕВТ', 'text' => 'Здравствуйте! Чем я могу вам помочь?'],
                ['speaker' => 'КЛИЕНТ', 'text' => 'Дайте что-нибудь от простуды, голова раскалывается и насморк.'],
                ['speaker' => 'ФАРМАЦЕВТ', 'text' => 'Возьмите Терафлю, он быстро снимет симптомы. И обязательно добавьте витамин C в шипучих таблетках, он поможет организму быстрее справиться с вирусом.'],
                ['speaker' => 'КЛИЕНТ', 'text' => 'Хорошо, давайте и витамин C.'],
                ['speaker' => 'ФАРМАЦЕВТ', 'text' => 'С вас 3400 тенге. Выздоравливайте!']
            ], JSON_UNESCAPED_UNICODE)
        ],
        [
            'pharmacy', 'pharm-2', $empMap['Елена В.'], strtotime('2026-06-20 09:15:00'), 'Рецептурный препарат без доп продаж', 60, 'Нейтральный',
            1, 0, // is_problem=true, is_upsell=false
            json_encode([
                ['speaker' => 'ФАРМАЦЕВТ', 'text' => 'Добрый день. Ваш рецепт, пожалуйста.'],
                ['speaker' => 'КЛИЕНТ', 'text' => 'Вот, держите.'],
                ['speaker' => 'ФАРМАЦЕВТ', 'text' => 'Секунду... Да, этот препарат есть. Оплачивайте картой.'],
                ['speaker' => 'КЛИЕНТ', 'text' => 'Спасибо.']
            ], JSON_UNESCAPED_UNICODE)
        ]
    ];

    $stmt = $pdo->prepare("
        INSERT INTO dialogs (
            mode, station_id, employee_id, time, operation_type, score, tone, is_problem, is_upsell, transcript
        ) VALUES (?, ?, ?, ?, ?, ?, ?, CAST(? AS BOOLEAN), CAST(? AS BOOLEAN), ?)
    ");
    foreach ($dialogs as $d) {
        $stmt->execute($d);
    }

    echo "Демо-данные успешно импортированы.\n";

} catch (PDOException $e) {
    die("Ошибка при инициализации базы данных: " . $e->getMessage() . "\n");
}
