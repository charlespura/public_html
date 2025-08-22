<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sidebar</title>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="icon" type="image/png" href="/web/picture/logo2.png" />
  
  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

  <style>
    @media (max-width: 768px) {
      #sidebar {
        position: fixed;
        left: -100%;
        z-index: 50;
        transition: left 0.3s ease;
      }
      #sidebar.sidebar-open {
        left: 0;
      }
      #mobile-menu-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 40;
      }
      #sidebar.sidebar-open ~ #mobile-menu-overlay {
        display: block;
      }
    }
  </style>
</head>
<body class="flex">
  <!-- Mobile menu button -->
  <div class="md:hidden fixed top-4 left-4 z-30">
    <button id="mobile-menu-button" class="text-gray-800 focus:outline-none">
      <i data-lucide="menu" class="w-6 h-6"></i>
    </button>
  </div>

  <!-- Sidebar -->
  <div id="sidebar" class="bg-gray-800 text-white w-64 transition-all duration-300 h-screen flex flex-col">
    <!-- Logo & Toggle -->
    <div class="flex items-center justify-between px-4 py-4 border-b border-gray-700">
      <!-- Large logo for expanded sidebar -->
      <img src="/web/picture/logo.png" alt="Logo" class="h-14 sidebar-logo-expanded" />
      <!-- Small logo for collapsed sidebar (initially hidden) -->
      <img src="/web/picture/logo2.png" alt="Logo" class="h-14 sidebar-logo-collapsed hidden" />
      <button id="sidebar-toggle" class="text-white focus:outline-none hidden md:block">
        <i data-lucide="chevron-right" class="w-5 h-5 transition-transform"></i>
      </button>
    </div>
    
    <?php include 'chatbot.php'; ?>

    <!-- Navigation -->
    <nav class="flex-1 px-2 py-4 space-y-2">
      <?php
        $currentPage = $_SERVER['PHP_SELF'];
      ?>

      <a href="/public_html/employee/employee.php"
         class="flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-700 <?php echo ($currentPage == '/public_html/employee/employee.php') ? 'bg-gray-700 text-white' : ''; ?>">
        <i data-lucide="home" class="w-5 h-5"></i>
        <span class="sidebar-text">Employee</span>
      </a>

      <a href="/public_html/index.php"
         class="flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-700 <?php echo ($currentPage == '/public_html/index.php') ? 'bg-gray-700 text-white' : ''; ?>">
        <i data-lucide="home" class="w-5 h-5"></i>
        <span class="sidebar-text">Dashboard</span>
      </a>

      <a href="/public_html/timeAndattendance/time.php"
         class="flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-700 <?php echo ($currentPage == '/public_html/timeAndattendance/time.php') ? 'bg-gray-700 text-white' : ''; ?>">
        <i data-lucide="clock" class="w-5 h-5"></i>
        <span class="sidebar-text">Time and Attendance</span>
      </a>

      <a href="/public_html/shift/assignShift.php"
         class="flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-700 <?php echo ($currentPage == '/public_html/shift/assignShift.php') ? 'bg-gray-700 text-white' : ''; ?>">
        <i data-lucide="calendar-range" class="w-5 h-5"></i>
        <span class="sidebar-text">Shift & Schedule</span>
      </a>

      <a href="/public_html/timesheet/timesheet.php"
         class="flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-700 <?php echo ($currentPage == '/public_html/timesheet/timesheet.php') ? 'bg-gray-700 text-white' : ''; ?>">
        <i data-lucide="file-text" class="w-5 h-5"></i>
        <span class="sidebar-text">Timesheet</span>
      </a>

      <a href="/public_html/leave/leave.php"
         class="flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-700 <?php echo ($currentPage == '/public_html/leave/leave.php') ? 'bg-gray-700 text-white' : ''; ?>">
        <i data-lucide="plane" class="w-5 h-5"></i>
        <span class="sidebar-text">Leave Management</span>
      </a>

      <a href="/public_html/claims/claims.php"
         class="flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-700 <?php echo ($currentPage == '/public_html/claims/claims.php') ? 'bg-gray-700 text-white' : ''; ?>">
        <i data-lucide="dollar-sign" class="w-5 h-5"></i>
        <span class="sidebar-text">Claims & Reimbursement</span>
      </a>
    </nav>
  </div>

  <!-- Mobile menu overlay -->
  <div id="mobile-menu-overlay"></div>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      // Desktop sidebar toggle
      const toggleBtn = document.getElementById("sidebar-toggle");
      const sidebar = document.getElementById("sidebar");
      const logoExpanded = document.querySelector(".sidebar-logo-expanded");
      const logoCollapsed = document.querySelector(".sidebar-logo-collapsed");
      const sidebarText = document.querySelectorAll(".sidebar-text");

      if (toggleBtn) {
        toggleBtn.addEventListener("click", () => {
          // Width & overflow toggle
          sidebar.classList.toggle("w-64");
          sidebar.classList.toggle("w-20");
          sidebar.classList.toggle("overflow-hidden");

          // Toggle logos
          if (logoExpanded && logoCollapsed) {
            logoExpanded.classList.toggle("hidden");
            logoCollapsed.classList.toggle("hidden");
          }

          // Toggle sidebar text
          sidebarText.forEach(el => {
            el.classList.toggle("hidden");
          });

          // Rotate the toggle icon
          const icon = toggleBtn.querySelector("i");
          if (icon) {
            icon.classList.toggle("rotate-180");
          }
        });
      }

      // Mobile menu toggle
      const mobileMenuButton = document.getElementById("mobile-menu-button");
      const mobileMenuOverlay = document.getElementById("mobile-menu-overlay");

      if (mobileMenuButton) {
        mobileMenuButton.addEventListener("click", () => {
          sidebar.classList.toggle("sidebar-open");
        });
      }

      if (mobileMenuOverlay) {
        mobileMenuOverlay.addEventListener("click", () => {
          sidebar.classList.remove("sidebar-open");
        });
      }

      // Initialize Lucide icons
      if (typeof lucide !== "undefined" && lucide.createIcons) {
        lucide.createIcons();
      }
    });
  </script>
</body>
</html>