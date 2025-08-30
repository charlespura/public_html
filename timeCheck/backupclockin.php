<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Clock In / Clock Out</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.2/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
      <link rel="icon" type="image/png" href="../picture/logo2.png" />

<body class="bg-gray-100 flex flex-col items-center justify-center min-h-screen">

<div class="bg-white p-6 rounded-2xl shadow-lg w-96 text-center">
    add logo here in center  href="../picture/logo2.png" />

    <h2 class="text-2xl font-bold mb-4">Facial Recognition Attendance</h2>

 <!-- Flash message -->
<?php if (isset($_SESSION['flash_message'])): ?>
    <div id="flashMessage" class="mb-4 p-3 rounded-lg transition-opacity duration-1000
                <?php echo (strpos($_SESSION['flash_message'], '✅') !== false) 
                    ? 'bg-green-100 text-green-700' 
                    : 'bg-red-100 text-red-700'; ?>">
        <?php echo $_SESSION['flash_message']; ?>
    </div>
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>





    <!-- Webcam Video -->
    <video id="video" width="320" height="240" autoplay class="mx-auto rounded-lg border"></video>
    <canvas id="canvas" width="320" height="240" style="display:none;"></canvas>

    <!-- Form for submitting webcam capture -->
    <form id="attendanceForm" action="verify.php" method="POST">
        <input type="hidden" name="current" id="current">
        <button type="button" id="snap"
            class="mt-4 bg-blue-500 hover:bg-blue-600 text-white font-semibold px-6 py-2 rounded-lg transition">
            Clock In / Clock Out
        </button>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const flash = document.getElementById("flashMessage");
    if (flash) {
        setTimeout(() => {
            flash.style.opacity = "0";
        }, 5000); // wait 5s before fading out
        setTimeout(() => {
            flash.remove();
        }, 6000); // fully remove after fade
    }
});
</script>
<script>
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const snap = document.getElementById('snap');
const input = document.getElementById('current');
const form = document.getElementById('attendanceForm');

// Start webcam
navigator.mediaDevices.getUserMedia({ video: true })
    .then(stream => { video.srcObject = stream; })
    .catch(err => { alert("Cannot access webcam: " + err); });

// Capture image and submit to verify.php
snap.addEventListener('click', () => {
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
    input.value = canvas.toDataURL('image/jpeg');
    form.submit();
});
</script>
</body>
</html>
