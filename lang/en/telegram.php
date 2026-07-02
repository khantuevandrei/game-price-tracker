<?php

return [
    'cancelled' => 'Action cancelled.',

    'start' => "Hi! This is a Steam game price tracking bot.\n\n"
        . "Available commands:\n"
        . "/start — greeting\n"
        . "/email — link email\n"
        . "/search — find a game\n"
        . "/track — track a game\n"
        . "/list — view tracked games\n"
        . "/price — view game info\n"
        . "/set — set target price\n"
        . "/untrack — stop tracking\n"
        . "/notify — toggle notifications\n"
        . "/cancel — cancel current command\n"
        . "/help — show commands",

    'help' => "Available commands:\n"
        . "/start — greeting\n"
        . "/email — link email\n"
        . "/search — find a game\n"
        . "/track — track a game\n"
        . "/list — view tracked games\n"
        . "/price — view game info\n"
        . "/set — set target price\n"
        . "/untrack — stop tracking\n"
        . "/notify — toggle notifications\n"
        . "/cancel — cancel current command\n"
        . "/help — show commands",

    // Email flow
    'email_current' => "Current email: :email\n\nEnter a new email to change it.\n\n/cancel to abort.",
    'email_prompt' => "Enter your email.\n\n/cancel to abort.",
    'email_invalid' => 'Invalid email format. Try /email again.',
    'email_code_sent' => 'A verification code has been sent to your email. Send it back here.',
    'email_code_invalid' => "Wrong code. Try again.\n\n/cancel to abort.",
    'email_linked' => "Email linked.\nNew password for the website login: :password",

    // Search flow
    'search_prompt' => "Send me the game title.\n\n/cancel to abort.",
    'search_no_results' => 'Nothing found.',
    'search_result_item' => ":num. :title\n",
    'search_footer' => "\nSend /track 1 to add a game to tracking.",

    // Track
    'track_usage' => 'Usage: /track 1',
    'track_no_search' => 'Run /search first.',
    'track_no_details' => "Couldn't fetch game data.",
    'track_free' => 'This game is free. Tracking not needed.',
    'track_success' => ':title is now being tracked.',

    // List
    'list_empty' => 'No games are being tracked.',
    'list_header' => "Tracked games:\n\n",
    'list_item' => ":num. :title\n Price: :price | Target: :target\n\n",
    'target_unset' => 'not set',

    // Price
    'price_usage' => 'Usage: /price 1',
    'invalid_index' => 'Invalid number. Use /list to see the numbers.',
    'price_result' => ":title\nPrice: :price\nTarget: :target",

    // Set
    'set_usage' => 'Usage: /set 1 15.99',
    'set_success' => 'Target price for :title set: $:price',

    // Untrack
    'untrack_usage' => 'Usage: /untrack 1',
    'untrack_success' => ':title is no longer tracked.',

    // Notify
    'notify_need_start' => 'Run /start first.',
    'notify_email_toggled' => 'Email notifications :status.',
    'notify_telegram_toggled' => 'Telegram notifications :status.',
    'notify_invalid_param' => 'Invalid parameter. Use: /notify email, /notify telegram, /notify',
    'notify_status' => "Email: :email\nTelegram: :telegram",
    'notify_enabled' => 'enabled',
    'notify_disabled' => 'disabled',
    'notify_on' => 'on',
    'notify_off' => 'off',
];
