<?php
// Главная страница личного кабинета (Дашборд на PHP)
require_once 'config.php';

// Получаем GET-параметры
$mode = isset($_GET['mode']) && $_GET['mode'] === 'pharmacy' ? 'pharmacy' : 'azs';
$stationId = isset($_GET['station']) ? $_GET['station'] : 'all';
$filter = isset($_GET['filter']) && in_array($_GET['filter'], ['all', 'problem', 'upsell']) ? $_GET['filter'] : 'all';

try {
    // 1. Получаем список доступных станций для выпадающего списка
    $stmt = $pdo->prepare("SELECT id, name FROM stations WHERE mode = :mode ORDER BY id");
    $stmt->execute(['mode' => $mode]);
    $stationsList = $stmt->fetchAll();

    // Название текущей выбранной точки
    $currentStationName = 'Все станции';
    if ($stationId !== 'all') {
        $stmt = $pdo->prepare("SELECT name FROM stations WHERE id = :id AND mode = :mode");
        $stmt->execute(['id' => $stationId, 'mode' => $mode]);
        $row = $stmt->fetch();
        if ($row) {
            $currentStationName = $row['name'];
        }
    }

    // 2. Получаем диалоги (выбираем только необходимые для рендеринга столбцы)
    $dialogsQuery = "
        SELECT d.id, d.time, d.operation_type, d.score, d.tone, d.transcript, 
               e.name as employee_name, e.role as employee_role
        FROM dialogs d
        JOIN employees e ON d.employee_id = e.id
        WHERE d.mode = :mode
    ";
    $params = ['mode' => $mode];

    if ($stationId !== 'all') {
        $dialogsQuery .= " AND d.station_id = :station_id";
        $params['station_id'] = $stationId;
    }

    if ($filter === 'problem') {
        $dialogsQuery .= " AND d.is_problem = true";
    } elseif ($filter === 'upsell') {
        $dialogsQuery .= " AND d.is_upsell = true";
    }

    $dialogsQuery .= " ORDER BY d.id DESC";
    $stmt = $pdo->prepare($dialogsQuery);
    $stmt->execute($params);
    $dialogs = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Ошибка выполнения запросов к СУБД PostgreSQL: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет | AiProtocol</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <img src="favicon.svg" alt="Logo" class="logo-svg">
            <span class="logo-text"><?php echo $mode === 'azs' ? 'PETRO DEMO' : 'PHARMA DEMO'; ?></span>
        </div>

        <!-- Переключатель сегментов -->
        <div style="margin-bottom: 30px; display: flex; flex-direction: column; gap: 8px;">
            <div style="font-size: 10px; color: var(--c-text-muted); text-transform: uppercase; font-weight: 800; letter-spacing: 0.5px; margin-bottom: 4px;">Сфера решения</div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                <a href="index.php?mode=azs" class="btn <?php echo $mode === 'azs' ? 'btn--primary' : 'btn--outline'; ?>" style="font-size: 11px; padding: 8px 0;">АЗС</a>
                <a href="index.php?mode=pharmacy" class="btn <?php echo $mode === 'pharmacy' ? 'btn--primary' : 'btn--outline'; ?>" style="font-size: 11px; padding: 8px 0;">Аптеки</a>
            </div>
        </div>

        <!-- Вкладки на чистом CSS (Только вкладка Диалоги) -->
        <div class="nav-group">
            <div class="nav-link active">💬 Диалоги</div>
        </div>
    </aside>

    <!-- Main Layout -->
    <div class="main-layout">
        <!-- Header -->
        <header class="header">
            <div>
                <div style="font-size:11px; text-transform:uppercase; color:var(--c-text-muted); font-weight:800; letter-spacing:0.5px; margin-bottom:4px;">Филиал</div>
                <div class="header-title"><?php echo htmlspecialchars($currentStationName); ?></div>
            </div>

            <div style="display:flex; align-items:center; gap:20px;">
                <!-- Выпадающий список выбора точек -->
                <div class="dropdown">
                    <button class="dropdown-btn">
                        <span>📍 Выбор точки</span>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <div class="dropdown-content">
                        <a href="index.php?mode=<?php echo $mode; ?>&station=all&filter=<?php echo $filter; ?>" class="dropdown-item <?php echo $stationId === 'all' ? 'active' : ''; ?>">Все станции</a>
                        <?php foreach ($stationsList as $st): ?>
                            <a href="index.php?mode=<?php echo $mode; ?>&station=<?php echo $st['id']; ?>&filter=<?php echo $filter; ?>" class="dropdown-item <?php echo $stationId === $st['id'] ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($st['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg, #3B82F6, #2563EB); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:14px; color:#fff;">A</div>
            </div>
        </header>

        <!-- View: Dialogs -->
        <div class="content-body">
            <!-- Таблица сессий/диалогов -->
            <div class="filter-tabs">
                <a href="index.php?mode=<?php echo $mode; ?>&station=<?php echo $stationId; ?>&filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">Все сессии</a>
                <a href="index.php?mode=<?php echo $mode; ?>&station=<?php echo $stationId; ?>&filter=problem" class="filter-tab <?php echo $filter === 'problem' ? 'active' : ''; ?>">Проблемы с лояльностью</a>
                <a href="index.php?mode=<?php echo $mode; ?>&station=<?php echo $stationId; ?>&filter=upsell" class="filter-tab <?php echo $filter === 'upsell' ? 'active' : ''; ?>">Идеальная допродажа</a>
            </div>

            <div class="table-wrap">
                <table class="p-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Сотрудник</th>
                            <th>Время</th>
                            <th>Тип операции</th>
                            <th>Скрипт</th>
                            <th>Тон</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dialogs as $d): ?>
                            <tr onclick="openModal(<?php echo $d['id']; ?>)">
                                <td style="opacity:0.4; font-weight:700;">#<?php echo $d['id']; ?></td>
                                <td>
                                    <div style="font-weight:700; color:#fff;"><?php echo htmlspecialchars($d['employee_name']); ?></div>
                                    <div style="font-size:10px; color:var(--c-text-muted); margin-top:2px;"><?php echo htmlspecialchars($d['employee_role']); ?></div>
                                </td>
                                <td style="color:var(--c-text-muted);"><?php echo date('d.m.Y H:i', (int)$d['time']); ?></td>
                                <td style="max-width:320px; color:var(--c-text-muted); font-size:13px; line-height:1.4;"><?php echo htmlspecialchars($d['operation_type']); ?></td>
                                <td>
                                    <span class="u-chip <?php echo $d['score'] >= 80 ? 'u-chip--green' : 'u-chip--red'; ?>"><?php echo $d['score']; ?>%</span>
                                </td>
                                <td style="color:#10B981"><?php echo htmlspecialchars($d['tone']); ?></td>
                                <td style="text-align:right;">
                                    <button class="btn btn--outline" style="padding:6px 12px; font-size:11px;">Слушать</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Модальные окна для каждой сессии диалога -->
    <?php foreach ($dialogs as $d): 
        $transcriptData = json_decode($d['transcript'], true);
    ?>
        <div class="demo-modal-overlay" id="modal-<?php echo $d['id']; ?>" onclick="closeModal(<?php echo $d['id']; ?>)">
            <div class="demo-modal" onclick="event.stopPropagation()">
                <span class="modal-close-btn" onclick="closeModal(<?php echo $d['id']; ?>)">×</span>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; border-bottom:1px solid rgba(255,255,255,0.06); padding-bottom:15px;">
                    <h2 style="font-weight:900; font-size:18px; color:#fff; letter-spacing:0.5px;">Анализ сессии #<?php echo $d['id']; ?></h2>
                </div>
                
                <div>
                    <div>

                        

                        
                        <!-- Расшифровка диалога -->
                        <div style="font-weight:800; margin-bottom:12px; font-size: 12px; color:#A0AEC0; text-transform:uppercase; letter-spacing:0.5px;">Расшифровка диалога</div>
                        <div style="background:rgba(0,0,0,0.2); padding:20px; border-radius:16px; border:1px solid rgba(255,255,255,0.04); display:flex; flex-direction:column; gap:12px;">
                            <?php foreach ($transcriptData as $t): 
                                $isStaff = ($t['speaker'] === 'КАССИР' || $t['speaker'] === 'ФАРМАЦЕВТ');
                            ?>
                                <div style="align-self: <?php echo $isStaff ? 'flex-start' : 'flex-end'; ?>; max-width:85%; background: rgba(59,130,246,0.08); padding:10px 14px; border-radius: 16px 16px <?php echo $isStaff ? '16px 0px' : '0px 16px'; ?>; color:#fff; border: 1px solid rgba(59,130,246,0.15); border-<?php echo $isStaff ? 'left' : 'right'; ?>: 4px solid #3B82F6;">
                                    <span style="font-size:9px; font-weight:800; color: #3B82F6; display:block; margin-bottom:4px; text-align: <?php echo $isStaff ? 'left' : 'right'; ?>;"><?php echo htmlspecialchars($t['speaker']); ?></span>
                                    <span style="line-height:1.5; font-size:13px;"><?php echo htmlspecialchars($t['text']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    <?php endforeach; ?>

    <script>
        function openModal(id) {
            const modal = document.getElementById('modal-' + id);
            if (modal) {
                modal.classList.add('active');
            }
        }

        function closeModal(id) {
            const modal = document.getElementById('modal-' + id);
            if (modal) {
                modal.classList.remove('active');
            }
        }
    </script>
</body>
</html>
