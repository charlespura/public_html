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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        
        .card {
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 35px -5px rgba(0, 0, 0, 0.15);
        }
        
        .btn {
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn:after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }
        
        .btn:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }
        
        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 1;
            }
            20% {
                transform: scale(25, 25);
                opacity: 1;
            }
            100% {
                opacity: 0;
                transform: scale(40, 40);
            }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
            100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
        }
        
        .flash-message {
            transition: opacity 0.5s ease-out;
        }
    </style>
</head>
<body class="flex flex-col items-center justify-center min-h-screen p-4">
    <div class="card bg-white p-8 rounded-2xl w-full max-w-md text-center fade-in">
        <!-- Logo -->
        <div class="mb-6 flex justify-center">
            <div class="w-24 h-24 rounded-full bg-blue-100 flex items-center justify-center p-4 shadow-inner">
                <img src="../picture/logo2.png" alt="Company Logo" class="w-full h-full object-contain">
            </div>
        </div>

        <h2 class="text-2xl font-bold mb-2 text-gray-800">Facial Recognition Attendance</h2>
        <p class="text-gray-600 mb-6">Position your face in the frame and click the button</p>

        <!-- Flash message -->
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div id="flashMessage" class="mb-6 p-4 rounded-lg transition-opacity duration-1000 flash-message
                        <?php echo (strpos($_SESSION['flash_message'], '✅') !== false) 
                            ? 'bg-green-100 text-green-700 border border-green-200' 
                            : 'bg-red-100 text-red-700 border border-red-200'; ?>">
                <?php echo $_SESSION['flash_message']; ?>
            </div>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>

        <!-- Webcam Container -->
        <div class="relative mb-6 mx-auto w-80 h-60 rounded-xl overflow-hidden border-4 border-white shadow-lg pulse">
            <video id="video" width="320" height="240" autoplay class="w-full h-full object-cover"></video>
            <div class="absolute inset-0 border-2 border-blue-400 rounded-xl m-2 pointer-events-none"></div>
        </div>
        <canvas id="canvas" width="320" height="240" style="display:none;"></canvas>

        <!-- Form for submitting webcam capture -->
        <form id="attendanceForm" action="verify.php" method="POST">
            <input type="hidden" name="current" id="current">
            <button type="button" id="snap"
                class="btn bg-blue-600 hover:bg-blue-700 text-white font-semibold px-8 py-3 rounded-lg shadow-md transition">
                Clock In / Clock Out
            </button>
        </form>
        
        <div class="mt-6 text-sm text-gray-500">
            Ensure good lighting and face the camera directly
        </div>
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
        .catch(err => { 
            alert("Cannot access webcam: " + err); 
        });

    // Capture image and submit to verify.php
    snap.addEventListener('click', () => {
        // Add click feedback animation
        snap.classList.add('opacity-75');
        setTimeout(() => snap.classList.remove('opacity-75'), 150);
        
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        input.value = canvas.toDataURL('image/jpeg');
        form.submit();
    });
    </script>
</body>
</html>