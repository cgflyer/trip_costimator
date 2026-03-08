function computeAircraftResult(ac, inputs, calculationFactors) {
    const {
        distance_nm,
        pax_weight,
        expected_fuel_cost,
        round_trip
    } = inputs;
    const {
        fuel_reserve_hours, 
        refuel_stop_time,
        reimbursement_fuel_cost
    } = calculationFactors;

    const cruise = ac.cruise;
    const costHr = ac.cost_hr;
    const gph = ac.fuel_burn;
    const useful = ac.useful_load;
    const maxFuelGal = ac.max_fuel_gal;
    const startupFuelGal = ac.startup_fuel_gal;

    // 1. Flight time (one way)
    const flightTimeOneWay = distance_nm / cruise;

    // 2. Max usable fuel (gal)
    const maxFuelByWeight = (useful - pax_weight) / 6;
    const maxUsableFuel = Math.min(maxFuelGal, maxFuelByWeight);

    // 3. Endurance (hrs)
    const endurance = maxUsableFuel / gph;

    // 4. Endurance minus 1 hr reserve
    const usableEndurance = endurance - fuel_reserve_hours;

    // 5. One-way legs (fuel stops + 1)
    const legsOneWay = usableEndurance <= 0 ? Infinity : Math.ceil(flightTimeOneWay / usableEndurance);

    // 6. Fuel burn (one way)
    const fuelOneWay = flightTimeOneWay * gph + legsOneWay * startupFuelGal;

    // 7. Rental cost (one way)
    const costOneWay = flightTimeOneWay * costHr;

    // 8. Trip time with refueling stops (one way)
    const tripTimeOneWay = flightTimeOneWay + (legsOneWay - 1) * refuel_stop_time;

    // 8. Fuel delta (one way)
    const deltaOneWay = fuelOneWay * (expected_fuel_cost - reimbursement_fuel_cost);

    // 9. Round-trip multiplier
    const multiplier = round_trip ? 2 : 1;

    // 10. Chargeable legs (all except last inbound leg)
    let refuelingStops = legsOneWay;
    let totalLegs = legsOneWay;
    if (round_trip) {
        totalLegs = legsOneWay * 2;
        refuelingStops = totalLegs - 1;
    }

    // 11. Total delta
    const totalDelta = deltaOneWay * refuelingStops / totalLegs;

    return {
        id: ac.id,
        time: flightTimeOneWay * multiplier,
        tripTime: tripTimeOneWay * multiplier,
        fuel: fuelOneWay * multiplier,
        cost: costOneWay * multiplier,
        fuelStops: (legsOneWay - 1) * multiplier,
        fuelDelta: totalDelta,
        maxUsableFuel,
        legsOneWay,
        refuelingStops
    };
}

function applyEstimator(inputs, aircraft_data, calculationFactors) {
    const results = aircraft_data.map(ac => computeAircraftResult(ac, inputs, calculationFactors));
    return results;
}

function renderResults(results, outputElement) {
    let html = `
        <table>
            <tr>
                <th>Aircraft</th>
                <th>Total Travel Time (hrs)</th>
                <th>Total Flying Time (hrs)</th>
                <th>Total Fuel (gal)</th>
                <th>Total Cost ($)</th>
                <th>Fuel Stops</th>
                <th>Extra Fueling Cost / Savings ($)</th>
            </tr>
    `;

    results.forEach(r => {
        html += `
            <tr>
                <td>${r.id}</td>
                <td>${r.tripTime.toFixed(1)}</td>
                <td>${r.time.toFixed(1)}</td>
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