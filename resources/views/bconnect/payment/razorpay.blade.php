<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Pay Invoice #{{ $invoice->invoice_number }}</title>
<script src="https://cdn.tailwindcss.com"></script><script src="https://checkout.razorpay.com/v1/checkout.js"></script></head>
<body class="bg-slate-950 text-white min-h-screen flex items-center justify-center">
<div class="text-center">
    <h1 class="text-2xl font-black text-cyan-400 mb-4">Pay Invoice #{{ $invoice->invoice_number }}</h1>
    <p class="text-slate-400 mb-6">Amount: ₹{{ number_format($invoice->amount, 2) }}</p>
    <button id="payBtn" class="px-8 py-3 bg-cyan-500 text-slate-900 font-bold rounded-lg hover:bg-cyan-400"><i class="fas fa-credit-card mr-2"></i>Pay Now</button>
    <a href="{{ route('bconnect.billing') }}" class="block mt-4 text-slate-500 hover:text-white">Cancel</a>
</div>
<script>
const options = {
    key: @json($key),
    amount: {{ $order['amount'] }},
    currency: @json($order['currency']),
    name: 'Believoo B-CONNECT',
    description: 'Invoice #{{ $invoice->invoice_number }}',
    order_id: @json($order['id']),
    handler: function (response) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('bconnect.billing.callback.razorpay') }}';
        form.innerHTML = `
            @csrf
            <input type="hidden" name="razorpay_payment_id" value="${response.razorpay_payment_id}">
            <input type="hidden" name="razorpay_order_id" value="${response.razorpay_order_id}">
            <input type="hidden" name="razorpay_signature" value="${response.razorpay_signature}">
        `;
        document.body.appendChild(form);
        form.submit();
    },
    prefill: { name: 'B-CONNECT Customer', email: 'customer@believoo.com' },
    theme: { color: '#06b6d4' }
};
const rzp = new Razorpay(options);
document.getElementById('payBtn').addEventListener('click', () => rzp.open());
</script>
</body>
</html>
