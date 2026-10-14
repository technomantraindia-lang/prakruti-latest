<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #334155; margin: 0; padding: 20px; background-color: #f8fafc; }
        .card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; overflow: hidden; }
        .header { background: #047857; color: #ffffff; padding: 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 700; }
        .content { padding: 24px; }
        .highlight { background: #ecfdf5; border-left: 4px solid #10b981; padding: 12px 16px; margin: 16px 0; border-radius: 0 4px 4px 0; }
        .footer { background: #f1f5f9; padding: 16px; text-align: center; font-size: 12px; color: #64748b; }
        .btn { display: inline-block; background: #047857; color: #ffffff; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>Prakruti Ayurvedic</h1>
            <p style="margin: 4px 0 0; opacity: 0.9;">Order & Tax Invoice Confirmation</p>
        </div>
        <div class="content">
            <p>Dear <strong>{{ $order->shipping_name }}</strong>,</p>
            <p>Thank you for choosing Prakruti Ayurvedic! Your order has been placed successfully and is being prepared with utmost care.</p>
            
            <div class="highlight">
                <p style="margin: 0 0 6px;"><strong>Invoice Number:</strong> {{ $order->invoice_number }}</p>
                <p style="margin: 0 0 6px;"><strong>Order ID:</strong> #{{ $order->id }}</p>
                <p style="margin: 0 0 6px;"><strong>Total Amount Paid:</strong> ₹{{ number_format($order->total_amount, 2) }}</p>
                @if($order->is_business_order && $order->customer_gstin)
                    <p style="margin: 0 0 6px;"><strong>Business Name:</strong> {{ $order->business_name }}</p>
                    <p style="margin: 0;"><strong>Customer GSTIN:</strong> {{ $order->customer_gstin }} (Eligible for ITC claim)</p>
                @endif
            </div>

            <p>Your official <strong>GST Rule 46 Tax Invoice</strong> has been generated and is attached to this email as a PDF for your accounting and GST compliance records.</p>
            
            <p>You can also download this invoice anytime from your account dashboard on our website.</p>

            <p style="margin-top: 25px;">Warm regards,<br><strong>Team Prakruti Ayurvedic</strong><br>Ahmedabad, Gujarat</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Prakruti Ayurvedic Pharmacy. All rights reserved.<br>
            Plot No. 42, GIDC Phase II, Vatva, Ahmedabad, Gujarat - 382445
        </div>
    </div>
</body>
</html>
