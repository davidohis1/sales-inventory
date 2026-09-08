<?php
/**
 * Processes pending email campaign sends in small batches.
 *
 * Run this on a schedule (e.g. every 2 minutes) so:
 *   - large campaigns (more than one batch's worth of recipients) finish
 *     sending in the background instead of during a single web request
 *   - scheduled campaigns actually go out at their scheduled_at time
 *
 * Usage:
 *   php scripts/process_campaigns.php
 *
 * Example crontab entry (every 2 minutes):
 *   * * * * * cd /path/to/oripio && php scripts/process_campaigns.php >> storage/campaign_cron.log 2>&1
 *
 * Safe to run even when nothing is pending — it just exits quickly.
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Core\CampaignSender;
use App\Core\Env;
use App\Models\CampaignRecipient;

Env::load(__DIR__ . '/../.env');
date_default_timezone_set(Env::get('APP_TIMEZONE', 'Africa/Lagos'));

$campaignIds = CampaignRecipient::campaignIdsWithPending();

if (empty($campaignIds)) {
    echo "[" . date('Y-m-d H:i:s') . "] No pending campaigns.\n";
    exit(0);
}

foreach ($campaignIds as $id) {
    $result = CampaignSender::sendBatch($id, 30);
    echo "[" . date('Y-m-d H:i:s') . "] Campaign #$id — sent: {$result['sent_count']}, failed: {$result['failed_count']}, still pending: {$result['pending_count']}, status: {$result['status']}\n";
}
