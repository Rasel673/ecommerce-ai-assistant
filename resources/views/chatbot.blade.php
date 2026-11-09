@if(config('ecommerce-ai.ui.enabled', true))
<div id="ecommerce-ai-wrapper" class="ecommerce-ai-container">
    {{-- Floating Toggle Launcher Button --}}
    <button id="ecommerce-ai-launcher" type="button" aria-label="Open AI Assistant" title="Open AI Assistant">
        <svg class="ecommerce-ai-icon-chat" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
        <svg class="ecommerce-ai-icon-close" style="display:none;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
    </button>

    {{-- Chatbot Dialog Window --}}
    <div id="ecommerce-ai-window" class="ecommerce-ai-window" style="display: none;" data-endpoint="{{ url(config('ecommerce-ai.route.prefix', 'api/ecommerce-ai') . '/chat') }}">
        {{-- Header --}}
        <div class="ecommerce-ai-header">
            <div class="ecommerce-ai-header-info">
                <div class="ecommerce-ai-avatar">AI</div>
                <div>
                    <h4 class="ecommerce-ai-title">{{ config('ecommerce-ai.ui.title', 'Store AI Assistant') }}</h4>
                    <span class="ecommerce-ai-status"><span class="ecommerce-ai-status-dot"></span> Online</span>
                </div>
            </div>
            <button id="ecommerce-ai-close-btn" type="button" class="ecommerce-ai-close-btn" aria-label="Close">&times;</button>
        </div>

        {{-- Messages Container --}}
        <div id="ecommerce-ai-messages" class="ecommerce-ai-messages">
            <div class="ecommerce-ai-msg ecommerce-ai-msg-assistant">
                <div class="ecommerce-ai-bubble">
                    Hello! I am your store AI assistant. Ask me about sales metrics, orders, top products, or inventory levels.
                </div>
            </div>
        </div>

        {{-- Loading / Typing Indicator --}}
        <div id="ecommerce-ai-loading" class="ecommerce-ai-loading" style="display: none;">
            <div class="ecommerce-ai-typing-indicator">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <span class="ecommerce-ai-loading-text">Analyzing store data...</span>
        </div>

        {{-- Error Banner --}}
        <div id="ecommerce-ai-error" class="ecommerce-ai-error" style="display: none;"></div>

        {{-- Input Footer --}}
        <div class="ecommerce-ai-footer">
            <form id="ecommerce-ai-form">
                <input
                    type="text"
                    id="ecommerce-ai-input"
                    class="ecommerce-ai-input"
                    placeholder="Ask about sales, orders, or stock..."
                    autocomplete="off"
                    required
                />
                <button type="submit" id="ecommerce-ai-submit" class="ecommerce-ai-submit" aria-label="Send">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>

