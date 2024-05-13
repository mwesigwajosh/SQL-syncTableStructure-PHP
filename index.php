<?php

function syncTableStructure($sourceTable, $destinationTable, $dbConnection)
{
    try {
        // Get the structure of the source table
        $sourceColumns = $dbConnection->query("SHOW COLUMNS FROM $sourceTable")->fetchAll(PDO::FETCH_ASSOC);

        // Get the structure of the destination table
        $destinationColumns = $dbConnection->query("SHOW COLUMNS FROM $destinationTable")->fetchAll(PDO::FETCH_ASSOC);

        // Drop columns from the destination table that don't exist in the source table
        foreach ($destinationColumns as $destinationColumn) {
            $columnName = $destinationColumn['Field'];
            if (!in_array($columnName, array_column($sourceColumns, 'Field'))) {
                $dbConnection->query("ALTER TABLE $destinationTable DROP COLUMN $columnName");
            }
        }

        // Create a mapping of column names to their positions in the source table
        $sourceColumnPositions = [];
        foreach ($sourceColumns as $index => $sourceColumn) {
            $sourceColumnPositions[$sourceColumn['Field']] = $index;
        }

        // Add new columns or alter existing ones to match the source table's structure
        foreach ($sourceColumns as $sourceColumn) {
            $columnName = $sourceColumn['Field'];
            $columnType = $sourceColumn['Type'];

            // Determine the position of the column in the destination table
            $position = isset($sourceColumnPositions[$columnName]) ? $sourceColumnPositions[$columnName] : count($destinationColumns);

            // Check if the column already exists in the destination table
            $columnExists = false;
            foreach ($destinationColumns as $destColumn) {
                if ($destColumn['Field'] === $columnName) {
                    $columnExists = true;
                    break;
                }
            }

            // If the column doesn't exist, add it to the destination table
            if (!$columnExists) {
                // Determine the position where the column should be added
                $afterColumn = ($position > 0) ? $destinationColumns[$position - 1]['Field'] : '';

                // Add the column to the destination table
                $dbConnection->query("ALTER TABLE $destinationTable ADD COLUMN $columnName $columnType AFTER $afterColumn");
            }
        }

        echo 'done';
    } catch (PDOException $e) {
        echo 'Error: ' . $e->getMessage();
    }
}

// Example usage
$sourceTable = 'students_a';
$destinationTable = 'students_b';
$dbConnection = new PDO('mysql:host=localhost;dbname=studs', 'root', '');

syncTableStructure($sourceTable, $destinationTable, $dbConnection);
