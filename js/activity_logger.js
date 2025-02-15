function logDocumentViewActivity(documentTitle, documentId) {
    const username = document.querySelector('meta[name="username"]').content;
    const userActivity = `User viewed document: ${documentTitle} (ID: ${documentId})`;
    logActivity(userActivity, documentTitle, documentId, username);
}

function logActivity(userActivity, documentTitle, documentId, username) {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "system_log/log_activity.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            console.log('Activity logged successfully.');
        }
    };
    xhr.send("activity=" + encodeURIComponent(userActivity) +
        "&document_title=" + encodeURIComponent(documentTitle) +
        "&document_id=" + encodeURIComponent(documentId) +
        "&username=" + encodeURIComponent(username));
}
