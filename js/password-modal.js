const modal = document.getElementById('passwordModal');
const closeBtn = document.getElementsByClassName('close')[0];

function openPasswordModal() {
    modal.style.display = "block";
    document.getElementById('passwordChangeForm').reset();
}

function closePasswordModal() {
    modal.style.display = "none";
}

closeBtn.onclick = closePasswordModal;

window.onclick = function(event) {
    if (event.target == modal) {
        closePasswordModal();
    }
}

async function handlePasswordChange(event) {
    event.preventDefault();
    
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    if (newPassword !== confirmPassword) {
        alert('New passwords do not match!');
        return false;
    }

    try {
        const response = await fetch('ajax/update_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `currentPassword=${encodeURIComponent(currentPassword)}&newPassword=${encodeURIComponent(newPassword)}`
        });

        const result = await response.json();
        
        if (result.success) {
            alert('Password updated successfully!');
            closePasswordModal();
        } else {
            alert(result.message || 'Error updating password');
        }
    } catch (error) {
        alert('Error updating password');
        console.error(error);
    }

    return false;
}
