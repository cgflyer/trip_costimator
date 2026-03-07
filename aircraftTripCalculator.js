function computeAircraftResult(ac, inputs) {
    const {
        distance_nm,
        pax_weight,
        expected_fuel_cost,
        round_trip
    } = inputs;

    const cruise = ac.cruise;
    const costHr = ac.cost_hr;
    const gph = ac.fuel_burn;
    const useful = ac.useful_load;
    const maxFuelGal = ac.max_fuel_gal;

    // 1. Flight time (one way)
    const timeOneWay = distance_nm / cruise;

    // 2. Max usable fuel (gal)
    const maxFuelByWeight = (useful - pax_weight) / 6;
    const maxUsableFuel = Math.min(maxFuelGal, maxFuelByWeight);

    // 3. Endurance (hrs)
    const endurance = maxUsableFuel / gph;

    // 4. Endurance minus 1 hr reserve
    const usableEndurance = endurance - 1;

    // 5. One-way legs (fuel stops + 1)
    const legsOneWay = usableEndurance <= 0 ? Infinity : Math.ceil(timeOneWay / usableEndurance);

    // 6. Fuel burn (one way)
    const fuelOneWay = timeOneWay * gph;

    // 7. Hobbs cost (one way)
    const costOneWay = timeOneWay * costHr;

    // 8. Fuel delta (one way)
    const deltaOneWay = fuelOneWay * (expected_fuel_cost - 5.50);

    // 9. Round-trip multiplier
    const multiplier = round_trip ? 2 : 1;

    // 10. Chargeable legs (all except last inbound leg)
    let chargeableLegs;
    if (!round_trip) {
        chargeableLegs = legsOneWay;
    } else {
        const totalLegs = legsOneWay * 2;
        chargeableLegs = totalLegs - 1;
    }

    // 11. Total delta
    const totalDelta = deltaOneWay * chargeableLegs;

    return {
        id: ac.id,
        time: timeOneWay * multiplier,
        fuel: fuelOneWay * multiplier,
        cost: costOneWay * multiplier,
        fuelStops: legsOneWay * multiplier,
        fuelDelta: totalDelta,
        maxUsableFuel,
        legsOneWay,
        chargeableLegs
    };
}

function applyEstimator(inputs) {
    const results = AIRCRAFT_DATA.map(ac => computeAircraftResult(ac, inputs));
    return results;
}

function renderResults(results, outputElement) {
    let html = `
        <table>
            <tr>
                <th>Aircraft</th>
                <th>Total Time (hrs)</th>
                <th>Total Fuel (gal)</th>
                <th>Total Cost ($)</th>
                <th>Fuel Stops</th>
                <th>Fuel Delta ($)</th>
            </tr>
    `;

    results.forEach(r => {
        html += `
            <tr>
                <td>${r.id}</td>
                <td>${r.time.toFixed(2)}</td>
                <td>${r.fuel.toFixed(1)}</td>
                <td>$${r.cost.toFixed(0)}</td>
                <td>${r.fuelStops}</td>
                <td>$${r.fuelDelta.toFixed(0)}</td>
            </tr>
        `;
    });

    html += "</table>";
    outputElement.innerHTML = html;
}