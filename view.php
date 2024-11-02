<?php
    session_start(); // Start the session

    // Check if the user is logged in by checking the 'username' session variable
    if (!isset($_SESSION['username'])) {
        // If the user is not logged in, redirect to the login page
        header("Location: index.php"); 
        exit(); 
    }
    include('dbcon.php');
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");

    if (isset($_GET['ID'])) {
    $scanID = $_GET['ID'];
    $ref_table = "users";
    $fetchdata = $database->getReference($ref_table)->getValue();
    $resultData = null;
    $userID = 'Unknown';
    $userName = 'Unknown';
    $scanImage = 'Unknown'; // Initialize scanImage variable

    if ($fetchdata != null) {
        foreach ($fetchdata as $key => $userData) {
            if (isset($userData['results'][$scanID])) {
                $resultData = $userData['results'][$scanID];
                $userID = $key; // Get the user ID from the key
                $userName = $userData['userName'] ?? 'Unknown'; // Get the userName
                $userfname = $userData['firstName'] ?? 'Unknown'; // Get the userFname
                $userlname = $userData['lastName'] ?? 'Unknown'; // Get the userLname
                
                // Fetch the scanImage from the database
                $scanImage = $database->getReference("users/$key/results/$scanID/scanImage")->getValue();

                break;
            }
        }

        if ($resultData != null) {
            $disease = $resultData['disease'] ?? 'Unknown';
            $status = isset($resultData['status']) ? ($resultData['status'] ? 'Evaluated' : 'Not Evaluated') : 'Unknown';
            $date = $resultData['timeAndDate'] ?? 'Unknown';
            $description = $resultData['description'] ?? 'Unknown';
            $token = "51314025-a892-4b48-b498-cfb67859739b";
            $imageURL = "https://firebasestorage.googleapis.com/v0/b/petscan-3c7c6.appspot.com/o/scanned_images%2F$scanID.jpg?alt=media&token=$token";
            $confidence = $resultData['confidence'] ?? 'Unknown';
            if($disease == 'Bacterial Dermatitis'){
                $description = 'Bacterial Dermatitis';
            }else if($disease == 'Hypersensitivity'){
                $description = 'Hypersensitivity';
            }else if($disease == 'Fungal Dermatitis'){
                $description = 'Fungal Dermatitis';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PETSCAN DASHBOARD</title>
    <!-- icon -->
    <link rel="icon" href="./assets/img/LOGO3.png">
    <!-- css -->
    <link rel="stylesheet" href="./css/styles.css">
    <!-- box icons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
</head>
<!-- body -->

<body>
    <div class="container">
        <!-- sidebar -->
        <div class="sidebar">
            <div class="sidebar-content">
                <div class="logo">
                    <img src="./assets/img/LOGO3.png" alt="PETSCAN">
                    <div class="logo-text">
                        <h1>PetScan</h1>
                        <p>ADMIN PANEL</p>
                    </div>
                </div>
                <!-- sidebar menu buttons -->
                <div class="sidebar-buttons">
                    <form method="post" action="./dashboard.php">
                        <button type="submit"><i class="bx bxs-dashboard"></i>Dashboard</button>
                    </form>
                    <form method="post" action="#">
                        <button type="submit" disabled class="active"><i class='bx bxs-check-square'></i>Recent Scans</button>
                    </form>
                    <form method="post" action="./analytics.php">
                        <button type="submit"><i class='bx bxs-data'></i>Dataset</button>
                    </form>
                    <div class="logout-button">
                        <form method="post" action="logout.php">
                            <button type="submit"><i class="bx bx-log-out"></i>Logout</button>
                        </form>
                    </div>

                </div>

            </div>
        </div>


    </div>
    <!-- dashboard content -->
    <div class="view-content">
        <!-- header -->
        <header>
            <h1>RECENT SCANS</h1>
            <p>Check and Evaluate the latest scans.</p>
        </header>
        <hr>
        <div class="scan-content">
            <h1>SCAN EVALUATION</h1>
            <p> <b>*You can change the value of the scan before importing to dataset. The dataset will be used for further training the model.</b></p>
            <br>
            <p>SCAN ID: <?php echo $scanID; ?></p>
            <p>USER NAME: <?php echo $userName; ?></p>
            <p>FULL NAME: <?php echo $userfname ?> <?php echo $userlname; ?></p>
            <div class="parent-container">
                <div class="result-container">
                    <img src="<?php echo $imageURL ?>" alt="scan">
                    <div class="scan-result">
                        <h1>RESULTS</h1>
                        <p>Disease Name: <?php echo $disease; ?></p>
                        <p>Status: <?php echo $status; ?></p>
                        <p>Validation: <span id="validation"><?php echo isset($resultData['validatedBy']) ? $resultData['validatedBy'] : 'App'; ?></span></p>
                        <h1>EDIT</h1>
                        <form id="editForm" action="#" method="post">
                            <label for="disease">Disease Name</label>
                            <select name="disease" id="disease" required>
                                <option value="Bacterial Dermatitis">Bacterial Dermatitis</option>
                                <option value="Hypersensitivity">Hypersensitivity</option>
                                <option value="Fungal Dermatitis">Fungal Dermatitis</option>
                            </select>
                            <button type="button" class="import-button">IMPORT TO DATASET</button>
                        </form>
                    </div>
                </div>
                
            </div>
        </div>
    </div>

    <script>
        function debounce(func, delay) {
            let debounceTimeout;
            return function() {
                const context = this;
                const args = arguments;
                clearTimeout(debounceTimeout);
                debounceTimeout = setTimeout(() => func.apply(context, args), delay);
            };
        }

        function alertEdit() {
            alert('Scan Imported to the Dataset.');

            var diseaseSelect = document.getElementById('disease');
            var selectedDisease = diseaseSelect.options[diseaseSelect.selectedIndex].text;

            // Define the data to be sent to the server
            var data = {
                action: 'move_to_folder',
                scanID: "<?php echo $scanID; ?>",
                selectedDisease: selectedDisease,
                userID: "<?php echo $userID; ?>",
                imageURL: "<?php echo $imageURL; ?>",
                date: "<?php echo $date; ?>",
                validatedBy: "<?php echo $_SESSION['username']; ?>"  // Pass the current logged-in user
            };

            // Make an AJAX request to the PHP script
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'dbcon.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    console.log(xhr.responseText); // Handle the server response here
                }
            };
            xhr.send('action=' + data.action + '&scanID=' + data.scanID + '&selectedDisease=' + data.selectedDisease + '&userID=' + data.userID + '&imageURL=' + data.imageURL + '&date=' + data.date + '&validatedBy=' + data.validatedBy);

            // Update the validation field in the UI
            document.getElementById("validation").innerText = data.validatedBy; // Update validator field on the page
        }

        // Apply debounce to alertEdit function with a 300ms delay
        const alertEditDebounced = debounce(alertEdit, 300);

        // Attach the debounced function to the Import button
        document.querySelector('.import-button').addEventListener('click', function(event) {
            event.preventDefault(); // Prevent form submission
            alertEditDebounced();
        });
    </script>

</body>

</html>
