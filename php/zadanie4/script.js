document.getElementById('geocodeForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const address = document.getElementById('address').value;
    const resultDiv = document.getElementById('result');
    const errorDiv = document.getElementById('error');

    resultDiv.classList.add('hidden');
    errorDiv.classList.add('hidden');
    errorDiv.textContent = '';

    try {
        const response = await fetch('/geocode.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ address }),
        });

        const data = await response.json();

        if (response.ok) {
            document.getElementById('fullAddress').textContent = data.structuredAddress.fullAddress;

            const componentsList = document.getElementById('components');
            componentsList.innerHTML = '';
            for (const [kind, name] of Object.entries(data.structuredAddress.components)) {
                const li = document.createElement('li');
                li.textContent = `${kind}: ${name}`;
                componentsList.appendChild(li);
            }

            document.getElementById('coordinates').textContent = `Широта: ${data.coordinates.latitude}, Долгота: ${data.coordinates.longitude}`;
            document.getElementById('metro').textContent = data.nearestMetro;

            resultDiv.classList.remove('hidden');
        } else {
            errorDiv.textContent = data.error || 'Неизвестная ошибка';
            errorDiv.classList.remove('hidden');
        }
    } catch (err) {
        errorDiv.textContent = 'Ошибка соединения: ' + err.message;
        errorDiv.classList.remove('hidden');
    }
});