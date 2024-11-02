<?php
require __DIR__.'/vendor/autoload.php';

use Kreait\Firebase\Factory;

// Function to download images
function downloadImages($validatedScans) {
    // Define the absolute path to the folder to store downloaded images
    $downloadFolder = '/Downloads/Validated_images/';

    // Create the folder if it doesn't exist
    if (!file_exists($downloadFolder)) {
        mkdir($downloadFolder, 0777, true);
    }

    // Create a zip file
    $zip = new ZipArchive();
    $zipFileName = 'validated_images.zip';

    if ($zip->open($downloadFolder . $zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
        // Iterate through the validated scans and add images to the zip file
        foreach ($validatedScans as $disease => $scans) {
            foreach ($scans as $scanID => $scanData) {
                // Retrieve the image URL
                $imageURL = $scanData['imageURL'];

                // Get the image data
                $imageData = file_get_contents($imageURL);

                // Define the file name for the downloaded image
                $fileName = $disease . '_' . $scanID . '.jpg';

                // Add the image to the zip file with the modified file name
                $zip->addFromString($fileName, $imageData);
            }
        }

        // Close the zip file
        $zip->close();

        // Set headers for zip file download
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
        header('Content-Length: ' . filesize($downloadFolder . $zipFileName));

        // Output the zip file
        readfile($downloadFolder . $zipFileName);

        // Delete the zip file after download
        unlink($downloadFolder . $zipFileName);
    } else {
        echo 'Failed to create zip file.';
        exit;
    }
}

// Fetch the validated scans from the Firebase Realtime Database
$factory = (new Factory)
    ->withDatabaseUri('https://petscan-3c7c6-default-rtdb.asia-southeast1.firebasedatabase.app/');
$database = $factory->createDatabase();
$validatedScans = $database->getReference('validated')->getValue();

// Trigger the download of images
downloadImages($validatedScans);
?>
