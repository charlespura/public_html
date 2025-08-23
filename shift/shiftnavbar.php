 <!-- ito lng papalitan mo ng mga modules na sa department nyo -->
<div class="bg-gray-100 border-b px-6 py-3 flex gap-4 text-sm font-medium text-gray-700">
  <a href="assignShift.php" class="hover:text-blue-600 transition-colors">Assign Shift</a>

    <a href="viewShift.php" class="hover:text-blue-600 transition-colors">View Shift </a>
     <a href="reqShift.php" class="hover:text-blue-600 transition-colors">View Request Shift </a>
  <div class="relative inline-block text-left">
  <!-- Configure Button -->
  <button id="configBtn" type="button" class="hover:text-blue-600 transition-colors">
    Configure
  </button>

  <!-- Dropdown Menu -->
  <div id="configMenu" class="hidden absolute mt-2 bg-white border rounded-lg shadow-lg p-2 space-y-2">
    <a href="addShift.php" class="block hover:text-blue-600 transition-colors">Add Shift</a>
    <a href="reqType.php" class="block hover:text-blue-600 transition-colors">Request Type</a>
     <a href="statusType.php" class="block hover:text-blue-600 transition-colors">Status Type</a>
  </div>
</div>

<script>
  const btn = document.getElementById("configBtn");
  const menu = document.getElementById("configMenu");

  btn.addEventListener("click", (e) => {
    e.preventDefault(); // stop default link
    menu.classList.toggle("hidden");
  });

  // Optional: close when clicking outside
  document.addEventListener("click", (e) => {
    if (!btn.contains(e.target) && !menu.contains(e.target)) {
      menu.classList.add("hidden");
    }
  });
</script>

<!-- lagay ka pa kung gusto mo  -->
</div>