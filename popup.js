document.addEventListener('DOMContentLoaded', async () => {
    const domainDisplay = document.getElementById('domainDisplay');
    const ageDisplay = document.getElementById('ageDisplay');
    const errorMessage = document.getElementById('errorMessage');

    ageDisplay.classList.add('loading'); // Add loading animation

    // Get the current tab's URL
    chrome.tabs.query({ active: true, currentWindow: true }, async (tabs) => {
        const url = new URL(tabs[0].url);
        const domain = url.hostname;

        domainDisplay.textContent = domain;

        try {
            // Base64 encoded URL
            const encodedUrl = "apikeyhere";
            const decodedUrl = atob(encodedUrl);
            const response = await fetch(`${decodedUrl}?domain=${encodeURIComponent(domain)}`);
            const data = await response.json();
            
            if (data.error) {
                ageDisplay.textContent = "Unavailable";
                showError(data.error);
            } else {
                ageDisplay.textContent = data.age;
            }
        } catch (error) {
            ageDisplay.textContent = "Error";
            showError("An error occurred while checking the domain age.");
        } finally {
            ageDisplay.classList.remove('loading'); // Remove loading animation
        }
    });

    function showError(message) {
        errorMessage.textContent = message;
        errorMessage.classList.remove('hidden');
    }
});
