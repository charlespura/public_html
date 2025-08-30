<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Convert full file path to relative URL
function imageUrl($fullPath) {
    if (empty($fullPath)) return '';

    $fullPath = str_replace('\\', '/', $fullPath);

    if (strpos($fullPath, '/public_html/') !== false) {
        $relative = substr($fullPath, strpos($fullPath, '/public_html/') + strlen('/public_html'));
    } else {
        $relative = $fullPath;
    }

    return $relative;
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
  <div class="flex h-full">
    <!-- Sidebar -->
    <?php include '../sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-y-auto">
      <main class="p-6 space-y-4">
        <div class="flex items-center justify-between border-b py-6">
          <h2 class="text-xl font-semibold text-gray-800">Time and Attendance</h2>
          <?php include '../profile.php'; ?>
        </div>
        <?php include 'timenavbar.php'; ?>
      </main>

      <!-- Attendance Table -->
      <div class="bg-white shadow-md rounded-2xl p-10 w-full mx-auto mt-10 mb-10">







<?php
// Assume $roles contains the role of the currently logged-in user
// e.g., $roles = $_SESSION['user_role'];

if (in_array($roles, ['Admin', 'Manager'])): 
?>

      <?php
      // DB connections
      include __DIR__ . '/../dbconnection/dbEmployee.php'; // hr3_system
      $empConn = $conn;

      include __DIR__ . '/../dbconnection/mainDB.php'; // hr3_maindb
      $mainConn = $conn;

      // Query attendance with images
      $sql = "
      SELECT 
  e.employee_id,
  CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
  d.name AS department_name,
  p.title AS position,   -- 👈 fixed here
  u.username,
  s.work_date,
  a.clock_in,
  a.clock_out,
  a.clock_in_image,
  a.clock_out_image,
  a.hours_worked,
  a.remarks
FROM hr3_maindb.attendance a
JOIN hr3_maindb.users u ON a.user_id = u.user_id
JOIN hr3_maindb.employee_schedules s ON a.schedule_id = s.schedule_id
JOIN hr3_system.employees e ON s.employee_id = e.employee_id
LEFT JOIN hr3_system.departments d ON e.department_id = d.department_id
LEFT JOIN hr3_system.positions p ON e.position_id = p.position_id
ORDER BY e.employee_id ASC, s.work_date DESC;

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
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee ID</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Work Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Clock In</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Clock Out</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Clock In Photo</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Clock Out Photo</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hours Worked</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Remarks</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <?php if ($result->num_rows > 0): ?>
              <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                  <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($row['employee_id'] ?? '') ?></td>
                  <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($row['employee_name'] ?? '') ?></td>
                   <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($row['department_name'] ?? '') ?></td>
                       <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($row['position'] ?? '') ?></td>
               
                 <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($row['username'] ?? '') ?></td>
                  <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($row['work_date'] ?? '') ?></td>
                  <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($row['clock_in'] ?? '') ?></td>
                  <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($row['clock_out'] ?? '') ?></td>
                  
              <td class="px-6 py-4 text-sm text-gray-900">
  <?php if (!empty($row['clock_in_image'])): ?>
    <img 
      src="/public_html/<?= htmlspecialchars(imageUrl($row['clock_in_image'])) ?>" 
      alt="Clock In" 
      class="h-16 w-16 object-cover rounded border cursor-pointer preview-img"
      data-src="/public_html/<?= htmlspecialchars(imageUrl($row['clock_in_image'])) ?>"
    >
  <?php else: ?>
    <span class="text-gray-400">No Photo</span>
  <?php endif; ?>
</td>

<td class="px-6 py-4 text-sm text-gray-900">
  <?php if (!empty($row['clock_out_image'])): ?>
    <img 
      src="/public_html/<?= htmlspecialchars(imageUrl($row['clock_out_image'])) ?>" 
      alt="Clock Out" 
      class="h-16 w-16 object-cover rounded border cursor-pointer preview-img"
      data-src="/public_html/<?= htmlspecialchars(imageUrl($row['clock_out_image'])) ?>"
    >
  <?php else: ?>
    <span class="text-gray-400">No Photo</span>
  <?php endif; ?>
</td>
<!-- Image Preview Modal -->
<div id="imagePreviewModal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden z-50">
  <div class="relative">
    <!-- Close Button -->
    <button id="closeImagePreview" class="absolute top-2 right-2 text-white text-3xl font-bold">&times;</button>
    <img id="imagePreview" src="" class="max-w-[90vw] max-h-[90vh] rounded shadow-lg" alt="Preview">
  </div>
</div>
<script>
  // Open modal when clicking an image
  document.querySelectorAll('.preview-img').forEach(img => {
    img.addEventListener('click', function() {
      const src = this.dataset.src;
      const modal = document.getElementById('imagePreviewModal');
      const preview = document.getElementById('imagePreview');
      preview.src = src;
      modal.classList.remove('hidden');
    });
  });

  // Close modal on button click
  document.getElementById('closeImagePreview').addEventListener('click', function() {
    document.getElementById('imagePreviewModal').classList.add('hidden');
  });

  // Close modal on background click
  document.getElementById('imagePreviewModal').addEventListener('click', function(e) {
    if (e.target === this) {
      this.classList.add('hidden');
    }
  });
</script>


                  <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($row['hours_worked'] ?? '') ?></td>
                  <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($row['remarks'] ?? '') ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="11" class="px-6 py-4 text-center text-gray-500">No attendance records found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      </div>
    </div>
  </div>

        <?php 
else: 
  
endif; 
?>


<?php
// Assume $roles contains the role of the currently logged-in user
// e.g., $roles = $_SESSION['user_role'];

if (in_array($roles, [ 'Employee'])): 
?>
<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in
if (!isset($_SESSION['employee_id'])) {
    header("Location: ../index.php");
    exit;
}

$loggedInEmployeeId = $_SESSION['employee_id'];

// DB connections
include __DIR__ . '/../dbconnection/dbEmployee.php'; // hr3_system
$empConn = $conn;

include __DIR__ . '/../dbconnection/mainDB.php'; // hr3_maindb
$mainConn = $conn;

// Query attendance for ONLY this employee
$sql = "
SELECT 
    e.employee_id,
    CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
    d.name AS department_name,
    p.title AS position,
    u.username,
    s.work_date,
    a.clock_in,
    a.clock_out,
    a.clock_in_image,
    a.clock_out_image,
    a.hours_worked,
    a.remarks
FROM hr3_maindb.attendance a
JOIN hr3_maindb.users u ON a.user_id = u.user_id
JOIN hr3_maindb.employee_schedules s ON a.schedule_id = s.schedule_id
JOIN hr3_system.employees e ON s.employee_id = e.employee_id
LEFT JOIN hr3_system.departments d ON e.department_id = d.department_id
LEFT JOIN hr3_system.positions p ON e.position_id = p.position_id
WHERE e.employee_id = ?
ORDER BY s.work_date DESC
";

$stmt = $mainConn->prepare($sql);
$stmt->bind_param("s", $loggedInEmployeeId);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="overflow-x-auto">
  <h2 class="text-xl font-semibold mb-4">My Attendance Log</h2>
  <table class="min-w-full divide-y divide-gray-200 table-auto">
    <thead class="bg-gray-50">
      <tr>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Work Date</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Clock In</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Clock Out</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Clock In Photo</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Clock Out Photo</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hours Worked</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Remarks</th>
      </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
      <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($row['work_date'] ?? '') ?></td>
            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($row['clock_in'] ?? '') ?></td>
            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($row['clock_out'] ?? '') ?></td>

            <!-- Clock In Photo -->
            <td class="px-6 py-4 text-sm text-gray-900">
              <?php if (!empty($row['clock_in_image'])): ?>
                <img 
                  src="/public_html/<?= htmlspecialchars(imageUrl($row['clock_in_image'])) ?>" 
                  alt="Clock In" 
                  class="h-16 w-16 object-cover rounded border cursor-pointer preview-img"
                  data-src="/public_html/<?= htmlspecialchars(imageUrl($row['clock_in_image'])) ?>"
                >
              <?php else: ?>
                <span class="text-gray-400">No Photo</span>
              <?php endif; ?>
            </td>

            <!-- Clock Out Photo -->
            <td class="px-6 py-4 text-sm text-gray-900">
              <?php if (!empty($row['clock_out_image'])): ?>
                <img 
                  src="/public_html/<?= htmlspecialchars(imageUrl($row['clock_out_image'])) ?>" 
                  alt="Clock Out" 
                  class="h-16 w-16 object-cover rounded border cursor-pointer preview-img"
                  data-src="/public_html/<?= htmlspecialchars(imageUrl($row['clock_out_image'])) ?>"
                >
              <?php else: ?>
                <span class="text-gray-400">No Photo</span>
              <?php endif; ?>
            </td>

            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($row['hours_worked'] ?? '') ?></td>
            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($row['remarks'] ?? '') ?></td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr>
          <td colspan="7" class="px-6 py-4 text-center text-gray-500">No attendance records found.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- ✅ Reuse same modal for preview -->
<div id="imagePreviewModal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden z-50">
  <div class="relative">
    <button id="closeImagePreview" class="absolute top-2 right-2 text-white text-3xl font-bold">&times;</button>
    <img id="imagePreview" src="" class="max-w-[90vw] max-h-[90vh] rounded shadow-lg" alt="Preview">
  </div>
</div>

<script>
document.querySelectorAll('.preview-img').forEach(img => {
  img.addEventListener('click', function() {
    const src = this.dataset.src;
    const modal = document.getElementById('imagePreviewModal');
    const preview = document.getElementById('imagePreview');
    preview.src = src;
    modal.classList.remove('hidden');
  });
});

document.getElementById('closeImagePreview').addEventListener('click', () => {
  document.getElementById('imagePreviewModal').classList.add('hidden');
});

document.getElementById('imagePreviewModal').addEventListener('click', function(e) {
  if (e.target === this) this.classList.add('hidden');
});
</script>



        <?php 
else: 
  
endif; 
?>


</body>
</html>
