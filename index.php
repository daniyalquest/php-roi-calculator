<?php
// Handle input cleanup for calculation
function sanitize_number($str) {
  return floatval(str_replace(',', '', $str));
}

// Initialize defaults
$years = min(intval($_POST['years'] ?? 10), 100);  // cap at 100
$rateInput = sanitize_number($_POST['rate'] ?? 7);
$rateInput = min($rateInput, 100); // Enforce 100% max
$rate = $rateInput / 100;
$initial = sanitize_number($_POST['initial'] ?? 10000);
$contribution = sanitize_number($_POST['contribution'] ?? 1000);
$frequency = intval($_POST['frequency'] ?? 12); // compounding & contribution frequency

// Set short vars for readability
$n = $frequency;
$t = $years;
$r = $rate;
$p = $initial;
$c = $contribution;

// Total contribution periods
$total_periods = $n * $t;

// --- Future Value Calculations ---

// FV of initial principal
$fv_principal = $p * pow(1 + $r / $n, $total_periods);

// FV of contributions
$fv_contributions = $c * ((pow(1 + $r / $n, $total_periods) - 1) / ($r / $n));

// Total future value
$totalAmount = round($fv_principal + $fv_contributions, 2);

// --- Capital Invested ---
$total_contributions = $c * $n * $t;
$investedCapital = $p + $total_contributions;

// --- Interest Breakdown ---
$compoundInterest = $totalAmount - $investedCapital;
$simpleEndValue = $investedCapital + (($p + ($total_contributions / 2)) * $r * $t);
$simpleInterest = $simpleEndValue - $investedCapital;

// --- Chart Data Preparation ---
$investedData = [];
$compoundData = [];
$simpleData = [];
$categories = [];

