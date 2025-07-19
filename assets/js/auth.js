const API_BASE_URL = 'https://games.teluapp.org/8puzzle/backend/api';

const Auth = {
    async checkAuth() {
        try {
            console.log('Checking auth at URL:', `${API_BASE_URL}/check_login.php`);
            console.log('Current cookies:', document.cookie);
            
            const response = await fetch(`${API_BASE_URL}/check_login.php`, {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            console.log('Auth check HTTP status:', response.status);
            
            if (!response.ok) {
                console.error('Auth check HTTP error:', response.status, response.statusText);
                return false;
            }
            
            // Get the raw text first for debugging
            const responseText = await response.text();
            console.log('Raw auth response text:', responseText);
            
            // Try to parse the response as JSON
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Response was not valid JSON');
                return false;
            }
            
            console.log('Auth check response data:', data);
            
            // Flexible check - look for either status: success or authenticated: true
            const isAuthenticated = data.status === 'success' || data.authenticated === true;
            console.log('Authentication result:', isAuthenticated);
            
            return isAuthenticated;
        } catch (error) {
            console.error('Auth check failed:', error);
            return false;
        }
    },
    
    redirectToLogin() {
        console.log('Redirecting to login page from:', window.location.pathname);
        // Add error parameter to help debug
        window.location.href = 'login.html?from=' + encodeURIComponent(window.location.pathname);
    },
    
    async logout() {
        try {
            console.log('Starting logout process...');
            
            const response = await fetch(`${API_BASE_URL}/logout.php`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            console.log('Logout response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const responseText = await response.text();
            console.log('Raw logout response:', responseText);
            
            try {
                const data = JSON.parse(responseText);
                console.log('Parsed logout response:', data);
                
                // Clear storage
                localStorage.clear();
                sessionStorage.clear();
                
                console.log('Cookies before clearing:', document.cookie);
                
                // Clear cookies
                document.cookie.split(";").forEach(function(c) { 
                    document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
                    document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/8puzzle");
                });
                
                console.log('Cookies after clearing:', document.cookie);
                
            } catch (jsonError) {
                console.error('JSON parsing error:', jsonError);
                alert('Warning: Logout response was not valid JSON');
            }
            
            // Always redirect to login regardless of response
            window.location.replace('login.html?action=logout');
            
        } catch (error) {
            console.error('Logout error:', error);
            alert('Logout failed: ' + error.message);
        }
    }
};

// Add this for testing
console.log('Auth.js loaded, API_BASE_URL =', API_BASE_URL);

// Make Auth available globally
window.Auth = Auth;