<?php
namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Env;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Response;
use App\Models\ActivityLog;
use App\Models\OnlineOrder;
use App\Models\Tenant;
use App\Models\Withdrawal;

class EarningsController
{
    /** Store earnings summary: total earned via Flutterwave, what's already claimed, and what's available. */
    public function summary(Request $request): void
    {
        $tenantId = (int) Auth::tenantId();
        $totalEarned = OnlineOrder::totalFlutterwaveEarnings($tenantId);
        $claimed = Withdrawal::claimedTotal($tenantId, 'store');

        Response::success([
            'total_earned' => $totalEarned,
            'available_balance' => round($totalEarned - $claimed, 2),
            'fee_percent' => Withdrawal::FEE_PERCENT['store'],
            'recent_payments' => OnlineOrder::recentPaidPayments($tenantId),
            'withdrawals' => Withdrawal::historyForTenant($tenantId, 'store'),
        ]);
    }

    public function requestWithdrawal(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }

        $tenantId = (int) Auth::tenantId();
        $amount = (float) $request->input('amount', 0);
        $bank = [
            'bank_name' => trim((string) $request->input('bank_name', '')),
            'account_name' => trim((string) $request->input('account_name', '')),
            'account_number' => trim((string) $request->input('account_number', '')),
        ];

        if ($amount <= 0) { Response::error('Enter a valid amount', 422); return; }
        if ($bank['bank_name'] === '' || $bank['account_name'] === '' || $bank['account_number'] === '') {
            Response::error('Bank name, account name and account number are all required', 422);
            return;
        }

        $totalEarned = OnlineOrder::totalFlutterwaveEarnings($tenantId);
        $available = $totalEarned - Withdrawal::claimedTotal($tenantId, 'store');
        if ($amount > $available) { Response::error('That amount is more than your available balance (' . number_format($available, 2) . ')', 422); return; }

        $id = Withdrawal::create($tenantId, 'store', $amount, $bank);
        $tenant = Tenant::findById($tenantId);
        $user = Auth::user();

        self::notify($tenant, $user, 'store', $id, $amount, $bank);
        ActivityLog::record($tenantId, Auth::id(), 'earnings.withdraw', "Requested withdrawal of {$amount} from store earnings");

        Response::success(['id' => $id], 'Withdrawal requested — it will be processed within 3 hours.');
    }

    /** Shared by both store-earnings and digital-product withdrawal requests. */
    public static function notify(?array $tenant, ?array $user, string $source, int $withdrawalId, float $amount, array $bank): void
    {
        $businessName = $tenant['business_name'] ?? 'A business';
        $sourceLabel = $source === 'digital_product' ? 'digital product' : 'online store';

        // Confirmation to the person requesting it.
        if (!empty($user['email'])) {
            Mailer::send($user['email'], 'Withdrawal request received', "
                <h2>Withdrawal requested</h2>
                <p>We've received your withdrawal request of <strong>" . number_format($amount, 2) . "</strong> from your {$sourceLabel} earnings.</p>
                <p>Withdrawals are processed manually and take a <strong>maximum of 3 hours</strong> during business hours.</p>
                <p>You'll be paid out to the bank details you provided.</p>");
        }

        // Notify the platform owner so they can action it.
        $payoutEmail = (string) Env::get('PLATFORM_PAYOUT_EMAIL', '');
        if ($payoutEmail !== '') {
            Mailer::send($payoutEmail, "Withdrawal request #{$withdrawalId} — {$businessName}", "
                <h2>New withdrawal request</h2>
                <p><strong>Business:</strong> " . htmlspecialchars($businessName) . " (" . htmlspecialchars($tenant['slug'] ?? '') . ")</p>
                <p><strong>Requested by:</strong> " . htmlspecialchars($user['full_name'] ?? '') . " (" . htmlspecialchars($user['email'] ?? '') . ")</p>
                <p><strong>Source:</strong> " . htmlspecialchars($sourceLabel) . "</p>
                <p><strong>Amount:</strong> " . number_format($amount, 2) . "</p>
                <p><strong>Bank:</strong> " . htmlspecialchars($bank['bank_name'] ?? '') . "</p>
                <p><strong>Account name:</strong> " . htmlspecialchars($bank['account_name'] ?? '') . "</p>
                <p><strong>Account number:</strong> " . htmlspecialchars($bank['account_number'] ?? '') . "</p>
                <p>Withdrawal ID: {$withdrawalId}</p>");
        }
    }
}
