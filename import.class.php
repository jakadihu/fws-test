<?php



use Doctrine\DBAL\DriverManager;


class Import{


    private $conn;
    private $log = array(   'processed_products'    => 0,
                            'saved_products'        => 0,
                            //'missed_products'       => 0,
                            'price_updated'         => 0,
                            //'missed_price_updated'  => 0,
                            'saved_categories'      => 0,
                        );


    public function __construct(){

        /**
        * conncet to database
        */

        $connectionParams = [
            'dbname' => DBNAME,
            'user' => DBUSER,
            'password' => DBPASSWORD,
            'host' => DBHOST,
            'driver' => DBDRIVER,
            'charset' => DBCHARSET,
        ];
        $this->conn = DriverManager::getConnection($connectionParams);

    }



    public function __destruct(){

        /**
        * close database connection
        */
        $this->conn->close();

    }



    /**
    * start processinig csv
    */
    public function processCSV(){


        /**
        * read csv data by rows
        */
        $row = 1;
        if( ( $handle = fopen( CSV_PATH, "r" ) ) !== FALSE ){

            while( ( $data = fgetcsv( $handle, 3000, "," ) ) !== FALSE ){

                //skip header
                if( $row > 1 ){
                    //process a row
                    $this->processRow( $data );
                }

                //if( $row > 5 ){ die; }

                $row ++;

            }

            fclose( $handle );
        }


        /**
        * simple output
        */
        return $this->buildSimpleLog();

    }



    /**
    * process csv row
    */
    private function processRow( $data ){

        //extract data
        $name = (string)($data[0] ?? "");
        $price = (int)($data[1] ?? -1);
        $categories = array(    (string)($data[2] ?? ""),
                                (string)($data[3] ?? ""),
                                (string)($data[4] ?? ""), );

        $this->processProduct( $name );
        $this->processPrice( $name, $price );
        $this->processCategories( $name, $categories );

    }




    /**
    * check and save product
    */
    private function processProduct( $name ){

        //processed prod counter for log
        $this->log['processed_products']++;

        //if product not exists
        if( !$this->productExists( $name ) ){

            $this->log['saved_products']++;

            $this->conn->createQueryBuilder()
                        ->insert( 'products' )
                        ->values( [ 'name' => ':name' ] )
                        ->setParameter( 'name', $name )
                        ->executeQuery();

        }


    }





    /**
    * check if product exists
    */
    private function productExists( $name ){

        //product exists
        $result = $this->conn->createQueryBuilder()
                            ->select( 'p.name' )
                            ->from( 'products', 'p' )
                            ->where( 'p.name = :name' )
                            ->setParameter( 'name', $name )
                            ->fetchOne();

        if( $result === false ){
            return false;
        }

        return true;

    }




    /**
    * check product price
    */
    private function processPrice( $name, $price ){

        //get last price
        $last_price = $this->getLastPrice( $name );

        //if the last price does not match the csv price, save new price
        if( $last_price != $price ){
            $this->log['price_updated']++;
            $this->setPrice( $name, $price );
        }

    }




    /**
    * get the last price for product
    */
    private function getLastPrice( $name ){

        //get latest price
        $result =  $this->conn->createQueryBuilder()
                                ->select( 'ph.price' )
                                ->from( 'price_history', 'ph' )
                                ->where( 'ph.product_name = :product_name' )
                                ->setParameter( 'product_name', $name )
                                ->orderBy( 'updated_at', 'desc' )
                                ->fetchOne();

        return (int)$result;

    }




    /**
    * set new price for product with datetime
    */
    private function setPrice( $name, $price ){

        $this->conn->createQueryBuilder()
                    ->insert( 'price_history' )
                    ->values( [ 'product_name'  => ':product_name',
                                'updated_at'    => ':updated_at',
                                'price'         => ':price' ] )
                    ->setParameter( 'product_name', $name )
                    ->setParameter( 'updated_at', date( 'Y-m-d H:i:s' ) )
                    ->setParameter( 'price', $price )
                    ->executeQuery();

    }




    /**
    * check product categories
    */
    private function processCategories( $name, $categories ){

        //category one by one
        foreach( $categories as $category ){

            //if category not empty string
            if( $category != "" ){

                //if category not exists for product, save category for product
                if( !$this->categoryExistsForProduct( $name, $category ) ){
                    $this->log['saved_categories']++;
                    $this->setCategoryForProduct( $name, $category );
                }

            }

        }

        //delete unused categories
        $this->removeCategoriesForProduct( $name, $categories );

    }




    /**
    * check if category exists for product
    */
    private function categoryExistsForProduct( $name, $category ){

        //category exists for product
        $result = $this->conn->createQueryBuilder()
                            ->select( 'pc.product_name' )
                            ->from( 'product_categories', 'pc' )
                            ->where( 'pc.product_name = :product_name' )
                            ->andWhere( 'pc.product_category = :product_category' )
                            ->setParameter( 'product_name', $name )
                            ->setParameter( 'product_category', $category )
                            ->fetchOne();

        if( $result === false ){
            return false;
        }

        return true;

    }




    /**
    * set category for product
    */
    private function setCategoryForProduct( $name, $category ){

        $this->conn->createQueryBuilder()
                    ->insert( 'product_categories' )
                    ->values( [ 'product_name'      => ':product_name',
                                'product_category'  => ':product_category' ] )
                    ->setParameter( 'product_name', $name )
                    ->setParameter( 'product_category', $category )
                    ->executeQuery();

    }



    /**
    * remove unused product categories
    */
    private function removeCategoriesForProduct( $name, $categories ){

        $this->conn->createQueryBuilder()
                    ->delete( 'product_categories' )
                    ->where( 'product_name = :product_name' )
                    ->andWhere( 'product_category not in ( :categories )' )
                    ->setParameter( 'product_name', $name )
                    ->setParameter( 'categories', $categories, \Doctrine\DBAL\ArrayParameterType::STRING )
                    ->executeQuery();

    }





    /**
    * build simple log for output
    */
    private function buildSimpleLog(){

        $output =   '<strong>' . $this->log['processed_products'] . '</strong> feldolgozva<br />
                    <strong>' . $this->log['saved_products'] . '</strong> termék mentve<br />
                    <strong>' . $this->log['price_updated'] . '</strong> ár frissítve<br />
                    <strong>' . $this->log['saved_categories'] . '</strong> kategória mentve a termékekhez';

        return $output;

    }


}



?>