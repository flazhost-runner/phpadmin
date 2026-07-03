<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 – Internal Server Error</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
             background:#f3f4f6;display:flex;align-items:center;justify-content:center;
             min-height:100vh;color:#374151}
        .card{background:#fff;border-radius:12px;padding:48px 40px;text-align:center;
              max-width:460px;width:90%;box-shadow:0 4px 24px rgba(0,0,0,.08)}
        h1{font-size:5rem;font-weight:800;color:#EF4444;line-height:1}
        h2{font-size:1.5rem;font-weight:600;margin:16px 0 8px}
        p{color:#6B7280;margin-bottom:28px}
        a{display:inline-block;background:#EF4444;color:#fff;padding:10px 24px;
          border-radius:6px;text-decoration:none;font-weight:500}
        a:hover{background:#DC2626}
    </style>
</head>
<body>
<div class="card">
    <h1>500</h1>
    <h2>Internal Server Error</h2>
    <p><?= htmlspecialchars($message ?? 'Something went wrong. Please try again later.', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
    <a href="/admin/v1/dashboard">Back to Dashboard</a>
</div>
</body>
</html>
