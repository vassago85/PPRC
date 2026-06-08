@if (blank($bodyHtml))
    <p class="text-sm text-gray-500 dark:text-gray-400">
        No message body was captured for this entry. Bodies are stored for emails sent after this feature was enabled, or when delivery failed before the message was rendered.
    </p>
@else
    <iframe
        title="Email preview"
        srcdoc="{!! htmlspecialchars($bodyHtml, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') !!}"
        class="w-full rounded-lg border border-gray-200 dark:border-white/10"
        style="min-height: 640px; background: #0b1120;"
        sandbox=""
        loading="lazy"
    ></iframe>
@endif
