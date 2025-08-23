


<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<?php
// Require Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Make sure you have created a tmp folder inside your project
// mkdir /Applications/XAMPP/xamppfiles/htdocs/public_html/shift/tmp
// chmod 777 /Applications/XAMPP/xamppfiles/htdocs/public_html/shift/tmp

$mpdf = new \Mpdf\Mpdf([
    'tempDir' => __DIR__ . '/tmp', // temporary files folder
    'format'  => 'A4',
    'orientation' => 'P'
]);

// Example HTML content for invoice/report
$html = '
<div style="text-align:center; margin-bottom:20px;">
    <img src="logo.png" width="150"><br>
    <h2>My Company</h2>
    <h3>Invoice Report</h3>
    <p>Date: ' . date("d/m/Y") . '</p>
</div>

<table border="1" cellpadding="8" cellspacing="0" width="100%">
    <thead>
        <tr style="background-color:#f2f2f2;">
            <th>ID</th>
            <th>Customer</th>
            <th>Item</th>
            <th>Quantity</th>
            <th>Price</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>1</td>
            <td>John Doe</td>
            <td>Product A</td>
            <td>2</td>
            <td>$50</td>
            <td>$100</td>
        </tr>
        <tr>
            <td>2</td>
            <td>Jane Smith</td>
            <td>Product B</td>
            <td>1</td>
            <td>$150</td>
            <td>$150</td>
        </tr>
        <tr>
            <td colspan="5" style="text-align:right;"><strong>Grand Total</strong></td>
            <td>$250</td>
        </tr>
    </tbody>
</table>

<p style="text-align:center; margin-top:30px;">Thank you for your business!</p>
';

// Write HTML to PDF
$mpdf->WriteHTML($html);

// Output PDF for download
$mpdf->Output('Invoice_Report_' . date("Ymd") . '.pdf', 'D'); // 'D' = download
