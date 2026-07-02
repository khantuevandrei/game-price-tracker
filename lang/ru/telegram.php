<?php

return [
    'not_linked' => 'Аккаунт не привязан. Отправь /start чтобы привязаться.',

    'cancelled' => 'Действие отменено.',

    'start' => "Привет! Это бот для отслеживания цен на игры в Steam.\n\n"
        ."Доступные команды:\n"
        ."/start — приветствие\n"
        ."/email — привязать email\n"
        ."/search — найти игру\n"
        ."/track — отслеживать игру\n"
        ."/list — посмотреть отслеживаемые игры\n"
        ."/price — посмотреть информацию об игре\n"
        ."/set — установить цель по цене\n"
        ."/untrack — прекратить отслеживание\n"
        ."/notify — вкл/откл уведомления\n"
        ."/link — привязать веб-аккаунт\n"
        ."/unlink — отвязать веб-аккаунт\n"
        ."/cancel — отмена текущей команды\n"
        .'/help — показать команды',

    'help' => "Доступные команды:\n"
        ."/start — приветствие\n"
        ."/email — привязать email\n"
        ."/search — найти игру\n"
        ."/track — отслеживать игру\n"
        ."/list — посмотреть отслеживаемые игры\n"
        ."/price — посмотреть информацию об игре\n"
        ."/set — установить цель по цене\n"
        ."/untrack — прекратить отслеживание\n"
        ."/notify — вкл/откл уведомления\n"
        ."/link — привязать веб-аккаунт\n"
        ."/unlink — отвязать веб-аккаунт\n"
        ."/cancel — отмена текущей команды\n"
        .'/help — показать команды',

    // Email flow
    'email_current' => "Текущий email: :email\n\nВведи новый email для смены.\n\n/cancel для отмены.",
    'email_prompt' => "Введи email.\n\n/cancel для отмены.",
    'email_invalid' => 'Неверный формат email. Попробуй /email ещё раз.',
    'email_code_sent' => 'На твой email отправлен код. Введи его.',
    'email_code_invalid' => "Неверный код. Попробуй ещё раз.\n\n/cancel для отмены.",
    'email_linked' => "Почта привязана.\nНовый пароль для входа на сайт: :password",

    // Search flow
    'search_prompt' => "Введи название игры.\n\n/cancel для отмены.",
    'search_no_results' => 'Ничего не найдено.',
    'search_result_item' => ":num. :title\n",
    'search_footer' => "\nОтправь /track 1 чтобы добавить игру в отслеживание.",

    // Track
    'track_usage' => 'Использование: /track 1',
    'track_no_search' => 'Сначала выполни поиск через /search',
    'track_no_details' => 'Не удалось получить данные игры.',
    'track_free' => 'Это бесплатная игра. Отслеживание не требуется.',
    'track_success' => ':title отслеживается.',

    // List
    'list_empty' => 'Игры не отслеживаются.',
    'list_header' => "Отслеживаемые игры:\n\n",
    'list_item' => ":num. :title\n Цена: :price | Цель: :target\n\n",
    'target_unset' => 'не задано',

    // Price
    'price_usage' => 'Пример использования: /price 1',
    'invalid_index' => 'Неверный номер. Используй /list чтобы увидеть номера.',
    'price_result' => ":title\nЦена: :price\nЦель: :target",

    // Set
    'set_usage' => 'Пример использования: /set 1 15.99',
    'set_success' => 'Цель для :title установлена: $:price',

    // Untrack
    'untrack_usage' => 'Пример использования: /untrack 1',
    'untrack_success' => ':title больше не отслеживается.',

    // Notify
    'notify_need_start' => 'Сначала используй /start',
    'notify_email_toggled' => 'Email уведомления :status.',
    'notify_telegram_toggled' => 'Telegram уведомления :status.',
    'notify_invalid_param' => 'Неверный параметр. Используй: /notify email, /notify telegram, /notify',
    'notify_status' => "Email: :email\nTelegram: :telegram",
    'notify_enabled' => 'включены',
    'notify_disabled' => 'отключены',
    'notify_on' => 'вкл',
    'notify_off' => 'откл',

    // Link (web → telegram)
    'link_usage' => 'Использование: /link ABC123',
    'link_invalid' => 'Неверный или истёкший код. Сгенерируй новый на сайте.',
    'link_success' => 'Telegram привязан к :email.',
    'link_merged' => 'Telegram привязан к :email. Отслеживаемые игры объединены.',

    // Link
    'unlink_not_linked' => 'Ты не привязан к веб-аккаунту. Отвязывать нечего.',
    'unlink_success' => "Telegram отвязан от :email.\nОтправь /start чтобы начать заново.",
];
