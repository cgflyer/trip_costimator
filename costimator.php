<?php

//error_reporting(E_ALL);
//ini_set('display_errors', 1);
// -------------------------------
// Static aircraft data
// -------------------------------
$conversions = [
    "nm_to_miles" => 1.15078,
    "gallon_to_lbs" => 6.0
];
$hidden_factors = [
    "reserve_fuel_hours" => 1,
    "refueling_stop_time" => 0.9,
    "reimbursement_fuel_cost" => 6.25,
    "ground_time_min" => 12, // typical time spent taxiing and runup at low rpm
    "ground_tach_factor" => 0.50, // tach runs at ~50% at 1200 RPM
    "climb_time_min" => 10, // time spent climbing
    "climb_speed_factor" => 0.80, // climb is typically 20% slower than cruise
    "climb_tach_factor" => 1.10 // tach runs ~10% faster at full power
    "approach_time_min" => 8, // time spent in approach and landing
    "approach_tach_factor" => 0.70 // tach runs ~30% slower at approach power
    "approach_speed_factor" => 0.75 // approach is typically 25% slower than cruise
];
$aircraft = [

"N9497M" => [
    // performance from POH 175mph 87pph 70% power 24/24 5000ft
    // performance from POH 179mph 85pph 68% power 23/24 7500ft
    "id" => "N9497M",
    "cruise" => 179 / $conversions["nm_to_miles"],
    "cost_hr" => 285,
    "fuel_burn" => 87 / $conversions["gallon_to_lbs"],
    "useful_load" => 1442,
    "max_fuel_gal" => 89,
    "startup_fuel_gal" => 12 / $conversions["gallon_to_lbs"],
    "hide" => false
],
"N3QZ" => [
    "id" => "N3QZ",
    "cruise" => 139,
    "cost_hr" => 200,
    "fuel_burn" => 13.2,
    "useful_load" => 1210,
    "max_fuel_gal" => 78,
    "startup_fuel_gal" => 1.5,
    "hide" => false
],
"N6833C" => [
    "id" => "N6833C",
    "cruise" => 127 / $conversions["nm_to_miles"],
    "cost_hr" => 142,
    "fuel_burn" => 9,
    "useful_load" => 1015,
    "max_fuel_gal" => 48,
    "startup_fuel_gal" => 1.4,
    "hide" => false
],
"N4135W" => [
    "id" => "N4135W",
    "cruise" => 118,
    "cost_hr" => 172,
    "fuel_burn" => 9.5,
    "useful_load" => 734,
    "max_fuel_gal" => 48,
    "startup_fuel_gal" => 1.4,
    "hide" => false
],
"N733NB" => [
    "id" => "N733NB",
    "cruise" => 110,
    "cost_hr" => 169,
    "fuel_burn" => 9,
    "useful_load" => 1067,
    "max_fuel_gal" => 40,
    "startup_fuel_gal" => 1.4,
    "hide" => true
],
"N737TY" => [
    "id" => "N737TY",
    "cruise" => 110,
    "cost_hr" => 169,
    "fuel_burn" => 9,
    "useful_load" => 1010,
    "max_fuel_gal" => 40,
    "startup_fuel_gal" => 1.4,
    "hide" => true
],
"N121DB" => [
    "id" => "N121DB",
    "cruise" => 150 / $conversions["nm_to_miles"],
    "cost_hr" => 185,
    "fuel_burn" => 10,
    "useful_load" => 1038,
    "max_fuel_gal" => 48,
    "startup_fuel_gal" => 1.4,
    "hide" => false
  ]
];
function loadPerformanceProfiles($csvPath) {
    $profilesByTail = [];

    if (($handle = fopen($csvPath, "r")) !== false) {
        $header = fgetcsv($handle); // read header row

        while (($row = fgetcsv($handle)) !== false) {
            $entry = array_combine($header, $row);
            $tail  = $entry['plane'];

            if (!isset($profilesByTail[$tail])) {
                $profilesByTail[$tail] = [];
            }

            $profilesByTail[$tail][] = [
                'name'                     => $entry['profile'],
                'altitude'                 => (int)$entry['altitude'],
                'brakeHorsepowerPercent'   => (float)$entry['brake_horsepower_percent'],
                'manifoldPressure'         => $entry['manifold_pressure'],
                'rpmSetting'               => (int)$entry['rpm_setting'],
                'fuelFlow'                 => (float)$entry['fuel_flow'],
                'tasMph'                   => (float)$entry['true_air_speed_mph'],
                'tasKts'                   => (float)$entry['true_air_speed_kts'],
                'isDefault'                => ($entry['default'] === "y")
            ];
        }

        fclose($handle);
    }

    return $profilesByTail;
}
function attachProfilesToAircraft(&$aircraft, $profilesByTail) {
    foreach ($aircraft as $tail => &$plane) {
        $profiles = $profilesByTail[$tail] ?? [];

        $plane['profiles'] = $profiles;

        // pick default or first profile
        $default = null;
        foreach ($profiles as $p) {
            if ($p['isDefault']) {
                $default = $p;
                break;
            }
        }
        if ($default === null && count($profiles) > 0) {
            $default = $profiles[0];
        }

        $plane['currentProfile'] = $default;
    }
}
// open the aircraft profile data from csv file
$profileData = loadPerformanceProfiles("aircraft-performance-profiles.csv");
attachProfilesToAircraft($aircraft, $profileData);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Aircraft Trip Cost Estimator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
        .error-message {
            color: #b00020;          /* deep red, readable but not harsh */
            font-size: 0.9rem;
            margin-top: 6px;
            font-weight: 500;
        }
        .info-icon {
            margin-left: 6px;
            cursor: pointer;
            font-size: 1.1em;
        }

        .profile-popup {
            position: fixed;
            background: rgba(0,0,0,0.85);
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.9em;
            z-index: 9999;
            max-width: 240px;
            text-align: left;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            opacity: 1;
            transition: opacity 2.5s ease-out;
        }

        .profile-popup.fade-out {
            opacity: 0;
        }
        .tail-cell {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .tail-text {
            font-weight: 600;
            margin-right: 4px;
        }

        .info-icon {
            cursor: pointer;
            font-size: 1.1em;
        }

    </style>
    <link
  href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
  rel="stylesheet"
  crossorigin="anonymous"
/>

<script
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
  crossorigin="anonymous">
</script>

</head>

<body>
<div class="container-fluid">
  <div class="row mb-4">
    <div class="col-12">
        <h2>Aircraft Trip Cost Estimator</h2>
        <div class="container-fluid mb-4">
            <div class="alert alert-primary" role="alert">
                <h5 class="mb-2">What This Tool Is Designed To Do</h5>
                <p class="mb-0">
                This estimator helps members compare the <strong>relative cost, fuel burn, and estimated flight time</strong> of our club aircraft for a <strong>hypothetical trip</strong>. It uses typical performance profiles and your inputs to provide a <strongside-by-side comparison</strong> of how each airplane might perform under normal conditions.  
                Its purpose is to support <strong>aircraft selection</strong>—helping you decide which plane is likely the most suitable or economical before you begin detailed planning.
                </p>
            </div>
        </div>
    </div>
  </div>

  <div class="row">
    <div class="col-12 col-md-4 mb-4">
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
                <!-- Multi-day reservation toggle -->
                <label>
                    <input type="checkbox" id="multiDayToggle">
                    Multi-day reservation
                </label>
                <label>
                    <input type="checkbox" id="toggleHidden">
                    Show advanced factors
                </label>

                <button id="computeButton" 
                    onclick="runEstimator()"
                    class="btn btn-lg btn-primary fw-bold px-4 py-2">
                    ComputeX
                </button>

            </div>

            <div>
                <!-- Hidden multi-day panel -->
                <div id="multiDayPanel" class="hidden-panel" style="display:none; margin-top:10px;">
                    <h3>Reservation Dates</h3>

                    <label>Start Date
                        <input type="date" id="reservationStart">
                    </label><br>

                    <label>End Date
                        <input type="date" id="reservationEnd">
                    </label><br>

                    <div id="dateError" class="error-message"></div>

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
        </div>
    </div>
    <div class="col-12 col-lg-8">
      <div class="table-responsive">
        <!-- your existing results table -->
      </div>
    </div>
  </div>
  <div class="row mb-4">
    <!-- Daily minimums table will be rendered here -->
    <div class="col-12" id="dailyMinimumContainer"></div>
  </div>
  <div class="row">
    <div class="col-12">
            <!-- Results table will be rendered here -->
        <div class="table-responsive mb-4">
            <table id="resultsTable" class="table table-striped table-sm">
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
                <tbody id="resultsBody">
                <!-- JS populates rows here -->
                </tbody>
            </table>
        </div>
    </div>
  </div>
  <div class="container-fluid mt-4">
    <div class="alert alert-warning" role="alert">
        <h5 class="mb-2">Important Limitations and Pilot Responsibilities</h5>
        <p>
        The values shown here are <strong>approximations</strong>. They do not account for real-world variables such as weather, winds aloft, aircraft loading, runway conditions, density altitude, fuel availability, or operational limitations.  
        This estimator is <strong>not a substitute</strong> for proper flight planning, performance calculations, or regulatory compliance.
        </p>
        <p class="mb-0">
        After selecting an aircraft, each pilot must complete <strong>full, accurate flight planning</strong> using current charts, weather briefings, NOTAMs, weight and balance, performance data, and all applicable regulations and club procedures.  
        Pilots remain responsible for ensuring the aircraft is suitable for the actual conditions of the flight.
        </p>
    </div>
  </div>
</div>



<!-- -------------------------------
     Pass PHP data to JavaScript
-------------------------------- -->
<script>
    <?php $aircraftArray = array_values($aircraft); ?>;
    const AIRCRAFT_DATA = <?php echo json_encode($aircraftArray); ?>;
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
document.getElementById("multiDayToggle").addEventListener("change", function () {
    const panel = document.getElementById("multiDayPanel");
    panel.style.display = this.checked ? "block" : "none";
});
</script>
<!-- Load calculation script -->
<script src="aircraftTripCalculator.js?v=<?php echo time(); ?>"></script>
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

        if (!validateInputs(inputs)) return; // Stop if validation fails
        const isMulti = document.getElementById("multiDayToggle").checked;
        let dailyMinimums = null;
        if (isMulti) {
            dailyMinimums = computeDailyMinimums(
                document.getElementById("reservationStart").value,
                document.getElementById("reservationEnd").value
            );
        }
        inputs.daily_minimums = dailyMinimums; // add to inputs for use in estimator
        trip_cost_estimates = applyEstimatorOnAllAircraft(inputs, 
            AIRCRAFT_DATA,
            calculationFactors);

        if (isMulti) {
            renderDailyMinimumTable(dailyMinimums);
        } else {
            document.getElementById("dailyMinimumContainer").innerHTML = "";
        }
        renderResults(trip_cost_estimates, 
            document.getElementById("resultsBody"));
        applyColorMap("resultsTable");
    }
    function validateInputs(inputs) {
        validationResult = true;
        validationResult &&= validateMultiDay();
        return validationResult;
    }
    function validateMultiDay() {
        const isMulti = document.getElementById("multiDayToggle").checked;
        if (!isMulti) return true; // no validation needed

        const startInput = document.getElementById("reservationStart").value;
        const endInput   = document.getElementById("reservationEnd").value;

        // Required fields
        if (!startInput || !endInput) {
            dateError.textContent = "Please enter both start and end dates for a multi-day reservation.";
            return false;
        }

        const start = new Date(startInput);
        const end   = new Date(endInput);

        // Today at midnight
        const today = new Date();
        today.setHours(0,0,0,0);

        // Start cannot be in the past
        if (start < today) {
            dateError.textContent = "Start date cannot be in the past.";
            return false;
        }

        // Start must be before end
        if (start > end) {
            dateError.textContent = "Start date must be before the end date.";
            return false;
        }
        // clear date error if validation passes
        dateError.textContent = "";
        return true;
    }
    function renderDailyMinimumTable(dailyArray) {
        const container = document.getElementById("dailyMinimumContainer");
        container.innerHTML = ""; // clear previous output

        if (!dailyArray || dailyArray.length === 0) return;

        const table = document.createElement("table");
        table.classList.add("daily-minimum-table");

        // Header row: dates
        const headerRow = document.createElement("tr");
        dailyArray.forEach(entry => {
            const th = document.createElement("th");
            th.textContent = entry.date.getDate(); // day of month
            headerRow.appendChild(th);
        });
        table.appendChild(headerRow);

        // Daily minimum row
        const minRow = document.createElement("tr");
        dailyArray.forEach(entry => {
            const td = document.createElement("td");
            td.textContent = entry.hours.toFixed(1);
            td.classList.add("daily-minimum-cell");
            minRow.appendChild(td);
        });
        table.appendChild(minRow);

        // Summary row
        const summaryRow = document.createElement("tr");
        const summaryCell = document.createElement("td");
        summaryCell.colSpan = dailyArray.length;
        summaryCell.classList.add("daily-summary-cell");

        const totalHours = dailyArray.reduce((sum, e) => sum + e.hours, 0);
        summaryCell.textContent = `Total Minimum Hours: ${totalHours.toFixed(1)}`;

        summaryRow.appendChild(summaryCell);
        table.appendChild(summaryRow);

        container.appendChild(table);
    }
</script>

</body>
</html>