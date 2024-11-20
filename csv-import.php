<?php
// Admin menu to add the CSV Import page
add_action( 'admin_menu', 'csv_import_menu' );
function csv_import_menu() {
    add_menu_page( 'CSV Import', 'CSV Import', 'manage_options', 'csv-import', 'csv_import_page' );
}

// Admin page for the plugin
function csv_import_page() {
    echo '<h1>Import Your CSV File</h1>';
    
    // Check if form is submitted and handle the upload
    if ( isset( $_POST['action'] ) && $_POST['action'] === 'import_csv' && isset( $_FILES['csv_file'] ) ) {
        $csvImporter = new CsvImporter();
        $csvImporter->uploadCsv( $_FILES['csv_file'] );
    }

    // Upload form
    echo '<form method="post" enctype="multipart/form-data">';
    echo '<input type="hidden" name="action" value="import_csv">';
    echo '<input type="file" name="csv_file">';
    echo '<input type="submit" value="Import" class="button button-primary">';
    echo '</form>';
}

// Class to handle CSV Import
class CsvImporter {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'csv_import';
        $this->createTable();
    }

    private function createTable() {
        global $wpdb;

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            location VARCHAR(255) NOT NULL,
            building_type VARCHAR(255) NOT NULL,
            max_price_per_room VARCHAR(255) NOT NULL,
            sda_design_category VARCHAR(255) NOT NULL,
            status VARCHAR(255) NOT NULL,
            vacancy VARCHAR(255) NOT NULL,
            has_fire_sprinklers VARCHAR(255) NOT NULL,
            has_breakout_room VARCHAR(255) NOT NULL,
            onsite_overnight_assistance VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(255) NOT NULL,
            website1 VARCHAR(255) NOT NULL,
            website2 VARCHAR(255) NOT NULL,
            website3 VARCHAR(255) NOT NULL,
            website4 VARCHAR(255) NOT NULL,
            website5 VARCHAR(255) NOT NULL
        )";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    public function uploadCsv( $file ) {
        if ( $file['error'] === UPLOAD_ERR_OK ) {
            $fileTmpPath = $file['tmp_name'];
            $fileName = $file['name'];
            $fileExtension = pathinfo( $fileName, PATHINFO_EXTENSION );

            if ( $fileExtension === 'csv' ) {
                $this->processCsv( $fileTmpPath );
            } else {
                echo "<p style='color: red;'>Invalid file type. Only CSV files are allowed.</p>";
            }
        } else {
            echo "<p style='color: red;'>File upload error.</p>";
        }
    }

    private function processCsv( $filePath ) {
        global $wpdb;

        // Delete existing data in this table
        $wpdb->query( "TRUNCATE TABLE {$this->table_name}" );

        if ( ( $handle = fopen( $filePath, 'r' ) ) !== FALSE ) {
            $header = fgetcsv( $handle ); // Skip the header row

            while ( ( $data = fgetcsv( $handle, 1000, ',' ) ) !== FALSE ) {
                $wpdb->insert( $this->table_name, [
                    'name' => $data[0],
                    'location' => $data[1],
                    'building_type' => $data[2],
                    'max_price_per_room' => $data[3],
                    'sda_design_category' => $data[4],
                    'status' => $data[5],
                    'vacancy' => $data[6],
                    'has_fire_sprinklers' => $data[7],
                    'has_breakout_room' => $data[8],
                    'onsite_overnight_assistance' => $data[9],
                    'email' => $data[10],
                    'phone' => $data[11],
                    'website1' => $data[12],
                    'website2' => $data[13],
                    'website3' => $data[14],
                    'website4' => $data[15],
                    'website5' => $data[16],
                ] );
            }

            fclose( $handle );
            echo "<p style='color: green;'>CSV file successfully uploaded and data inserted into the database.</p>";
        } else {
            echo "<p style='color: red;'>Error opening the CSV file.</p>";
        }
    }

    public function __destruct() {
        global $wpdb;
        $wpdb->close();
    }
}
?>
