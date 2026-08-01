<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Production</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="login-header">
            <h2>Production</h2>
            <p>Sign in to your account</p>
        </div>

        <form action="{{ route('login') }}" method="POST">
            @csrf
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="UserName">Username</label>
                <input type="text" id="UserName" name="UserName" value="{{ old('UserName') }}" placeholder="Enter username" required autofocus autocomplete="username">
                @error('UserName')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group" style="margin-bottom: 2rem;">
                <label for="Password">Password</label>
                <input type="password" id="Password" name="Password" placeholder="Enter password" required autocomplete="current-password">
                @error('Password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn-action" style="width: 100%; justify-content: center; padding: 0.9rem;">
                Login
            </button>
        </form>
    </div>
</body>
</html>