<style>
    :root {
        --ecommerce-ai-primary: {{ config('ecommerce-ai.ui.accent_color', '#7c3aed') }};
        --ecommerce-ai-primary-hover: #6d28d9;
        --ecommerce-ai-bg: #ffffff;
        --ecommerce-ai-text: #1f2937;
        --ecommerce-ai-muted: #6b7280;
        --ecommerce-ai-border: #e5e7eb;
    }

    .ecommerce-ai-container {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 999999;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }

    #ecommerce-ai-launcher {
        width: 58px;
        height: 58px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--ecommerce-ai-primary), var(--ecommerce-ai-primary-hover));
        color: #ffffff;
        border: none;
        cursor: pointer;
        box-shadow: 0 10px 25px -5px rgba(124, 58, 237, 0.5), 0 8px 10px -6px rgba(124, 58, 237, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    #ecommerce-ai-launcher:hover {
        transform: scale(1.08);
    }

    #ecommerce-ai-launcher svg {
        width: 26px;
        height: 26px;
    }

    .ecommerce-ai-window {
        position: absolute;
        bottom: 74px;
        right: 0;
        width: 380px;
        max-width: calc(100vw - 32px);
        height: 540px;
        max-height: calc(100vh - 120px);
        background: var(--ecommerce-ai-bg);
        border-radius: 16px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--ecommerce-ai-border);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        animation: ecommerceAiSlideIn 0.25s ease-out;
    }

    @keyframes ecommerceAiSlideIn {
        from { opacity: 0; transform: translateY(12px) scale(0.96); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .ecommerce-ai-header {
        background: linear-gradient(135deg, var(--ecommerce-ai-primary), var(--ecommerce-ai-primary-hover));
        color: #ffffff;
        padding: 14px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .ecommerce-ai-header-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .ecommerce-ai-avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 13px;
    }

    .ecommerce-ai-title {
        margin: 0;
        font-size: 15px;
        font-weight: 600;
    }

    .ecommerce-ai-status {
        font-size: 12px;
        opacity: 0.9;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .ecommerce-ai-status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background-color: #10b981;
        display: inline-block;
    }

    .ecommerce-ai-close-btn {
        background: transparent;
        border: none;
        color: #ffffff;
        font-size: 22px;
        cursor: pointer;
        padding: 0 4px;
        line-height: 1;
        opacity: 0.85;
    }

    .ecommerce-ai-close-btn:hover {
        opacity: 1;
    }

    .ecommerce-ai-messages {
        flex: 1;
        padding: 16px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 12px;
        background: #f9fafb;
    }

    .ecommerce-ai-msg {
        display: flex;
        flex-direction: column;
        max-width: 82%;
    }

    .ecommerce-ai-msg-assistant {
        align-self: flex-start;
    }

    .ecommerce-ai-msg-user {
        align-self: flex-end;
    }

    .ecommerce-ai-bubble {
        padding: 10px 14px;
        border-radius: 14px;
        font-size: 14px;
        line-height: 1.55;
        word-break: break-word;
    }

    .ecommerce-ai-bubble p {
        margin: 0 0 8px 0;
    }

    .ecommerce-ai-bubble p:last-child {
        margin-bottom: 0;
    }

    .ecommerce-ai-bubble ol.ecommerce-ai-list {
        margin: 8px 0;
        padding-left: 20px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .ecommerce-ai-bubble ul.ecommerce-ai-list {
        margin: 8px 0;
        padding-left: 20px;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .ecommerce-ai-bubble ul.ecommerce-ai-sublist {
        margin: 4px 0 6px 0;
        padding-left: 18px;
        display: flex;
        flex-direction: column;
        gap: 4px;
        color: #4b5563;
        font-size: 13px;
        list-style-type: circle;
    }

    .ecommerce-ai-bubble li {
        line-height: 1.45;
    }

    .ecommerce-ai-bubble strong {
        font-weight: 600;
        color: #111827;
    }

    .ecommerce-ai-msg-user .ecommerce-ai-bubble strong {
        color: #ffffff;
    }

    .ecommerce-ai-code {
        background: rgba(0, 0, 0, 0.06);
        color: #d946ef;
        padding: 1px 5px;
        border-radius: 4px;
        font-size: 12px;
        font-family: monospace;
    }

    .ecommerce-ai-spacer {
        height: 8px;
    }

    .ecommerce-ai-table-wrap {
        overflow-x: auto;
        margin: 8px 0;
    }

    .ecommerce-ai-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
        text-align: left;
    }

    .ecommerce-ai-table th, .ecommerce-ai-table td {
        padding: 6px 8px;
        border: 1px solid #e5e7eb;
    }

    .ecommerce-ai-table th {
        background: #f3f4f6;
        font-weight: 600;
    }

    .ecommerce-ai-msg-assistant .ecommerce-ai-bubble {
        background: #ffffff;
        color: var(--ecommerce-ai-text);
        border: 1px solid var(--ecommerce-ai-border);
        border-bottom-left-radius: 2px;
    }

    .ecommerce-ai-msg-user .ecommerce-ai-bubble {
        background: var(--ecommerce-ai-primary);
        color: #ffffff;
        border-bottom-right-radius: 2px;
    }

    .ecommerce-ai-loading {
        padding: 8px 16px;
        background: #f9fafb;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        color: var(--ecommerce-ai-muted);
    }

    .ecommerce-ai-typing-indicator {
        display: flex;
        gap: 4px;
    }

    .ecommerce-ai-typing-indicator span {
        width: 6px;
        height: 6px;
        background: var(--ecommerce-ai-primary);
        border-radius: 50%;
        animation: ecommerceAiBounce 1.2s infinite ease-in-out;
    }

    .ecommerce-ai-typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
    .ecommerce-ai-typing-indicator span:nth-child(3) { animation-delay: 0.4s; }

    @keyframes ecommerceAiBounce {
        0%, 80%, 100% { transform: scale(0); }
        40% { transform: scale(1); }
    }

    .ecommerce-ai-error {
        background: #fef2f2;
        color: #b91c1c;
        border-top: 1px solid #fecaca;
        font-size: 12px;
        padding: 8px 14px;
    }

    .ecommerce-ai-footer {
        padding: 12px;
        background: #ffffff;
        border-top: 1px solid var(--ecommerce-ai-border);
    }

    .ecommerce-ai-footer form {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
    }

    .ecommerce-ai-input {
        flex: 1;
        padding: 10px 14px;
        border: 1px solid var(--ecommerce-ai-border);
        border-radius: 20px;
        font-size: 14px;
        outline: none;
        transition: border-color 0.15s;
    }

    .ecommerce-ai-input:focus {
        border-color: var(--ecommerce-ai-primary);
    }

    .ecommerce-ai-submit {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: var(--ecommerce-ai-primary);
        color: #ffffff;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.15s;
    }

    .ecommerce-ai-submit:hover {
        background: var(--ecommerce-ai-primary-hover);
    }

    .ecommerce-ai-submit svg {
        width: 17px;
        height: 17px;
    }

    @media (max-width: 480px) {
        .ecommerce-ai-container {
            bottom: 16px;
            right: 16px;
        }
        .ecommerce-ai-window {
            width: calc(100vw - 32px);
            height: calc(100vh - 100px);
            bottom: 68px;
        }
    }
</style>

<script>
(function() {
    const launcher = document.getElementById('ecommerce-ai-launcher');
    const win = document.getElementById('ecommerce-ai-window');
    const closeBtn = document.getElementById('ecommerce-ai-close-btn');
    const form = document.getElementById('ecommerce-ai-form');
    const input = document.getElementById('ecommerce-ai-input');
    const messages = document.getElementById('ecommerce-ai-messages');
    const loading = document.getElementById('ecommerce-ai-loading');
    const errorBox = document.getElementById('ecommerce-ai-error');
    const iconChat = launcher.querySelector('.ecommerce-ai-icon-chat');
    const iconClose = launcher.querySelector('.ecommerce-ai-icon-close');

    const history = [];

    function toggleChat() {
        const isOpen = win.style.display !== 'none';
        win.style.display = isOpen ? 'none' : 'flex';
        iconChat.style.display = isOpen ? 'block' : 'none';
        iconClose.style.display = isOpen ? 'none' : 'block';
        if (!isOpen) {
            input.focus();
            scrollToBottom();
        }
    }

    launcher.addEventListener('click', toggleChat);
    closeBtn.addEventListener('click', toggleChat);

    function scrollToBottom() {
        messages.scrollTop = messages.scrollHeight;
    }

    function renderMarkdown(rawText) {
        if (!rawText) return '';

        // Step 1: Escape HTML entities for XSS safety
        let text = String(rawText)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        // Step 2: Auto-split unformatted inline lists if model returned run-on text
        text = text.replace(/([^\n])\s+(\d+\.\s+[\*A-Za-z0-9])/g, '$1\n$2');
        text = text.replace(/([^\n])\s+-\s+(\*\*[A-Za-z0-9])/g, '$1\n   - $2');
        text = text.replace(/([^\n])\s+(Let me know|If you (?:would|need)|Feel free to|Would you like|Do you want)/gi, '$1\n\n$2');

        // Step 3: Inline code `code`
        text = text.replace(/`([^`]+)`/g, '<code class="ecommerce-ai-code">$1</code>');

        // Step 4: Bold **bold** or __bold__
        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        text = text.replace(/__(.*?)__/g, '<strong>$1</strong>');

        // Step 5: Process line by line into structured lists & paragraphs
        const lines = text.split(/\r?\n/);
        let out = [];
        let listStack = [];
        let tableRows = [];
        let inTable = false;

        function closeAllLists() {
            while (listStack.length > 0) {
                out.push('</' + listStack.pop() + '>');
            }
        }

        function flushTable() {
            if (!inTable || tableRows.length === 0) {
                inTable = false;
                tableRows = [];
                return;
            }
            let html = '<div class="ecommerce-ai-table-wrap"><table class="ecommerce-ai-table">';
            let isHeader = true;
            for (let i = 0; i < tableRows.length; i++) {
                const row = tableRows[i];
                if (row.match(/^\|?(\s*:?-+:?\s*\|?)+$/)) {
                    isHeader = false;
                    continue;
                }
                const cells = row.split('|').map(c => c.trim());
                if (cells.length > 0 && cells[0] === '') cells.shift();
                if (cells.length > 0 && cells[cells.length - 1] === '') cells.pop();
                if (cells.length === 0) continue;
                const tag = isHeader ? 'th' : 'td';
                html += '<tr>' + cells.map(c => '<' + tag + '>' + c + '</' + tag + '>').join('') + '</tr>';
                isHeader = false;
            }
            html += '</table></div>';
            out.push(html);
            tableRows = [];
            inTable = false;
        }

        for (let i = 0; i < lines.length; i++) {
            const rawLine = lines[i];
            const trimmed = rawLine.trim();

            if (trimmed.startsWith('|') && trimmed.endsWith('|') && trimmed.length > 2) {
                closeAllLists();
                inTable = true;
                tableRows.push(trimmed);
                continue;
            } else if (inTable) {
                flushTable();
            }

            if (trimmed === '') {
                closeAllLists();
                out.push('<div class="ecommerce-ai-spacer"></div>');
                continue;
            }

            const olMatch = rawLine.match(/^(\s*)(\d+)\.\s+(.*)$/);
            const ulMatch = rawLine.match(/^(\s*)([-*•])\s+(.*)$/);

            if (olMatch) {
                while (listStack.length > 0 && listStack[listStack.length - 1] === 'ul') {
                    out.push('</' + listStack.pop() + '>');
                }
                if (listStack.length === 0) {
                    listStack.push('ol');
                    out.push('<ol class="ecommerce-ai-list">');
                }
                out.push('<li>' + olMatch[3] + '</li>');
                continue;
            }

            if (ulMatch) {
                const indent = ulMatch[1].length;
                if (listStack.length > 0 && listStack[listStack.length - 1] === 'ol' && indent > 0) {
                    listStack.push('ul');
                    out.push('<ul class="ecommerce-ai-sublist">');
                } else if (listStack.length === 0) {
                    listStack.push('ul');
                    out.push('<ul class="ecommerce-ai-list">');
                }
                out.push('<li>' + ulMatch[3] + '</li>');
                continue;
            }

            closeAllLists();
            out.push('<p class="ecommerce-ai-p">' + trimmed + '</p>');
        }

        closeAllLists();
        if (inTable) flushTable();

        return out.join('');
    }

    function appendMessage(role, text) {
        const msgDiv = document.createElement('div');
        msgDiv.className = 'ecommerce-ai-msg ecommerce-ai-msg-' + role;

        const bubble = document.createElement('div');
        bubble.className = 'ecommerce-ai-bubble';

        if (role === 'assistant') {
            bubble.innerHTML = renderMarkdown(text);
        } else {
            bubble.textContent = text; // User messages safely escaped
        }

        msgDiv.appendChild(bubble);
        messages.appendChild(msgDiv);
        scrollToBottom();
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const text = input.value.trim();
        if (!text) return;

        appendMessage('user', text);
        input.value = '';
        errorBox.style.display = 'none';
        loading.style.display = 'flex';
        scrollToBottom();

        const endpoint = win.getAttribute('data-endpoint');

        // Extract CSRF token if present in DOM
        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        };
        if (csrfTokenMeta) {
            headers['X-CSRF-TOKEN'] = csrfTokenMeta.getAttribute('content');
        }

        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: headers,
                body: JSON.stringify({
                    message: text,
                    history: history.slice(-10),
                }),
            });

            const data = await res.json();

            if (!res.ok) {
                const errorMsg = data.error || data.message || ('Error ' + res.status);
                errorBox.textContent = errorMsg;
                errorBox.style.display = 'block';
            } else {
                appendMessage('assistant', data.answer);
                history.push({ role: 'user', content: text });
                history.push({ role: 'assistant', content: data.answer });
            }
        } catch (err) {
            errorBox.textContent = 'Network or connection error. Please try again.';
            errorBox.style.display = 'block';
        } finally {
            loading.style.display = 'none';
            scrollToBottom();
        }
    });
})();
</script>
@endif
