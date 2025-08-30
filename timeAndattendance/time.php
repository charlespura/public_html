<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<?php
// Convert full server path to web URL
function imageUrl($fullPath) {
    if (empty($fullPath)) return '';
    // Replace server path with relative URL
    return str_replace($_SERVER['DOCUMENT_ROOT'], '', $fullPath);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Time and Attendance</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="icon" type="image/png" href="../picture/logo2.png" />

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
    <?php include '../sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-y-auto">

      <!-- Main Top Header -->
      <main class="p-6 space-y-4">
        <div class="flex items-center justify-between border-b py-6">
          <h2 class="text-xl font-semibold text-gray-800" id="main-content-title">Time and Attendance</h2>
          <?php include '../profile.php'; ?>
        </div>

        <?php include 'timenavbar.php'; ?>

      </main>

      <!-- Attendance Table Container -->
      <div class="bg-white shadow-md rounded-2xl p-10 w-full mx-auto mt-10 mb-10">

      <?php
      // Include DB connections
      include __DIR__ . '/../dbconnection/dbEmployee.php'; // hr3_system
      $empConn = $conn;

      include __DIR__ . '/../dbconnection/mainDB.php'; // hr3_maindb
      $mainConn = $conn;

      // Fetch attendance with employee info from two databases
      $sql = "
  SELECT 
    e.employee_id,
    CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
    d.name AS department_name,
    u.username,
    s.work_date,
    a.clock_in,
    a.clock_out,
    a.hours_worked,
    a.remarks
FROM hr3_maindb.attendance a
JOIN hr3_maindb.users u ON a.user_id = u.user_id
JOIN hr3_maindb.employee_schedules s ON a.schedule_id = s.schedule_id
JOIN hr3_system.employees e ON s.employee_id = e.employee_id
LEFT JOIN hr3_system.departments d ON e.department_id = d.department_id
ORDER BY e.employee_id ASC, s.work_date DESC
      ";
      $result = $mainConn->query($sql);

      if (!$result) {
          die("Query failed: " . $mainConn->error);
      }
      ?>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 table-auto">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee ID</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Work Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clock In</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clock Out</th>
           <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clock Photo</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clock Photo</th>
        
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hours Worked</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <?php if ($result->num_rows > 0): ?>
              <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($row['employee_id'] ?? '') ?></td>
  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($row['employee_name'] ?? '') ?></td>
  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($row['department_name'] ?? '') ?></td>
  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($row['username'] ?? '') ?></td>
  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($row['work_date'] ?? '') ?></td>
  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($row['clock_in'] ?? '') ?></td>
  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($row['clock_out'] ?? '') ?></td>
<td>
    <?php if (!empty($row['clock_in_image'])): ?>
        <img src="/uploads/clock_in/<?= htmlspecialchars(imageUrl($row['clock_in_image'])) ?>" 
             alt="Clock In" class="h-16 w-16 object-cover rounded">
    <?php else: ?>
        <span class="text-gray-400">No Photo</span>
    <?php endif; ?>
</td>

<td>
    <?php if (!empty($row['clock_out_image'])): ?>
        <img src="<?= htmlspecialchars(imageUrl($row['clock_out_image'])) ?>" 
             alt="Clock Out" class="h-16 w-16 object-cover rounded">
    <?php else: ?>
        <span class="text-gray-400">No Photo</span>
    <?php endif; ?>
</td>


  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($row['hours_worked'] ?? '') ?></td>
  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($row['remarks'] ?? '') ?></td>
</tr>
   <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="9" class="px-6 py-4 text-center text-gray-500">No attendance records found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      </div> <!-- End Table Container -->

    </div> <!-- End Main Content -->

  </div> <!-- End Flex Layout -->

</body>
</html>
