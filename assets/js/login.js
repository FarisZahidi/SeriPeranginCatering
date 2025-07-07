document.getElementById('loginForm').addEventListener('submit', function(e) {
    var username = document.getElementById('username').value.trim();
    var password = document.getElementById('password').value.trim();
    if (!username || !password) {
        alert('Please enter both username and password.');
        e.preventDefault();
    }
}); 