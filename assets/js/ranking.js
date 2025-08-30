async function loadRanking(ciclo_id) {
    const podioDiv = document.getElementById('podio');
    const errorContainer = document.getElementById('error-container');
    const dataUrl = `ranking_reto.php?ciclo_id=${ciclo_id}&t=${Date.now()}`;

    try {
        console.log('Intentando cargar ranking desde:', dataUrl);
        const response = await fetch(dataUrl);
        if (!response.ok) {
            const text = await response.text();
            console.error('Respuesta no válida de ranking_reto.php:', text);
            throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
        }

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Contenido no es JSON:', text);
            throw new Error('La respuesta del servidor no es JSON');
        }

        const results = await response.json();
        console.log('Datos recibidos de ranking_reto.php:', results);

        if (results.error) {
            errorContainer.textContent = `Error del servidor: ${results.message}`;
            errorContainer.classList.remove('hidden');
            podioDiv.innerHTML = '';
            return;
        }

        if (!results.length) {
            errorContainer.textContent = 'No hay datos disponibles para mostrar el ranking.';
            errorContainer.classList.remove('hidden');
            podioDiv.innerHTML = '';
            return;
        }

        // Map results to podium positions
        const puestos = [
            { class: 'primero', number: 1, icon: '🥇' },
            { class: 'segundo', number: 2, icon: '🥈' },
            { class: 'tercero', number: 3, icon: '🥉' }
        ];

        let html = '';
        puestos.forEach(puesto => {
            const rank = results.find(r => Number(r.puesto) === puesto.number) || null;
            if (rank && typeof rank.nombre === 'string' && typeof rank.apellido_paterno === 'string') {
                const promedioAvance = parseFloat(rank.promedio_avance);
                const formattedPromedio = isNaN(promedioAvance) ? '0.0' : promedioAvance.toFixed(1);
                html += `
                    <div class="puesto-card ${puesto.class}">
                        <div class="card-content">
                            <div class="rank-badge">${puesto.number}</div>
                            <span class="card-icon">${puesto.icon}</span>
                            <div class="nombre">
                                ${rank.nombre}<br>
                                <span class="apellido">${rank.apellido_paterno}</span>
                            </div>
                            <div class="numero-container">
                                <div class="caja-numero">
                                    ${puesto.number}
                                </div>
                            </div>
                            <div class="valor">
                                ${formattedPromedio}
                            </div>
                        </div>
                    </div>
                `;
            } else {
                html += `
                    <div class="puesto-card ${puesto.class} sin-datos">
                        <div class="card-content">
                            <div class="rank-badge">${puesto.number}</div>
                            <span class="card-icon">❓</span>
                            <div class="nombre">SIN DATOS</div>
                            <div class="numero-container">
                                <div class="caja-numero">
                                    ${puesto.number}
                                </div>
                            </div>
                            <div class="valor">0.0</div>
                        </div>
                    </div>
                `;
            }
        });

        podioDiv.innerHTML = html;
        errorContainer.classList.add('hidden');
    } catch (error) {
        console.error('Error al cargar ranking:', error);
        errorContainer.textContent = `Error al cargar ranking: ${error.message}. Revisa la consola para más detalles.`;
        errorContainer.classList.remove('hidden');
        podioDiv.innerHTML = '';
    }
}