
<body class="bg-gray-100 min-h-screen">

<!-- Chatbot Toggle Button -->
<button id="chatbotToggle"
  class="fixed bottom-6 right-6 bg-blue-600 text-white rounded-full p-4 shadow-lg hover:bg-blue-700 transition">
  💬
</button>

<!-- Chatbot Box -->
<div id="chatbotBox"
  class="fixed bottom-20 right-6 w-80 bg-white border border-gray-300 rounded-xl shadow-lg opacity-0 scale-95 pointer-events-none transition-all duration-300 overflow-hidden">
  <div class="p-4 border-b border-gray-200 font-semibold bg-blue-600 text-white">
    Chatbot
  </div>
  <div class="p-4 h-60 overflow-y-auto text-sm text-gray-700">
    <p>Hello! How can I help you?</p>
    <!-- Add your chatbot content here -->
  </div>
</div>

<script>
  const toggleBtn = document.getElementById('chatbotToggle');
  const chatBox = document.getElementById('chatbotBox');

  toggleBtn.addEventListener('click', () => {
    const isOpen = chatBox.classList.contains('opacity-100');

    if (isOpen) {
      // Close animation
      chatBox.classList.remove('opacity-100', 'scale-100', 'pointer-events-auto');
      chatBox.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
    } else {
      // Open animation
      chatBox.classList.remove('opacity-0', 'scale-95', 'pointer-events-none');
      chatBox.classList.add('opacity-100', 'scale-100', 'pointer-events-auto');
    }
  });
</script>