for ($i = 0; $i <= $t; $i++) {
    $categories[] = "$i";

    $periods = $n * $i;

    $compound = $p * pow(1 + $r / $n, $periods) +
                $c * ((pow(1 + $r / $n, $periods) - 1) / ($r / $n));
    $compoundData[] = round($compound, 2);

    $investedSoFar = $p + $c * $n * $i;
    $investedData[] = $investedSoFar;

    $simple = $investedSoFar + (($p + ($c * $n * $i / 2)) * $r * $i);
    $simpleData[] = round($simple, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>ROI Calculator</title>
  <link rel="stylesheet" href="index.css" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.highcharts.com/highcharts.js"></script>
</head>
<body>
  <div class="mx-4 mt-[25px] p-4 md:p-6">
  <h2 class="md:text-4xl text-2xl font-bold mb-2">Return on investment (ROI) calculator</h2>
  <div class="max-w-3xl my-5 text-gray-500 text-[16px] leading-relaxed">
    <p class="mb-4">
    Meeting your long-term investment goal depends on many factors including capital, return rate, inflation, taxes, and time horizon.
    </p>
    <p>
    Use this ROI calculator to estimate your investment growth.
    </p>
  </div>

  <div class="flex flex-col md:flex-row gap-6">
    <!-- Inputs -->
    <form method="post" class="w-full md:w-[600px]">

    <!-- Years -->
    <label class="block my-4" for="years">
      How many years will you invest for?
      <span class="info-container">
      <span class="info-icon">i</span>
      <span class="tooltip-text">The number of years you Wish to analyze. This can be any number from 1 to 100.</span>
      </span>
    </label>
    <div class="relative">
      <input id="years" name="years"
       value="<?= htmlspecialchars($years) ?>"
       class="inputs w-full pr-10 appearance-none"
       inputmode="numeric"
       pattern="\d+"
       required />
      <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500">yrs</span>
    </div>

    <!-- Rate -->
    <label class="block my-4" for="rate">
      What is your expected rate of return on this investment? (%)
      <span class="info-container">
      <span class="info-icon">i</span>
      <span class="tooltip-text">The historical average stock market return, as measured by the S&amp;P 500, generally hovers around 10 percent annually before adjusting for inflation, and about 6 to 7 percent when adjusted for inflation.</span>
      </span>
    </label>
    <div class="relative">
      <input
      id="rate"
      name="rate"
      type="text"
      inputmode="decimal"
      maxlength="6"
      class="inputs w-full pr-10 appearance-none"
      value="<?= htmlspecialchars($_POST['rate'] ?? 7) ?>"
      required
      />
      <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500">%</span>
    </div>

    <!-- Initial Investment -->
    <label class="block my-4" for="initial">How much will you initially invest?</label>
    <div class="relative">
      <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">$</span>
      <input
      id="initial"
      name="initial"
      type="text"
      inputmode="decimal"
      pattern="^\d{1,3}(,\d{3})*(\.\d+)?$"
      value="<?= htmlspecialchars(number_format($initial, 0, '.', ',')) ?>"
      class="inputs w-full pl-8 pr-4 appearance-none"
      maxlength="15"
      required
      />
    </div>

    <!-- Contribution -->
    <label class="block my-4" for="contribution">How much will your periodic contribution be?</label>
    <div class="relative">
      <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">$</span>
      <input
      inputmode="decimal"
      pattern="^\d{1,3}(,\d{3})*(\.\d+)?$"
      class="inputs w-full pl-8 pr-4 appearance-none"
      id="contribution"
      name="contribution"
      maxlength="15"
      type="text"
      value="<?= htmlspecialchars(number_format($contribution, 0, '.', ',')) ?>"
      required
      />
    </div>

    <!-- Frequency -->
    <label class="block my-4" for="frequency">How often will you contribute to your investment goal?</label>
    <select id="frequency" name="frequency" class="inputs w-full p-2 border rounded">
      <option value="12" <?= $frequency == 12 ? 'selected' : '' ?>>Monthly</option>
      <option value="26" <?= $frequency == 26 ? 'selected' : '' ?>>Every Two Weeks</option>
      <option value="52" <?= $frequency == 52 ? 'selected' : '' ?>>Weekly</option>
      <option value="1" <?= $frequency == 1 ? 'selected' : '' ?>>Annually</option>
    </select>

    <!-- Submit -->
    <button type="submit" class="Bluebtn mt-6 w-full bg-blue-600 text-white py-2 rounded">
      Calculate
    </button>
    </form>

    <!-- Chart Output -->
    <div class="w-full px-2 md:px-0">
    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6 w-full">
      <h3 class="text-[16px] md:text-xl font-semibold mb-2">
      Total Investment fund after <span id="display-years"><?= htmlspecialchars($years) ?></span> years
      </h3>
      <p class="text-[16px] md:text-3xl font-bold text-blue-600 mb-2" id="total-amount">$<?= number_format($totalAmount, 0) ?></p>
      <p class="text-[14px] md:text-[16px] text-gray-600 mb-4">Annual Investment Returns</p>
      <div id="container" style="width: 100%; height: 450px;"></div>
    </div>
    </div>
  </div>
  </div>

  <!-- JavaScript formatting logic -->
  <script>
  function enforceRateInput(input) {
    input.addEventListener('input', function () {
    // Strip invalid characters
    let value = this.value.replace(/[^0-9.]/g, '');

    // Only allow one decimal point
    const parts = value.split('.');
    if (parts.length > 2) {
      value = parts[0] + '.' + parts[1]; // Keep only the first decimal
    }

    // Convert to float for validation
    const num = parseFloat(value);
    if (!isNaN(num) && num > 100) {
      value = '100';
    }

    this.value = value;
    });

    input.addEventListener('blur', function () {
    // Optional: remove trailing dot or unnecessary zeros
    this.value = this.value.replace(/(\.\d*?)0+$/, '$1').replace(/\.$/, '');
    });
  }

  // Apply to rate input
  enforceRateInput(document.getElementById('rate'));
  </script>

  <script>
  function formatNumberInput(input) {
    input.addEventListener('input', function () {
    let clean = this.value.replace(/,/g, '').replace(/[^\d.]/g, '');
    let parts = clean.split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    if (parts.length > 2) parts = [parts[0], parts[1]];
    this.value = parts.join('.');
    });

    input.addEventListener('blur', function () {
    this.value = this.value.replace(/(\.\d*?)0+$/, '$1').replace(/\.$/, '');
    });
  }

  function enforceIntegerOnly(input, maxValue = null) {
    input.addEventListener('input', function () {
    this.value = this.value.replace(/[^\d]/g, '');

    // Enforce maximum value if provided
    if (maxValue !== null) {
      const numeric = parseInt(this.value, 10);
      if (!isNaN(numeric) && numeric > maxValue) {
      this.value = maxValue;
      }
    }
    });
  }

  function enforceDecimalOnly(input) {
    input.addEventListener('input', function () {
    let value = input.value;
    input.value = value
      .replace(/[^0-9.]/g, '')         // remove all non-numeric and non-dot
      .replace(/^0+(\d)/, '$1')        // remove leading zero if followed by digit
      .replace(/(\..*?)\..*/g, '$1');  // allow only one dot
    });
  }

  // Apply to inputs
  formatNumberInput(document.getElementById('initial'));
  formatNumberInput(document.getElementById('contribution'));
  enforceIntegerOnly(document.getElementById('years'), 100);
  enforceDecimalOnly(document.getElementById('rate'));
  </script>

  <!-- Highcharts chart -->
  <script>
  Highcharts.chart('container', {
    chart: { type: 'area' },
    title: { text: 'Annual Investment Returns' },
    xAxis: {
    categories: <?= json_encode($categories) ?>,
    title: { text: 'Years' }
    },
    yAxis: { title: { text: 'Thousands of Dollars' } },
    legend: { reversed: true },
    plotOptions: {
    area: {
      stacking: 'normal',
      marker: { enabled: false }
    }
    },
    series: [
  {
    name: 'Compound interest $<?= number_format($compoundInterest, 0) ?>',
    data: <?= json_encode($compoundData) ?>,
    color: '#ef4444'
  },
  {
    name: 'Simple Interest $<?= number_format($simpleInterest, 0) ?>',
    data: <?= json_encode($simpleData) ?>,
    color: '#059669'
  },
  {
    name: 'Invested capital $<?= number_format($investedCapital, 0) ?>',
    data: <?= json_encode($investedData) ?>,
    color: '#1e40af'
  }
]
  });
  </script>
</body>
</html>
