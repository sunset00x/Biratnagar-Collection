<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Set HTTP headers to force CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="orders_export_' . date('Y-m-d_H-i-s') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Output CSV Header Columns
fputcsv($output, [
    'Order ID',
    'Order Code',
    'Customer Name',
    'Phone Number',
    'Email Address',
    'Address',
    'City',
    'District',
    'Province',
    'Payment Method',
    'Items Purchased',
    'Items Subtotal (NPR)',
    'Discount (NPR)',
    'Delivery Charge (NPR)',
    'Total Payable (NPR)',
    'Status',
    'Order Date'
]);

// Prepare query for fetching order items
$itemStmt = $pdo->prepare("SELECT product_name, quantity, price FROM order_items WHERE order_id = ?");

// Fetch all orders
$orders = $pdo->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();

foreach ($orders as $o) {
    // Fetch items for current order
    $itemStmt->execute([$o['id']]);
    $items = $itemStmt->fetchAll();

    $itemDetails = [];
    $itemsSubtotal = 0;

    foreach ($items as $i) {
        $itemDetails[] = $i['product_name'] . ' (x' . $i['quantity'] . ')';
        $itemsSubtotal += ($i['price'] * $i['quantity']);
    }

    $itemListString = implode('; ', $itemDetails);
    $discount = $o['discount_amount'] ?? 0;
    $deliveryFee = max(0, $o['total_amount'] - ($itemsSubtotal - $discount));

    // Write row to CSV
    fputcsv($output, [
        $o['id'],
        $o['order_code'],
        $o['customer_name'],
        $o['phone'],
        $o['email'],
        $o['address'],
        $o['city'],
        $o['district'],
        $o['province'],
        $o['payment_method'],
        $itemListString,
        number_format((float)$itemsSubtotal, 2, '.', ''),
        number_format((float)$discount, 2, '.', ''),
        number_format((float)$deliveryFee, 2, '.', ''),
        number_format((float)$o['total_amount'], 2, '.', ''),
        $o['status'],
        $o['created_at']
    ]);
}

fclose($output);
exit;
?>