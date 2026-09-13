<?php
require_once __DIR__ . '/db.php';

/**
 * Core function to send SMS via Nepalese Gateways (Aakash SMS / Sparrow SMS)
 */
function sendSMS($toPhone, $messageText, $eventTag = 'NOTIFICATION') {
    global $pdo;

    // Sanitize phone number to standard 10-digit format
    $cleanPhone = preg_replace('/[^0-9]/', '', $toPhone);
    if (strlen($cleanPhone) === 10 && strpos($cleanPhone, '98') === 0) {
        // Standard Nepali mobile format
    } else {
        return false;
    }

    // Fetch SMS Settings
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM store_settings WHERE setting_key LIKE 'sms_%'");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $provider = $settings['sms_provider'] ?? 'aakash';
    $token = $settings['sms_api_token'] ?? '';
    $senderId = $settings['sms_sender_id'] ?? 'RamroPasal';

    if (empty($token)) {
        return false; // API token not configured
    }

    $status = 'FAILED';
    $resCode = 'NO_RESPONSE';

    if ($provider === 'aakash') {
        // Aakash SMS API Integration
        $url = "https://sms.aakashsms.com/sms/v3/send";
        $postData = [
            'auth_token' => $token,
            'to' => $cleanPhone,
            'text' => $messageText
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $resCode = (string)$httpCode;
        if ($httpCode == 200) {
            $status = 'SENT';
        }
    } elseif ($provider === 'sparrow') {
        // Sparrow SMS API Integration
        $url = "http://api.sparrowsms.com/v2/sms/";
        $queryParams = http_build_query([
            'token' => $token,
            'from' => $senderId,
            'to' => $cleanPhone,
            'text' => $messageText
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url . "?" . $queryParams,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $resCode = (string)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $resData = json_decode($response, true);

        if (isset($resData['response_code']) && $resData['response_code'] == 200) {
            $status = 'SENT';
        }
    }

    // Log SMS delivery attempt in database
    $logStmt = $pdo->prepare("INSERT INTO sms_logs (phone, message, event, response_code, status) VALUES (?, ?, ?, ?, ?)");
    $logStmt->execute([$cleanPhone, $messageText, $eventTag, $resCode, $status]);

    return ($status === 'SENT');
}

/**
 * Trigger SMS helper for specific order lifecycle events
 */
function triggerOrderSMS($orderId, $eventType, $extraData = []) {
    global $pdo;

    // Fetch settings triggers
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM store_settings WHERE setting_key LIKE 'sms_trigger_%'");
    $triggers = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    if ($eventType === 'placed' && ($triggers['sms_trigger_placed'] ?? '0') !== '1') return;
    if ($eventType === 'dispatched' && ($triggers['sms_trigger_dispatched'] ?? '0') !== '1') return;
    if ($eventType === 'delivered' && ($triggers['sms_trigger_delivered'] ?? '0') !== '1') return;

    // Fetch order details
    $oStmt = $pdo->prepare("SELECT order_code, customer_name, phone, total_amount FROM orders WHERE id = ?");
    $oStmt->execute([$orderId]);
    $order = $oStmt->fetch();

    if (!$order || empty($order['phone'])) return;

    $name = $order['customer_name'];
    $code = $order['order_code'];
    $total = number_format($order['total_amount'], 0);
    $phone = $order['phone'];

    if ($eventType === 'placed') {
        $msg = "Hi {$name}, your order #{$code} of Rs. {$total} is confirmed at Biratnagar Ramro Pasal!";
        sendSMS($phone, $msg, 'ORDER_PLACED');
    } elseif ($eventType === 'dispatched') {
        $riderName = $extraData['rider_name'] ?? 'Rider';
        $riderPhone = $extraData['rider_phone'] ?? 'N/A';
        $msg = "Hi {$name}, your order #{$code} is out for delivery with rider {$riderName} (Ph: {$riderPhone}).";
        sendSMS($phone, $msg, 'OUT_FOR_DELIVERY');
    } elseif ($eventType === 'delivered') {
        $msg = "Thank you for shopping with us! Order #{$code} has been delivered successfully.";
        sendSMS($phone, $msg, 'ORDER_DELIVERED');
    }
}
?>