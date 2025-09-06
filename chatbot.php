

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Chatbot</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
  <!-- Chatbot Toggle Button with Tooltip -->
  <div class="relative flex justify-end items-end">
    <button id="chatbotToggle"
      class="fixed bottom-6 right-6 bg-gray-800 rounded-full p-2 shadow-lg hover:bg-gray-700 transition group">
      <img src="/public_html/picture/logo2.png" alt="Chatbot" class="w-10 h-10 object-contain">
      <!-- Tooltip -->
      <span class="absolute bottom-full mb-2 right-1/2 transform translate-x-1/2 
                   bg-gray-900 text-white text-xs rounded py-1 px-2 opacity-0 
                   pointer-events-none group-hover:opacity-100 transition-opacity
                   whitespace-nowrap shadow-lg">
        Chat with us
      </span>
    </button>
  </div>

  <!-- Chatbot Box -->
  <div id="chatbotBox"
    class="fixed bottom-20 right-6 w-80 bg-gray-800 border border-gray-700 rounded-xl shadow-lg opacity-0 scale-95 pointer-events-none transition-all duration-300 overflow-hidden">
    
    <!-- Header -->
    <div class="p-4 border-b border-gray-700 font-semibold bg-gray-900 text-white">
      Chatbot
    </div>
    
    <!-- Chat content -->
    <div id="chatContent" class="p-4 h-60 overflow-y-auto text-sm text-white flex flex-col gap-2">
      <p>Hello! How can I help you?</p>
    </div>

    <!-- Input -->
    <div class="p-3 border-t border-gray-700 bg-gray-900 flex gap-2">
      <input id="userInput" type="text" placeholder="Type a message..."
        class="flex-1 rounded-lg px-3 py-2 text-black text-sm focus:outline-none">
      <button id="sendBtn" class="bg-blue-600 hover:bg-blue-500 text-white px-3 py-2 rounded-lg">
        Send
      </button>
    </div>
  </div>
<script>
  const toggleBtn = document.getElementById('chatbotToggle');
  const chatBox = document.getElementById('chatbotBox');
  const chatContent = document.getElementById('chatContent');
  const userInput = document.getElementById('userInput');
  const sendBtn = document.getElementById('sendBtn');

  toggleBtn.addEventListener('click', () => {
    const isOpen = chatBox.classList.contains('opacity-100');

    if (isOpen) {
      chatBox.classList.remove('opacity-100', 'scale-100', 'pointer-events-auto');
      chatBox.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
    } else {
      chatBox.classList.remove('opacity-0', 'scale-95', 'pointer-events-none');
      chatBox.classList.add('opacity-100', 'scale-100', 'pointer-events-auto');
    }
  });

  async function sendMessage() {
    const message = userInput.value.trim();
    if (!message) return;

    // Add user message to chat
    const userMsg = document.createElement("p");
    userMsg.textContent = "You: " + message;
    chatContent.appendChild(userMsg);
    chatContent.scrollTop = chatContent.scrollHeight;
    userInput.value = "";

    try {
      // Send to PHP (Gemini)
      const response = await fetch("/public_html/chatbot_api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ message })
      });

      const data = await response.json();
      console.log("Full API response:", data);

      // Extract bot reply safely
      let reply = "No response";
      if (
        data.candidates &&
        data.candidates[0] &&
        data.candidates[0].content &&
        data.candidates[0].content.parts &&
        data.candidates[0].content.parts[0].text
      ) {
        reply = data.candidates[0].content.parts[0].text;
      }

      // Add bot message
      const botMsg = document.createElement("p");
      botMsg.textContent = "Bot: " + reply;
      chatContent.appendChild(botMsg);
      chatContent.scrollTop = chatContent.scrollHeight;
    } catch (err) {
      console.error("Chatbot error:", err);
      const botMsg = document.createElement("p");
      botMsg.textContent = "Bot: Sorry, something went wrong.";
      chatContent.appendChild(botMsg);
    }
  }

  sendBtn.addEventListener("click", sendMessage);
  userInput.addEventListener("keypress", (e) => {
    if (e.key === "Enter") sendMessage();
  });
</script>

</body>
</html>
