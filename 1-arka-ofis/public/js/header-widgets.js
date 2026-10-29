/**
 * Header araçları: canlı saat + hava durumu (Open-Meteo, anahtarsız).
 * Hava durumu pill'ine tıklayınca şehir değişir (localStorage'da saklanır).
 */
(function () {
    // ---------- Saat + tarih ----------
    const clockTime = document.getElementById('clock-time');
    const clockDate = document.getElementById('clock-date');

    if (clockTime) {
        const tick = () => {
            const now = new Date();
            clockTime.textContent = now.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' });
            if (clockDate) {
                clockDate.textContent = now.toLocaleDateString('tr-TR', { day: 'numeric', month: 'long', weekday: 'short' });
            }
        };
        tick();
        setInterval(tick, 1000);
    }

    // ---------- Hava durumu ----------
    const weatherEl = document.getElementById('weather-widget');

    if (!weatherEl) return;

    const CITIES = [
        { name: 'İstanbul', lat: 41.01, lon: 28.98 },
        { name: 'Ankara', lat: 39.93, lon: 32.86 },
        { name: 'İzmir', lat: 38.42, lon: 27.14 },
        { name: 'Bursa', lat: 40.19, lon: 29.06 },
        { name: 'Antalya', lat: 36.90, lon: 30.70 },
        { name: 'Adana', lat: 37.00, lon: 35.32 },
        { name: 'Konya', lat: 37.87, lon: 32.48 },
        { name: 'Trabzon', lat: 41.00, lon: 39.72 },
    ];

    // Open-Meteo WMO hava kodları → simge + etiket
    const CODES = {
        0: ['☀️', 'Açık'], 1: ['🌤️', 'Az bulutlu'], 2: ['⛅', 'Parçalı bulutlu'], 3: ['☁️', 'Bulutlu'],
        45: ['🌫️', 'Sisli'], 48: ['🌫️', 'Sisli'],
        51: ['🌦️', 'Çisenti'], 53: ['🌦️', 'Çisenti'], 55: ['🌦️', 'Çisenti'],
        61: ['🌧️', 'Yağmurlu'], 63: ['🌧️', 'Yağmurlu'], 65: ['🌧️', 'Şiddetli yağmur'],
        66: ['🌨️', 'Sulu kar'], 67: ['🌨️', 'Sulu kar'],
        71: ['❄️', 'Kar'], 73: ['❄️', 'Kar'], 75: ['❄️', 'Yoğun kar'], 77: ['❄️', 'Kar'],
        80: ['🌦️', 'Sağanak'], 81: ['🌦️', 'Sağanak'], 82: ['⛈️', 'Şiddetli sağanak'],
        85: ['🌨️', 'Kar sağanağı'], 86: ['🌨️', 'Kar sağanağı'],
        95: ['⛈️', 'Fırtınalı'], 96: ['⛈️', 'Fırtınalı'], 99: ['⛈️', 'Fırtınalı'],
    };

    const render = (city, temp, code) => {
        const [icon, label] = CODES[code] ?? ['🌡️', ''];
        weatherEl.innerHTML =
            '<span class="text-base leading-none">' + icon + '</span>' +
            '<span class="font-semibold text-slate-700">' + Math.round(temp) + '°</span>' +
            '<span class="text-slate-500">' + city.name + '</span>';
        weatherEl.title = label + ' · ' + city.name + ' (değiştirmek için tıklayın)';
    };

    let cityIndex = parseInt(localStorage.getItem('weather-city') || '0', 10);
    if (isNaN(cityIndex) || cityIndex < 0 || cityIndex >= CITIES.length) cityIndex = 0;

    const load = async () => {
        const city = CITIES[cityIndex];
        try {
            const res = await fetch(
                'https://api.open-meteo.com/v1/forecast?latitude=' + city.lat +
                '&longitude=' + city.lon + '&current=temperature_2m,weather_code',
                { signal: AbortSignal.timeout(8000) }
            );
            const data = await res.json();
            if (data?.current) {
                render(city, data.current.temperature_2m, data.current.weather_code);
            }
        } catch (e) {
            // Çevrimdışı / erişilemedi: pill'i sessizce gizle
            weatherEl.style.display = 'none';
        }
    };

    weatherEl.addEventListener('click', () => {
        cityIndex = (cityIndex + 1) % CITIES.length;
        localStorage.setItem('weather-city', String(cityIndex));
        weatherEl.innerHTML = '<span class="text-slate-400">…</span>';
        load();
    });

    load();
    // 30 dakikada bir tazele
    setInterval(load, 30 * 60 * 1000);
})();
