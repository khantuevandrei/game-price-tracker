<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Telegram') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            @if ($user->telegram_id)
            {{ __('Your Telegram is linked. You can unlink it at any time.') }}
            @else
            {{ __('Link your Telegram to get price alerts directly in chat.') }}
            @endif
        </p>
    </header>

    <div class="mt-6 space-y-4">
        @if ($user->telegram_id)
        <p class="text-sm text-gray-700">
            {{ __('Linked chat ID:') }} <code>{{ $user->telegram_id }}</code>
        </p>

        <button
            type="button"
            id="tg-unlink-btn"
            class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
            {{ __('Unlink Telegram') }}
        </button>
        @else
        <button
            type="button"
            id="tg-link-btn"
            class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
            {{ __('Link Telegram') }}
        </button>

        <div id="tg-link-result" class="hidden mt-4 p-4 bg-gray-100 rounded">
            <p class="text-sm text-gray-700">
                {{ __('Open the bot and send:') }}
            </p>
            <p class="mt-2 font-mono text-lg">
                <a id="tg-bot-link" href="#" target="_blank" class="text-blue-600 underline"></a>
            </p>
            <p class="mt-2 font-mono text-lg">
                /link <span id="tg-link-code" class="font-bold"></span>
            </p>
            <p class="mt-2 text-xs text-gray-500">
                {{ __('Code expires in 10 minutes.') }}
            </p>
        </div>
        @endif
    </div>
</section>

<script>
    (() => {
        const csrf = @json(csrf_token());
        const linkUrl = @json(route('telegram.link.generate'));
        const unlinkUrl = @json(route('telegram.unlink'));
        const confirmMsg = @json(__('Unlink Telegram from your account?'));

        const linkBtn = document.getElementById('tg-link-btn');
        if (linkBtn) {
            linkBtn.onclick = async () => {
                linkBtn.disabled = true;
                try {
                    const r = await fetch(linkUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json'
                        },
                    });
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    const data = await r.json();
                    document.getElementById('tg-link-code').textContent = data.code;
                    const link = document.getElementById('tg-bot-link');
                    link.href = 'https://t.me/' + data.bot_username;
                    link.textContent = '@' + data.bot_username;
                    document.getElementById('tg-link-result').classList.remove('hidden');
                } catch (e) {
                    console.error(e);
                    alert('Failed: ' + e.message);
                } finally {
                    linkBtn.disabled = false;
                }
            };
        }

        const unlinkBtn = document.getElementById('tg-unlink-btn');
        if (unlinkBtn) {
            unlinkBtn.onclick = async () => {
                if (!confirm(confirmMsg)) return;
                unlinkBtn.disabled = true;
                await fetch(unlinkUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },
                });
                location.reload();
            };
        }
    })();
</script>
