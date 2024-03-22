document.addEventListener('DOMContentLoaded', function() {
    const flashMessages = document.querySelectorAll('.flash-message');

    flashMessages.forEach(function(msg) {
        setTimeout(function() {
            let fadeEffect = setInterval(function () {
                if (!msg.style.opacity) {
                    msg.style.opacity = 1;
                }
                if (msg.style.opacity > 0) {
                    msg.style.opacity -= 0.1;
                } else {
                    clearInterval(fadeEffect);
                    msg.style.display = 'none';
                }
            }, 50);
        }, 5000);
    });
});
