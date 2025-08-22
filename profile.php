<!-- dito nyo cacall kung sino user admin employee etc -->

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<!-- Right: User Info -->
<div class="relative flex items-center gap-4">
  <!-- Clock -->
  <span id="clock" class="text-sm text-gray-600 font-mono"></span>

  <button id="userDropdownToggle" class="flex items-center gap-2 focus:outline-none">
    <img src="" alt="profile picture" class="w-8 h-8 rounded-full border object-cover" />
    <span class="text-sm text-gray-800 font-medium"> Charles </span>
    <i data-lucide="chevron-down" class="w-4 h-4 text-gray-600"></i>
  </button>

  <!-- Dropdown -->
  <div id="userDropdown" class="absolute right-0 mt-2 w-40 bg-white rounded shadow-lg hidden z-20">
    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</a>
  </div>
</div>

<script>
function updateClock() {
  const now = new Date();
  let hours = now.getHours();
  let minutes = now.getMinutes();
  let seconds = now.getSeconds();
  
  // Add leading zero if needed
  hours = hours.toString().padStart(2, '0');
  minutes = minutes.toString().padStart(2, '0');
  seconds = seconds.toString().padStart(2, '0');

  document.getElementById('clock').textContent = `${hours}:${minutes}:${seconds}`;
}

// Update every second
setInterval(updateClock, 1000);
updateClock(); // initial call
</script>
