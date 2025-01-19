document.addEventListener('DOMContentLoaded', function() {
    const removeButtons = document.querySelectorAll('.remove-favorite-button');
    const removeNotification = document.getElementById('remove-notification');
    const removeNotificationMessage = document.getElementById('remove-notification-message');
    const closeRemoveNotificationButton = document.getElementById('close-remove-notification');

    removeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const auctionId = this.getAttribute('data-auction-id');

            // Optional: find the .auction-card element to remove from DOM
            const auctionCard = document.querySelector(`.auction-card[data-auction-id="${auctionId}"]`);

            fetch('../php/remove_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'auction_id=' + encodeURIComponent(auctionId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // 1. Remove the card from the DOM
                    if (auctionCard) {
                        auctionCard.remove();
                    }
                    // 2. Show popup
                    removeNotificationMessage.textContent = 'Removed from favorites!';
                    removeNotification.classList.add('show');
                    // 3. Hide after 5 seconds
                    setTimeout(() => {
                        removeNotification.classList.remove('show');
                    }, 5000);
                } else {
                    // Show error message in popup
                    removeNotificationMessage.textContent = 'Error removing from favorites.';
                    removeNotification.classList.add('show');
                    setTimeout(() => {
                        removeNotification.classList.remove('show');
                    }, 5000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                removeNotificationMessage.textContent = 'A network error occurred.';
                removeNotification.classList.add('show');
                setTimeout(() => {
                    removeNotification.classList.remove('show');
                }, 5000);
            });
        });
    });

    closeRemoveNotificationButton.addEventListener('click', function() {
        removeNotification.classList.remove('show');
    });
});
