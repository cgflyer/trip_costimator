<?php
// -------------------------------
// Static aircraft data
// -------------------------------
$aircraft = [

[
    "id" => "N9497M",
    "cruise" => 160.765,
    "cost_hr" => 285,
    "fuel_burn" => 18,
    "useful_load" => 1442,
    "max_fuel_gal" => 89
],
[
    "id" => "N3QZ",
    "cruise" => 139,
    "cost_hr" => 190,
    "fuel_burn" => 13.2,
    "useful_load" => 1210,
    "max_fuel_gal" => 78
],
[
    "id" => "N6833C",
    "cruise" => 116,
    "cost_hr" => 135,
    "fuel_burn" => 9,
    "useful_load" => 1015,
    "max_fuel_gal" => 48
],
[
    "id" => "N4135W",
    "cruise" => 116,
    "cost_hr" => 162,
    "fuel_burn" => 9,
    "useful_load" => 734,
    "max_fuel_gal" => 48
],
[
    "id" => "N733NB",
    "cruise" => 112,
    "cost_hr" => 159,
    "fuel_burn" => 9,
    "useful_load" => 1067,
    "max_fuel_gal" => 40
],
[
    "id" => "N737TY",
    "cruise" => 112,
    "cost_hr" => 159,
    "fuel_burn" => 9,
    "useful_load" => 1010,
    "max_fuel_gal" => 40
],
[
    "id" => "N121DB",
    "cruise" => 134.695,
    "cost_hr" => 175,
    "fuel_burn" => 9.5,
    "useful_load" => 1038,
    "max_fuel_gal" => 48
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