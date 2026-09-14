<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Pay Invoice #{{ $invoice->invoice_number }}</title>
<script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-slate-950 text-white min-h-screen flex items-center justify-center">
<div class="text-center">
    <h1 class="text-2xl font-black text-cyan-400 mb-4">Pay Invoice #{{ $invoice->invoice_number }}</h1>
    <p class="text-slate-400 mb-6">Amount: ₹{{ number_format($invoice->amount, 2) }}</p>
    <form id="cashfreeForm" action="{{ $action }}" method="POST">
        @foreach($payload as $k => $v)
            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
        @endforeach
        <button type="submit" class="px-8 py-3 bg-cyan-500 text-slate-900 font-bold rounded-lg hover:bg-cyan-400"><i class="fas fa-credit-card mr-2"></i>Pay with Cashfree</button>
    </form>
    <a href="{{ route('bconnect.billing') }}" class="block mt-4 text-slate-500 hover:text-white">Cancel</a>
</div>
<script>document.getElementById('cashfreeForm').submit();</script>
</body>
</html>
