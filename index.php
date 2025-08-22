
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="icon" type="image/png" href="/web/picture/logo2.png" />

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      lucide.createIcons();
    });
  </script>
</head>
<body class="h-screen overflow-hidden">

  <!-- FLEX LAYOUT: Sidebar + Main -->
  <div class="flex h-full">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-y-auto">

      <!-- Main Top Header (inside content) -->
      <main class="p-6 space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between border-b py-6">
          <!-- Left: Title -->
          <h2 class="text-xl font-semibold text-gray-800" id="main-content-title">Dashboard</h2>
<?php include __DIR__ . '/profile.php'; ?>

        </div>

     
      </main>
<!-- ================== Dashboard Cards Section ================== -->
<div class="px-6 mt-6">
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    
    <!-- Card 1 -->
    <div class="bg-white shadow-lg rounded-2xl p-8 h-64 hover:shadow-2xl transition flex flex-col">
      <!-- Header -->
      <div class="flex items-center space-x-3">
        <!-- Example Logo/Icon -->
        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" 
             viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
        </svg>
        <h2 class="text-lg font-semibold text-gray-700">Total Employees</h2>
      </div>
      <hr class="my-4">
      <!-- 👉 Add content for Card 1 here -->
      <div class="flex-1 flex items-center justify-center text-gray-400">
        Content area
      </div>
    </div>

    <!-- Card 2 -->
    <div class="bg-white shadow-lg rounded-2xl p-8 h-64 hover:shadow-2xl transition flex flex-col">
      <div class="flex items-center space-x-3">
        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" 
             viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3"></path>
        </svg>
        <h2 class="text-lg font-semibold text-gray-700">Departments</h2>
      </div>
      <hr class="my-4">
      <!-- 👉 Add content for Card 2 here -->
      <div class="flex-1 flex items-center justify-center text-gray-400">
        Content area
      </div>
    </div>

    <!-- Card 3 -->
    <div class="bg-white shadow-lg rounded-2xl p-8 h-64 hover:shadow-2xl transition flex flex-col">
      <div class="flex items-center space-x-3">
        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" stroke-width="2" 
             viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2h6v2m2 0a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v8a2 2 0 002 2h10z"></path>
        </svg>
        <h2 class="text-lg font-semibold text-gray-700">Active Projects</h2>
      </div>
      <hr class="my-4">
      <!-- 👉 Add content for Card 3 here -->
      <div class="flex-1 flex items-center justify-center text-gray-400">
        Content area
      </div>
    </div>

    <!-- Card 4 -->
    <div class="bg-white shadow-lg rounded-2xl p-8 h-64 hover:shadow-2xl transition flex flex-col">
      <div class="flex items-center space-x-3">
        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" stroke-width="2" 
             viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
        <h2 class="text-lg font-semibold text-gray-700">Pending Requests</h2>
      </div>
      <hr class="my-4">
      <!-- 👉 Add content for Card 4 here -->
      <div class="flex-1 flex items-center justify-center text-gray-400">
        Content area
      </div>
    </div>

    <!-- Card 5 -->
    <div class="bg-white shadow-lg rounded-2xl p-8 h-64 hover:shadow-2xl transition flex flex-col">
      <div class="flex items-center space-x-3">
        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" 
             viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4
          4-1.79 4-4-1.79-4-4-4z"></path>
        </svg>
        <h2 class="text-lg font-semibold text-gray-700">Attendance Today</h2>
      </div>
      <hr class="my-4">
      <!-- 👉 Add content for Card 5 here -->
      <div class="flex-1 flex items-center justify-center text-gray-400">
        Content area
      </div>
    </div>

    <!-- Card 6 -->
    <div class="bg-white shadow-lg rounded-2xl p-8 h-64 hover:shadow-2xl transition flex flex-col">
      <div class="flex items-center space-x-3">
        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" 
             viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"></path>
        </svg>
        <h2 class="text-lg font-semibold text-gray-700">New Hires</h2>
      </div>
      <hr class="my-4">
      <!-- 👉 Add content for Card 6 here -->
      <div class="flex-1 flex items-center justify-center text-gray-400">
        Content area
      </div>
    </div>

  </div>
</div>
<!-- ================== End Dashboard Cards Section ================== -->

    
    </div>
  </div>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const userDropdownToggle = document.getElementById("userDropdownToggle");
      const userDropdown = document.getElementById("userDropdown");

      userDropdownToggle.addEventListener("click", function () {
        userDropdown.classList.toggle("hidden");
      });

      // Close dropdown when clicking outside
      document.addEventListener("click", function (event) {
        if (!userDropdown.contains(event.target) && !userDropdownToggle.contains(event.target)) {
          userDropdown.classList.add("hidden");
        }
      });
    });
  </script>
</body>
</html>
