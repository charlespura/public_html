
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>



<?php
// Assume $roles contains the role of the currently logged-in user
// e.g., $roles = $_SESSION['user_role'];

if (in_array($roles, ['Admin', 'Manager'])): 
    
?>
<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>

<div class="bg-gray-800 px-4 py-3 flex flex-wrap md:flex-nowrap gap-2 text-sm font-medium text-white rounded-b-md overflow-x-auto relative">

    <a href=".php"
       class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700 <?php echo ($currentPage == '.php') ? 'bg-gray-700 text-white' : 'text-white'; ?>">
       <i data-lucide="calendar-range" class="w-4 h-4"></i>
       <span> </span>
    </a>

 
   

    <div class="relative inline-block text-left">
        <button id="configBtn" type="button"
            class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700 <?php echo in_array($currentPage, ['addShift.php','reqType.php','statusType.php']) ? 'bg-gray-700 text-white' : 'text-white'; ?>">
            <i data-lucide="settings" class="w-4 h-4"></i>
            <span>Configure</span>
            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="configArrow"></i>
        </button>

        <!-- Fixed Dropdown overlay -->
        <div id="configMenu"
             class="hidden fixed bg-gray-800 border border-gray-700 rounded-lg shadow-lg p-2 space-y-2 w-48 z-50"
             style="top:0; left:0;">
            <a href="addShift.php"
               class="block px-3 py-2 rounded hover:bg-gray-700 <?php echo ($currentPage == '.php') ? 'bg-gray-700 text-white' : 'text-white'; ?>">
                
            </a>
            <a href="reqType.php"
               class="block px-3 py-2 rounded hover:bg-gray-700 <?php echo ($currentPage == '.php') ? 'bg-gray-700 text-white' : 'text-white'; ?>">
                
            </a>
            <a href="statusType.php"
               class="block px-3 py-2 rounded hover:bg-gray-700 <?php echo ($currentPage == '.php') ? 'bg-gray-700 text-white' : 'text-white'; ?>">
            </a>
        </div>
    </div>

</div>
<?php 
else: 
  
endif; 
?>

<script>
const btn = document.getElementById("configBtn");
const menu = document.getElementById("configMenu");
const arrow = document.getElementById("configArrow");

btn.addEventListener("click", (e) => {
    e.preventDefault();
    menu.classList.toggle("hidden");
    arrow.classList.toggle("rotate-180");

    // Position dropdown below the button
    const rect = btn.getBoundingClientRect();
    menu.style.top = rect.bottom + window.scrollY + "px";
    menu.style.left = rect.left + window.scrollX + "px";
});

document.addEventListener("click", (e) => {
    if (!btn.contains(e.target) && !menu.contains(e.target)) {
        menu.classList.add("hidden");
        arrow.classList.remove("rotate-180");
    }
});

// Initialize Lucide icons
if (typeof lucide !== "undefined" && lucide.createIcons) {
    lucide.createIcons();
}
</script>
