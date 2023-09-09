<?php

// add database connection file
require_once "db.php";


class NBP_API
{
    private $conn;

    // contain last converted currencys names
    private $last_sourceCurrency;
    private $last_targetCurrency;

    public function __construct()
    {
        $this->conn = conn_open();
    }

    // get data from api and fill database
    public function getExchangeRates()
    {
        $url = "http://api.nbp.pl/api/exchangerates/tables/A?format=json";
        $data = file_get_contents($url);
        $rates = json_decode($data, true);

        if (is_array($rates) && !empty($rates)) {
            $table = $rates[0];
            $date = $table['effectiveDate'];

            foreach ($table['rates'] as $rate) {
                $currency = $rate['currency'];
                $exchangeRate = $rate['mid'];

                // save data to database
                $this->saveExchangeRate($currency, $exchangeRate, $date);
            }
        }
    }

    // save data to database
    private function saveExchangeRate($currency, $rate, $date)
    {
        $currency = $this->conn->real_escape_string($currency);
        $rate = $this->conn->real_escape_string($rate);
        $date = $this->conn->real_escape_string($date);

        $query = "INSERT INTO exchange_rates (currency, rate, date) VALUES ('$currency', '$rate', '$date') 
              ON DUPLICATE KEY UPDATE rate = VALUES(rate), date = VALUES(date)";
        $result = $this->conn->query($query);

        if (!$result) {
            throw new Exception("Błąd zapisu danych: " . $this->conn->error);
        }
    }
    
