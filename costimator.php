<?php
// -------------------------------
// Static aircraft data
// -------------------------------
$aircraft = [
    [
        "id" => "N12345",
        "cruise" => 110,
        "cost_hr" => 145,
        "fuel_burn" => 9.5,
        "useful_load" => 850,
        "max_fuel_gal" => 50
    ],
    [
        "id" => "N54321",
        "cruise" => 125,
        "cost_hr" => 165,
        "fuel_burn" => 10.2,
        "useful_load" => 900,
        "max_fuel_gal" => 56
    ],
    // Add all 7 aircraft here...
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
    </style>
</head>

<body>

<h2>Aircraft Trip Cost Estimator</h2>

<!-- -------------------------------
     User Input Form
-------------------------------- -->
<div class="form-section">
    <label>Trip Distance (NM):<br>
        <input id="distance" type="number" min="1">
    </label><br>

    <label>Total Passenger + Baggage Weight (lbs):<br>
        <input id="pax" type="number" min="0">
    </label><br>

    <label>Expected Fuel Cost ($/gal):<br>
        <input id="fuelcost" type="number" step="0.01" min="0">
    </label><br>

    <label>
        Round Trip?
        <input id="roundtrip" type="checkbox">
    </label><br><br>

    <button onclick="runEstimator()">Compute</button>
</div>

<!-- Results Table -->
<div id="results"></div>

<!-- -------------------------------
     Pass PHP data to JavaScript
-------------------------------- -->
<script>
    const AIRCRAFT_DATA = <?php echo json_encode($aircraft); ?>;


</script>

<!-- Load calculation script -->
<script src="aircraftTripCalculator.js"></script>
<script>
    function runEstimator() {
        const inputs = {
            distance_nm: Number(document.getElementById("distance").value),
            pax_weight: Number(document.getElementById("pax").value),
            expected_fuel_cost: Number(document.getElementById("fuelcost").value),
            round_trip: document.getElementById("roundtrip").checked
        };

    trip_cost_estimates = applyEstimator(inputs, aircraft_data = AIRCRAFT_DATA);

    renderResults(trip_cost_estimates, document.getElementById("results"));
}
</script>

</body>
</html>