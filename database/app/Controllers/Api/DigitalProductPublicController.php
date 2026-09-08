<?php
namespace App\Controllers\Api;

use App\Core\Env;
use App\Core\Flutterwave;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Response;
use App\Models\DigitalProduct;
use App\Models\DigitalProductOrder;
use App\Models\Tenant;

class DigitalProductPublicController
{
    public function buy(Request $request): void
    {
        $slug = (string) $request->param('slug');
        $product = DigitalProduct::findBySlug($slug);
        if (!$product) { Response::error('Product not found', 404); return; }

        $buyerName = trim((string) $request->input('buyer_name', ''));
        $buyerEmail = trim((string) $request->input('buyer_email', ''));
        if ($buyerName === '' || !filter_var($buyerEmail, FILTER_VALIDATE_EMAIL)) {
            Response::error('Your name and a valid email are required', 422);
            return;
        }

        $tenant = Tenant::findById((int) $product['tenant_id']);
        $txRef = 'DPP-' . $product['id'] . '-' . strtoupper(bin2hex(random_bytes(6)));
        DigitalProductOrder::create((int) $product['id'], (int) $product['tenant_id'], $buyerName, $buyerEmail, (float) $product['price'], $txRef);

        if (!Flutterwave::isConfigured()) {
            Response::success(['simulated' => true, 'tx_ref' => $txRef], 'Flutterwave is not configured — using simulated checkout for development');
            return;
        }

        $base = rtrim((string) Env::get('APP_URL', ''), '/');
        $result = Flutterwave::initializePayment([
            'tx_ref' => $txRef,
            'amount' => (string) $product['price'],
            'currency' => $tenant['currency'] ?? 'NGN',
            'redirect_url' => ($base ?: '') . '/payments/callback',
            'customer' => ['email' => $buyerEmail, 'name' => $buyerName],
            'customizations' => ['title' => $product['name'], 'description' => 'Digital product purchase'],
        ]);

        if (($result['status'] ?? '') !== 'success' || empty($result['data']['link'])) {
            Response::error($result['message'] ?? 'Could not start payment with Flutterwave', 502);
            return;
        }
        Response::success(['link' => $result['data']['link'], 'tx_ref' => $txRef]);
    }

    /** Called from the shared /payments/callback page for DPP- tx_refs. */
    public static function verifyPurchase(string $txRef, string $transactionId): array
    {
        $order = DigitalProductOrder::findByTxRef($txRef);
        if (!$order) return ['ok' => false, 'message' => 'Order not found for this payment reference'];
        if ($order['status'] === 'successful') {
            return ['ok' => true, 'message' => 'Already confirmed', 'download_token' => $order['download_token']];
        }

        if (Flutterwave::isConfigured() && $transactionId !== '') {
            $verify = Flutterwave::verifyTransaction($transactionId);
            $ok = ($verify['status'] ?? '') === 'success'
                && ($verify['data']['status'] ?? '') === 'successful'
                && (float) ($verify['data']['amount'] ?? 0) >= (float) $order['amount']
                && ($verify['data']['tx_ref'] ?? '') === $txRef;
            if (!$ok) {
                DigitalProductOrder::markFailed($txRef);
                return ['ok' => false, 'message' => 'Payment could not be verified'];
            }
        }

        $token = DigitalProductOrder::markSuccessful($txRef, $transactionId ?: 'SIMULATED');
        DigitalProduct::incrementSales((int) $order['product_id']);

        $base = rtrim((string) Env::get('APP_URL', ''), '/');
        $downloadUrl = ($base ?: '') . '/api/digital-products/download/' . $token;
        Mailer::send($order['buyer_email'], 'Your purchase is ready to download', "
            <h2>Thank you for your purchase!</h2>
            <p>Your payment was successful. You can download your product using the link below.</p>
            <p><a href=\"{$downloadUrl}\">Download your product</a></p>
            <p>Keep this email — you can use this link any time.</p>");

        return ['ok' => true, 'message' => 'Payment confirmed', 'download_token' => $token];
    }

    /** Streams the deliverable to a buyer with a valid, successful-order token. */
    public function download(Request $request): void
    {
        $token = (string) $request->param('token');
        $order = DigitalProductOrder::findByToken($token);
        if (!$order) { http_response_code(404); echo 'Invalid or expired download link.'; return; }

        $stmt = \App\Core\Database::connect()->prepare('SELECT * FROM digital_products WHERE id = ?');
        $stmt->execute([$order['product_id']]);
        $product = $stmt->fetch();
        if (!$product || empty($product['file_path'])) { http_response_code(404); echo 'No file available for this product.'; return; }

        $root = rtrim((string) Env::get('UPLOAD_DIR', __DIR__ . '/../../../public/uploads'), '/');
        $absolute = str_replace('/uploads', $root, $product['file_path']);
        if (!is_file($absolute)) { http_response_code(404); echo 'File not found.'; return; }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($product['file_name'] ?: $product['file_path']) . '"');
        header('Content-Length: ' . filesize($absolute));
        readfile($absolute);
        exit;
    }
}
