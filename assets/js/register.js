document.getElementById('registerForm').addEventListener('submit', function(e) {
    var password = document.getElementById('password').value;
    var confirm = document.getElementById('confirm_password').value;
    if (password !== confirm) {
        alert('Passwords do not match.');
        e.preventDefault();
    }
}); 