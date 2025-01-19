function toggleEditMode() {
    const editForm = document.getElementById('editForm');
    const profileContent = document.querySelector('.profile-content');
    
    if (editForm.style.display === 'none' || !editForm.style.display) {
        editForm.style.display = 'grid';
        profileContent.style.display = 'none';
    } else {
        editForm.style.display = 'none';
        profileContent.style.display = 'grid';
    }
}

function toggleUploadForm() {
    const uploadForm = document.getElementById('profilePicForm');
    if (uploadForm.style.display === 'none' || !uploadForm.style.display) {
        uploadForm.style.display = 'flex';
    } else {
        uploadForm.style.display = 'none';
    }
}

// Hide the upload form initially
document.addEventListener('DOMContentLoaded', function() {
    const uploadForm = document.getElementById('profilePicForm');
    if (uploadForm) {
        uploadForm.style.display = 'none';
    }
});