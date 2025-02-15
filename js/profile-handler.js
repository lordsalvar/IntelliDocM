const verificationModal = document.getElementById('verificationModal');

function openVerificationModal() {
    verificationModal.style.display = "block";
    document.getElementById('verifyPassword').value = '';
    document.getElementById('verifyPassword').focus();
}

function closeVerificationModal() {
    verificationModal.style.display = "none";
}

async function confirmChanges() {
    const password = document.getElementById('verifyPassword').value;
    if (!password) {
        alert('Please enter your password');
        return;
    }

    formData.append('password', password);

    try {
        const response = await fetch('ajax/update_profile.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        
        if (result.success) {
            // Update profile information instantly
            document.querySelector('.profile span').textContent = formData.get('full_name');
            
            // Show success message
            const successAlert = document.createElement('div');
            successAlert.className = 'alert success';
            successAlert.innerHTML = `<i class="fas fa-check-circle"></i> ${result.message}`;
            
            const profileHeader = document.querySelector('.profile-header');
            profileHeader.appendChild(successAlert);
            
            // Remove alert after 3 seconds
            setTimeout(() => successAlert.remove(), 3000);
            
            closeVerificationModal();
        } else {
            alert(result.message || 'Error updating profile');
        }
    } catch (error) {
        alert('Error updating profile');
        console.error(error);
    }
}

// Also update password change function to show loading
async function handlePasswordChange(event) {
    event.preventDefault();
    
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    if (newPassword !== confirmPassword) {
        alert('New passwords do not match!');
        return false;
    }

    showLoading('Updating your password...');

    try {
        // Artificial delay for better UX
        await new Promise(resolve => setTimeout(resolve, 1000));

        const response = await fetch('ajax/update_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `currentPassword=${encodeURIComponent(currentPassword)}&newPassword=${encodeURIComponent(newPassword)}`
        });

        const result = await response.json();
        
        hideLoading();
        
        if (result.success) {
            alert('Password updated successfully!');
            closePasswordModal();
        } else {
            alert(result.message || 'Error updating password');
        }
    } catch (error) {
        hideLoading();
        alert('Error updating password');
        console.error(error);
    }

    return false;
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target == verificationModal) {
        closeVerificationModal();
    }
}
