<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>M-PESA Payment</title>
  <a href="mpesa.html"></a>
  <style>
    body { font-family: Arial, sans-serif; padding: 30px; background: #f5f5f5; }
    .container { max-width: 500px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    label, input { display: block; width: 100%; margin-bottom: 15px; }
    input { padding: 10px; border-radius: 5px; border: 1px solid #ccc; }
    button { padding: 12px; background: #4caf50; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
    button:hover { background: #43a047; }
    .error { color: red; }
    .success { color: green; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Pay with M-PESA</h2>

   <?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $consumerKey = 'Your_ConsumerKey';
    $consumerSecret = 'Your_ConsumerSecret';
    $shortcode = '174379'; // sandbox shortcode
    $passkey = 'Your_Passkey';
    $callbackURL = 'https://yourdomain.com/callback.php';

    $phone = htmlspecialchars($_POST['phone']);
    $amount = (int)$_POST['amount'];

    $error = '';
    $success = '';

    // Basic validation
    if (!preg_match('/^2547\d{8}$/', $phone)) {
        $error = "Phone number must be in format 2547XXXXXXXX";
    } elseif ($amount <= 0) {
        $error = "Amount must be greater than zero";
    }

    if (empty($error)) {
        $timestamp = date('YmdHis');
        $password = base64_encode($shortcode . $passkey . $timestamp);

        $credentials = base64_encode("$consumerKey:$consumerSecret");
        $token_url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

        // Get OAuth token
        $ch = curl_init($token_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic $credentials"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $token_response = curl_exec($ch);

        if ($token_response === false) {
            $error = "Token request failed: " . curl_error($ch);
        } else {
            $token_data = json_decode($token_response);
            if (!isset($token_data->access_token)) {
                $error = "Failed to get access token. Response: $token_response";
            } else {
                $access_token = $token_data->access_token;

                // Prepare STK push payload
                $stkData = [
                    "BusinessShortCode" => $shortcode,
                    "Password" => $password,
                    "Timestamp" => $timestamp,
                    "TransactionType" => "CustomerPayBillOnline",
                    "Amount" => $amount,
                    "PartyA" => $phone,
                    "PartyB" => $shortcode,
                    "PhoneNumber" => $phone,
                    "CallBackURL" => $callbackURL,
                    "AccountReference" => "Fab Enterprise",
                    "TransactionDesc" => "Order Payment"
                ];

                $ch = curl_init('https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest');
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer $access_token",
                    "Content-Type: application/json"
                ]);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($stkData));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                $response = curl_exec($ch);

                if ($response === false) {
                    $error = "STK Push request failed: " . curl_error($ch);
                } else {
                    $responseData = json_decode($response, true);
                    if (isset($responseData['ResponseCode']) && $responseData['ResponseCode'] == '0') {
                        $success = "STK Push sent successfully! Check your phone to complete payment.";
                    } else {
                        $error = "STK Push error: " . ($responseData['errorMessage'] ?? json_encode($responseData));
                    }
                }
                curl_close($ch);
            }
        }
        curl_close($ch);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pay with M-PESA</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      padding: 30px;
      background: #f5f5f5;
    }

    .container {
      max-width: 500px;
      margin: auto;
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    label, input {
      display: block;
      width: 100%;
      margin-bottom: 15px;
    }

    input {
      padding: 10px;
      border-radius: 5px;
      border: 1px solid #ccc;
    }

    button {
      padding: 12px;
      background: #4caf50;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
    }

    button:hover {
      background: #43a047;
    }

    .error {
      color: red;
      margin-bottom: 15px;
    }

    .success {
      color: green;
      margin-bottom: 15px;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Pay with M-PESA</h2>

    <?php if (!empty($error)): ?>
      <p class="error"><?= $error ?></p>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <p class="success"><?= $success ?></p>
    <?php endif; ?>

    <form method="POST" action="">
      <label for="phone">Phone Number (format 2547...)</label>
      <input type="text" name="phone" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
      <label for="amount">Amount (KES)</label>
      <input type="number" name="amount" required value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>">
      <button type="submit">Pay Now</button>
    </form>
  </div>
</body>
</html>