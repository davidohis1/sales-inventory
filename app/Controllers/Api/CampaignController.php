<?php
namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\CampaignSender;
use App\Core\Request;
use App\Core\Response;
use App\Models\ActivityLog;
use App\Models\Campaign;
use App\Models\CampaignRecipient;

class CampaignController
{
    private const AUDIENCES = ['all', 'debtors', 'recent', 'manual'];

    public function index(Request $request): void
    {
        Response::success(Campaign::listForTenant(Auth::tenantId()));
    }

    public function show(Request $request): void
    {
        $campaign = Campaign::find(Auth::tenantId(), (int) $request->param('id'));
        if (!$campaign) { Response::error('Campaign not found', 404); return; }
        Response::success($campaign);
    }

    /** Live count for the audience picker, e.g. "412 of 500 customers have an email on file." */
    public function audiencePreview(Request $request): void
    {
        $audience = (string) $request->input('audience', 'all');
        $manualIds = (array) $request->input('customer_ids', []);
        if (!in_array($audience, self::AUDIENCES, true)) { Response::error('Invalid audience', 422); return; }

        $matches = CampaignRecipient::resolveAudience(Auth::tenantId(), $audience, $manualIds);
        Response::success(['count' => count($matches)]);
    }

    public function store(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }

        $subject = trim((string) $request->input('subject', ''));
        $body = (string) $request->input('body_html', '');
        $audience = (string) $request->input('audience', 'all');
        $manualIds = (array) $request->input('customer_ids', []);
        $scheduledAt = $request->input('scheduled_at') ?: null;
        $sendNow = (bool) $request->input('send_now', false);

        if ($subject === '' || trim(strip_tags($body)) === '') { Response::error('Subject and message body are required', 422); return; }
        if (!in_array($audience, self::AUDIENCES, true)) { Response::error('Invalid audience', 422); return; }

        $tenantId = Auth::tenantId();
        $customers = CampaignRecipient::resolveAudience($tenantId, $audience, $manualIds);
        if (empty($customers)) { Response::error('No customers with a saved email match this audience', 422); return; }

        $status = 'draft';
        if ($sendNow) { $status = 'sending'; }
        elseif ($scheduledAt) { $status = 'scheduled'; }

        $campaignId = Campaign::create([
            'tenant_id' => $tenantId,
            'created_by' => Auth::id(),
            'subject' => $subject,
            'body_html' => $body,
            'audience' => $audience,
            'audience_customer_ids' => $audience === 'manual' ? json_encode(array_map('intval', $manualIds)) : null,
            'status' => $status,
            'scheduled_at' => $scheduledAt,
            'total_recipients' => count($customers),
        ]);
        CampaignRecipient::seed($campaignId, $customers);
        ActivityLog::record($tenantId, Auth::id(), 'campaign.create', "Created campaign \"$subject\" (" . count($customers) . " recipients)");

        // Small lists: send the first batch immediately for instant feedback. Anything left over
        // (or anything scheduled for later) is picked up by the cron batch script.
        if ($sendNow) {
            CampaignSender::sendBatch($campaignId, 30);
        }

        Response::success(Campaign::find($tenantId, $campaignId), 'Campaign created', 201);
    }

    /** Manually trigger/continue sending — used for a draft, or to nudge a "sending" campaign along without waiting on cron. */
    public function send(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }
        $tenantId = Auth::tenantId();
        $id = (int) $request->param('id');
        $campaign = Campaign::find($tenantId, $id);
        if (!$campaign) { Response::error('Campaign not found', 404); return; }
        if (in_array($campaign['status'], ['sent'], true)) { Response::error('Campaign has already been sent', 422); return; }

        if ($campaign['status'] === 'draft') { Campaign::setStatus($id, 'sending'); }
        $result = CampaignSender::sendBatch($id, 30);

        Response::success($result, $result['pending_count'] > 0 ? 'Sending — more recipients remain and will continue automatically' : 'Campaign sent');
    }
}
