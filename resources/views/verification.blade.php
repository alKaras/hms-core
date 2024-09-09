<!DOCTYPE html>
<html>
<head>
    <title>Verification</title>
</head>
<body>
<h1>Email Verification</h1>
<p>Натисніть кнопку знизу для верифікації пошти</p>
<button id="verifyButton">Верифікувати</button>

<script>
    document.getElementById('verifyButton').addEventListener('click', async () => {
        const token = "{{ $token }}";
        const email = "{{ $email }}";

        try {
            const response = await fetch('http://localhost:8000/api/auth/email-verify', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ token, email }),
            });

            const data = await response.json();

            if (response.ok) {
                alert(data.message || 'Verification successful!');
                window.location.href = 'http://localhost:3000';
            } else {
                alert(data.error || 'Verification failed!');
            }
        } catch (error) {
            alert('An error occurred: ' + error.message);
        }
    });
</script>
</body>
</html>
