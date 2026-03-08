<?php
// -------------------------------
// Static aircraft data
// -------------------------------
$conversions = [
    "nm_to_miles" => 1.15078,
    "gallon_to_lbs" => 6.0
];
$hidden_factors = [
    "reserve_fuel_hours" => 1,
    "refueling_stop_time" => 0.5,
    "reimbursement_fuel_cost" => 5.50
];
$aircraft = [

[
    // performance from POH 175mph 87pph 70% power 24/24 5000ft
    // performance from POH 179mph 85pph 68% power 23/24 7500ft
    "id" => "N9497M",
    "cruise" => 179 / $conversions["nm_to_miles"],
    "cost_hr" => 285,
    "fuel_burn" => 87 / $conversions["gallon_to_lbs"],
    "useful_load" => 1442,
    "max_fuel_gal" => 89,
    "startup_fuel_gal" => 12 / $conversions["gallon_to_lbs"]
],
[
    "id" => "N3QZ",
    "cruise" => 139,
    "cost_hr" => 190,
    "fuel_burn" => 13.2,
    "useful_load" => 1210,
    "max_fuel_gal" => 78,
    "startup_fuel_gal" => 1.5
],
[
    "id" => "N6833C",
    "cruise" => 127 / $conversions["nm_to_miles"],
    "cost_hr" => 135,
    "fuel_burn" => 9,
    "useful_load" => 1015,
    "max_fuel_gal" => 48,
    "startup_fuel_gal" => 1.4
],
[
    "id" => "N4135W",
    "cruise" => 118,
    "cost_hr" => 162,
    "fuel_burn" => 9.5,
    "useful_load" => 734,
    "max_fuel_gal" => 48,
    "startup_fuel_gal" => 1.4
],
[
    "id" => "N733NB",
    "cruise" => 110,
    "cost_hr" => 159,
    "fuel_burn" => 9,
    "useful_load" => 1067,
    "max_fuel_gal" => 40,
    "startup_fuel_gal" => 1.4
],
[
    "id" => "N737TY",
    "cruise" => 110,
    "cost_hr" => 159,
    "fuel_burn" => 9,
    "useful_load" => 1010,
    "max_fuel_gal" => 40,
    "startup_fuel_gal" => 1.4
],
[
    "id" => "N121DB",
    "cruise" => 150 / $conversions["nm_to_miles"],
    "cost_hr" => 175,
    "fuel_burn" => 10,
    "useful_load" => 1038,
    "max_fuel_gal" => 48,
    "startup_fuel_gal" => 1.4
]
];

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Aircraft Trip Cost Estimator</title>

    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; margin-top: 20px; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #eee; }
        input { margin: 5px 0; padding: 5px; }
        .form-section { margin-bottom: 20px; }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 20px;
            align-items: start;
        }

        .hidden-panel {
            display: none;
            border: 1px solid #ccc;
            padding: 12px;
            background: #f9f9f9;
            border-radius: 6px;
        }
    </style>
</head>

<body>

<h2>Aircraft Trip Cost Estimator</h2>

    <p> The following calculator is provided as a convenience for quickly
        comparing the rough costs and times of hypothetical trips. The calculations
        presented in this tool are not to be used for flight planning. 
        
        The calculations in the spreadsheet provide estimates of fuel stops, flight
        time, and rental cost for the plane in cruise flight assuming no winds.

        Calculations include a 1 hour reserve, time on the ground for refueling, and
        allow you to calculate the extra fuel cost (or savings) if you need to refuel.

        The numbers here are simply estimates of the approximate costs and time you
        can expect to
        spend using the various planes in our fleet. Once you have chosen the plane
        for your trip, refer to the POH for actual flight planning details for
        the route, weight and balance, and specific conditions of your trip.
    </p>



<!-- -------------------------------
     User Input Form
-------------------------------- -->
<div>
    <label>
        <input type="checkbox" id="toggleHidden">
        Show advanced factors
    </label>
</div>

<div class="form-grid">

    <!-- LEFT COLUMN: Main form -->
    <div>
        <h3>Trip Inputs</h3>

        <label>Trip Distance (nm)
            <input type="number" id="distance">
        </label><br>

        <label>Passenger and Bag Weight (lbs)
            <input type="number" id="paxWeight">
        </label><br>

        <label>Expected Refuel Price ($/gal)
            <input type="number" id="fuelPrice" step="0.01">
        </label><br>

        <label>
            Round Trip?
            <input id="roundtrip" type="checkbox">
        </label><br><br>

        <button onclick="runEstimator()">Compute</button>

    </div>

    <!-- RIGHT COLUMN: Hidden factors -->
    <div id="hiddenPanel" class="hidden-panel">
        <h3>Advanced Factors</h3>

        <label>Fuel Reserve (hours)
            <input type="number" id="fuelReserve" step="0.1">
        </label><br>

        <label>Refuel Stop Time (minutes)
            <input type="number" id="refuelTime" step="0.1">
        </label><br>

        <label>Reimbursement Fuel Cost ($/gal)
            <input type="number" id="reimbursementFuelCost" step="0.01">
        </label><br>
    </div>

</div>

<!-- Results Table -->
<div id="results"></div>

<!-- -------------------------------
     Pass PHP data to JavaScript
-------------------------------- -->
<script>
    const AIRCRAFT_DATA = <?php echo json_encode($aircraft); ?>;
    const HIDDEN_FACTORS = <?php echo json_encode($hidden_factors); ?>;


</script>
<script>
// Populate hidden fields from PHP → JS object
window.addEventListener("DOMContentLoaded", () => {
    document.getElementById("distance").value = 250; // default distance
    document.getElementById("paxWeight").value = 170 * 2 + 30; // default 2 passengers
    document.getElementById("fuelPrice").value = 5.50; // default fuel price

    document.getElementById("fuelReserve").value =
        HIDDEN_FACTORS.reserve_fuel_hours;

    document.getElementById("refuelTime").value =
        HIDDEN_FACTORS.refueling_stop_time * 60; // convert hours to minutes

    document.getElementById("reimbursementFuelCost").value =
        HIDDEN_FACTORS.reimbursement_fuel_cost;
});

// Toggle hidden panel
document.getElementById("toggleHidden").addEventListener("change", function () {
    const panel = document.getElementById("hiddenPanel");
    panel.style.display = this.checked ? "block" : "none";
});
</script>
<!-- Load calculation script -->
<script src="aircraftTripCalculator.js"></script>
<script>
    function runEstimator() {
        const inputs = {
            distance_nm: Number(document.getElementById("distance").value),
            pax_weight: Number(document.getElementById("paxWeight").value),
            expected_fuel_cost: Number(document.getElementById("fuelPrice").value),
            round_trip: document.getElementById("roundtrip").checked
        };
        const calculationFactors = {
            fuel_reserve_hours: Number(document.getElementById("fuelReserve").value),
            refuel_stop_time: Number(document.getElementById("refuelTime").value) / 60, // convert minutes to hours
            reimbursement_fuel_cost: Number(document.getElementById("reimbursementFuelCost").value)
        };

    trip_cost_estimates = applyEstimator(inputs, 
        AIRCRAFT_DATA,
        calculationFactors);

    renderResults(trip_cost_estimates, 
        document.getElementById("results"), 
        "resultsTable");
    applyColorMap("resultsTable");
}
</script>

</body>
</html>