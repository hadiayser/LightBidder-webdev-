/* /JS/profile.js */

/**
 * Toggles the display of the Edit Profile form and the Profile Content.
 */
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

/**
 * Toggles the display of the Profile Picture Upload form.
 */
function toggleUploadForm() {
    const uploadForm = document.getElementById('profilePicForm');
    const editOverlay = document.querySelector('.edit-overlay');

    if (uploadForm.style.display === 'none' || uploadForm.style.display === '') {
        uploadForm.style.display = 'block';
        uploadForm.classList.add('show');
        editOverlay.style.pointerEvents = 'none'; // Disable overlay interactions
        document.getElementById('profile_picture').focus(); // Set focus to the file input
    } else {
        uploadForm.style.display = 'none';
        uploadForm.classList.remove('show');
        editOverlay.style.pointerEvents = 'auto'; // Re-enable overlay interactions
    }
}

// Close the upload form when clicking outside of it
document.addEventListener('click', function(event) {
    const uploadForm = document.getElementById('profilePicForm');
    const profilePicContainer = document.querySelector('.profile-picture-container');
    const editOverlay = document.querySelector('.edit-overlay');

    if (uploadForm.style.display === 'block' && !profilePicContainer.contains(event.target)) {
        uploadForm.style.display = 'none';
        uploadForm.classList.remove('show');
        editOverlay.style.pointerEvents = 'auto';
    }
});

/**
 * Preview the selected image before uploading.
 */
function previewImage(event) {
    const preview = document.getElementById('preview');
    const file = event.target.files[0];

    if (file) {
        const reader = new FileReader();

        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }

        reader.readAsDataURL(file);
    } else {
        preview.src = '#';
        preview.style.display = 'none';
    }
}
