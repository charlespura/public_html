<?php
/**
 * HR Employee Claims Portal — PHP + Tailwind + Vanilla JS
 * ------------------------------------------------------
 * Single-file demo web app that shows how you could wire up an
 * employee-claims workflow using Adobe Acrobat (PDF Services) API.
 *
 * IMPORTANT: This is a working skeleton with stubs for Adobe API calls.
 * You must fill in the Adobe credentials and confirm the exact endpoints
 * per your Adobe Developer Console project. The UI, routing, and file
 * handling are complete; replace the stubbed functions with real calls
 * (or swap for Adobe's official SDK via a microservice).
 *
 * How to run locally:
 *   1) Create a folder and put this file as `index.php`.
 *   2) Create `storage/` and `uploads/` directories (writable by PHP).
 *   3) Start: `php -S localhost:8000` (PHP 8.0+ recommended).
 *   4) Open http://localhost:8000 in your browser.
 *
 * Environment variables expected (place in your system or a loader):
 *   ADOBE_CLIENT_ID, ADOBE_CLIENT_SECRET, ADOBE_ORG_ID, ADOBE_TECH_ACCT_ID,
 *   ADOBE_ACCOUNT_ID, ADOBE_PRIVATE_KEY (PEM contents or file path).
 *
 * NOTE: Adobe's auth model may be OAuth Server‑to‑Server (JWT) in your org.
 *       Adjust `getAdobeAccessToken()` accordingly to your integration.
 */

// ----------------------------
// Minimal config
// ----------------------------
$STORAGE = __DIR__ . '/storage';
$UPLOADS = __DIR__ . '/uploads';
if (!is_dir($STORAGE)) mkdir($STORAGE, 0777, true);
if (!is_dir($UPLOADS)) mkdir($UPLOADS, 0777, true);

// Helper: sanitize filename
function safe_name($name) {
  return preg_replace('/[^A-Za-z0-9_\-.]/', '_', $name);
}

// ----------------------------
// Routing
// ----------------------------
$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  switch ($action) {
    case 'submit-claim':
      handleSubmitClaim();
      exit;
    case 'extract-claim-data':
      handleExtractData();
      exit;
    case 'compress-claim':
      handleCompress();
      exit;
    case 'protect-claim':
      handleProtect();
      exit;
  }
}

// ----------------------------
// Handlers
// ----------------------------
function handleSubmitClaim() {
  global $UPLOADS, $STORAGE;

  $employeeName = $_POST['employee_name'] ?? 'Employee';
  $employeeId   = $_POST['employee_id'] ?? 'ID';
  $claimTitle   = $_POST['claim_title'] ?? 'Claim';
  $claimDate    = $_POST['claim_date'] ?? date('Y-m-d');
  $category     = $_POST['category'] ?? 'General';
  $notes        = $_POST['notes'] ?? '';

  if (empty($_FILES['receipts']['name'][0])) {
    respondJSON(['ok' => false, 'error' => 'Please upload at least one receipt.']);
  }

  // Save uploads
  $paths = [];
  foreach ($_FILES['receipts']['name'] as $i => $name) {
    $tmp  = $_FILES['receipts']['tmp_name'][$i];
    $dest = $UPLOADS . '/' . uniqid('rcpt_') . '_' . safe_name($name);
    move_uploaded_file($tmp, $dest);
    $paths[] = $dest;
  }

  // 1) Convert all inputs to PDF (using Acrobat Create PDF / OCR)
  $pdfs = [];
  foreach ($paths as $p) {
    $pdfs[] = adobe_convert_to_pdf_and_ocr($p); // returns path to PDF
  }

  // 2) Combine PDFs into a single claim packet
  $claimSlug = safe_name($employeeName . '_' . $employeeId . '_' . $claimTitle . '_' . $claimDate);
  $combined  = $STORAGE . '/' . $claimSlug . '_claim_packet.pdf';
  $ok = adobe_combine_pdfs($pdfs, $combined);
  if (!$ok) respondJSON(['ok' => false, 'error' => 'Failed to combine PDFs (Adobe API).']);

  // 3) (Optional) Add a simple cover page locally (bonus)
  // In production you might use Document Generation/HTML->PDF to build a branded cover page.
  // For demo, we’ll skip creating a real cover page. You can add one via Acrobat API as well.

  respondJSON([
    'ok' => true,
    'message' => 'Claim packet created successfully.',
    'download' => '/storage/' . basename($combined)
  ]);
}

