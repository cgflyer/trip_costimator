let airportCoords = {}

fetch("airport-sample.json")
    .then(response => response.json())
    .then(data => {
        airportCoords = data;
    });

// Airport coordinate lookup (add as many as you want)
// Haversine distance in nautical miles
function distanceNM(icao1, icao2) {
    const a1 = airportCoords[icao1];
    const a2 = airportCoords[icao2];

    if (!a1 || !a2) {
        throw new Error("Unknown airport ICAO code");
    }

    const toRad = deg => deg * Math.PI / 180;

    const R = 6371000; // Earth radius in meters
    const φ1 = toRad(a1.lat);
    const φ2 = toRad(a2.lat);
    const Δφ = toRad(a2.lat - a1.lat);
    const Δλ = toRad(a2.lon - a1.lon);

    const h = Math.sin(Δφ / 2) ** 2 +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ / 2) ** 2;

    const dMeters = 2 * R * Math.asin(Math.sqrt(h));
    const dNM = dMeters / 1852; // convert meters → nautical miles

    return dNM;
}

// Example:
console.log("KTKI → KBIV:", distanceNM("KTKI", "KBIV").toFixed(1), "NM");