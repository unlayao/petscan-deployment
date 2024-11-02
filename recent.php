<?php
session_start(); // Start the session

// Check if the user is logged in by checking the 'username' session variable
if (!isset($_SESSION['username'])) {
    // If the user is not logged in, redirect to the login page
    header("Location: index.php");
    exit();
}



//retrieve data from the database
include('dbcon.php');
$ref_table = "users";
$fetchdata = $database->getReference($ref_table)->getValue();
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");


$validatedScans = $database->getReference('validated')->getValue();
$validatedScanIDs = [];
if ($validatedScans) {
    foreach ($validatedScans as $disease => $scans) {
        foreach ($scans as $scanID => $scanData) {
            $validatedScanIDs[$scanID] = $disease;
        }
    }
}

// Function to update scan status based on validated folder existence
function getScanStatus($scanID, $disease, $status)
{
    global $validatedScanIDs;
    if (isset($validatedScanIDs[$scanID]) && $validatedScanIDs[$scanID] === $disease) {
        return true; // Set status to true if scanID exists in validated scans and matches the disease
    } else {
        return $status; // Otherwise, keep the original status
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
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
                    <form method="post" action="./recent.php">
                        <button type="submit" disabled class="active"><i class='bx bxs-check-square'></i>Evaluation</button>
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
    <div class="db-content">

        <!-- header -->
        <header>
            <h1>SCAN EVALUATION</h1>
            <p>Check and Evaluate the latest scans.</p>
        </header>
        <div class="filter-container">
            <div class="disease-filter">
                <button>All</button>
                <button>Bacterial</button>
                <button>Fungal</button>
                <button>HyperSen</button>
                <button>X Evaluated</button>
                <button>Evaluated</button>
                <button class="filter-button" id="clear-filters">Clear Filters</button>
            </div>
            <div class="date-filter">
                <div class="date-selector" style="display: none;">
                    <input type="date" name="start-date" id="start">
                    <i class='bx bx-right-arrow-alt'></i>
                    <input type="date" name="end-date" id="end">
                </div>
                <div class="date-buttons">
                    <button class="filter-button" id="custom">Custom</button>
                    <button class="filter-button" id="today">Today</button>
                    <button class="filter-button" id="last7days">Last 7 Days</button>
                </div>
            </div>
        </div>
        <hr>
        <div class="recent-scans">
            <div class="eval-table">
                <?php
                if ($fetchdata == null) {
                    echo "<p>No scans available.</p>";
                } else {
                    $allScans = [];

                    // Collect all scans into an array
                    foreach ($fetchdata as $key => $userData) {
                        if (isset($userData['results'])) {
                            $results = $userData['results'];
                            foreach ($results as $resultID => $resultData) {
                                $scanID = $resultID;
                                $disease = $resultData['disease'] ?? ''; // Disease name
                                $status = isset($resultData['status']) ? $resultData['status'] : false; // Status
                                $date = $resultData['timeAndDate'] ?? ''; // Date and time
                                $userfname = $userData['firstName'] ?? ''; // User first name
                                $userlname = $userData['lastName'] ?? ''; // User last name
                                $confidence = $resultData['confidence'] ?? '';

                                // Add each scan to the array
                                $allScans[] = [
                                    'scanID' => $scanID,
                                    'disease' => $disease,
                                    'status' => $status,
                                    'date' => $date,
                                    'userID' => $key,
                                    'userfname' => $userfname,
                                    'userlname' => $userlname,
                                    'confidence' => $confidence
                                ];
                            }
                        }
                    }

                    // Sort the scans by date in descending order
                    usort($allScans, function ($a, $b) {
                        return strtotime($b['date']) - strtotime($a['date']);
                    });

                    // Display all sorted scans

                }
                ?>
                <table>
                    <tr>
                        <th>Image</th>
                        <th>User ID</th>
                        <th>Date</th>
                        <th>Confidence</th>
                        <th>Disease</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    <?php
                    foreach ($allScans as $scan) {
                        $scanID = $scan['scanID'];
                        $userID = $scan['userID'];
                        $disease = $scan['disease'];
                        $status = $scan['status'];
                        $date = $scan['date'];
                        $userfname = $scan['userfname'];
                        $userlname = $scan['userlname'];
                        $confidence = $scan['confidence'];

                        // Retrieve the token from the Firebase Storage response
                        $token = "51314025-a892-4b48-b498-cfb67859739b";

                        // Construct the image URL with the token
                        $imageURL = "https://firebasestorage.googleapis.com/v0/b/petscan-3c7c6.appspot.com/o/scanned_images%2F$scanID.jpg?alt=media&token=$token";

                        // Debug the value of disease
                        error_log("Disease Value: " . print_r($disease, true));

                        // Convert disease code to disease name
                        if ($disease == 1) {
                            $disease1++;
                            $disease = "Bacterial Dermatitis";
                        } else if ($disease == 2) {
                            $disease2++;
                            $disease = "Hypersensitivity";
                        } else if ($disease == 3) {
                            $disease3++;
                            $disease = "Fungal Dermatitis";
                        }

                        // Determine evaluation status
                        $evaluation = $status ? "Evaluated" : "Not Evaluated";
                        echo "<tr data-scan-id='$scanID'>
                            <td><img src='$imageURL' alt='scan' onclick=\"openImageModal('$imageURL')\"></td>
                            <td>$userfname $userlname</td>
                            <td>$date</td>
                            <td>$confidence</td>
                            <td>$disease</td>
                            <td>$evaluation</td>
                            <td><a href='#' onclick=\"addToDataset(this, '$scanID', '$userID', '$disease', '$confidence', '$date', '$userfname', '$userlname', '$imageURL')\">Add</a> 
                        <a href='#' onclick=\"openModal('$userfname', '$userlname', '$date', '$disease', '$evaluation', '$imageURL', '$scanID', '$userID', '$confidence')\">Edit</a></td>
                        </tr>";
                    }
                    ?>
                </table>
            </div>
            <!-- Image Modal: for image preview-->
            <div id="imageModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span class="close-btn" onclick="closeModal()">&times;</span>
                    <h2>Image Preview</h2>
                    <img id="modalImage" src="" alt="User Image" style="width:auto; height:500px;">
                </div>
            </div>
            <!-- Edit Modal: for editing popup -->
            <div id="editModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span class="close-btn" onclick="closeModal()">&times;</span>
                    <div class="modal-info">
                        <h2>Edit Scan</h2>
                        <!-- Image -->
                        <img id="editModalImage" src="" alt="User  Image" style="width:auto;height:300px;"><br>
                        <!-- Information -->
                        <form id="editForm" action="#" method="post">
                            <label for="userName">Name:</label>
                            <input type="text" id="userName" name="userName" readonly><br>

                            <label for="date">Date:</label>
                            <input type="text" id="date" name="date" readonly><br>

                            <label for="disease">Disease:</label>
                            <input type="text" id="disease" name="disease" readonly><br>

                            <label for="confidence">Confidence:</label>
                            <input type="text" id="confidence" name="confidence" readonly><br>

                            <label for="evaluation">Status:</label>
                            <input type="text" id="evaluation" name="evaluation" readonly><br>
                            
                            <label for="newEvaluation">Edit Disease:</label>
                            <select id="newEvaluation" name="newEvaluation">
                                <option value="Bacterial Dermatitis">Bacterial Dermatitis</option>
                                <option value="Fungal Dermatitis">Fungal Dermatitis</option>
                                <option value="Hypersensitivity">Hypersensitivity</option>
                            </select><br><br>

                            <!-- Hidden fields for scanID and userID -->
                            <input type="hidden" id="scanID" name="scanID">
                            <input type="hidden" id="userID" name="userID">
                        </form>
                        <button type="button" class="import-button" onclick="importToDataset()">Import to Dataset</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

</body>

</html>

<script>
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }

    // Get all card elements and add click event listener to each
    const cards = document.querySelectorAll(".card");
    cards.forEach(card => {
        card.addEventListener("click", () => {
            const scanID = card.getAttribute("data-scan-id");
            window.location.href = "./view.php?ID=" + encodeURIComponent(scanID);
        });
    });

    // Store active filters
    let activeDiseaseFilter = "all"; 
    let activeDateFilter = { start: null, end: null }; 

    // Get filter buttons and date inputs
    const filterButtons = document.querySelectorAll(".disease-filter button");
    const customButton = document.getElementById("custom");
    const todayButton = document.getElementById("today");
    const last7DaysButton = document.getElementById("last7days");
    const clearFiltersButton = document.getElementById("clear-filters");
    const dateSelector = document.querySelector(".date-selector");
    const startDateInput = document.getElementById("start");
    const endDateInput = document.getElementById("end");

    // Date and disease filter functions
    function applyDiseaseFilter(filter) {
        activeDiseaseFilter = filter.toLowerCase();
        updateScanDisplay();
    }
    function applyDateFilter(start, end) {
        activeDateFilter = { start, end };
        updateScanDisplay();
    }

    // Display scan data based on filters
    function updateScanDisplay() {
    const scanRows = document.querySelectorAll(".eval-table tr[data-scan-id]");
    scanRows.forEach(row => {
        const disease = row.querySelector("td:nth-child(5)").textContent.toLowerCase();
        const date = new Date(row.querySelector("td:nth-child(3)").textContent);
        const isEvaluated = row.querySelector("td:nth-child(6)").textContent.toLowerCase() === "evaluated"; // Check if the status is evaluated

        const showByDisease = 
            activeDiseaseFilter === "all" || 
            (activeDiseaseFilter === "evaluated" && isEvaluated) || 
            (activeDiseaseFilter === "x evaluated" && !isEvaluated) || // Handle the "Not Evaluated" filter
            disease.includes(activeDiseaseFilter);

        const showByDate = !activeDateFilter.start || (
            date >= new Date(activeDateFilter.start) && date <= new Date(activeDateFilter.end)
        );

        row.style.display = showByDisease && showByDate ? "" : "none";
    });
}

    // Event listeners for disease filters
    filterButtons.forEach(button => {
        button.addEventListener("click", () => {
            applyDiseaseFilter(button.textContent);
        });
    });

    // Date filtering
    customButton.addEventListener("click", () => {
        dateSelector.style.display = dateSelector.style.display === "none" ? "block" : "none";
    });
    todayButton.addEventListener("click", () => {
        const today = new Date();
        applyDateFilter(today, today);
    });
    last7DaysButton.addEventListener("click", () => {
        const today = new Date();
        const lastWeek = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 7);
        applyDateFilter(lastWeek, today);
    });
    startDateInput.addEventListener("change", () => {
        applyDateFilter(startDateInput.value, endDateInput.value || new Date());
    });
    endDateInput.addEventListener("change", () => {
        applyDateFilter(startDateInput.value, endDateInput.value || new Date());
    });
    clearFiltersButton.addEventListener("click", () => {
        activeDiseaseFilter = "all";
        activeDateFilter = { start: null, end: null };
        dateSelector.style.display = "none";
        updateScanDisplay();
    });

    // Modal functions for image preview and editing
    function openImageModal(imageUrl) {
        // Set the source of the modal image to the clicked image
        document.getElementById('modalImage').src = imageUrl;

        // Display the modal
        document.getElementById('imageModal').style.display = 'block';
    }
    function closeModal() {
        document.querySelectorAll(".modal").forEach(modal => modal.style.display = "none");
    }
    function openModal(userfname, userlname, date, disease, evaluation, imageUrl, scanID, userID, confidence) {
        // Set the values of the read-only fields
        document.getElementById('userName').value = userfname + ' ' + userlname; // Combine first and last name
        document.getElementById('date').value = date;
        document.getElementById('disease').value = disease;
        document.getElementById('confidence').value = confidence; // Set confidence value
        document.getElementById('evaluation').value = evaluation;

        // Set the image for the edit modal
        document.getElementById('editModalImage').src = imageUrl;

        // Set the hidden values
        document.getElementById('scanID').value = scanID;
        document.getElementById('userID').value = userID;

        // Display the edit modal
        document.getElementById('editModal').style.display = 'block';
    }

    function addToDataset(button, scanID, userID, disease, confidence, date, userfname, userlname, imageURL) {
        // Disable the button to prevent multiple clicks
        button.innerText = "Adding..."; // Change button text
        button.style.pointerEvents = "none"; // Disable pointer events

        const selectedDisease = disease; // Use the disease from the parameters
        const validatedBy = "<?php echo $_SESSION['username']; ?>"; // Get the current user

        // Create an AJAX request
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'dbcon.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                alert('Scan added to the dataset!');
                // Optionally, refresh the page or update the UI to reflect the changes
            } else {
                console.error('Error adding scan: ' + xhr.status);
                // Re-enable the button if there was an error
                button.innerText = "Add"; // Reset button text
                button.style.pointerEvents = "auto"; // Re-enable pointer events
            }
        };

        // Send the data
        xhr.send(`action=move_to_folder&scanID=${scanID}&selectedDisease=${selectedDisease}&userID=${userID}&imageURL=${imageURL}&date=${date}&validatedBy=${validatedBy}`);
    }


    // Save changes function
    function saveChanges() {
        // Implement the function for saving changes here (e.g., AJAX request to server)
        closeModal();
    }
    window.onclick = event => {
        if (event.target.classList.contains("modal")) closeModal();
    };
</script>
