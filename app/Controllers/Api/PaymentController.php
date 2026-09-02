<?php
namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Env;
use App\Core\Flutterwave;
use App\Core\Request;
use App\Core\Response;
use App\Models\ActivityLog;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;

class PaymentController
{
    /**
     * Starts a Flutterwave "Standard" checkout for the given plan. The
     * frontend redirects the browser to the returned link; Flutterwave then
     * redirects back to /payments/callback where activatePlan() runs.
     */
    public function initialize(Request $request): void
    {
        $planKey = (string) $request->input('plan_key', '');
        $plan = Plan::findByKey($planKey);
        if (!$plan) { Response::error('Unknown plan', 422); return; }

        $tenant = Tenant::findById((int) Auth::tenantId());
        $user = User::find((int) Auth::tenantId(), (int) Auth::id());
        if (!$tenant || !$user) { Response::error('Account not found', 404); return; }

        $txRef = 'SUB-' . $tenant['id'] . '-' . strtoupper(bin2hex(random_bytes(6)));
        Payment::create((int) $tenant['id'], (int) $plan['id'], (float) $plan['price_monthly'], $tenant['currency'] ?: 'NGN', $txRef);

        if (!Flutterwave::isConfigured()) {
            // Dev-friendly fallback so the flow is fully testable without live Flutterwave keys:
            // the frontend detects `simulated: true` and offers a "Simulate successful payment" button
            // that calls verify() directly with this tx_ref.
            Response::success(['simulated' => true, 'tx_ref' => $txRef], 'Flutterwave is not configured — using simulated checkout for development');
            return;
        }

        $base = rtrim((string) Env::get('APP_URL', ''), '/');
        $result = Flutterwave::initializePayment([
            'tx_ref' => $txRef,
            'amount' => (string) $plan['price_monthly'],
            'currency' => $tenant['currency'] ?: 'NGN',
            'redirect_url' => ($base ?: '') . '/payments/callback',
            'customer' => [
                'email' => $user['email'],
                'name' => $user['full_name'],
                'phonenumber' => $user['phone'] ?? '',
            ],
            'customizations' => [
                'title' => 'Subscription — ' . $plan['name'] . ' plan',
                'description' => $tenant['business_name'] . ' monthly subscription',
            ],
            'meta' => ['tenant_id' => $tenant['id'], 'plan_id' => $plan['id']],
        ]);

        if (($result['status'] ?? '') !== 'success' || empty($result['data']['link'])) {
            Response::error($result['message'] ?? 'Could not start payment with Flutterwave', 502);
            return;
        }

        Response::success(['link' => $result['data']['link'], 'tx_ref' => $txRef]);
    }

    /** Called by the /payments/callback page (and available directly for the dev "simulate" button). */
    public function verify(Request $request): void
    {
        $txRef = (string) $request->input('tx_ref', '');
        $transactionId = (string) $request->input('transaction_id', '');
        $result = self::verifySubscriptionPayment($txRef, $transactionId);
        if (!$result['ok']) { Response::error($result['message'], 422); return; }
        Response::success(['activated' => true], $result['message']);
    }

    /** Shared logic used by both the JSON API above and the browser-facing /payments/callback page route. */
    public static function verifySubscriptionPayment(string $txRef, string $transactionId): array
    {
        $payment = Payment::findByTxRef($txRef);
        if (!$payment) return ['ok' => false, 'message' => 'Payment record not found'];

        if ($payment['status'] === 'successful') {
            return ['ok' => true, 'message' => 'Payment already confirmed', 'tenant_id' => (int) $payment['tenant_id']];
        }

        if (Flutterwave::isConfigured() && $transactionId !== '') {
            $verify = Flutterwave::verifyTransaction($transactionId);
            $ok = ($verify['status'] ?? '') === 'success'
                && ($verify['data']['status'] ?? '') === 'successful'
                && (float) ($verify['data']['amount'] ?? 0) >= (float) $payment['amount']
                && ($verify['data']['tx_ref'] ?? '') === $txRef;

            if (!$ok) {
                Payment::markResult($txRef, 'failed', $transactionId, json_encode($verify));
                return ['ok' => false, 'message' => 'Payment could not be verified'];
            }
            Payment::markResult($txRef, 'successful', $transactionId, json_encode($verify));
        } else {
            // Simulated / dev-mode confirmation (no live Flutterwave keys configured).
            Payment::markResult($txRef, 'successful', $transactionId ?: 'SIMULATED', null);
        }

        self::activatePlan($payment);
        return ['ok' => true, 'message' => 'Payment confirmed — plan activated', 'tenant_id' => (int) $payment['tenant_id']];
    }

    private static function activatePlan(array $payment): void
    {
        $tenant = Tenant::findById((int) $payment['tenant_id']);
        if (!$tenant) return;

        // Stack onto the remaining time if they still have an active paid period, otherwise start fresh from now.
        $now = new \DateTimeImmutable();
        $currentEnd = ($tenant['subscription_status'] === 'active' && $tenant['subscription_ends_at'])
            ? new \DateTimeImmutable($tenant['subscription_ends_at']) : $now;
        $base = $currentEnd > $now ? $currentEnd : $now;
        $newEnd = $base->modify('+30 days')->format('Y-m-d H:i:s');

        Tenant::updatePlan((int) $tenant['id'], (int) $payment['plan_id'], 'active', $newEnd);
        ActivityLog::record((int) $tenant['id'], null, 'billing.payment', 'Subscription payment confirmed via Flutterwave', ['tx_ref' => $payment['tx_ref'], 'amount' => $payment['amount']]);
    }

    public function history(Request $request): void
    {
        Response::success(Payment::historyForTenant((int) Auth::tenantId()));
    }

    /** Flutterwave server-to-server webhook (POST /api/payments/webhook). No auth middleware — verified by signature instead. */
    public function webhook(Request $request): void
    {
        $signature = $request->headers['Verif-Hash'] ?? $request->headers['verif-hash'] ?? '';
        if (!Flutterwave::verifyWebhookSignature((string) $signature)) {
            Response::error('Invalid signature', 401);
            return;
        }

        $txRef = (string) ($request->body['data']['tx_ref'] ?? $request->body['txRef'] ?? '');
        $transactionId = (string) ($request->body['data']['id'] ?? $request->body['id'] ?? '');
        $status = (string) ($request->body['data']['status'] ?? $request->body['status'] ?? '');

        $payment = $txRef !== '' ? Payment::findByTxRef($txRef) : null;
        if ($payment && $payment['status'] !== 'successful' && $status === 'successful') {
            Payment::markResult($txRef, 'successful', $transactionId, json_encode($request->body));
            self::activatePlan($payment);
        }

        Response::success(null, 'Webhook processed');
    }
}
