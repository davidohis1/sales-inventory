<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Receipt — <?= htmlspecialchars($tenant['business_name']) ?></title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/store.css">
<style>
  .receipt-box { max-width: 480px; margin: 30px auto; background: #fff; border: 1px solid #e7e3da; border-radius: 12px; padding: 26px; }
  .receipt-box table { width: 100%; border-collapse: collapse; margin-top: 14px; }
  .receipt-box th, .receipt-box td { text-align: left; padding: 6px 0; font-size: 13.5px; border-bottom: 1px solid #f0efe9; }
  .receipt-totals div { display: flex; justify-content: space-between; padding: 4px 0; font-size: 14px; }
  .receipt-totals .grand { font-weight: 700; font-size: 17px; border-top: 2px solid #e7e3da; margin-top: 6px; padding-top: 10px; }
  @media print { .no-print { display: none; } }
</style>
</head>
<body>
<div class="receipt-box" id="receipt-root"><p>Loading receipt…</p></div>
<script>
(async function () {
    const base = <?= json_encode($base) ?>;
    const slug = <?= json_encode($slug) ?>;
    const id = <?= json_encode($params['id']) ?>;
    const root = document.getElementById('receipt-root');
    try {
        const res = await fetch(`${base}/api/${slug}/receipts/${id}/public`);
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        const s = json.data;
        const sym = s.currency === 'NGN' ? '\u20a6' : (s.currency + ' ');
        const fmt = (n) => sym + Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const rows = s.items.map(i => `<tr><td>${i.product_name}</td><td>${i.quantity}</td><td>${fmt(i.unit_price)}</td><td>${fmt(i.line_total)}</td></tr>`).join('');
        root.innerHTML = `
            <h2 style="margin-top:0;">${s.business_name}</h2>
            <p style="color:#8a8578;">Receipt ${s.receipt_no} &middot; ${new Date(s.created_at.replace(' ','T')).toLocaleString()}</p>
            <table><thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead><tbody>${rows}</tbody></table>
            <div class="receipt-totals">
                <div><span>Subtotal</span><span>${fmt(s.subtotal)}</span></div>
                <div><span>Discount</span><span>${fmt(s.discount)}</span></div>
                <div class="grand"><span>Total</span><span>${fmt(s.total)}</span></div>
                <div><span>Paid</span><span>${fmt(s.amount_paid)}</span></div>
                <div><span>Balance Due</span><span>${fmt(s.balance_due)}</span></div>
            </div>
            <button class="btn-store no-print" style="margin-top:18px;" onclick="window.print()">Print</button>`;
    } catch (e) {
        root.innerHTML = `<p>Sorry, this receipt could not be found.</p>`;
    }
})();
</script>
</body>
</html>
