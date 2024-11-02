<?php
require __DIR__.'/vendor/autoload.php';

use Kreait\Firebase\Factory;

$factory = (new Factory)
    ->withServiceAccount('./petscan-3c7c6-firebase-adminsdk-mn1ir-a382d3f335.json')
    ->withDatabaseUri('https://petscan-3c7c6-default-rtdb.asia-southeast1.firebasedatabase.app/')
    ->withDefaultStorageBucket('petscan-3c7c6.appspot.com')
    ->withProjectId('petscan-3c7c6');

$database = $factory->createDatabase();
$storage = $factory->createStorage()->getBucket();

function moveScanToValidated($scanID, $userID, $selectedDisease, $imageURL, $date, $validatedBy) {
    global $database, $storage;

    $diseaseFolder = '';
    if ($selectedDisease === "Bacterial Dermatitis") {
        $diseaseFolder = 'Bacterial Dermatitis';
    } elseif ($selectedDisease === "Hypersensitivity") {
        $diseaseFolder = 'Hypersensitivity';
    } elseif ($selectedDisease === "Fungal Dermatitis") {
        $diseaseFolder = 'Fungal Dermatitis';
    } else {
        return false;
    }

    $newImagePath = "evaluated/$diseaseFolder/$scanID.jpg";

    try {
        $storage->object("scanned_images/$scanID.jpg")->copy($storage, [
            'name' => $newImagePath
        ]);

        $imageUrl = "https://firebasestorage.googleapis.com/v0/b/petscan-3c7c6.appspot.com/o/" . urlencode($newImagePath) . "?alt=media";

        // Exclude scanID from the data
        $data = [
            'imageURL' => $imageUrl,
            'status' => true, // Update status to true
            'date' => $date,
            'userID' => $userID,
            'validatedBy' => $validatedBy  // Store the current user as the validator
        ];

        // Move the scan data to the validated folder
        $database->getReference('validated/' . $diseaseFolder . '/' . $scanID)->set($data);

        // Update the status in the user's results
        updateScanStatus($scanID, $userID, true);

        return true;
    } catch (Exception $e) {
        return false;
    }
}


function updateScanStatus($scanID, $userID, $status) {
    global $database;

    try {
        // Update the status directly under the user's node
        $database->getReference('users/' . $userID . '/results/' . $scanID . '/status')->set($status);

        return true;
    } catch (Exception $e) {
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'upload') {
        $scanID = $_POST['scanID'];
        $selectedDisease = $_POST['disease'];
        $userID = $_POST['userID'];
        $imageURL = $_POST['imageURL'];
        $date = $_POST['date'];

        if (moveScanToValidated($scanID, $userID, $selectedDisease, $imageURL, $date)) {
            echo 'Success';
        } else {
            echo 'Error';
        }
    } elseif ($_POST['action'] === 'move_to_folder') {
        $scanID = $_POST['scanID'];
        $selectedDisease = $_POST['selectedDisease'];
        $userID = $_POST['userID'];
        $imageURL = $_POST['imageURL'];
        $date = $_POST['date'];
        $validatedBy = $_POST['validatedBy'];

        if (moveScanToValidated($scanID, $userID, $selectedDisease, $imageURL, $date, $validatedBy)) {
            echo 'Success';
        } else {
            echo 'Error';
        }
    }
}
?>
