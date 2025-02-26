<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket</title>
</head>
<body>
    <h1>Buy Tickets</h1>
    @if (session('error'))
        <div style="color: red;">{{ session('error') }}</div>
    @endif
    @if (session('success'))
        <div style="color: green;">{{ session('success') }}</div>
    @endif
    <form action="{{ route('donation.process') }}" method="POST">
        @csrf
        <label for="amount">Amount (USD):</label>
        <input type="number" name="amount" id="amount" step="0.01" min="1" required>
        <button type="submit">Buy Ticket</button>
    </form>
</body>
</html>