function handleExtractData() {
  if (empty($_FILES['claim_pdf']['name'])) {
    respondJSON(['ok' => false, 'error' => 'Upload a claim PDF to extract.']);
  }
  $tmp  = $_FILES['claim_pdf']['tmp_name'];
  $name = safe_name($_FILES['claim_pdf']['name']);
  $dest = __DIR__ . '/uploads/' . uniqid('claim_') . '_' . $name;
  move_uploaded_file($tmp, $dest);

  // Use Acrobat Extract API to get structured JSON (text, tables, key-values)
  $json = adobe_extract_pdf($dest);
  if (!$json) respondJSON(['ok' => false, 'error' => 'Failed to extract data (Adobe API).']);

  $out = __DIR__ . '/storage/' . pathinfo($dest, PATHINFO_FILENAME) . '_extracted.json';
  file_put_contents($out, json_encode($json, JSON_PRETTY_PRINT));

  respondJSON([
    'ok' => true,
    'message' => 'Extraction complete.',
    'download' => '/storage/' . basename($out)
  ]);
}

function handleCompress() {
  if (empty($_FILES['claim_pdf']['name'])) {
    respondJSON(['ok' => false, 'error' => 'Upload a claim PDF to compress.']);
  }
  $tmp  = $_FILES['claim_pdf']['tmp_name'];
  $name = safe_name($_FILES['claim_pdf']['name']);
  $dest = __DIR__ . '/uploads/' . uniqid('compress_') . '_' . $name;
  move_uploaded_file($tmp, $dest);

  $out = __DIR__ . '/storage/' . pathinfo($dest, PATHINFO_FILENAME) . '_compressed.pdf';
  $ok  = adobe_compress_pdf($dest, $out);
  if (!$ok) respondJSON(['ok' => false, 'error' => 'Compression failed (Adobe API).']);

  respondJSON(['ok' => true, 'download' => '/storage/' . basename($out)]);
}

function handleProtect() {
  if (empty($_FILES['claim_pdf']['name'])) {
    respondJSON(['ok' => false, 'error' => 'Upload a claim PDF to protect.']);
  }
  $password = $_POST['password'] ?? '';
  if (!$password) respondJSON(['ok' => false, 'error' => 'Enter a password.']);

  $tmp  = $_FILES['claim_pdf']['tmp_name'];
  $name = safe_name($_FILES['claim_pdf']['name']);
  $dest = __DIR__ . '/uploads/' . uniqid('protect_') . '_' . $name;
  move_uploaded_file($tmp, $dest);

  $out = __DIR__ . '/storage/' . pathinfo($dest, PATHINFO_FILENAME) . '_protected.pdf';
  $ok  = adobe_protect_pdf($dest, $out, $password);
  if (!$ok) respondJSON(['ok' => false, 'error' => 'Protection failed (Adobe API).']);

  respondJSON(['ok' => true, 'download' => '/storage/' . basename($out)]);
}

function respondJSON($arr) {
  header('Content-Type: application/json');
  echo json_encode($arr);
}

// ----------------------------
// Adobe API — STUBS (replace with real calls)
// ----------------------------

/**
 * Acquire an Adobe access token (JWT / OAuth Server‑to‑Server).
 * Fill based on your integration. Return a string token.
 */
function getAdobeAccessToken() {
  // TODO: Implement IMS token flow.
  // Example sketch (pseudo):
  // 1) Build JWT with org/tech account and scopes
  // 2) POST to https://ims-na1.adobelogin.com/ims/exchange/jwt
  // 3) Return access_token
  return getenv('ADOBE_ACCESS_TOKEN') ?: null;
}

