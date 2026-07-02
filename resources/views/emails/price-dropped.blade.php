<h1>{{ __('alerts.mail_heading') }}</h1>
<p>{{ __('alerts.mail_body', [
    'game' => $trackedGame->game->title,
    'price' => $newPrice,
    'currency' => $trackedGame->game->currency,
]) }}</p>
<p>{{ __('alerts.mail_target', ['target' => $trackedGame->target_price]) }}</p>
<p><a href="{{ url('/games/'.$trackedGame->game->steam_app_id) }}">{{ __('alerts.mail_cta') }}</a></p>
