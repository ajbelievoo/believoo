<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AMC Payment - {{ config('app.name') }}</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: #f5f5f5;
        }
        .payment-container {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .loading {
            font-size: 1.2rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="loading">Loading payment gateway...</div>
    </div>

    <form id="razorpay-form" action="{{ $callback_url }}" method="POST" style="display:none;">
        @csrf
        <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
        <input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
        <input type="hidden" name="razorpay_signature" id="razorpay_signature">
        <input type="hidden" name="internal_order_id" value="{{ $internal_order_id }}">
        <input type="hidden" name="agreement_id" value="{{ $agreement_id }}">
    </form>

    <script>
        const options = {
            key: '{{ $key_id }}',
            amount: {{ $amount }},
            currency: '{{ $currency }}',
            name: '{{ config('app.name') }}',
            description: '{{ $description }}',
            order_id: '{{ $order_id }}',
            prefill: {
                name: '{{ $prefill_name }}',
                email: '{{ $prefill_email }}',
                contact: '{{ $prefill_contact }}'
            },
            theme: {
                color: '#6366f1'
            },
            handler: function(response) {
                document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
                document.getElementById('razorpay_order_id').value = response.razorpay_order_id;
                document.getElementById('razorpay_signature').value = response.razorpay_signature;
                document.getElementById('razorpay-form').submit();
            },
            modal: {
                ondismiss: function() {
                    window.location.href = '{{ route('client.dashboard', ['tab' => 'agreements']) }}';
                }
            }
        };

        const rzp = new Razorpay(options);
        rzp.open();
    </script>
</body>
</html>