/** Convert any input file to searchable PDF (Create PDF + OCR). */
function adobe_convert_to_pdf_and_ocr($path) {
  global $STORAGE;
  $token = getAdobeAccessToken();
  if (!$token) {
    // For demo, pretend input is already PDF; copy to storage.
    $out = $STORAGE . '/' . pathinfo($path, PATHINFO_FILENAME) . '_std.pdf';
    copy($path, $out);
    return $out;
  }

  // TODO: Replace with real REST call to CreatePDF + OCR operation.
  // Endpoint examples evolve; consult Adobe docs for your region.
  // Use cURL in PHP to upload file content, set headers:
  //   Authorization: Bearer {token}
  //   x-api-key: {client_id}
  // Save response binary to $out.

  $out = $STORAGE . '/' . pathinfo($path, PATHINFO_FILENAME) . '_std.pdf';
  copy($path, $out); // placeholder
  return $out;
}

/** Combine/merge PDFs into a single output. */
function adobe_combine_pdfs(array $pdfPaths, $outPath) {
  $token = getAdobeAccessToken();
  if (!$token) {
    // Local placeholder combine: naive concat is non-trivial; we just pick the first.
    // In real use, call Acrobat Combine API to properly merge.
    return copy($pdfPaths[0], $outPath);
  }
  // TODO: Implement real Combine operation.
  return copy($pdfPaths[0], $outPath);
}

/** Extract structured content (text/tables) from a PDF into JSON. */
function adobe_extract_pdf($pdfPath) {
  $token = getAdobeAccessToken();
  if (!$token) {
    // Fake extraction result for demo
    return [
      'summary' => 'Demo extraction (replace with Acrobat Extract API).',
      'file' => basename($pdfPath),
      'totals' => [
        ['label' => 'Hotel', 'amount' => 320.00, 'date' => date('Y-m-d')],
        ['label' => 'Meals', 'amount' => 58.40, 'date' => date('Y-m-d')],
      ],
    ];
  }
  // TODO: Implement real Extract API call and decode JSON.
  return null;
}

/** Compress a PDF and write to outPath. */
function adobe_compress_pdf($pdfPath, $outPath) {
  $token = getAdobeAccessToken();
  if (!$token) {
    return copy($pdfPath, $outPath); // placeholder
  }
  // TODO: Implement real Compress operation.
  return copy($pdfPath, $outPath);
}

/** Protect a PDF with a password. */
function adobe_protect_pdf($pdfPath, $outPath, $password) {
  $token = getAdobeAccessToken();
  if (!$token) {
    return copy($pdfPath, $outPath); // placeholder
  }
  // TODO: Implement real Protect operation with password parameter.
  return copy($pdfPath, $outPath);
}

