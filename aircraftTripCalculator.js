function computeAircraftResult(ac, inputs, calculationFactors, 
    minimumHoursCharge) {
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

    const cruise = ac.currentProfile.tasKts;
    const costHr = ac.cost_hr;
    const gph = ac.currentProfile.fuelFlow;
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
        cost: Math.max(costOneWay * multiplier, costHr * minimumHoursCharge),
        fuelStops: (legsOneWay - 1) * multiplier,
        fuelDelta: totalDelta,
        currentProfile: ac.currentProfile,
        maxUsableFuel,
        legsOneWay,
        refuelingStops
    };
}

function applyEstimator(inputs, aircraft_data, calculationFactors) {
    let minimumHoursCharge = 0;
    if (inputs.daily_minimums) {
        minimumHoursCharge = inputs.daily_minimums.reduce((sum, day) => sum + day.hours, 0);
    }      
    const results = aircraft_data.map(ac => computeAircraftResult(ac, inputs, 
        calculationFactors, minimumHoursCharge));
    return results;
}

function renderResults(results, outputElement, tableId) {
    let html = `
        <table id="${tableId}">
            <thead>
            <tr>
                <th>Aircraft</th>
                <th>Total Travel Time (hrs)</th>
                <th>Total Flying Time (hrs)</th>
                <th>Total Fuel (gal)</th>
                <th>Total Cost ($)</th>
                <th>Fuel Stops</th>
                <th>Extra Fueling Cost / Savings ($)</th>
            </tr>
            </thead>
            <tbody>
    `;
    results.forEach(r => {
        const profile = r.currentProfile;
        const tooltip = 
            `${profile.name}\n` +
            `TAS: ${profile.tasKts} kts\n` +
            `RPM: ${profile.rpmSetting}\n` +
            `Power: ${profile.brakeHorsepowerPercent}%`;

        html += `
            <tr>
                <td title="${tooltip}">${r.id}</td>
                <td class="trip-time">${r.tripTime.toFixed(1)}</td>
                <td>${r.time.toFixed(1)}</td>
                <td>${r.fuel.toFixed(1)}</td>
                <td class="rental-cost">$${r.cost.toFixed(0)}</td>
                <td>${r.fuelStops}</td>
                <td>$${r.fuelDelta.toFixed(0)}</td>
            </tr>
        `;
    });

    html += "</tbody></table>";
    outputElement.innerHTML = html;
}

function normalize(value, min, max) {
    return (value - min) / (max - min);
}

function redYellowGreen(t) {
    // t = 0 → red, 0.5 → yellow, 1 → green
    let r, g, b = 64;

    if (t <= 0.5) {
        // red → yellow
        r = 255;
        g = Math.round((510 - 128) * t + 64);   // 0 → 255
    } else {
        // yellow → green
        g = 255;
        r = Math.round((510 - 128) * (1 - t) + 64); // 255 → 0
    }

    return `rgb(${r}, ${g}, ${b})`;
}
function softRedYellowGreen(t) {
    // ease the curve slightly
    t = Math.pow(t, 0.7);

    const softRed    = [245,  90,  90];
    const softYellow = [245, 220, 120];
    const softGreen  = [ 90, 190,  90];


    let r, g, b;

    if (t <= 0.5) {
        // red → yellow
        const k = t / 0.5;
        r = softRed[0]   + (softYellow[0] - softRed[0]) * k;
        g = softRed[1]   + (softYellow[1] - softRed[1]) * k;
        b = softRed[2]   + (softYellow[2] - softRed[2]) * k;
    } else {
        // yellow → green
        const k = (t - 0.5) / 0.5;
        r = softYellow[0] + (softGreen[0] - softYellow[0]) * k;
        g = softYellow[1] + (softGreen[1] - softYellow[1]) * k;
        b = softYellow[2] + (softGreen[2] - softYellow[2]) * k;
    }

    return `rgb(${Math.round(r)}, ${Math.round(g)}, ${Math.round(b)})`;
}
function applyColorMap(tableId) {
    const rows = [...document.querySelectorAll(`#${tableId} tbody tr`)];

    const times = rows.map(r => parseFloat(r.querySelector(".trip-time").textContent));
    const costs = rows.map(r => {
        const cell = r.querySelector(".rental-cost");
        return parseFloat(cell.textContent.replace("$", ""));
    });
    const minTime = Math.min(...times);
    const maxTime = Math.max(...times);
    const minCost = Math.min(...costs);
    const maxCost = Math.max(...costs);

    rows.forEach((row, i) => {
        const timeCell = row.querySelector(".trip-time");
        const costCell = row.querySelector(".rental-cost");

        const tNorm = 1 - normalize(times[i], minTime, maxTime);
        const cNorm = 1 - normalize(costs[i], minCost, maxCost);

        timeCell.style.backgroundColor = softRedYellowGreen(tNorm);
        costCell.style.backgroundColor = softRedYellowGreen(cNorm);
    });
}

// Example function to compute daily minimums based on reservation dates
function computeDailyMinimums(startDate, endDate) {
    const start = new Date(startDate);
    const end = new Date(endDate);

    const days = [];
    let d = new Date(start);

    while (d <= end) {
        const dayOfWeek = d.getDay(); // 0=Sun, 1=Mon, ... 6=Sat
        const month = d.getMonth();   // 0=Jan, 4=May, 7=Aug
        const day = d.getDate();

        // Weekend = Fri (5), Sat (6), Sun (0)
        const isWeekend = (dayOfWeek === 5 || dayOfWeek === 6 || dayOfWeek === 0);

        // Original flexible summer definition: May 1 – Aug 31
        const isSummer =
            (month > 4 && month < 7) ||
            (month === 4 && day >= 1) ||
            (month === 7 && day <= 31);

        let hours;
        if (isWeekend) {
            hours = 2.0;
        } else {
            hours = isSummer ? 1.5 : 1.0;
        }

        days.push({
            date: new Date(d),
            hours
        });

        d.setDate(d.getDate() + 1);
    }

    return days;
}