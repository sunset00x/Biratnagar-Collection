<!-- AI Chatbot Floating Widget -->
<div id="ai-chatbot-container">
  <!-- Chat Bubble Toggle Button -->
  <button id="chatbot-toggle-btn" aria-label="Open AI Assistant">
    💬
  </button>

  <!-- Chatbot Window -->
  <div id="chatbot-window" class="chatbot-hidden">
    <div class="chatbot-header">
      <div class="chatbot-title">
        <span class="status-indicator"></span>
        <strong> AI SalesMan</strong>
      </div>
      <button id="chatbot-close-btn">&times;</button>
    </div>

    <div id="chatbot-messages" class="chatbot-messages">
      <div class="message bot-message">
        👋 Namaste! I am your Biratnagar Collection AI Assistant. How can I help you today?
      </div>
    </div>

    <!-- Quick Action Chips -->
    <div class="chatbot-chips">
      <button onclick="sendQuickChip('Track my order')">📦 Track Order</button>
      <button onclick="sendQuickChip('Show top products')">🛒 Browse Products</button>
      <button onclick="sendQuickChip('Delivery options')">🚚 Delivery Info</button>
    </div>

    <form id="chatbot-form" class="chatbot-input-area">
      <input type="text" id="chatbot-input" placeholder="Ask anything or enter order code..." autocomplete="off" required />
      <button type="submit">Send</button>
    </form>
  </div>
</div>

<style>
#ai-chatbot-container { position: fixed; bottom: 25px; right: 25px; z-index: 9999; font-family: Inter, sans-serif; }
#chatbot-toggle-btn { background: #087443; color: white; border: none; padding: 14px 22px; border-radius: 50px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 15px rgba(8,116,67,0.3); font-size: 15px; }
#chatbot-window { width: 360px; height: 500px; background: #ffffff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); display: flex; flex-direction: column; overflow: hidden; border: 1px solid #e2e8f0; position: absolute; bottom: 60px; right: 0; transition: all 0.3s ease; }
.chatbot-hidden { display: none !important; }
.chatbot-header { background: #087443; color: white; padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; }
.status-indicator { display: inline-block; width: 8px; height: 8px; background: #22c55e; border-radius: 50%; margin-right: 6px; }
#chatbot-close-btn { background: none; border: none; color: white; font-size: 22px; cursor: pointer; }
.chatbot-messages { flex: 1; padding: 15px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; background: #f8fafc; }
.message { max-width: 82%; padding: 10px 14px; border-radius: 12px; font-size: 13px; line-height: 1.4; white-space: pre-line; }
.bot-message { background: #ffffff; border: 1px solid #e2e8f0; color: #1e293b; align-self: flex-start; border-bottom-left-radius: 2px; }
.user-message { background: #087443; color: white; align-self: flex-end; border-bottom-right-radius: 2px; }
.chatbot-chips { padding: 8px 12px; display: flex; gap: 6px; overflow-x: auto; background: #fff; border-top: 1px solid #f1f5f9; }
.chatbot-chips button { background: #f1f5f9; border: none; padding: 5px 10px; border-radius: 12px; font-size: 11px; cursor: pointer; white-space: nowrap; color: #475569; font-weight: 600; }
.chatbot-input-area { display: flex; padding: 10px; background: #fff; border-top: 1px solid #e2e8f0; }
.chatbot-input-area input { flex: 1; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; }
.chatbot-input-area button { background: #087443; color: white; border: none; padding: 8px 14px; margin-left: 6px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 13px; }
.chat-product-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px; margin-top: 6px; display: flex; gap: 10px; align-items: center; }
.chat-product-card img { width: 45px; height: 45px; object-fit: cover; border-radius: 6px; }
.chat-product-card button { background: #087443; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 11px; cursor: pointer; margin-top: 4px; }
</style>

<script>
document.getElementById('chatbot-toggle-btn').addEventListener('click', () => {
  document.getElementById('chatbot-window').classList.toggle('chatbot-hidden');
});
document.getElementById('chatbot-close-btn').addEventListener('click', () => {
  document.getElementById('chatbot-window').classList.add('chatbot-hidden');
});

document.getElementById('chatbot-form').addEventListener('submit', function(e) {
  e.preventDefault();
  const input = document.getElementById('chatbot-input');
  const msg = input.value.trim();
  if (!msg) return;

  appendMessage(msg, 'user-message');
  input.value = '';

  fetch('/biratnagar-collection/api/chatbot.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ message: msg })
  })
  .then(res => res.json())
  .then(data => {
    appendMessage(data.reply, 'bot-message');
    if (data.type === 'product_list' && data.products) {
      renderChatProducts(data.products);
    }
  })
  .catch(() => {
    appendMessage("Sorry, I'm having trouble connecting right now.", 'bot-message');
  });
});

function sendQuickChip(text) {
  document.getElementById('chatbot-input').value = text;
  document.getElementById('chatbot-form').dispatchEvent(new Event('submit'));
}

function appendMessage(text, className) {
  const container = document.getElementById('chatbot-messages');
  const div = document.createElement('div');
  div.className = `message ${className}`;
  div.innerHTML = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
  container.appendChild(div);
  container.scrollTop = container.scrollHeight;
}

function renderChatProducts(products) {
  const container = document.getElementById('chatbot-messages');
  products.forEach(p => {
    const card = document.createElement('div');
    card.className = 'chat-product-card';
    card.innerHTML = `
      <img src="${p.image || 'https://picsum.photos/seed/'+p.id+'/100/100'}" alt="${p.name}">
      <div style="flex:1;">
        <div style="font-weight:700; font-size:12px;">${p.name}</div>
        <div style="color:#087443; font-weight:800; font-size:11px;">Rs. ${Number(p.price).toLocaleString()}</div>
        <button onclick="addToCart(${p.id})">🛒 Add to Cart</button>
      </div>
    `;
    container.appendChild(card);
  });
  container.scrollTop = container.scrollHeight;
}
</script>