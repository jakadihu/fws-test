<?php


    require_once 'vendor/autoload.php';
    require_once 'config.php';
    require_once 'import.class.php';
    require_once 'export.class.php';



    /**
    * dumb gui dashboard and action controller
    */
    switch( $_GET["action"] ?? "" ){

        case "import":
            $import = new Import();
            echo $import->processCSV();
            break;

        case "export":
            $export = new Export();
            $xml = $export->exportXML();
            header("Content-type: text/xml");
            echo $xml;
            break;

        default:
            echo    '<a href="./?action=import">Import CSV ></a><br />
                    <br />
                    <a href="./?action=export">Export XML ></a><br />';
            break;

    }






?>