    // download data from form
    public function getCurrency()
    {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $amount = $_POST["amount"];
            $sourceCurrency = $_POST["sourceCurrency"];
            $targetCurrency = $_POST["targetCurrency"];

            $this->last_sourceCurrency = $_POST["sourceCurrency"];
            $this->last_targetCurrency = $_POST["targetCurrency"];


            // clear $_POST array
            // will not show last conversion for every reload page
            $_POST = array();


            $convertedAmount = $this->calcCurrency($sourceCurrency, $targetCurrency, $amount);

            // print conversion result on the screen
            echo "<span>Wynik: " . $convertedAmount . "</span>";

            // save conversion result to database
            $this->saveCurrencyCalc($sourceCurrency, $targetCurrency, $convertedAmount);
        }
    }

    // generating table with every exchange rates
    public function generateFullTable()
    {
        $query = "SELECT currency, rate FROM exchange_rates";
        $result = $this->conn->query($query);

        if ($result->num_rows > 0) {
            echo '<div id="fullTable">';
            echo '<input type="text" id="searchInput" placeholder="Szukaj waluty lub kraju">';
            echo '<table>';
            echo '<th colspan="2" class="tableTittle" style="font-size: 1.47em;">Aktualne kursy walut:</th>';
            echo '<tr>
                    <th>Waluta</th>
                    <th>Stawka</th>
                </tr>';

                while ($row = $result->fetch_assoc()) {
                    echo '<tr class="data-row">';
                    echo '<td>' . $row['currency'] . '</td>';
                    echo '<td>' . $row['rate'] . '</td>';
                    echo '</tr>';
                }

            echo '</table>';
            echo '</div>';
        } else {
            echo 'Nie znaleziono danych.';
        }
    }

    public function generateConvertedTable()
    {
        if(isset($_POST['sourceCurrency'])) {
            // get values from POST
                $last_sourceCurrency = $_POST["sourceCurrency"];
                $last_targetCurrency = $_POST["targetCurrency"];
        
            $query = "SELECT source_currency, target_currency, converted_amount
                    FROM conversion_history
                    WHERE source_currency = '$last_sourceCurrency'
                    AND target_currency = '$last_targetCurrency'
                    ORDER BY id DESC
                    LIMIT 10";
            $result = $this->conn->query($query);
        
            if ($result->num_rows > 0) {
                echo '<div id="convertedTable">';
                echo '<table>';
                echo '<th colspan="3" class="tableTittle" style="font-size: 1.47em;">Podobne kalkulacje:</th>';
                echo '<tr>
                        <th>Waluta źródłowa</th>
                        <th>Waluta docelowa</th>
                        <th>Przeliczona kwota</th>
                    </tr>';
        
                    while ($row = $result->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . $row['source_currency'] . '</td>';
                        echo '<td>' . $row['target_currency'] . '</td>';
                        echo '<td>' . $row['converted_amount'] . '</td>';
                        echo '</tr>';
                    }
        
                echo '</table>';
                echo '</div>';
            }
        }
    }

    public function saveCurrencyCalc($sourceCurrency, $targetCurrency, $convertedAmount)
    {

        if ($convertedAmount == null || $sourceCurrency == $targetCurrency) {
            exit;
        }

        $query = "INSERT INTO conversion_history (source_currency, target_currency, converted_amount) 
              VALUES ('$sourceCurrency', '$targetCurrency', '$convertedAmount')";
        $result = $this->conn->query($query);

        if (!$result) {
            throw new Exception("Błąd podczas zapisu danych: " . $this->conn->error);
        }
    }

    public function calcCurrency($sourceCurrency, $targetCurrency, $amount)
    {

        // when source currency and target currency are the same
        if ($sourceCurrency == $targetCurrency) {
            echo '<div style="color:#ff0000; font-size: 2em; font-weight: 700;">', 'Musisz wybrać dwie różne waluty.', '</div>';
            exit;
        }

        $query = "SELECT currency, rate FROM exchange_rates WHERE currency = '$sourceCurrency' OR currency = '$targetCurrency'";
        $result = $this->conn->query($query);
        if ($result->num_rows !== 0) {
            $sourceRate = null;
            $targetRate = null;


            foreach ($result as $row) {
                $currency = $row['currency'];
                $rate = $row['rate'];

                if ($currency === $sourceCurrency) {
                    $sourceRate = $rate;
                } else if ($currency === $targetCurrency) {
                    $targetRate = $rate;
                }
            }

            // calculate currency and show only 2 points after dot
            if ($sourceRate !== null && $targetRate !== null) {
                $convertedAmount = ($amount / $sourceRate) * $targetRate;
                $convertedAmount = number_format($convertedAmount, 2, '.', '');

                return $convertedAmount;
            } else
                return null;
        }
    }


    // to print tables on the screen
    public function generateTables() {
        echo '<div id="tableBox">';
        $this-> generateFullTable();
        $this-> generateConvertedTable();
        echo "</div>";
    }

}

// calls
$currencyClass = new NBP_API();
$currencyClass-> generateTables();

?>


<!DOCTYPE html>
<html lang="PL-pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Strona Główna</title>
    <link rel="stylesheet" href="main.css">
</head>

<body>


    <article>
        <form method="POST">
            <label for="amount">Kwota:</label>
            <input type="number" id="amount" name="amount" step="1" required>
            <br><br>

            <label for="sourceCurrency">Waluta źródłowa:</label>
            <select id="sourceCurrency" name="sourceCurrency" required>
                <option value="euro">Euro (EUR)</option>
                <option value="dolar amerykański">Dolar amerykański (USD)</option>
                <option value="bat (Tajlandia)">bat tajlandzki (BAT)</option>
            </select>
            <br><br>

            <label for="targetCurrency">Waluta docelowa:</label>
            <select id="targetCurrency" name="targetCurrency" required>
                <option value="dolar amerykański">Dolar amerykański (USD)</option>
                <option value="euro">Euro (EUR)</option>
                <option value="bat (Tajlandia)">bat tajlandzki (BAT)</option>
                <option value="dolar australijski">dolar australijski</option>
            </select>
            <br><br>

            <input type="submit" value="Przelicz">
        </form>


        <!-- print here result of conversion -->
        <?php
            $currencyClass->getCurrency();
            ?>
    </article>
    <hr/>


    <script src="scripts.js"></script>
</body>

</html>