<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>404 - Page Not Found</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: sans-serif;
            color: white;
            text-align: center;
            display: flex;
            justify-content: center;
            align-items: center;
            background: url('/images/404-hiker.jpg') no-repeat center center fixed;
            background-position: 50% 37.5%;
            background-size: cover;
        }
        h1, p, a {
            text-shadow:
                -1px -1px 0 rgba(0, 0, 0, 0.25),
                1px -1px 0 rgba(0, 0, 0, 0.25),
                -1px  1px 0 rgba(0, 0, 0, 0.25),
                1px  1px 0 rgba(0, 0, 0, 0.25);
        }
        h1 {
            font-size: 20vw;
            margin: 0;
            padding-bottom: 7vw;
        }
        p {
            font-size: 7vw;
            margin: 1rem 0;
        }
        p.link {
            color: #ffdd57;
            font-size: 7vw;
            font-weight: bold;
        }
        a {
            color: #ffdd57;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }

        @media (min-width: 1280px) {
            h1 {
                font-size: 15rem;
            }
            p, a {
                font-size: 5rem;
            }
        }
    </style>
</head>
<body>
<div class="overlay">
    <h1>Oops!</h1>
    <p>We couldn't find that page.</p>
    <p class="link">
        <a href="{{ url('/') }}">Back to Base</a>
    </p>
</div>
</body>
</html>
