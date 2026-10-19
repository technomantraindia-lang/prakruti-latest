<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Your Prakruti Monthly Ration Basket Refill</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      background-color: #f6f3ee;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      color: #2b332b;
      -webkit-font-smoothing: antialiased;
    }
    .wrapper {
      width: 100%;
      background-color: #f6f3ee;
      padding: 30px 15px;
      box-sizing: border-box;
    }
    .email-container {
      max-width: 620px;
      margin: 0 auto;
      background: #ffffff;
      border-radius: 16px;
      overflow: hidden;
      border: 1px solid #e7ded0;
      box-shadow: 0 8px 30px rgba(23, 56, 38, 0.06);
    }
    .header-bar {
      background: linear-gradient(135deg, #173826 0%, #1f4a33 100%);
      padding: 30px 24px 26px;
      text-align: center;
      color: #ffffff;
    }
    .brand-kicker {
      display: inline-block;
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      color: #dfba6c;
      margin-bottom: 6px;
    }
    .brand-name {
      margin: 0;
      font-size: 26px;
      font-weight: 800;
      letter-spacing: -0.01em;
      color: #ffffff;
    }
    .tagline {
      margin: 6px 0 0;
      font-size: 13px;
      color: #c9ded0;
    }
    .content-body {
      padding: 30px 28px;
    }
    .badge-pill {
      display: inline-block;
      background: #eaf4ed;
      color: #173826;
      border: 1px solid #bedec8;
      border-radius: 20px;
      padding: 4px 12px;
      font-size: 12px;
      font-weight: 700;
      margin-bottom: 14px;
    }
    h2 {
      margin: 0 0 12px;
      font-size: 20px;
      color: #173826;
      font-weight: 800;
      line-height: 1.3;
    }
    p {
      margin: 0 0 14px;
      font-size: 14px;
      line-height: 1.6;
      color: #434c44;
    }
    .cycle-highlight {
      background: #fbf8f2;
      border-left: 4px solid #173826;
      border-radius: 0 10px 10px 0;
      padding: 14px 16px;
      margin: 18px 0 22px;
    }
    .cycle-highlight p {
      margin: 4px 0;
      font-size: 13px;
    }
    .cycle-highlight strong {
      color: #173826;
    }
    .basket-table {
      width: 100%;
      border-collapse: collapse;
      margin: 20px 0;
      font-size: 13px;
    }
    .basket-table th {
      background: #f1ebd8;
      color: #173826;
      font-weight: 700;
      text-align: left;
      padding: 10px 12px;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .basket-table td {
      padding: 10px 12px;
      border-bottom: 1px solid #f0e9dc;
      color: #333d34;
    }
    .basket-table tr:last-child td {
      border-bottom: none;
    }
    .table-item-name {
      font-weight: 700;
      color: #173826;
    }
    .table-total-row {
      background: #faf6ed;
      font-weight: 800;
      font-size: 14px;
    }
    .table-total-row td {
      color: #173826;
      border-top: 2px solid #e2d7c3;
    }
    .actions-section {
      text-align: center;
      margin: 26px 0 10px;
    }
    .btn-reorder {
      display: inline-block;
      background: #173826;
      color: #ffffff !important;
      text-decoration: none;
      font-weight: 700;
      font-size: 15px;
      padding: 14px 28px;
      border-radius: 10px;
      box-shadow: 0 4px 14px rgba(23, 56, 38, 0.2);
    }
    .whatsapp-box {
      margin-top: 22px;
      background: #f0fdf4;
      border: 1px dashed #86efac;
      border-radius: 12px;
      padding: 14px 18px;
      text-align: center;
    }
    .btn-whatsapp {
      display: inline-block;
      background: #16a34a;
      color: #ffffff !important;
      text-decoration: none;
      font-weight: 700;
      font-size: 13px;
      padding: 8px 18px;
      border-radius: 8px;
      margin-top: 6px;
    }
    .edit-note {
      font-size: 12px;
      color: #718072;
      text-align: center;
      margin-top: 14px;
      line-height: 1.5;
    }
    .footer-bar {
      background: #f3efe6;
      border-top: 1px solid #e7ded0;
      padding: 20px;
      text-align: center;
      font-size: 11px;
      color: #757f76;
      line-height: 1.6;
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="email-container">
      
      <!-- Brand Header -->
      <div class="header-bar">
        <span class="brand-kicker">100% Certified Organic · Farm-Direct</span>
        <h1 class="brand-name">Prakruti Corporation</h1>
        <p class="tagline">Smart Family Grocery Planner · Household Monthly Replenishment</p>
      </div>

      <!-- Main Body -->
      <div class="content-body">
        <span class="badge-pill">📅 30-Day Monthly Ration Cycle Refill</span>
        <h2>Namaste {{ $recipientName }},</h2>
        <p>
          It's time to replenish your household pantry! Your customized <strong>Prakruti Family Nutrition Basket</strong> was scheduled for a 30-day nutrition cycle to keep your kitchen stocked with wholesome organic staples.
        </p>

        <!-- Automated Counter Timeline Box -->
        <div class="cycle-highlight">
          <p><strong>🛒 Cycle Overview:</strong> Previous order placed on <strong>{{ $orderDate }}</strong>.</p>
          <p><strong>⏳ Renewal Due Date:</strong> <strong>{{ $renewalDate }}</strong> (refill recommended by <strong>{{ $reminderDate }}</strong>).</p>
          <p><strong>📦 Packaging:</strong> Eco-Fresh Vacuum Packaging locks in 100% farm flavor and prevents oxidation without chemical preservatives.</p>
        </div>

        <!-- Basket Summary -->
        <h3 style="margin: 18px 0 8px; font-size: 15px; color: #173826; text-transform: uppercase; letter-spacing: 0.04em;">
          Your Previous Monthly Ration Basket:
        </h3>

        <table class="basket-table">
          <thead>
            <tr>
              <th>Staple Item</th>
              <th>Pack Size</th>
              <th>Qty</th>
              <th style="text-align: right;">Amount</th>
            </tr>
          </thead>
          <tbody>
            @foreach($items as $item)
            <tr>
              <td>
                <span class="table-item-name">{{ $item['name'] }}</span>
                @if(!empty($item['label']))
                  <br><span style="font-size: 11px; color: #727e73;">{{ $item['label'] }}</span>
                @endif
              </td>
              <td>{{ $item['package_size'] ?? 'Standard' }}</td>
              <td>{{ $item['qty'] }}</td>
              <td style="text-align: right;">₹{{ number_format((float) ($item['price'] * $item['qty']), 2) }}</td>
            </tr>
            @endforeach
            <tr class="table-total-row">
              <td colspan="3">Estimated Monthly Total</td>
              <td style="text-align: right;">₹{{ number_format((float) $monthlyTotal, 2) }}</td>
            </tr>
          </tbody>
        </table>

        <!-- Direct Actions -->
        <div class="actions-section">
          <a href="{{ $reorderUrl }}" class="btn-reorder" target="_blank">
            🛒 1-Click: Add Previous Ration Basket to Cart
          </a>
        </div>

        <p class="edit-note">
          Need to make adjustments? You can freely <strong>edit quantities, swap grains, or add extra organic pantry items</strong> directly in your cart before placing your order.
        </p>

        <!-- WhatsApp Reorder Option -->
        <div class="whatsapp-box">
          <p style="margin: 0; font-size: 13px; font-weight: 700; color: #15803d;">
            📱 Prefer ordering over WhatsApp?
          </p>
          <p style="margin: 4px 0 8px; font-size: 12px; color: #4b5563;">
            Send your saved monthly basket directly to our care concierge on WhatsApp:
          </p>
          <a href="{{ $whatsappUrl }}" class="btn-whatsapp" target="_blank">
            Chat on WhatsApp (+91 98765 43210)
          </a>
        </div>
      </div>

      <!-- Footer -->
      <div class="footer-bar">
        <strong>Prakruti Corporation</strong><br>
        Pure Organic Food Products · Ahmedabad, Gujarat - 382445<br>
        This is an automated 30-day household replenishment service based on your family nutrition planner.<br>
        &copy; {{ date('Y') }} Prakruti Corporation. All rights reserved.
      </div>
    </div>
  </div>
</body>
</html>