?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>HR Expense Claims Portal</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-900">
  <div class="max-w-5xl mx-auto p-6">
    <header class="mb-6">
      <h1 class="text-3xl font-bold">HR Expense Claims Portal</h1>
      <p class="text-sm text-slate-600">Submit receipts, merge into a claim packet, extract data, compress, and protect PDFs using Adobe Acrobat Services.</p>
    </header>

    <!-- Submit Claim Card -->
    <section class="bg-white rounded-2xl shadow p-6 mb-6">
      <h2 class="text-xl font-semibold mb-4">Submit New Claim</h2>
      <form id="submitClaimForm" class="space-y-4" enctype="multipart/form-data">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Employee Name</label>
            <input name="employee_name" class="w-full rounded-xl border p-2" placeholder="Jane Doe" required />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Employee ID</label>
            <input name="employee_id" class="w-full rounded-xl border p-2" placeholder="E12345" required />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Claim Title</label>
            <input name="claim_title" class="w-full rounded-xl border p-2" placeholder="Manila Conference Travel" required />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Date</label>
            <input type="date" name="claim_date" class="w-full rounded-xl border p-2" required />
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Category</label>
            <select name="category" class="w-full rounded-xl border p-2">
              <option>Travel</option>
              <option>Accommodation</option>
              <option>Meals</option>
              <option>Training</option>
              <option>General</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Receipts (images, PDFs, docs)</label>
            <input type="file" name="receipts[]" multiple class="w-full rounded-xl border p-2 bg-white" required />
            <p class="text-xs text-slate-500 mt-1">You can upload JPG/PNG/PDF/DOCX/XLSX etc.</p>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Notes</label>
          <textarea name="notes" class="w-full rounded-xl border p-2" rows="3" placeholder="Any additional context..."></textarea>
        </div>
        <div class="flex items-center gap-3">
          <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white" type="submit">Create Claim Packet (Merge + OCR)</button>
          <span id="submitClaimStatus" class="text-sm text-slate-600"></span>
        </div>
      </form>
    </section>

    <!-- Extract Data Card -->
    <section class="bg-white rounded-2xl shadow p-6 mb-6">
      <h2 class="text-xl font-semibold mb-4">Extract Data from Existing Claim PDF</h2>
      <form id="extractForm" class="space-y-4" enctype="multipart/form-data">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="md:col-span-2">
            <input type="file" name="claim_pdf" class="w-full rounded-xl border p-2 bg-white" required />
          </div>
          <div>
            <button class="w-full px-4 py-2 rounded-xl bg-emerald-600 text-white" type="submit">Extract JSON</button>
          </div>
        </div>
        <span id="extractStatus" class="text-sm text-slate-600"></span>
      </form>
    </section>

    <!-- Utilities: Compress / Protect -->
    <section class="bg-white rounded-2xl shadow p-6 mb-6">
      <h2 class="text-xl font-semibold mb-4">Utilities</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <form id="compressForm" class="space-y-3" enctype="multipart/form-data">
          <label class="block text-sm font-medium">Compress PDF</label>
          <input type="file" name="claim_pdf" class="w-full rounded-xl border p-2 bg-white" required />
          <button class="px-4 py-2 rounded-xl bg-sky-600 text-white" type="submit">Compress</button>
          <span id="compressStatus" class="text-sm text-slate-600"></span>
        </form>
        <form id="protectForm" class="space-y-3" enctype="multipart/form-data">
          <label class="block text-sm font-medium">Protect PDF (Password)</label>
          <input type="file" name="claim_pdf" class="w-full rounded-xl border p-2 bg-white" required />
          <input type="password" name="password" placeholder="Password" class="w-full rounded-xl border p-2" required />
          <button class="px-4 py-2 rounded-xl bg-rose-600 text-white" type="submit">Protect</button>
          <span id="protectStatus" class="text-sm text-slate-600"></span>
        </form>
      </div>
    </section>

    <footer class="text-xs text-slate-500">
      <p>Demo skeleton. Replace Adobe API stubs with real calls using your credentials.</p>
    </footer>
  </div>

  <script>
    async function postForm(action, formEl, statusEl) {
      statusEl.textContent = 'Working…';
      const body = new FormData(formEl);
      try {
        const res = await fetch('?action=' + action, { method: 'POST', body });
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || 'Something went wrong');
        statusEl.innerHTML = `${data.message || 'Done.'} ` + (data.download ? `• <a class="text-indigo-700 underline" href="${data.download}" target="_blank">Download</a>` : '');
      } catch (e) {
        statusEl.textContent = '❌ ' + e.message;
      }
    }

    document.getElementById('submitClaimForm').addEventListener('submit', (e) => {
      e.preventDefault();
      postForm('submit-claim', e.target, document.getElementById('submitClaimStatus'));
    });

    document.getElementById('extractForm').addEventListener('submit', (e) => {
      e.preventDefault();
      postForm('extract-claim-data', e.target, document.getElementById('extractStatus'));
    });

    document.getElementById('compressForm').addEventListener('submit', (e) => {
      e.preventDefault();
      postForm('compress-claim', e.target, document.getElementById('compressStatus'));
    });

    document.getElementById('protectForm').addEventListener('submit', (e) => {
      e.preventDefault();
      postForm('protect-claim', e.target, document.getElementById('protectStatus'));
    });
  </script>
</body>
</html>
