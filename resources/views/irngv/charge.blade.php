<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>در حال انتقال به درگاه پرداخت</title>

    <!-- جلوگیری از ارسال Referer -->
    <meta name="referrer" content="no-referrer">

    <style>
        body {
            font-family: sans-serif;
            text-align: center;
            margin-top: 80px;
        }
    </style>
</head>
<body>

<h3>در حال انتقال به درگاه پرداخت...</h3>
<p>لطفاً چند لحظه صبر کنید</p>

<form id="payForm" method="POST" action="{{ route('irngv.charge.pay') }}">
    @csrf

    <input type="hidden" name="orderId" value="{{ $orderId }}">
    <input type="hidden" name="amount" value="{{ $amount }}">
    <input type="hidden" name="description" value="{{ $description }}">
    <input type="hidden" name="mobile" value="{{ $mobile }}">
    <input type="hidden" name="callbackUrl" value="{{ $callbackUrl }}">
    <input type="hidden" name="irngvCallbackUrl" value="{{ $irngvCallbackUrl }}">
</form>

<script>
    // ارسال خودکار فرم بعد از لود صفحه
    setTimeout(function () {
        document.getElementById('payForm').submit();
    }, 800);
</script>

</body>
</html>
