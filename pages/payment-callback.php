<?php
// Handle eSewa Callback (v2 Base64 API Response)
if (isset($_GET['data'])) {
    $encodedData = $_GET['data'];
    $decodedData = json_decode(base64_decode($encodedData), true);

    if ($decodedData && isset($decodedData['status']) && $decodedData['status'] === 'COMPLETE') {
        $transactionUuid = $decodedData['transaction_uuid'];
        $totalAmount = $decodedData['total_amount'];

        // Retrieve corresponding order
        $stmt = $pdo->prepare("SELECT id FROM orders WHERE order_code = ?");
        $stmt->execute([$transactionUuid]);
        $order = $stmt->fetch();

        if ($order) {
            // Update Order Status to Confirmed
            $uStmt = $pdo->prepare("UPDATE orders SET status = 'Order Confirmed' WHERE id = ?");
            $uStmt->execute([$order['id']]);

            // Log successful transaction
            $logStmt = $pdo->prepare("INSERT INTO payment_logs (order_id, transaction_id, gateway, amount, status) VALUES (?, ?, 'eSewa', ?, 'COMPLETE')");
            $logStmt->execute([$order['id'], $decodedData['transaction_code'] ?? 'N/A', $totalAmount]);

            // Trigger Email Receipt
            if (function_exists('sendOrderEmailReceipt')) {
                sendOrderEmailReceipt($order['id']);
            }

            $_SESSION['cart'] = [];
            unset($_SESSION['applied_coupon']);
            $_SESSION['last_order_id'] = $order['id'];
            header("Location: index.php?page=confirmation");
            exit;
        }
    }
}

// Redirect back to checkout if payment failed
header("Location: index.php?page=checkout&error=payment_failed");
exit;
?>