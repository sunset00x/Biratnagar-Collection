<!-- AI Chatbot Floating Widget -->
<div id="ai-chatbot-container">
  <button id="chatbot-toggle-btn" aria-label="Open AI Assistant">
    💬 AI Salesman
  </button>

  <div id="chatbot-window" class="chatbot-hidden">
    <div class="chatbot-header">
      <div class="chatbot-title">
        <span class="status-indicator"></span>
        <strong>AI Salesman Assistant</strong>
      </div>
      <button id="chatbot-close-btn">&times;</button>
    </div>

    <div id="chatbot-messages" class="chatbot-messages">
      <div class="message bot-message">
        🙏 Namaste! I am your AI Salesman. Ask me to find products, assemble recipe bundles, filter by price, apply coupons, or track orders!
      </div>
    </div>

    <div class="chatbot-chips">
      <button onclick="sendQuickChip('Ingredients for Momo')">🥟 Momo Recipe</button>
      <button onclick="sendQuickChip('Chiya patti under 300')">☕ Neplish Search</button>
      <button onclick="sendQuickChip('Apply RAMRO10')">🎟️ Apply Coupon</button>
      <button onclick="sendQuickChip('Track my order')">📦 Track Order</button>
    </div>

    <div id="image-preview-container" style="display:none; padding:8px 12px; background:#f1f5f9; border-top:1px solid #cbd5e1; align-items:center; justify-content:space-between;">
      <span id="image-preview-name" style="font-size:12px; color:#334155; font-weight:600;"></span>
      <button type="button" onclick="clearImagePreview()" style="background:none; border:none; color:#dc2626; cursor:pointer; font-weight:bold; font-size:14px;">✕</button>
    </div>

    <form id="chatbot-form" class="chatbot-input-area">
      <label for="chatbot-file-input" class="file-upload-btn" title="Upload Photo">📷</label>
      <input type="file" id="chatbot-file-input" accept="image/*" style="display:none;" />
      
      <input type="text" id="chatbot-input" placeholder="Ask anything in English/Neplish..." autocomplete="off" />
      <button type="submit">Send</button>
    </form>
  </div>
</div>

<style>
#ai-chatbot-container { position: fixed; bottom: 25px; right: 25px; z-index: 9999; font-family: Inter, sans-serif; }
#chatbot-toggle-btn { background: #087443; color: white; border: none; padding: 14px 22px; border-radius: 50px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 15px rgba(8,116,67,0.3); font-size: 15px; }
#chatbot-window { width: 370px; height: 530px; background: #ffffff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); display: flex; flex-direction: column; overflow: hidden; border: 1px solid #e2e8f0; position: absolute; bottom: 60px; right: 0; transition: all 0.3s ease; }
.chatbot-hidden { display: none !important; }
.chatbot-header { background: #087443; color: white; padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; }
.status-indicator { display: inline-block; width: 8px; height: 8px; background: #22c55e; border-radius: 50%; margin-right: 6px; }
#chatbot-close-btn { background: none; border: none; color: white; font-size: 22px; cursor: pointer; }
.chatbot-messages { flex: 1; padding: 15px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; background: #f8fafc; }
.message { max-width: 85%; padding: 10px 14px; border-radius: 12px; font-size: 13px; line-height: 1.4; white-space: pre-line; }
.bot-message { background: #ffffff; border: 1px solid #e2e8f0; color: #1e293b; align-self: flex-start; border-bottom-left-radius: 2px; }
.user-message { background: #087443; color: white; align-self: flex-end; border-bottom-right-radius: 2px; }
.chatbot-chips { padding: 8px 12px; display: flex; gap: 6px; overflow-x: auto; background: #fff; border-top: 1px solid #f1f5f9; }
.chatbot-chips button { background: #f1f5f9; border: none; padding: 5px 10px; border-radius: 12px; font-size: 11px; cursor: pointer; white-space: nowrap; color: #475569; font-weight: 600; }
.chatbot-input-area { display: flex; padding: 10px; background: #fff; border-top: 1px solid #e2e8f0; align-items: center; }
.file-upload-btn { cursor: pointer; font-size: 18px; padding: 0 8px; }
.chatbot-input-area input[type="text"] { flex: 1; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; }
.chatbot-input-area button[type="submit"] { background: #087443; color: white; border: none; padding: 8px 14px; margin-left: 6px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 13px; }
.chat-product-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px; margin-top: 6px; display: flex; gap: 10px; align-items: center; }
.chat-product-card img { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; }
.chat-product-card .p-title { font-weight:700; font-size:12px; color:#0f172a; }
.chat-product-card .p-price { color:#087443; font-weight:800; font-size:11px; margin-top:2px; }
.chat-product-card button { background: #087443; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 11px; cursor: pointer; margin-top: 4px; font-weight:600; }
</style>

<script>
let selectedBase64Image = null;

document.getElementById('chatbot-toggle-btn').addEventListener('click', () => {
  document.getElementById('chatbot-window').classList.toggle('chatbot-hidden');
});
document.getElementById('chatbot-close-btn').addEventListener('click', () => {
  document.getElementById('chatbot-window').classList.add('chatbot-hidden');
});

document.getElementById('chatbot-file-input').addEventListener('change', function(e) {
  const file = e.target.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = function(event) {
    selectedBase64Image = event.target.result;
    document.getElementById('image-preview-name').textContent = "📷 " + file.name;
    document.getElementById('image-preview-container').style.display = 'flex';
  };
  reader.readAsDataURL(file);
});

function clearImagePreview() {
  selectedBase64Image = null;
  document.getElementById('chatbot-file-input').value = '';
  document.getElementById('image-preview-container').style.display = 'none';
}

document.getElementById('chatbot-form').addEventListener('submit', function(e) {
  e.preventDefault();
  const input = document.getElementById('chatbot-input');
  const msg = input.value.trim();

  if (!msg && !selectedBase64Image) return;

  if (msg) appendMessage(msg, 'user-message');
  if (selectedBase64Image) appendMessage("📷 Sent photo for visual catalog search", 'user-message');

  const payload = { message: msg, image: selectedBase64Image };

  input.value = '';
  clearImagePreview();

  // Dynamic relative path targeting api/chatbot.php
  fetch('api/chatbot.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(async res => {
    const text = await res.text();
    try {
      return JSON.parse(text);
    } catch (err) {
      console.error("Backend Server Error:", text);
      throw new Error("Invalid JSON response");
    }
  })
  .then(data => {
    appendMessage(data.reply, 'bot-message');

    if ((data.type === 'product_list' || data.type === 'recipe_bundle') && data.products) {
      renderChatProducts(data.products);
    }

    if (data.type === 'cart_action' && data.action === 'added') {
      const cartBadge = document.querySelector('.cart-count');
      if (cartBadge) {
        let currentCount = parseInt(cartBadge.textContent) || 0;
        cartBadge.textContent = currentCount + 1;
      }
    }
  })
  .catch(err => {
    console.error(err);
    appendMessage("Sorry, I am having trouble connecting right now. Please verify your connection.", 'bot-message');
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
        <div class="p-title">${p.name}</div>
        <div class="p-price">Rs. ${Number(p.price).toLocaleString()}</div>
        <button onclick="location.href='product.php?id=${p.id}'">🛍️ View / Buy</button>
      </div>
    `;
    container.appendChild(card);
  });
  container.scrollTop = container.scrollHeight;
}
</script>